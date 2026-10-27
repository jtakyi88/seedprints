<?php
defined( 'ABSPATH' ) || exit;

/** Read the existing Site Kit destination; do not inject a tag or change consent. */
function sp_analytics_configuration() {
	$modules = (array) get_option( 'googlesitekit_active_modules', array() );
	$settings = (array) get_option( 'googlesitekit_analytics-4_settings', array() );
	$tagmanager = (array) get_option( 'googlesitekit_tagmanager_settings', array() );
	$measurement = (string) ( $settings['measurementID'] ?? '' );
	$tag = (string) ( $settings['googleTagID'] ?? '' );
	$destinations = wp_json_encode( $settings['googleTagContainerDestinationIDs'] ?? array() );
	$verified = preg_match( '/^G-[A-Z0-9]+$/', $measurement ) && ( ! $tag || $tag === $measurement || false !== strpos( $destinations, $measurement ) );
	$active = in_array( 'google-site-kit/google-site-kit.php', (array) get_option( 'active_plugins', array() ), true );
	$gtm = ! empty( $tagmanager['useSnippet'] ) && ! empty( $tagmanager['containerID'] );
	$bridge = 'seedprints' === get_stylesheet() && $active && in_array( 'analytics-4', $modules, true ) && ! empty( $settings['useSnippet'] ) && $verified && ! $gtm && ! defined( 'GTM4WP_VERSION' ) && ! class_exists( 'WC_Google_Analytics_Integration' ) && ! in_array( 'ads', $modules, true );
	$excluded = is_user_logged_in() && in_array( 'loggedinUsers', (array) ( $settings['trackingDisabled'] ?? array() ), true );
	$allowed = ! $excluded && ! apply_filters( 'googlesitekit_analytics-4_tag_blocked', false );
	return array( 'allowed' => $allowed, 'bridge' => (bool) $bridge, 'measurementId' => $bridge ? $measurement : '', 'destinationVerified' => (bool) $verified );
}

/** One GA4 ecommerce owner; other Site Kit providers and Google Ads remain native. */
function sp_analytics_dequeue_native_provider() {
	if ( sp_analytics_configuration()['bridge'] && wp_script_is( 'seedprints-analytics', 'enqueued' ) ) {
		wp_dequeue_script( 'googlesitekit-events-provider-woocommerce' );
	}
}
add_action( 'wp_enqueue_scripts', 'sp_analytics_dequeue_native_provider', 10030 );
add_action( 'wp_footer', 'sp_analytics_dequeue_native_provider', 19 );

function sp_analytics_item( $product, $quantity = 1, $in_cart = false, $unit_price = null ) {
	$fee = ! $in_cart && sp_custom_product_allowed( $product->get_id() ) ? sp_custom_fee( $product->get_id() ) : 0;
	$extra = is_wp_error( $fee ) ? 0 : $fee;
	$price = null === $unit_price ? wc_get_price_excluding_tax( $product, array( 'price' => (float) $product->get_price() + $extra ) ) : $unit_price;
	return array( 'item_id' => (string) ( $product->get_parent_id() ?: $product->get_id() ), 'item_variant' => $product->is_type( 'variation' ) ? (string) $product->get_id() : '', 'item_name' => $product->get_name(), 'price' => (float) $price, 'quantity' => (int) $quantity );
}

// Native simple forms have no data-product_id or selected-variation price.
add_action( 'woocommerce_before_add_to_cart_button', function () {
	global $product;
	if ( $product instanceof WC_Product ) {
		echo '<script type="application/json" class="sp-product-analytics">' . wp_json_encode( sp_analytics_item( $product ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>';
	}
}, 1 );
add_filter( 'woocommerce_available_variation', function ( $data, $parent, $variation ) {
	$data['seedprints_analytics_item'] = sp_analytics_item( $variation );
	return $data;
}, 30, 3 );

/** Full-page/native add-on forms need a server receipt; AJAX and custom forms own theirs. */
add_action( 'woocommerce_add_to_cart', function ( $key, $product_id, $quantity ) {
	if ( 'seedprints' !== get_stylesheet() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || empty( $_REQUEST['add-to-cart'] ) || ! empty( $GLOBALS['sp_custom_authorized_cart'] ) || ! WC()->session ) { return; }
	$item = WC()->cart->get_cart_item( $key );
	if ( empty( $item['data'] ) ) { return; }
	$data = sp_analytics_item( $item['data'], $quantity, true );
	$events = (array) WC()->session->get( 'sp_native_add_events', array() );
	$events[] = array( 'event' => 'add_to_cart', 'ecommerce' => array( 'currency' => get_woocommerce_currency(), 'value' => $data['price'] * $quantity, 'items' => array( $data ) ) );
	WC()->session->set( 'sp_native_add_events', array_slice( $events, -10 ) );
}, 100, 3 );

function sp_collect_ecommerce_events() {
	$events = WC()->session ? (array) WC()->session->get( 'sp_native_add_events', array() ) : array();
	if ( WC()->session ) { WC()->session->__unset( 'sp_native_add_events' ); }
	$currency = get_woocommerce_currency();
	if ( is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) { $item = sp_analytics_item( $product ); $events[] = array( 'event' => 'view_item', 'ecommerce' => array( 'currency' => $currency, 'value' => $item['price'], 'items' => array( $item ) ) ); }
	}
	if ( is_checkout() && ! is_order_received_page() && WC()->cart ) {
		$items = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			$quantity = (int) $item['quantity'];
			if ( $quantity > 0 ) { $items[] = sp_analytics_item( $item['data'], $quantity, true, (float) $item['line_total'] / $quantity ); }
		}
		$events[] = array( 'event' => 'begin_checkout', 'ecommerce' => array( 'currency' => $currency, 'value' => (float) WC()->cart->get_cart_contents_total(), 'items' => $items ) );
	}
	if ( is_order_received_page() ) {
		$order = wc_get_order( absint( get_query_var( 'order-received' ) ) );
		$key = isset( $_GET['key'] ) && is_string( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		if ( $order && $order->is_paid() && $key && hash_equals( $order->get_order_key(), $key ) ) {
			$items = array();
			foreach ( $order->get_items() as $item ) {
				$product = $item->get_product(); $quantity = (int) $item->get_quantity();
				if ( $product && $quantity > 0 ) { $items[] = sp_analytics_item( $product, $quantity, true, (float) $item->get_total() / $quantity ); }
			}
			$events[] = array( 'event' => 'purchase', 'ecommerce' => array( 'transaction_id' => (string) $order->get_id(), 'currency' => $order->get_currency(), 'value' => (float) ( $order->get_total() - $order->get_total_tax() - $order->get_shipping_total() ), 'tax' => (float) $order->get_total_tax(), 'shipping' => (float) $order->get_shipping_total(), 'items' => $items ) );
		}
	}
	return $events;
}

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'WC' ) ) { return; }
	if ( 'production' !== wp_get_environment_type() ) { if ( WC()->session ) { WC()->session->__unset( 'sp_native_add_events' ); } return; }
	$events = sp_collect_ecommerce_events();
	if ( $events ) { echo '<script id="sp-ecommerce-events" type="application/json">' . wp_json_encode( $events, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>'; }
}, 13 );

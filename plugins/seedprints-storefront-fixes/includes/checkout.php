<?php
defined( 'ABSPATH' ) || exit;

/** Render native WooCommerce forms; retain saved Elementor content for rollback. */
add_filter( 'template_include', function ( $template ) {
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
		return SPFIX_DIR . 'templates/commerce-page.php';
	}
	return $template;
}, 1000 );

/** Square already supports this hook. Move its eligible wallet area ahead of login/coupons. */
add_action( 'wp', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() ) { return; }
	global $wp_filter;
	$hook = $wp_filter['woocommerce_before_checkout_form'] ?? null;
	if ( ! $hook instanceof WP_Hook ) { return; }
	foreach ( $hook->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $entry ) {
			$callback = $entry['function'];
			if ( is_array( $callback ) && is_object( $callback[0] ) && 'render_button' === $callback[1] && str_ends_with( get_class( $callback[0] ), '\\Gateway\\Digital_Wallet' ) ) {
				remove_action( 'woocommerce_before_checkout_form', $callback, $priority );
				add_action( 'woocommerce_before_checkout_form', $callback, 1, $entry['accepted_args'] );
			}
		}
	}
}, 100 );

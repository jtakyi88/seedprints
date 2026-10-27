<?php
defined( 'ABSPATH' ) || exit;

foreach ( array( 'woocommerce_product_single_add_to_cart_text', 'woocommerce_product_add_to_cart_text' ) as $hook ) {
	add_filter( $hook, function ( $text, $product ) {
		if ( $product->is_type( 'simple' ) || ( 'woocommerce_product_single_add_to_cart_text' === current_filter() && $product->is_type( 'variable' ) ) ) { return 'Add to bag'; }
		return $product->is_type( 'variable' ) ? 'Choose options' : $text;
	}, 20, 2 );
}

function spfix_icon( $name ) {
	$paths = array(
		'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
		'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
		'account' => '<circle cx="12" cy="8" r="4"/><path d="M4 22v-3a8 8 0 0 1 16 0v3"/>',
		'bag' => '<path d="M4 7h16l1 15H3L4 7Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/>',
	);
	return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true" focusable="false">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

function spfix_navigation() {
	$links = array(
		'Apparel' => home_url( '/product-category/apparel/' ),
		'Home & table' => home_url( '/product-category/home-essentials/' ),
		'Custom' => home_url( '/product-category/custom-prints-and-more/' ),
		'Our story' => home_url( '/about-us/' ),
	);
	foreach ( $links as $label => $url ) {
		echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
}

function spfix_bag_count() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	return '<span class="spfix-bag-count">' . absint( $count ) . '</span>';
}

add_filter( 'body_class', function ( $classes ) {
	if ( class_exists( 'WooCommerce' ) ) { $classes[] = 'spfix-global-header'; }
	return $classes;
} );

add_action( 'wp_body_open', function () {
	if ( ! class_exists( 'WooCommerce' ) ) { return; }
	$checkout = is_checkout() && ! is_order_received_page();
	?>
	<a class="spfix-skip" href="#spfix-content-start">Skip to content</a>
	<?php if ( ! $checkout ) : ?><div class="spfix-ribbon">Bible Made Simple. Faith for everyday life.</div><?php endif; ?>
	<header class="spfix-header" aria-label="Seed Prints">
		<a class="spfix-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo wp_get_attachment_image( 11863, 'medium', false, array( 'alt' => 'Seed Prints — Bible Made Simple', 'loading' => 'eager' ) ); ?></a>
		<?php if ( ! $checkout ) : ?>
			<nav class="spfix-desktop-nav" aria-label="Shop"><?php spfix_navigation(); ?></nav>
			<details class="spfix-mobile-menu"><summary>Menu</summary><nav aria-label="Mobile shop"><?php spfix_navigation(); ?></nav></details>
		<?php else : ?><span class="spfix-checkout-label">Secure checkout</span><?php endif; ?>
		<div class="spfix-header-tools">
			<?php if ( ! $checkout ) : ?>
				<button type="button" data-spfix-open="spfix-search" aria-label="Search products"><?php echo spfix_icon( 'search' ); ?><span class="spfix-tool-text">Search</span></button>
				<a href="<?php echo esc_url( home_url( '/wishlist/' ) ); ?>" aria-label="Wishlist"><?php echo spfix_icon( 'heart' ); ?><span class="spfix-tool-text">Wishlist</span></a>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="Account"><?php echo spfix_icon( 'account' ); ?><span class="spfix-tool-text">Account</span></a>
			<?php endif; ?>
			<a class="spfix-bag-trigger" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-spfix-open="spfix-bag" aria-label="Open your bag"><?php echo spfix_icon( 'bag' ); ?><span class="spfix-tool-text">Bag</span><?php echo spfix_bag_count(); ?></a>
		</div>
	</header>
	<div id="spfix-content-start" tabindex="-1"></div>
	<?php
}, 5 );

function spfix_bag_lines() {
	ob_start();
	echo '<div class="spfix-bag-lines">';
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		echo '<p>Your bag is waiting for something meaningful.</p><a class="spfix-button spfix-button-secondary" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">Shop the collection</a>';
	} else {
		echo '<ul class="spfix-bag-list">';
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$product = $item['data'];
			if ( ! $product || ! $product->exists() || $item['quantity'] < 1 ) { continue; }
			$name = $product->get_name();
			echo '<li><a class="spfix-bag-image" href="' . esc_url( $product->get_permalink( $item ) ) . '">' . $product->get_image( 'woocommerce_thumbnail', array( 'alt' => $name ) ) . '</a><div>';
			echo '<a class="spfix-bag-name" href="' . esc_url( $product->get_permalink( $item ) ) . '">' . esc_html( $name ) . '</a>';
			echo wp_kses_post( wc_get_formatted_cart_item_data( $item ) );
			echo '<p>Quantity: ' . absint( $item['quantity'] ) . ' · ' . wp_kses_post( WC()->cart->get_product_subtotal( $product, $item['quantity'] ) ) . '</p>';
			echo '<a class="spfix-remove" href="' . esc_url( wc_get_cart_remove_url( $key ) ) . '" data-cart-key="' . esc_attr( $key ) . '" aria-label="' . esc_attr( 'Remove ' . $name ) . '">Remove</a></div></li>';
		}
		echo '</ul><div class="spfix-bag-subtotal"><strong>Subtotal</strong><strong>' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</strong></div><p class="spfix-bag-note">Shipping and taxes are calculated at checkout.</p>';
	}
	echo '</div>';
	return ob_get_clean();
}

add_action( 'wp_footer', function () {
	if ( ! class_exists( 'WooCommerce' ) ) { return; }
	$open = WC()->session && WC()->session->get( 'spfix_open_bag' );
	if ( WC()->session ) { WC()->session->__unset( 'spfix_open_bag' ); }
	?>
	<p id="spfix-feedback" class="spfix-sr-only" role="status" aria-live="polite" aria-atomic="true"></p>
	<dialog id="spfix-search" class="spfix-dialog spfix-search" aria-labelledby="spfix-search-title">
		<div class="spfix-dialog-heading"><h2 id="spfix-search-title">Find something meaningful</h2><form method="dialog"><button aria-label="Close search" class="spfix-close">×</button></form></div>
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>"><label for="spfix-search-input">Search by product, verse or word</label><input id="spfix-search-input" type="search" name="s" placeholder="Try “placemat” or “Jesus”" required><input type="hidden" name="post_type" value="product"><button class="spfix-button" type="submit">Search products</button></form>
	</dialog>
	<dialog id="spfix-bag" class="spfix-dialog spfix-bag" aria-labelledby="spfix-bag-title"<?php echo $open ? ' data-spfix-auto-open' : ''; ?>>
		<div class="spfix-dialog-heading"><h2 id="spfix-bag-title">Your bag</h2><form method="dialog"><button aria-label="Close bag" class="spfix-close">×</button></form></div>
		<p class="spfix-bag-status" role="status" aria-live="polite"></p>
		<?php echo spfix_bag_lines(); ?>
		<div class="spfix-bag-actions"><a class="spfix-button" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">Checkout</a><a class="spfix-button spfix-button-secondary" href="<?php echo esc_url( wc_get_cart_url() ); ?>">View bag</a></div>
	</dialog>
	<?php
}, 10 );

add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$fragments['span.spfix-bag-count'] = spfix_bag_count();
	$fragments['div.spfix-bag-lines'] = spfix_bag_lines();
	return $fragments;
} );

add_action( 'woocommerce_add_to_cart', function () {
	if ( WC()->session && ! wp_doing_ajax() ) { WC()->session->set( 'spfix_open_bag', true ); }
} );

add_action( 'woocommerce_before_add_to_cart_button', function () {
	echo '<p class="spfix-options-feedback" role="status" aria-live="polite"></p>';
} );

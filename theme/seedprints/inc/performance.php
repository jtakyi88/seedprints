<?php
/** Skip late block-only CSS when the rendered storefront does not use Woo blocks. */
defined( 'ABSPATH' ) || exit;

add_filter( 'render_block', function ( $html, $block ) {
	if ( str_starts_with( (string) ( $block['blockName'] ?? '' ), 'woocommerce/' ) ) {
		$GLOBALS['sp_rendered_woocommerce_block'] = true;
	}
	return $html;
}, 100, 2 );

function sp_filter_unused_block_styles( $html, $handle ) {
	if ( ! in_array( $handle, array( 'wc-square-cart-checkout-block', 'wc-blocks-style' ), true ) ) { return $html; }
	// Wait until the template has rendered. Retain early CSS and transactional pages.
	if ( ! did_action( 'wp_footer' ) || is_admin() || 'seedprints' !== get_stylesheet() || is_cart() || is_checkout() || is_account_page() || ! empty( $GLOBALS['sp_rendered_woocommerce_block'] ) ) { return $html; }
	return '';
}
add_filter( 'style_loader_tag', 'sp_filter_unused_block_styles', 20, 2 );

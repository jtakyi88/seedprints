<?php
/** Product search snippets from the merchant's existing product copy. */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', function () {
	if ( ! is_product() ) { return; }
	// A dedicated SEO integration owns descriptions when present.
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) { return; }
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product ) { return; }
	$copy = $product->get_meta( '_sp_verse_meaning' ) ?: ( $product->get_short_description() ?: $product->get_description() );
	// Legacy prose can contain stale written prices. Never promote them in snippets.
	if ( preg_match( '/\p{Sc}|\b(?:USD|price|cost)\b/iu', $copy ) ) { $copy = ''; }
	$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( strip_shortcodes( $copy ), true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	if ( ! $text ) { $text = $product->get_name() . ' from Seed Prints. Bible Made Simple.'; }
	$description = wp_html_excerpt( $text, 160, '…' );
	echo '<meta name="description" content="' . esc_attr( $description ) . '">';
}, 5 );

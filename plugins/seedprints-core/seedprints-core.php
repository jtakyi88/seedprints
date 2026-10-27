<?php
/**
 * Plugin Name: Seed Prints Core
 * Description: Store collections, saved pieces, offline verse lookup and custom-design requests.
 * Version: 0.1.0
 * Requires Plugins: woocommerce
 * Requires PHP: 8.0
 */
defined( 'ABSPATH' ) || exit;
define( 'SP_CORE_DIR', plugin_dir_path( __FILE__ ) );
require_once SP_CORE_DIR . 'includes/pricing.php';
require_once SP_CORE_DIR . 'includes/custom.php';
require_once SP_CORE_DIR . 'includes/analytics.php';
require_once SP_CORE_DIR . 'includes/review-photos.php';

add_action( 'init', function () {
	foreach ( array( 'sp_collection' => 'Collections', 'sp_product_type' => 'Product types' ) as $taxonomy => $label ) {
		register_taxonomy( $taxonomy, 'product', array( 'label' => $label, 'public' => true, 'show_ui' => true, 'show_in_rest' => true, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'sp_collection' === $taxonomy ? 'collection' : 'type' ) ) );
	}
	register_post_type( 'sp_custom_request', array( 'label' => 'Custom requests', 'public' => false, 'show_ui' => true, 'show_in_menu' => 'woocommerce', 'supports' => array( 'title', 'editor' ), 'capability_type' => 'post', 'capabilities' => array( 'create_posts' => 'manage_woocommerce', 'edit_posts' => 'manage_woocommerce', 'edit_others_posts' => 'manage_woocommerce', 'publish_posts' => 'manage_woocommerce', 'read_private_posts' => 'manage_woocommerce', 'delete_posts' => 'manage_woocommerce' ), 'map_meta_cap' => true ) );
} );

function sp_custom_product_allowed( $id ) {
	$p = wc_get_product( $id );
	if ( $p && $p->is_type( 'variation' ) ) { $p = wc_get_product( $p->get_parent_id() ); }
	return $p && 'publish' === $p->get_status() && ( 'yes' === $p->get_meta( '_sp_customizable' ) || in_array( $p->get_id(), array( 12689, 12999, 12376 ), true ) );
}

function sp_lookup_verse( $reference ) {
	$reference = trim( preg_replace( '/\s+/', ' ', str_replace( '–', '-', $reference ) ) );
	if ( strlen( $reference ) > 80 || ! preg_match( '/^([1-3]?\s*[A-Za-z ]+?)\s*(\d{1,3}):(\d{1,3})(?:\s*-\s*(\d{1,3}))?$/', $reference, $m ) ) {
		return new WP_Error( 'verse_reference', 'Try a reference such as John 3:16 or Psalm 23:1-3.', array( 'status' => 400 ) );
	}
	$key = strtolower( preg_replace( '/\s+/', '', $m[1] ) );
	$aliases = array( 'psalm' => 'psalms', 'ps' => 'psalms', 'psa' => 'psalms', 'prov' => 'proverbs', 'jn' => 'john', 'mt' => 'matthew', 'matt' => 'matthew', 'mk' => 'mark', 'lk' => 'luke', 'rev' => 'revelation', 'rom' => 'romans', 'phil' => 'philippians', 'songofsongs' => 'songofsolomon' );
	$key = $aliases[ $key ] ?? $key;
	$books = json_decode( file_get_contents( SP_CORE_DIR . 'data/kjv/index.json' ), true );
	$book = null;
	foreach ( $books as $candidate ) { if ( strtolower( str_replace( ' ', '', $candidate['name'] ) ) === $key ) { $book = $candidate; break; } }
	if ( ! $book ) { return new WP_Error( 'verse_book', 'That book was not found in the KJV library.', array( 'status' => 404 ) ); }
	$chapter = (int) $m[2]; $first = (int) $m[3]; $last = isset( $m[4] ) ? (int) $m[4] : $first;
	if ( $last < $first || $last - $first > 9 ) { return new WP_Error( 'verse_range', 'Choose a passage of up to 10 verses.', array( 'status' => 400 ) ); }
	$data = json_decode( file_get_contents( SP_CORE_DIR . 'data/kjv/' . $book['slug'] . '.json' ), true );
	$text = array();
	for ( $verse = $first; $verse <= $last; ++$verse ) {
		if ( ! isset( $data[ $chapter ][ $verse ] ) ) { return new WP_Error( 'verse_missing', 'That chapter or verse was not found. Check the reference and try again.', array( 'status' => 404 ) ); }
		$text[] = $data[ $chapter ][ $verse ];
	}
	$text = implode( ' ', $text );
	if ( mb_strlen( $text ) > 1200 ) { return new WP_Error( 'verse_length', 'Choose a shorter passage for your design.', array( 'status' => 400 ) ); }
	return array( 'reference' => $book['name'] . ' ' . $chapter . ':' . $first . ( $last !== $first ? '-' . $last : '' ), 'text' => $text, 'translation' => 'King James Version' );
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'seedprints/v1', '/verse', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'args' => array( 'reference' => array( 'required' => true, 'type' => 'string', 'maxLength' => 80, 'sanitize_callback' => 'sanitize_text_field' ) ), 'callback' => function ( $request ) { return sp_lookup_verse( $request['reference'] ); } ) );
	register_rest_route( 'seedprints/v1', '/products', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function ( $request ) {
		if ( ! function_exists( 'sp_product_card' ) ) { return new WP_Error( 'theme_required', 'Saved pieces are temporarily unavailable.', array( 'status' => 503 ) ); }
		$raw = $request->get_param( 'include' );
		$ids = array_slice( array_unique( array_filter( array_map( 'absint', is_array( $raw ) ? $raw : explode( ',', (string) $raw ) ) ) ), 0, 50 );
		$result = array();
		foreach ( $ids as $id ) { $p = wc_get_product( $id ); if ( $p && 'publish' === $p->get_status() && ! $p->is_type( 'variation' ) && $p->is_visible() ) { $result[] = array( 'id' => $id, 'html' => sp_product_card( $p ) ); } }
		return rest_ensure_response( $result );
	} ) );
	register_rest_route( 'seedprints/v1', '/quick-add/(?P<id>\d+)', array( 'methods' => 'GET', 'permission_callback' => '__return_true', 'callback' => function ( $request ) {
		$p = wc_get_product( absint( $request['id'] ) );
		if ( ! $p || 'publish' !== $p->get_status() || ! $p->is_visible() || ! $p->is_type( 'variable' ) || ( function_exists( 'product_addons' ) && product_addons()->get_product_option_ids( $p->get_id() ) ) || sp_custom_product_allowed( $p->get_id() ) ) { return new WP_Error( 'product_options', 'Open the product page to choose this piece.', array( 'status' => 400 ) ); }
		$previous = $GLOBALS['product'] ?? null;
		$GLOBALS['product'] = $p;
		ob_start(); echo '<p class="sp-lead">' . wp_kses_post( $p->get_price_html() ) . '</p>'; woocommerce_variable_add_to_cart(); $html = ob_get_clean();
		$GLOBALS['product'] = $previous;
		return array( 'name' => $p->get_name(), 'html' => $html );
	} ) );
	register_rest_route( 'seedprints/v1', '/wishlist', array(
		array( 'methods' => 'GET', 'permission_callback' => function () { return is_user_logged_in(); }, 'callback' => function () { return array_map( 'absint', (array) get_user_meta( get_current_user_id(), '_sp_wishlist', true ) ); } ),
		array( 'methods' => 'POST', 'permission_callback' => function () { return is_user_logged_in(); }, 'callback' => function ( $request ) {
			$ids = $request->get_param( 'ids' );
			if ( ! is_array( $ids ) || count( $ids ) > 50 ) { return new WP_Error( 'wishlist_items', 'Save up to 50 pieces.', array( 'status' => 400 ) ); }
			$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
			$ids = array_values( array_filter( $ids, function ( $id ) { return 'product' === get_post_type( $id ) && 'publish' === get_post_status( $id ); } ) );
			update_user_meta( get_current_user_id(), '_sp_wishlist', $ids );
			return $ids;
		} ),
	) );
} );

add_action( 'woocommerce_product_options_general_product_data', function () {
	woocommerce_wp_text_input( array( 'id' => '_sp_verse_reference', 'label' => 'Verse reference', 'placeholder' => 'Revelation 1:18' ) );
	woocommerce_wp_text_input( array( 'id' => '_sp_verse_meaning', 'label' => 'One-line meaning', 'description' => 'A short, clear explanation for the product page.' ) );
} );
add_action( 'woocommerce_admin_process_product_object', function ( $product ) {
	foreach ( array( '_sp_verse_reference', '_sp_verse_meaning' ) as $key ) { if ( isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ) { $product->update_meta_data( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ); } }
} );

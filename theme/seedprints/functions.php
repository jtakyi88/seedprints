<?php
/** Seed Prints theme foundation. */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'spfix_bag_count' ) && is_file( __DIR__ . '/lib/commerce/seedprints-storefront-fixes.php' ) ) {
	define( 'SPFIX_DIR', __DIR__ . '/lib/commerce/' );
	define( 'SPFIX_URL', get_theme_file_uri( '/lib/commerce/' ) );
	require_once __DIR__ . '/lib/commerce/seedprints-storefront-fixes.php';
}
remove_action( 'wp_body_open', 'spfix_header', 5 );
require_once __DIR__ . '/inc/components.php';
require_once __DIR__ . '/inc/catalog.php';
require_once __DIR__ . '/inc/pages.php';
require_once __DIR__ . '/inc/size-guide.php';
require_once __DIR__ . '/inc/performance.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'seedprints', get_template_directory() . '/languages' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'custom-logo' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
	add_editor_style( 'assets/css/theme.css' );
} );

add_action( 'wp_enqueue_scripts', function () {
	$path = '/assets/css/theme.css';
	wp_enqueue_style( 'seedprints-storefront', get_theme_file_uri( $path ), array(), (string) filemtime( get_theme_file_path( $path ) ) );
	wp_enqueue_script( 'seedprints-storefront', get_theme_file_uri( '/assets/js/storefront.js' ), array( 'jquery', 'seedprints-commerce' ), (string) filemtime( get_theme_file_path( '/assets/js/storefront.js' ) ), true );
	if ( is_page( 'custom' ) ) { wp_enqueue_script( 'seedprints-custom', get_theme_file_uri( '/assets/js/custom.js' ), array( 'seedprints-storefront' ), (string) filemtime( get_theme_file_path( '/assets/js/custom.js' ) ), true ); }
	if ( is_product() ) {
		foreach ( array( 'wc-flexslider', 'wc-photoswipe', 'wc-photoswipe-ui-default' ) as $handle ) { wp_enqueue_script( $handle ); }
		wp_enqueue_style( 'photoswipe' ); wp_enqueue_style( 'photoswipe-default-skin' );
		if ( isset( wp_scripts()->registered['wc-single-product'] ) ) { wp_scripts()->registered['wc-single-product']->deps = array_unique( array_merge( wp_scripts()->registered['wc-single-product']->deps, array( 'wc-flexslider', 'wc-photoswipe-ui-default' ) ) ); }
		wp_enqueue_script( 'wc-single-product' );
	}
	wp_enqueue_script( 'wc-add-to-cart' );
	wp_enqueue_script( 'wc-add-to-cart-variation' );
	wp_localize_script( 'seedprints-storefront', 'seedprintsSite', array(
		'api' => esc_url_raw( rest_url( 'seedprints/v1/' ) ),
		'nonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
		'loggedIn' => is_user_logged_in(),
		'production' => 'production' === wp_get_environment_type(),
		'currency' => get_woocommerce_currency(),
	) );
}, 10010 );

add_action( 'wp_head', function () {
	foreach ( array( 'inter', 'fraunces' ) as $font ) {
		echo '<link rel="preload" as="font" type="font/woff2" crossorigin href="' . esc_url( get_theme_file_uri( '/assets/fonts/' . $font . '-latin.woff2' ) ) . '">';
	}
	$organization = array( '@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'Seed Prints', 'url' => home_url( '/' ), 'logo' => wp_get_attachment_url( 11863 ), 'description' => 'Bible Made Simple. Scripture-inspired apparel, home goods and meaningful custom prints.', 'email' => 'info@seedprints.com', 'telephone' => '+1-443-292-2198', 'sameAs' => array( 'https://www.facebook.com/seedprints', 'https://www.instagram.com/seedprints/' ) );
	if ( is_front_page() ) { echo '<meta name="description" content="Wear the Word. Shop Scripture-inspired apparel, Bible-learning placemats, drinkware and meaningful custom prints from Seed Prints.">'; }
	echo '<script type="application/ld+json">' . wp_json_encode( $organization, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>';
}, 3 );

add_filter( 'seedprints_navigation_links', function () {
	return array( 'Apparel' => home_url( '/product-category/apparel/' ), 'Home & table' => home_url( '/product-category/home-essentials/' ), 'Drinkware' => home_url( '/product-category/drinkware/' ), 'Custom' => home_url( '/custom/' ), 'Gifts' => home_url( '/product-category/gifts/' ), 'Our story' => home_url( '/about-us/' ) );
} );

add_filter( 'render_block_core/post-content', function ( $html ) {
	return str_ireplace( '</h1>', '</h2>', preg_replace( '/<h1(\s[^>]*)?>/i', '<h2$1>', $html ) );
} );

add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_script( 'seedprints-editor', get_theme_file_uri( '/assets/js/editor.js' ), array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ), (string) filemtime( get_theme_file_path( '/assets/js/editor.js' ) ), true );
} );

add_action( 'init', function () {
	$blocks = array( 'header', 'hero', 'press', 'categories', 'rail', 'collections', 'verse', 'custom-steps', 'reviews', 'founder', 'signup', 'footer', 'archive', 'product', 'story', 'custom', 'wishlist', 'contact', 'cart', 'checkout' );
	foreach ( $blocks as $name ) {
		register_block_type( 'seedprints/' . $name, array(
			'api_version' => 3,
			'attributes' => array(
				'heading' => array( 'type' => 'string', 'default' => '' ),
				'body' => array( 'type' => 'string', 'default' => '' ),
				'reference' => array( 'type' => 'string', 'default' => '' ),
				'imageId' => array( 'type' => 'number', 'default' => 0 ),
			),
			'render_callback' => function ( $attributes ) use ( $name ) {
				$function = 'sp_render_' . str_replace( '-', '_', $name );
				if ( ! function_exists( $function ) ) { return ''; }
				ob_start();
				$function( $attributes );
				return ob_get_clean();
			},
		) );
	}
} );

add_action( 'init', function () {
	register_block_type( 'seedprints/copyright', array(
		'api_version' => 3,
		'render_callback' => function () {
			return '<p class="sp-copyright">' . esc_html( sprintf(
				/* translators: %s: current year. */
				__( 'Copyright © %s Seed Prints', 'seedprints' ),
				wp_date( 'Y' )
			) ) . '</p>';
		},
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	// The home bag refreshes on open and from add responses; avoid an automatic full-WP cart request.
	if ( is_front_page() ) { wp_dequeue_script( 'wc-cart-fragments' ); }
	// Keep native commerce dependencies together at the footer, away from the first paint.
	foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate', 'underscore', 'wp-util', 'wc-add-to-cart-variation' ) as $handle ) { wp_scripts()->add_data( $handle, 'group', 1 ); }
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post || ! has_shortcode( $post->post_content, 'contact-form-7' ) ) {
		foreach ( array( 'contact-form-7', 'swv' ) as $handle ) { wp_dequeue_script( $handle ); wp_dequeue_style( $handle ); }
	}
}, 10020 );

add_action( 'wp_head', function () {
	if ( ! is_front_page() && ! is_product() ) { return; }
	$image_id = is_product() ? wc_get_product( get_queried_object_id() )->get_image_id() : 11884;
	if ( is_front_page() ) {
		$template = get_block_template( get_stylesheet() . '//front-page', 'wp_template' );
		$find = function ( $blocks ) use ( &$find, &$image_id ) { foreach ( $blocks as $block ) { if ( 'seedprints/hero' === $block['blockName'] && ! empty( $block['attrs']['imageId'] ) ) { $image_id = absint( $block['attrs']['imageId'] ); } if ( ! empty( $block['innerBlocks'] ) ) { $find( $block['innerBlocks'] ); } } };
		if ( $template ) { $find( parse_blocks( $template->content ) ); }
	}
	$map = json_decode( (string) @file_get_contents( get_theme_file_path( '/assets/media/index.json' ) ), true );
	$images = $map[(string)$image_id] ?? array();
	if ( ! $images ) { return; }
	$hero_map = is_front_page() ? json_decode( (string) @file_get_contents( get_theme_file_path( '/assets/media/hero-index.json' ) ), true ) : array();
	$mobile = $hero_map[(string)$image_id]['mobile'] ?? array();
	if ( ! empty( $mobile['webp'] ) ) {
		$format = ! empty( $mobile['avif'] ) ? 'avif' : 'webp';
		sp_preload_image( $mobile[$format], '100vw', '(max-width: 767px)', 'image/' . $format );
	}
	sp_preload_image( $images, is_product() ? '(max-width: 767px) calc(100vw - 32px), 50vw' : '(max-width: 767px) 100vw, 65vw', ! empty( $mobile['webp'] ) ? '(min-width: 768px)' : '' );
}, 2 );
function sp_preload_image( $images, $sizes, $media = '', $type = '' ) {
	$last = end( $images );
	$srcset = array_map( function ( $image ) { return get_theme_file_uri( '/assets/media/' . $image['file'] ) . ' ' . $image['width'] . 'w'; }, $images );
	echo '<link rel="preload" as="image" fetchpriority="high" href="' . esc_url( get_theme_file_uri( '/assets/media/' . $last['file'] ) ) . '" imagesrcset="' . esc_attr( implode( ', ', $srcset ) ) . '" imagesizes="' . esc_attr( $sizes ) . '"' . ( $media ? ' media="' . esc_attr( $media ) . '"' : '' ) . ( $type ? ' type="' . esc_attr( $type ) . '"' : '' ) . '>';
}

foreach ( array( 'flexslider', 'photoswipe' ) as $feature ) { add_filter( 'woocommerce_single_product_' . $feature . '_enabled', '__return_true' ); }
add_action( 'wp_footer', function () { if ( is_product() && ! has_action( 'wp_footer', 'woocommerce_photoswipe' ) ) { woocommerce_photoswipe(); } }, 14 );

add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false' );

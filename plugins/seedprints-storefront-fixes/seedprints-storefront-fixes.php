<?php
/**
 * Plugin Name: Seed Prints storefront fixes
 * Description: Focused storefront repairs while the standalone block theme is built.
 * Version: 0.1.0
 * Requires Plugins: woocommerce
 * Text Domain: seedprints-fixes
 */
defined( 'ABSPATH' ) || exit;
define( 'SPFIX_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPFIX_URL', plugin_dir_url( __FILE__ ) );
require_once SPFIX_DIR . 'includes/layout.php';
require_once SPFIX_DIR . 'includes/commerce.php';
require_once SPFIX_DIR . 'includes/checkout.php';

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'seedprints-layout-fixes', SPFIX_URL . 'assets/layout.css', array(), (string) filemtime( SPFIX_DIR . 'assets/layout.css' ) );
	wp_enqueue_script( 'seedprints-layout-fixes', SPFIX_URL . 'assets/layout.js', array(), (string) filemtime( SPFIX_DIR . 'assets/layout.js' ), true );
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style( 'seedprints-commerce', SPFIX_URL . 'assets/commerce.css', array( 'seedprints-layout-fixes' ), (string) filemtime( SPFIX_DIR . 'assets/commerce.css' ) );
		if ( wp_script_is( 'wc-cart-fragments', 'registered' ) ) { wp_enqueue_script( 'wc-cart-fragments' ); }
		wp_enqueue_script( 'seedprints-commerce', SPFIX_URL . 'assets/commerce.js', array( 'jquery' ), (string) filemtime( SPFIX_DIR . 'assets/commerce.js' ), true );
		wp_localize_script( 'seedprints-commerce', 'seedprintsCommerce', array(
			'addUrl' => WC_AJAX::get_endpoint( 'add_to_cart' ),
			'removeUrl' => WC_AJAX::get_endpoint( 'remove_from_cart' ),
			'refreshUrl' => WC_AJAX::get_endpoint( 'get_refreshed_fragments' ),
		) );
	}
}, 10000 );

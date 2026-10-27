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

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'seedprints-layout-fixes', SPFIX_URL . 'assets/layout.css', array(), (string) filemtime( SPFIX_DIR . 'assets/layout.css' ) );
	wp_enqueue_script( 'seedprints-layout-fixes', SPFIX_URL . 'assets/layout.js', array(), (string) filemtime( SPFIX_DIR . 'assets/layout.js' ), true );
}, 10000 );

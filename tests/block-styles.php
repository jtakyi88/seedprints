<?php
if ( PHP_SAPI !== 'cli' || defined( 'ABSPATH' ) ) { exit; }
define( 'ABSPATH', __DIR__ );
$GLOBALS['sp_style_test'] = array( 'footer' => 0, 'admin' => false, 'theme' => 'seedprints', 'cart' => false, 'checkout' => false, 'account' => false );
$GLOBALS['sp_style_callbacks'] = array();
function add_filter( $name, $callback, $priority = 10, $args = 1 ) { $GLOBALS['sp_style_callbacks'][$name] = $callback; }
function did_action( $name ) { return $GLOBALS['sp_style_test']['footer']; }
function is_admin() { return $GLOBALS['sp_style_test']['admin']; }
function get_stylesheet() { return $GLOBALS['sp_style_test']['theme']; }
function is_cart() { return $GLOBALS['sp_style_test']['cart']; }
function is_checkout() { return $GLOBALS['sp_style_test']['checkout']; }
function is_account_page() { return $GLOBALS['sp_style_test']['account']; }
require $argv[1];
$count = 0;
function check_style( $condition, $message ) { if ( ! $condition ) { fwrite( STDERR, "FAIL: $message\n" ); exit( 1 ); } ++$GLOBALS['count']; }
$tag = '<link rel="stylesheet" href="block.css">';
check_style( $tag === sp_filter_unused_block_styles( $tag, 'wc-blocks-style' ), 'Styles printed before footer stay intact' );
$GLOBALS['sp_style_test']['footer'] = 1;
foreach ( array( 'wc-blocks-style', 'wc-square-cart-checkout-block' ) as $handle ) {
 check_style( '' === sp_filter_unused_block_styles( $tag, $handle ), 'Unused late block style skipped' );
 foreach ( array( 'cart', 'checkout', 'account', 'admin' ) as $context ) {
  $GLOBALS['sp_style_test'][$context] = true;
  check_style( $tag === sp_filter_unused_block_styles( $tag, $handle ), 'Transactional/admin style retained' );
  $GLOBALS['sp_style_test'][$context] = false;
 }
}
check_style( $tag === sp_filter_unused_block_styles( $tag, 'wc-square-digital-wallet' ), 'Native wallet styling retained' );
check_style( $tag === sp_filter_unused_block_styles( $tag, 'seedprints-storefront' ), 'Theme styling retained' );
$GLOBALS['sp_style_test']['theme'] = 'other';
check_style( $tag === sp_filter_unused_block_styles( $tag, 'wc-blocks-style' ), 'Other themes untouched' );
$GLOBALS['sp_style_test']['theme'] = 'seedprints';
$render = $GLOBALS['sp_style_callbacks']['render_block'];
check_style( 'content' === $render( 'content', array( 'blockName' => 'seedprints/rail' ) ), 'Custom block output untouched' );
check_style( empty( $GLOBALS['sp_rendered_woocommerce_block'] ), 'Custom rail needs no Woo block CSS' );
$render( 'native content', array( 'blockName' => 'woocommerce/product-collection' ) );
check_style( $tag === sp_filter_unused_block_styles( $tag, 'wc-blocks-style' ), 'Actual native Woo block retains CSS' );
check_style( $tag === sp_filter_unused_block_styles( $tag, 'wc-square-cart-checkout-block' ), 'Actual native Woo block retains Square block CSS' );
echo json_encode( array( 'assertions' => $count, 'passed' => true, 'settings_changed' => false ) ) . "\n";

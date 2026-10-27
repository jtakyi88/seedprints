<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'staging' !== wp_get_environment_type() ) { exit; }
$GLOBALS['count'] = 0;
function sp_assert_analytics( $test, $name ) { if ( ! $test ) { WP_CLI::error( $name ); } ++$GLOBALS['count']; }
sp_ensure_cart();
WC()->cart->empty_cart();
$_REQUEST['add-to-cart'] = 613;
$key = WC()->cart->add_to_cart( 613, 3 );
sp_assert_analytics( (bool) $key, 'Native synthetic cart add' );
$queue = WC()->session->get( 'sp_native_add_events' );
sp_assert_analytics( 1 === count( $queue ), 'One server receipt' );
sp_assert_analytics( '613' === $queue[0]['ecommerce']['items'][0]['item_id'], 'Native receipt ID' );
sp_assert_analytics( 45.0 === $queue[0]['ecommerce']['value'], 'Native quantity total' );
$events = sp_collect_ecommerce_events();
sp_assert_analytics( 1 === count( $events ), 'Queued receipt consumed' );
sp_assert_analytics( ! WC()->session->get( 'sp_native_add_events' ), 'Receipt cleared' );
sp_assert_analytics( array() === sp_collect_ecommerce_events(), 'No replay on next page' );
$p = wc_get_product( 657 ); $v = $p->get_available_variations();
sp_assert_analytics( ! empty( $v[0]['seedprints_analytics_item']['item_variant'] ), 'Variation metadata' );
$config = sp_analytics_configuration();
sp_assert_analytics( ! $config['bridge'] && ! $config['measurementId'], 'Stage never bridges GA4' );
WC()->cart->empty_cart();
unset( $_REQUEST['add-to-cart'] );
echo wp_json_encode( array( 'assertions' => $GLOBALS['count'], 'passed' => true, 'orders_created' => 0, 'payments_attempted' => 0, 'emails_sent' => 0 ) ) . "\n";

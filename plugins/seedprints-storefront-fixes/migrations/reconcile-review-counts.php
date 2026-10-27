<?php
/** Run with wp eval-file on staging first. Changes derived counters, never reviews/prices. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! class_exists( 'WC_Comments' ) ) {
	throw new RuntimeException( 'Requires WooCommerce through WP-CLI.' );
}
$updated = 0;
foreach ( wc_get_products( array( 'limit' => -1, 'return' => 'ids', 'status' => 'publish' ) ) as $product_id ) {
	$product = wc_get_product( $product_id );
	$actual = WC_Comments::get_review_count_for_product( $product );
	if ( $actual !== $product->get_review_count() || WC_Comments::get_rating_counts_for_product( $product ) !== $product->get_rating_counts() ) {
		WC_Comments::clear_transients( $product_id );
		++$updated;
	}
}
WP_CLI::success( sprintf( 'Reconciled derived review counters for %d products; review content was preserved.', $updated ) );

<?php
/** Photo-led mobile navigation; the existing header menu remains the no-JS fallback. */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_footer', function () {
	if ( is_checkout() && ! is_order_received_page() ) { return; }
	$youth = wc_get_product( 12247 );
	$arrow = '<svg class="sp-mobile-arrow" viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M4 12 12 4M4 4h8v8"/></svg>';
	$tiles = array(
		array( 'Women', 'women', 12160 ),
		array( 'Men', 'men', 11884 ),
		array( 'Youth', 'youth', $youth ? $youth->get_image_id() : 13093 ),
		array( 'Home & table', 'home-essentials', 13189 ),
		array( 'Drinkware', 'drinkware', 13261 ),
		array( 'Gifts', 'gifts', 13101 ),
	);
	?>
	<dialog id="sp-mobile-navigation" class="spfix-dialog sp-mobile-drawer" aria-labelledby="sp-mobile-navigation-title">
		<div class="spfix-dialog-heading"><h2 id="sp-mobile-navigation-title">Shop Seed Prints</h2><form method="dialog"><button class="spfix-close" aria-label="Close menu">×</button></form></div>
		<nav aria-label="Mobile categories">
			<a class="sp-mobile-shop-all" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Shop all pieces <?php echo $arrow; ?></a>
			<div class="sp-mobile-category-grid">
			<?php foreach ( $tiles as $tile ) :
				$term = get_term_by( 'slug', $tile[1], 'product_cat' );
				if ( ! $term || is_wp_error( $term ) ) { continue; }
				$url = get_term_link( $term );
				if ( is_wp_error( $url ) ) { continue; }
				?><a href="<?php echo esc_url( $url ); ?>"><?php echo sp_media( $tile[2], '', '(max-width:420px) calc((100vw - 64px)/2), 178px' ); ?><span><?php echo esc_html( $tile[0] ); ?><?php echo $arrow; ?></span></a>
			<?php endforeach; ?>
			</div>
			<div class="sp-mobile-menu-links"><a href="<?php echo esc_url( home_url( '/product-category/apparel/' ) ); ?>">All apparel</a><a href="<?php echo esc_url( home_url( '/custom/' ) ); ?>">Design your own</a><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">Our story</a><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Your account</a></div>
		</nav>
	</dialog>
	<?php
}, 14 );

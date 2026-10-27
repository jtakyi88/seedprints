<?php
defined( 'ABSPATH' ) || exit;

function sp_render_header( $a ) { if ( function_exists( 'spfix_header' ) ) { spfix_header( $a['imageId'] ?? 0 ); } }

function sp_render_footer() { ?>
	<footer class="sp-footer"><div class="sp-container"><div class="sp-footer-grid"><div><a class="sp-footer-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">Seed Prints<span>Bible made simple</span></a><p>Thoughtful pieces. Everyday faith.<br>Founder-led in Maryland.</p></div><nav aria-label="Footer shop"><h2>Shop</h2><a href="<?php echo esc_url( home_url( '/product-category/apparel/' ) ); ?>">Apparel</a><a href="<?php echo esc_url( home_url( '/product-category/home-essentials/' ) ); ?>">Home & table</a><a href="<?php echo esc_url( home_url( '/product-category/drinkware/' ) ); ?>">Drinkware</a><a href="<?php echo esc_url( home_url( '/custom/' ) ); ?>">Custom designs</a><a href="<?php echo esc_url( home_url( '/product-category/gifts/' ) ); ?>">Gifts</a></nav><nav aria-label="Help"><h2>Here to help</h2><a href="<?php echo esc_url( home_url( '/customer-care/#shipping' ) ); ?>">Shipping</a><a href="<?php echo esc_url( home_url( '/customer-care/#exchange' ) ); ?>">Returns & exchanges</a><a href="<?php echo esc_url( home_url( '/customer-care/' ) ); ?>">Sizing & FAQ</a><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Your account</a><a href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">Our story</a></nav><div><h2>Let’s talk</h2><a href="mailto:info@seedprints.com">info@seedprints.com</a><a href="sms:+14432922198">Text (443) 292-2198</a><a href="https://www.instagram.com/seedprints/">Instagram ↗</a><a href="https://www.facebook.com/seedprints">Facebook ↗</a><p>5000 Thayer Center, Suite C<br>Oakland, Maryland 21550</p></div></div><div class="sp-footer-bottom"><p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Seed Prints</p><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy policy</a><span class="sp-payments" aria-label="Payments processed securely by Square">Square · Visa · Mastercard · American Express</span></div></div></footer>
<?php }

function sp_render_story( $a ) { ?>
	<main id="main-content"><section class="sp-story-hero sp-container"><div><p class="sp-eyebrow">The heart behind Seed Prints</p><h1><?php echo esc_html( $a['heading'] ?: 'Planting seeds of faith.' ); ?></h1><p class="sp-lead">From one mom’s heart to the things you wear, use and share.</p></div><?php echo sp_media( $a['imageId'] ?: 11882, 'The founder of Seed Prints', '(max-width:767px) 100vw, 45vw', true ); ?></section><section class="sp-section sp-reading"><h2>A truth we live and see every day.</h2><p>Seed Prints began as a simple desire from one mom’s heart—to make the Bible not just a book we read, but a truth we live and see every day.</p><p>As a mom of growing kids, I knew that the teen years might bring fewer open doors for deep conversations about faith. So I started looking for quiet, consistent ways to keep God’s Word in sight and in mind.</p><p>What began with a few Scripture prints for our walls has grown into a mission: to make Bible literacy easy, beautiful, and a natural part of everyday life.</p><h2>Made with intention.</h2><p>I’m a scientist by training, so I care deeply about the details—and I pour that same precision into every design and product we offer. I also love words, nature, and photography, and those passions come through in our work.</p><p>From Scripture-inspired prints to wholesome apparel and custom designs, each item is created with intention, to encourage hearts and inspire faith.</p><p>Whether you’re a fellow mom trying to plant seeds of truth in your home, a believer looking for meaningful gifts, or someone who values beauty rooted in purpose—you’re welcome here.</p><div class="sp-actions"><?php echo sp_button( 'Shop the collection', wc_get_page_permalink( 'shop' ) ); echo sp_button( 'Start your design', home_url( '/custom/' ), true ); ?></div></section><?php sp_render_press(); ?></main>
<?php }

function sp_render_custom() {
	echo '<main id="main-content" class="sp-container sp-custom-page"><p class="sp-eyebrow">Made personal</p><h1>Make it meaningful.</h1><p class="sp-lead">Choose a piece, add your words and make a little something that’s yours.</p>';
	if ( function_exists( 'sp_custom_form' ) ) { sp_custom_form(); }
	else { echo '<p>Tell us what you have in mind.</p>' . sp_button( 'Start a custom request', home_url( '/contact-us/' ) ); }
	echo '</main>';
}

function sp_render_wishlist() { ?>
	<main id="main-content" class="sp-container sp-section"><p class="sp-eyebrow">Keep a little meaning close</p><h1>Your saved pieces</h1><div id="sp-wishlist" class="sp-product-grid" aria-live="polite"><p class="sp-loading">Loading your saved pieces…</p></div><noscript><p>Turn on JavaScript to see the pieces saved on this device.</p></noscript></main>
<?php }

function sp_render_contact() { ?>
	<main id="main-content" class="sp-container sp-section"><p class="sp-eyebrow">We’re here to help</p><h1>Let’s talk.</h1><div class="sp-contact-grid"><div><p class="sp-lead">A question, a custom idea or a little help finding the right piece? We’d love to hear from you.</p><p><a href="mailto:info@seedprints.com">info@seedprints.com</a><br><a href="sms:+14432922198">Text (443) 292-2198</a></p><p>5000 Thayer Center, Suite C<br>Oakland, Maryland 21550</p></div><div><?php if ( shortcode_exists( 'form' ) ) { echo do_shortcode( '[form id="12771"]' ); } ?></div></div></main>
<?php }

function sp_render_cart() {
	if ( ! WC()->cart && did_action( 'wp_loaded' ) ) { wc_load_cart(); }
	echo '<main id="main-content" class="sp-container spfix-commerce-page"><h1>Your bag</h1>' . do_shortcode( '[woocommerce_cart]' ) . '</main>';
}

function sp_render_checkout() {
	if ( ! WC()->cart && did_action( 'wp_loaded' ) ) { wc_load_cart(); }
	echo '<main id="main-content" class="sp-container spfix-commerce-page"><h1>' . ( is_order_received_page() ? 'Thank you' : 'Checkout' ) . '</h1>' . do_shortcode( '[woocommerce_checkout]' ) . '</main>';
}

add_filter( 'seedprints_navigation_markup', function ( $html, $links ) {
	ob_start();
	foreach ( $links as $label => $url ) {
		if ( 'Apparel' !== $label ) { echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'; continue; }
		echo '<details class="sp-mega"><summary>Apparel</summary><div class="sp-mega-panel">';
		foreach ( array( array( 'Women', 'women', 12160 ), array( 'Men', 'men', 11884 ), array( 'Youth', 'youth', wc_get_product( 12247 ) ? wc_get_product( 12247 )->get_image_id() : 13093 ) ) as $tile ) {
			echo '<a href="' . esc_url( home_url( '/product-category/' . $tile[1] . '/' ) ) . '">' . sp_media( $tile[2], 'Shop ' . $tile[0], '240px' ) . '<span>' . esc_html( $tile[0] ) . '</span></a>';
		}
		echo '<a class="sp-mega-all" href="' . esc_url( $url ) . '">Shop all apparel ↗</a></div></details>';
	}
	return ob_get_clean();
}, 10, 2 );

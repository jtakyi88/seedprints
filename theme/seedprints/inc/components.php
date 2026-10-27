<?php
defined( 'ABSPATH' ) || exit;

function sp_button( $label, $url, $secondary = false, $extra = '' ) {
	return '<a class="sp-button' . ( $secondary ? ' sp-button--secondary' : '' ) . '" href="' . esc_url( $url ) . '" ' . $extra . '>' . esc_html( $label ) . '</a>';
}

function sp_media( $id, $alt, $sizes = '(max-width: 767px) 100vw, 50vw', $eager = false, $class = '' ) {
	static $map;
	if ( null === $map ) { $map = json_decode( (string) @file_get_contents( get_theme_file_path( '/assets/media/index.json' ) ), true ) ?: array(); }
	$images = $map[ (string) $id ] ?? array();
	if ( $images ) {
		$largest = end( $images );
		$srcset = array_map( function ( $image ) { return get_theme_file_uri( '/assets/media/' . $image['file'] ) . ' ' . $image['width'] . 'w'; }, $images );
		return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( get_theme_file_uri( '/assets/media/' . $largest['file'] ) ) . '" srcset="' . esc_attr( implode( ', ', $srcset ) ) . '" sizes="' . esc_attr( $sizes ) . '" width="' . absint( $largest['width'] ) . '" height="' . absint( $largest['height'] ) . '" alt="' . esc_attr( $alt ) . '" loading="' . ( $eager ? 'eager' : 'lazy' ) . '" decoding="async"' . ( $eager ? ' fetchpriority="high"' : '' ) . '>';
	}
	return wp_get_attachment_image( $id, 'large', false, array( 'alt' => $alt, 'sizes' => $sizes, 'loading' => $eager ? 'eager' : 'lazy', 'fetchpriority' => $eager ? 'high' : 'auto', 'class' => $class ) );
}

function sp_hero_media( $id, $alt ) {
	$map = json_decode( (string) @file_get_contents( get_theme_file_path( '/assets/media/hero-index.json' ) ), true );
	$mobile = $map[(string)$id]['mobile'] ?? array();
	$html = '<picture>';
	foreach ( array( 'avif', 'webp' ) as $format ) {
		if ( empty( $mobile[$format] ) ) { continue; }
		$srcset = array_map( function ( $image ) { return get_theme_file_uri( '/assets/media/' . $image['file'] ) . ' ' . $image['width'] . 'w'; }, $mobile[$format] );
		$html .= '<source type="image/' . $format . '" media="(max-width: 767px)" srcset="' . esc_attr( implode( ', ', $srcset ) ) . '" sizes="100vw">';
	}
	return $html . sp_media( $id, $alt, '(max-width: 767px) 100vw, 65vw', true ) . '</picture>';
}

function sp_product_card( $product, $eager = false ) {
	if ( ! $product instanceof WC_Product || ! $product->is_visible() ) { return ''; }
	$id = $product->get_id();
	$custom = function_exists( 'sp_custom_product_allowed' ) && sp_custom_product_allowed( $id );
	$tracking_price = (float) $product->get_price();
	if ( $custom && function_exists( 'sp_custom_fee' ) ) { $fee = sp_custom_fee( $id ); if ( ! is_wp_error( $fee ) ) { $tracking_price += $fee; } }
	$tracking_price = function_exists( 'sp_analytics_item' ) ? sp_analytics_item( $product )['price'] : wc_get_price_excluding_tax( $product, array( 'price' => $tracking_price ) );
	ob_start(); ?>
	<article class="sp-product-card <?php echo has_term( 'apparel', 'product_cat', $id ) ? 'sp-product-card--apparel' : 'sp-product-card--objects'; ?>" data-product-id="<?php echo absint( $id ); ?>" data-product-name="<?php echo esc_attr( $product->get_name() ); ?>" data-product-price="<?php echo esc_attr( $tracking_price ); ?>">
		<div class="sp-product-photo">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo sp_media( $product->get_image_id(), $product->get_name() . ' — product photo', '(max-width: 767px) 46vw, (max-width: 1100px) 30vw, 24vw', $eager ); ?>
			<?php $gallery = array_values( array_diff( $product->get_gallery_image_ids(), array( $product->get_image_id() ) ) ); if ( $gallery ) { echo sp_media( $gallery[0], $product->get_name() . ' — alternate view', '(max-width: 767px) 46vw, 24vw', false, 'sp-product-alternate' ); } ?></a>
			<button class="sp-wish-button" type="button" data-sp-wish="<?php echo absint( $id ); ?>" aria-label="<?php echo esc_attr( 'Save ' . $product->get_name() ); ?>" aria-pressed="false"><?php echo spfix_icon( 'heart' ); ?></button>
			<?php if ( $product->is_on_sale() ) : ?><span class="sp-sale">Sale</span><?php endif; ?>
		</div>
		<div class="sp-product-copy"><h3><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3><div class="sp-product-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
		<?php if ( $product->get_review_count() > 0 ) : ?><div class="sp-rating"><?php echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ); ?><span><?php echo absint( $product->get_review_count() ); ?> reviews</span></div><?php endif; ?>
		<?php if ( $product->is_type( 'variable' ) ) : $colors = $product->get_variation_attributes()['pa_color'] ?? array(); if ( $colors ) : ?><div class="sp-card-swatches" aria-label="Available colors"><?php foreach ( array_slice( $colors, 0, 3 ) as $color ) : $term = get_term_by( 'slug', $color, 'pa_color' ); $label = $term && ! is_wp_error( $term ) ? $term->name : $color; $hex = array( 'black' => '#1f1b16', 'white' => '#fff', 'red' => '#c8102e', 'navy' => '#26384a', 'pink' => '#dfb3bb', 'gray' => '#8a8178', 'grey' => '#8a8178' )[strtolower( $color )] ?? '#efe7da'; ?><a href="<?php echo esc_url( add_query_arg( 'attribute_pa_color', $color, $product->get_permalink() ) ); ?>" aria-label="<?php echo esc_attr( 'View ' . $product->get_name() . ' in ' . $label ); ?>"><span style="background:<?php echo esc_attr( $hex ); ?>" aria-hidden="true"></span><span class="spfix-sr-only"><?php echo esc_html( $label ); ?></span></a><?php endforeach; ?><?php if ( count( $colors ) > 3 ) : ?><a href="<?php echo esc_url( $product->get_permalink() ); ?>" aria-label="See all colors">+<?php echo absint( count( $colors ) - 3 ); ?></a><?php endif; ?></div><?php endif; endif; ?>
		<?php if ( ! $product->is_in_stock() ) : ?><span class="sp-stock-label">Sold out</span>
		<?php elseif ( $custom ) : echo sp_button( 'Personalize', add_query_arg( 'product', $id, home_url( '/custom/' ) ), true );
		elseif ( $product->is_type( 'simple' ) && $product->is_purchasable() ) : ?>
		<a class="sp-button sp-button--secondary button add_to_cart_button ajax_add_to_cart" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-product_id="<?php echo absint( $id ); ?>" data-quantity="1" aria-label="<?php echo esc_attr( 'Add to bag: ' . $product->get_name() ); ?>" rel="nofollow">Add to bag</a>
		<?php elseif ( $product->is_type( 'variable' ) && function_exists( 'product_addons' ) && product_addons()->get_product_option_ids( $id ) ) : echo sp_button( 'Choose options', $product->get_permalink(), true );
		elseif ( $product->is_type( 'variable' ) ) : ?><button class="sp-button sp-button--secondary" type="button" data-sp-quick="<?php echo absint( $id ); ?>">Choose options</button>
		<?php else : echo sp_button( 'View product', $product->get_permalink(), true ); endif; ?>
		</div>
	</article>
	<?php return ob_get_clean();
}

function sp_products( $collection = '', $limit = 8 ) {
	$args = array( 'status' => 'publish', 'limit' => $limit, 'type' => array( 'simple', 'variable' ), 'visibility' => 'catalog', 'orderby' => 'include', 'order' => 'DESC' );
	$args['include'] = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => $limit, 'fields' => 'ids', 'meta_key' => 'total_sales', 'orderby' => array( 'meta_value_num' => 'DESC', 'ID' => 'DESC' ) ) );
	if ( $collection ) {
		$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => $limit, 'fields' => 'ids', 'tax_query' => array( array( 'taxonomy' => 'sp_collection', 'field' => 'slug', 'terms' => $collection ) ) ) );
		if ( ! $ids ) { return array(); }
		$args['include'] = $ids;
	}
	return wc_get_products( $args );
}

function sp_render_hero( $a ) { ?>
	<section class="sp-hero">
		<div class="sp-container sp-hero-inner"><div class="sp-hero-copy"><p class="sp-eyebrow">Bible made simple</p><h1><?php echo esc_html( $a['heading'] ?: 'Wear the Word.' ); ?></h1><p class="sp-lead"><?php echo esc_html( $a['body'] ?: 'Meaningful apparel and everyday pieces that keep Scripture close.' ); ?></p><div class="sp-actions"><?php echo sp_button( 'Shop bestsellers', home_url( '/shop/?orderby=popularity' ) ); echo sp_button( 'Design your own', home_url( '/custom/' ), true ); ?></div></div></div>
		<div class="sp-hero-photo"><?php echo sp_hero_media( $a['imageId'] ?: 11884, 'Man wearing the Jesus Died Once, Alive Forever tee' ); ?></div>
	</section>
<?php }

function sp_render_press() { ?>
	<div class="sp-press sp-container"><span>As seen on</span><a href="<?php echo esc_url( home_url( '/introducing-seed-prints-transforming-faith-into-fashion-and-daily-living-on-cbs/' ) ); ?>"><?php echo wp_get_attachment_image( 12743, 'large', false, array( 'alt' => 'Watch the Seed Prints feature on CBS; featured on ABC, NBC and CBS', 'loading' => 'lazy' ) ); ?></a></div>
<?php }

function sp_render_categories( $a ) {
	$cards = array( array( 'Women', 'women', 12160 ), array( 'Men', 'men', 11884 ), array( 'Youth', 'youth', wc_get_product( 12247 ) ? wc_get_product( 12247 )->get_image_id() : 13093 ), array( 'Home & table', 'home-essentials', 13189 ), array( 'Gifts', 'gifts', 13101 ) ); ?>
	<section class="sp-section sp-container"><div class="sp-section-heading"><h2><?php echo esc_html( $a['heading'] ?: 'Faith for every day.' ); ?></h2><p>Find a little meaning in the things you wear, use and give.</p></div><div class="sp-category-grid">
	<?php foreach ( $cards as $card ) : $term = get_term_by( 'slug', $card[1], 'product_cat' ); ?><a class="sp-category-card" href="<?php echo esc_url( $term ? get_term_link( $term ) : wc_get_page_permalink( 'shop' ) ); ?>"><?php echo sp_media( $card[2], 'Shop Seed Prints ' . $card[0], '(max-width:767px) 65vw, 19vw' ); ?><span><?php echo esc_html( $card[0] ); ?><span aria-hidden="true">↗</span></span></a><?php endforeach; ?>
	</div></section>
<?php }

function sp_render_rail( $a ) { ?>
	<section class="sp-section sp-container"><div class="sp-section-heading sp-heading-row"><div><p class="sp-eyebrow">Made to mean more</p><h2><?php echo esc_html( $a['heading'] ?: 'Bestsellers' ); ?></h2></div><a class="sp-text-link" href="<?php echo esc_url( home_url( '/shop/?orderby=popularity' ) ); ?>">Shop all <span aria-hidden="true">↗</span></a></div><div class="sp-rail" tabindex="0" aria-label="Bestselling products; scroll for more">
	<?php foreach ( sp_products() as $product ) { echo sp_product_card( $product ); } ?>
	</div></section>
<?php }

function sp_render_collections() {
	$tiles = array(
		array( 'Attributes of Jesus', 'A simple statement. An eternal truth.', 'attributes-of-jesus', 11939, 'What these words mean', home_url( '/product/jesus-died-once/#tab-description' ) ),
		array( 'Bible placemats', 'A little learning around the table.', 'bible-placemats', 13189, 'Discover the collection', home_url( '/product/new-testament-bible-placemat/' ) ),
		array( 'Pray for the Nations', 'Wear a reminder to pray beyond yourself.', 'pray-for-the-nations', 13093, 'The heart behind the design', home_url( '/about-us/' ) ),
	); ?>
	<section class="sp-section sp-collections" aria-label="Collections"><?php foreach ( $tiles as $i => $tile ) : ?><article class="sp-collection-tile <?php echo $i % 2 ? 'sp-tile-reverse' : ''; ?>"><div class="sp-tile-photo"><?php echo sp_media( $tile[3], $tile[0] . ' collection', '(max-width:767px) 100vw, 50vw' ); ?></div><div class="sp-tile-copy"><p class="sp-eyebrow">The Seed Prints collections</p><h2><?php echo esc_html( $tile[0] ); ?></h2><p class="sp-lead"><?php echo esc_html( $tile[1] ); ?></p><div class="sp-actions"><?php echo sp_button( 'Shop the collection', add_query_arg( 'collection', $tile[2], wc_get_page_permalink( 'shop' ) ) ); echo sp_button( 'Learn the story', $tile[5], true ); ?></div></div></article><?php endforeach; ?></section>
<?php }

function sp_render_verse( $a ) { ?>
	<section class="sp-verse"><div class="sp-container"><p class="sp-eyebrow">Verse of the week</p><blockquote><p>“<?php echo esc_html( $a['body'] ?: 'Thy word is a lamp unto my feet, and a light unto my path.' ); ?>”</p><cite><?php echo esc_html( $a['reference'] ?: 'Psalm 119:105 · KJV' ); ?></cite></blockquote></div></section>
<?php }

function sp_render_custom_steps( $a ) { ?>
	<section class="sp-section sp-container sp-custom-teaser"><div><p class="sp-eyebrow">Made personal</p><h2><?php echo esc_html( $a['heading'] ?: 'Your words. Your witness.' ); ?></h2><p class="sp-lead">Create a meaningful piece for your home, your people or your ministry.</p><?php echo sp_button( 'Start your design', home_url( '/custom/' ) ); ?></div><ol class="sp-steps"><li><span aria-hidden="true">01</span><h3>Choose your piece</h3><p>Start with a customizable product.</p></li><li><span aria-hidden="true">02</span><h3>Make it meaningful</h3><p>Add a verse, your own words or an image.</p></li><li><span aria-hidden="true">03</span><h3>Make it yours</h3><p>Review the design, then add it to your bag or send a request.</p></li></ol></section>
<?php }

function sp_render_reviews( $a ) {
	$reviews = array(
		array( 'I ordered a scripture shirt from Seed Prints! From ordering the shirt to pickup was a smooth and wonderful process. I’m glad I used this service, and I’m enjoying my shirt!', 'Maureen', 'Bowie', 5 ),
		array( 'The fabric is incredibly soft and comfortable—ideal for everyday wear. The print catches attention immediately, and both of us feel proud sporting the thoughtful message together.', 'Tiffany BK.', 'Georgia', 0 ),
		array( 'The quality of their work and product is impeccable from the material, the design and prompt delivery.', 'James T.', 'Connecticut', 0 ),
		array( 'The designs are always creative and inspiring, and Seed Prints has done a fantastic job of bringing my custom designs to life quickly and accurately.', 'Emma R.', 'Virginia', 0 ),
	);
	$photos = array();
	foreach ( get_comments( array( 'status' => 'approve', 'meta_key' => '_sp_review_photo', 'number' => 8 ) ) as $comment ) {
		$photo = get_comment_meta( $comment->comment_ID, '_sp_review_photo', true );
		if ( empty( $photo['consent'] ) || 'product' !== get_post_type( $comment->comment_post_ID ) || 'publish' !== get_post_status( $comment->comment_post_ID ) ) { continue; }
		$rating = absint( get_comment_meta( $comment->comment_ID, 'rating', true ) );
		$photos[] = array( wp_trim_words( $comment->comment_content, 55 ), $comment->comment_author, get_the_title( $comment->comment_post_ID ), $rating <= 5 ? $rating : 0, add_query_arg( array( 'action' => 'sp_review_photo', 'review' => $comment->comment_ID ), admin_url( 'admin-post.php' ) ), get_permalink( $comment->comment_post_ID ) . '#reviews' );
		if ( count( $photos ) >= 4 ) { break; }
	}
	$reviews = array_merge( $photos, $reviews ); ?>
	<section class="sp-section sp-container"><div class="sp-section-heading"><p class="sp-eyebrow">Words from our community</p><h2><?php echo esc_html( $a['heading'] ?: 'Meaning that goes with you.' ); ?></h2></div><div class="sp-review-rail" tabindex="0" aria-label="Customer stories; scroll for more"><?php foreach ( $reviews as $review ) : ?><figure class="sp-review-card"><?php if ( ! empty( $review[4] ) ) : ?><img class="sp-review-customer-photo" src="<?php echo esc_url( $review[4] ); ?>" alt="<?php echo esc_attr( 'Customer photo of ' . $review[2] ); ?>" loading="lazy" width="320" height="240"><?php endif; ?><?php if ( $review[3] ) : ?><div class="sp-review-stars" aria-label="<?php echo esc_attr( $review[3] . ' out of 5 stars' ); ?>"><?php echo esc_html( str_repeat( '★', $review[3] ) . str_repeat( '☆', 5 - $review[3] ) ); ?></div><?php endif; ?><blockquote><p>“<?php echo esc_html( $review[0] ); ?>”</p></blockquote><figcaption><strong><?php echo esc_html( $review[1] ); ?></strong><span><?php echo esc_html( $review[2] ); ?></span><?php if ( ! empty( $review[5] ) ) : ?><a class="sp-text-link" href="<?php echo esc_url( $review[5] ); ?>">Read the reviews</a><?php endif; ?></figcaption></figure><?php endforeach; ?></div></section>
<?php }

function sp_render_founder( $a ) { ?>
	<section class="sp-section sp-container sp-founder"><div class="sp-founder-photo"><?php echo sp_media( $a['imageId'] ?: 11882, 'The founder of Seed Prints', '(max-width:767px) 100vw, 40vw' ); ?></div><div><p class="sp-eyebrow">Rooted in faith. Made with care.</p><h2><?php echo esc_html( $a['heading'] ?: 'From one mom’s heart.' ); ?></h2><p class="sp-lead">What began as a desire to keep God’s Word in sight has grown into a mission: to make Bible literacy easy, beautiful and part of everyday life.</p><p>Founder-led in Maryland, with a scientist’s eye for detail and a love of words, nature and photography.</p><?php echo sp_button( 'Read our story', home_url( '/about-us/' ), true ); ?></div></section>
<?php }

function sp_render_signup( $a ) { ?>
	<section class="sp-signup" id="newsletter"><div class="sp-container"><p class="sp-eyebrow">A little encouragement in your inbox</p><h2><?php echo esc_html( $a['heading'] ?: '10% off + a weekly verse.' ); ?></h2><p>Join the Seed Prints list for your first-order discount and encouragement along the way.</p><div class="sp-signup-form"><?php echo shortcode_exists( 'happyforms' ) ? do_shortcode( '[happyforms id="12754" /]' ) : '<p><a href="mailto:info@seedprints.com">Email us to join the list</a></p>'; ?></div></div></section>
<?php }

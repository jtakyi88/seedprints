<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () { if ( is_admin() || wp_doing_ajax() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) { return; } foreach ( array( 'type', 'collection', 'filter_color', 'filter_size', 'min_price', 'max_price' ) as $key ) { if ( isset( $_GET[$key] ) && is_string( $_GET[$key] ) && '' === trim( $_GET[$key] ) ) { unset( $_GET[$key], $_REQUEST[$key] ); } } }, 1 );

add_filter( 'loop_shop_per_page', function () { return 24; }, 30 );
add_filter( 'woocommerce_default_catalog_orderby', function () { return 'popularity'; } );
add_filter( 'woocommerce_product_query_tax_query', function ( $query ) {
	foreach ( array( 'collection' => 'sp_collection', 'type' => 'sp_product_type' ) as $key => $taxonomy ) {
		if ( ! empty( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) && taxonomy_exists( $taxonomy ) ) {
			$query[] = array( 'taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => sanitize_title( wp_unslash( $_GET[ $key ] ) ) );
		}
	}
	return $query;
} );

function sp_filter_select( $label, $name, $taxonomy ) {
	if ( ! taxonomy_exists( $taxonomy ) ) { return; }
	$selected = isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ? sanitize_title( wp_unslash( $_GET[ $name ] ) ) : '';
	echo '<label for="sp-filter-' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label><select id="sp-filter-' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"><option value="">All ' . esc_html( strtolower( $label ) ) . '</option>';
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) { echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $selected, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>'; }
	}
	echo '</select>';
}

function sp_render_archive() {
	$title = is_search() ? sprintf( 'Results for “%s”', get_search_query() ) : woocommerce_page_title( false );
	$base = is_product_taxonomy() ? get_term_link( get_queried_object() ) : wc_get_page_permalink( 'shop' );
	?>
	<main class="sp-container sp-catalog" id="main-content">
		<?php woocommerce_breadcrumb(); ?>
		<div class="sp-catalog-heading"><p class="sp-eyebrow">Bible made simple</p><h1><?php echo esc_html( $title ); ?></h1><p class="sp-lead">Meaningful pieces for the life you live and the people you love.</p></div>
		<div class="sp-catalog-toolbar"><button class="sp-button sp-button--secondary" type="button" data-spfix-open="sp-filter-sheet">Filters <span aria-hidden="true">＋</span></button><div class="sp-filter-chips">
		<?php foreach ( array( 'type', 'filter_color', 'filter_size', 'collection', 'min_price', 'max_price' ) as $key ) { if ( ! empty( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ) { echo '<a href="' . esc_url( remove_query_arg( $key ) ) . '">' . esc_html( ucwords( str_replace( '-', ' ', sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ) ) ) . ' <span aria-hidden="true">×</span><span class="spfix-sr-only"> Remove filter</span></a>'; } } ?>
		</div><?php woocommerce_catalog_ordering(); ?></div>
		<?php woocommerce_result_count(); wc_print_notices(); ?>
		<div class="sp-product-grid">
		<?php $index = 0; if ( have_posts() ) { while ( have_posts() ) { the_post(); $product = wc_get_product( get_the_ID() ); echo sp_product_card( $product, $index++ < 4 ); } } else { echo '<div class="sp-empty"><h2>A fresh start?</h2><p>No pieces match these filters. Try another size, color or collection.</p>' . sp_button( 'Clear filters', $base, true ) . '</div>'; } ?>
		</div><?php woocommerce_pagination(); ?>
	</main>
	<dialog class="spfix-dialog sp-filter-sheet" id="sp-filter-sheet" aria-labelledby="sp-filter-title"><div class="spfix-dialog-heading"><h2 id="sp-filter-title">Find your piece</h2><form method="dialog"><button class="spfix-close" aria-label="Close filters">×</button></form></div><form method="get" action="<?php echo esc_url( $base ); ?>" class="sp-filter-form">
		<?php sp_filter_select( 'Type', 'type', 'sp_product_type' ); sp_filter_select( 'Color', 'filter_color', 'pa_color' ); sp_filter_select( 'Size', 'filter_size', 'pa_size' ); sp_filter_select( 'Collection', 'collection', 'sp_collection' ); ?>
		<fieldset><legend>Price</legend><div class="sp-price-inputs"><label>Minimum <input type="number" name="min_price" min="0" step="1" inputmode="numeric" value="<?php echo isset( $_GET['min_price'] ) && is_scalar( $_GET['min_price'] ) ? esc_attr( wc_clean( wp_unslash( $_GET['min_price'] ) ) ) : ''; ?>"></label><label>Maximum <input type="number" name="max_price" min="0" step="1" inputmode="numeric" value="<?php echo isset( $_GET['max_price'] ) && is_scalar( $_GET['max_price'] ) ? esc_attr( wc_clean( wp_unslash( $_GET['max_price'] ) ) ) : ''; ?>"></label></div></fieldset>
		<?php if ( is_search() ) : ?><input type="hidden" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"><input type="hidden" name="post_type" value="product"><?php endif; ?>
		<div class="sp-actions"><button type="submit" class="sp-button">Apply filters</button><?php echo sp_button( 'Clear all', $base, true ); ?></div>
	</form></dialog>
	<?php wp_reset_postdata();
}

function sp_render_product() {
	global $product;
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product ) { return; }
	echo '<main class="sp-container sp-pdp" id="main-content">';
	woocommerce_breadcrumb();
	// WooCommerce's block-theme compatibility layer removes classic hooks.
	// This template deliberately renders native Woo forms inside our block canvas.
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
	foreach ( array( 'woocommerce_show_product_images' => array( 'woocommerce_before_single_product_summary', 20 ), 'woocommerce_template_single_title' => array( 'woocommerce_single_product_summary', 5 ), 'woocommerce_template_single_rating' => array( 'woocommerce_single_product_summary', 10 ), 'woocommerce_template_single_price' => array( 'woocommerce_single_product_summary', 10 ) ) as $callback => $hook ) { if ( false === has_action( $hook[0], $callback ) ) { add_action( $hook[0], $callback, $hook[1] ); } }
	if ( ( ! function_exists( 'sp_custom_product_allowed' ) || ! sp_custom_product_allowed( $product->get_id() ) ) && false === has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' ) ) { add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 ); }
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	wc_get_template_part( 'content', 'single-product' );
	echo '</main>';
}

add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$verse = get_post_meta( $product->get_id(), '_sp_verse_reference', true );
	$meaning = get_post_meta( $product->get_id(), '_sp_verse_meaning', true );
	if ( $verse ) { echo '<div class="sp-product-verse"><strong>' . esc_html( $verse ) . '</strong><p>' . esc_html( $meaning ) . '</p></div>'; }
	if ( function_exists( 'sp_custom_product_allowed' ) && sp_custom_product_allowed( $product->get_id() ) ) {
		echo '<div class="sp-personalize"><p>Make this piece your own with a verse, your words or an image.</p>' . sp_button( 'Start your design', add_query_arg( 'product', $product->get_id(), home_url( '/custom/' ) ) ) . '</div>';
	}
}, 12 );

add_action( 'wp', function () {
	if ( is_product() && function_exists( 'sp_custom_product_allowed' ) && sp_custom_product_allowed( get_queried_object_id() ) ) { remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 ); }
} );

add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	if ( has_term( 'apparel', 'product_cat', $product->get_id() ) ) { echo '<button type="button" class="sp-text-button" data-spfix-open="sp-size-guide">Find your fit — size guide</button>'; }
	echo '<div class="sp-delivery-info"><p>Shipping calculated at checkout.</p><a href="' . esc_url( home_url( '/customer-care/#shipping' ) ) . '">Shipping & delivery</a><a href="' . esc_url( home_url( '/customer-care/#exchange' ) ) . '">Returns & exchanges</a></div>';
}, 35 );

add_action( 'wp_footer', function () {
	if ( ! is_product() ) { return; }
	?>
	<dialog id="sp-size-guide" class="spfix-dialog sp-size-guide" aria-labelledby="sp-size-title"><div class="spfix-dialog-heading"><h2 id="sp-size-title">Find your fit</h2><form method="dialog"><button class="spfix-close" aria-label="Close size guide">×</button></form></div><p>Use the measurements in our apparel size chart to choose your fit.</p><?php sp_size_guide_text(); ?><img src="<?php echo esc_url( content_url( '/uploads/2025/06/Seeds-Prints-Size-Charts-1080-x-1980-px-1080-x-2280-px.png' ) ); ?>" alt="Seed Prints apparel size chart; open the full chart for measurements" loading="lazy" width="1080" height="2280"><a class="sp-text-link" href="<?php echo esc_url( content_url( '/uploads/2025/06/Seeds-Prints-Size-Charts-1080-x-1980-px-1080-x-2280-px.png' ) ); ?>">Open the full-size chart</a></dialog>
	<div class="sp-mobile-buy"><span><?php echo wp_kses_post( wc_get_product( get_queried_object_id() )->get_price_html() ); ?></span><button class="sp-button" type="button" data-sp-buy>Choose options</button></div>
	<?php
}, 15 );

add_action( 'wp_footer', function () {
	if ( ! is_woocommerce() && ! is_front_page() && ! is_page( 'wishlist' ) ) { return; }
	?>
	<dialog id="sp-quick-add" class="spfix-dialog sp-quick-add" aria-labelledby="sp-quick-title"><div class="spfix-dialog-heading"><h2 id="sp-quick-title">Choose your options</h2><form method="dialog"><button class="spfix-close" aria-label="Close product options">×</button></form></div><div class="sp-quick-content" aria-live="polite"></div></dialog>
	<?php
}, 16 );

add_action( 'wp', function () {
	if ( ! is_product() ) { return; }
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
} );
add_action( 'woocommerce_after_single_product_summary', function () {
	global $product;
	echo '<div class="sp-product-accordions">';
	$story = apply_filters( 'the_content', $product->get_description() );
	$details = $product->get_short_description();
	foreach ( array( 'Story' => $story, 'Details & care' => wpautop( $details ), 'Shipping' => '<p>Shipping rates and delivery options are calculated at checkout based on your address and order.</p><p><a href="' . esc_url( home_url( '/customer-care/#shipping' ) ) . '">Read our shipping and exchange policy</a></p>' ) as $label => $body ) {
		if ( ! trim( wp_strip_all_tags( $body ) ) ) { continue; }
		$body = str_ireplace( '</h1>', '</h2>', preg_replace( '/<h1(\s[^>]*)?>/i', '<h2$1>', $body ) );
		$images = new WP_HTML_Tag_Processor( $body );
		while ( $images->next_tag( 'IMG' ) ) { $images->set_attribute( 'loading', 'lazy' ); $images->remove_attribute( 'fetchpriority' ); if ( ! $images->get_attribute( 'alt' ) ) { $src = (string) $images->get_attribute( 'src' ); $images->set_attribute( 'alt', str_contains( strtolower( $src ), 'notcutemockup' ) ? 'Not Cute Jesus tee from the Seed Prints collection' : 'Additional photo from the Seed Prints collection' ); } }
		$body = $images->get_updated_html();
		echo '<details class="sp-accordion"' . ( 'Story' === $label ? ' id="tab-description"' : '' ) . '><summary><h2>' . esc_html( $label ) . '</h2></summary><div class="sp-accordion-body">' . wp_kses_post( $body ) . '</div></details>';
	}
	if ( comments_open( $product->get_id() ) ) { echo '<details class="sp-accordion" id="reviews"><summary><h2>Reviews (' . absint( $product->get_review_count() ) . ')</h2></summary><div class="sp-accordion-body">'; comments_template(); echo '</div></details>'; }
	echo '</div><section class="sp-related"><h2>A little more meaning.</h2><div class="sp-rail" tabindex="0" aria-label="Related pieces">';
	foreach ( wc_get_related_products( $product->get_id(), 4 ) as $id ) { echo sp_product_card( wc_get_product( $id ) ); }
	echo '</div></section>';
}, 10 );

function sp_cart_shipping_progress() {
	if ( ! WC()->cart || WC()->cart->is_empty() ) { return; }
	$packages = WC()->cart->get_shipping_packages();
	if ( count( $packages ) !== 1 ) { return; }
	$zone = WC_Shipping_Zones::get_zone_matching_package( reset( $packages ) );
	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		if ( 'free_shipping' !== $method->id || 'min_amount' !== $method->get_option( 'requires' ) ) { continue; }
		$threshold = (float) $method->get_option( 'min_amount' );
		if ( $threshold <= 0 ) { continue; }
		$total = (float) WC()->cart->get_displayed_subtotal();
		if ( 'no' === $method->get_option( 'ignore_discounts', 'no' ) ) { $total -= (float) WC()->cart->get_discount_total(); if ( WC()->cart->display_prices_including_tax() ) { $total -= (float) WC()->cart->get_discount_tax(); } }
		$remaining = max( 0, $threshold - $total );
		echo '<div class="sp-shipping-progress"><p>' . ( $remaining ? wp_kses_post( wc_price( $remaining ) ) . ' away from free shipping for this destination.' : 'Your order qualifies for free shipping for this destination.' ) . '</p><progress max="100" value="' . esc_attr( min( 100, max( 0, $total / $threshold * 100 ) ) ) . '" aria-label="Progress toward free shipping"></progress></div>';
		break;
	}
}
function sp_bag_upsell() {
	if ( ! WC()->cart || WC()->cart->is_empty() ) { return; }
	$in_bag = array_map( function ( $item ) { return $item['product_id']; }, WC()->cart->get_cart() );
	$p = wc_get_product( 12371 );
	if ( ! $p || ! $p->is_visible() || ! $p->is_in_stock() || in_array( $p->get_id(), $in_bag, true ) ) { return; }
	echo '<aside class="sp-bag-upsell"><p class="sp-eyebrow">A little learning at the table</p><a href="' . esc_url( $p->get_permalink() ) . '">' . sp_media( $p->get_image_id(), $p->get_name(), '72px' ) . '<span>' . esc_html( $p->get_name() ) . '<br>' . wp_kses_post( $p->get_price_html() ) . '</span><span aria-hidden="true">↗</span></a></aside>';
}
add_action( 'seedprints_bag_merchandising', 'sp_cart_shipping_progress', 5 );
add_action( 'seedprints_bag_merchandising', 'sp_bag_upsell', 10 );
add_action( 'woocommerce_before_cart', 'sp_cart_shipping_progress' );
add_action( 'wp', function () { if ( is_cart() ) { remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display', 10 ); add_action( 'woocommerce_after_cart_table', 'sp_bag_upsell', 15 ); } } );

add_action('woocommerce_before_add_to_cart_button',function(){global$product;if($product&&function_exists('product_addons')&&product_addons()->get_product_option_ids($product->get_id()))echo'<input type="hidden" data-sp-native-configuration value="1">';},1);
add_filter('woocommerce_get_price_html',function($html,$product){if(function_exists('sp_custom_product_allowed')&&sp_custom_product_allowed($product->get_id())&&function_exists('sp_custom_price_html'))return sp_custom_price_html($product).'<small class="sp-customization-note">Customization included</small>';return$html;},100,2);

add_filter('woocommerce_gallery_image_html_attachment_image_params',function($params,$id,$size,$main){global$product;if($product&&empty($params['alt']))$params['alt']=$product->get_name().($main?' — main product photo':' — alternate product photo');return$params;},20,4);

add_filter('woocommerce_gallery_image_html_attachment_image_params',function($params,$id,$size,$main){
 static$map;if(null===$map)$map=json_decode((string)@file_get_contents(get_theme_file_path('/assets/media/index.json')),true)?:array();$images=$map[(string)$id]??array();if(!$images)return$params;$large=end($images);$url=get_theme_file_uri('/assets/media/'.$large['file']);
 $params['src']=$url;$params['srcset']=implode(', ',array_map(function($image){return get_theme_file_uri('/assets/media/'.$image['file']).' '.$image['width'].'w';},$images));$params['sizes']='(max-width: 767px) calc(100vw - 32px), 50vw';$params['width']=$large['width'];$params['height']=$large['height'];$params['data-src']=$url;$params['data-large_image']=$url;$params['data-large_image_width']=$large['width'];$params['data-large_image_height']=$large['height'];$params['loading']=$main?'eager':'lazy';if($main)$params['fetchpriority']='high';return$params;
},25,4);

add_filter('woocommerce_single_product_image_thumbnail_html',function($html,$id){
 $map=json_decode((string)@file_get_contents(get_theme_file_path('/assets/media/index.json')),true);$images=$map[(string)$id]??array();if(!$images)return$html;$small=$images[0];$processor=new WP_HTML_Tag_Processor($html);if($processor->next_tag(array('class_name'=>'woocommerce-product-gallery__image'))){$url=get_theme_file_uri('/assets/media/'.$small['file']);$processor->set_attribute('data-thumb',$url);$processor->set_attribute('data-thumb-srcset',$url.' '.$small['width'].'w');$processor->set_attribute('data-thumb-sizes','100px');}return$processor->get_updated_html();
},25,2);

function sp_product_gallery(){
 global$product;$ids=array_values(array_unique(array_filter(array_merge(array($product->get_image_id()),$product->get_gallery_image_ids()))));if(!$ids){echo wc_placeholder_img();return;}
 echo'<div class="woocommerce-product-gallery woocommerce-product-gallery--with-images woocommerce-product-gallery--columns-4 images '.(has_term('apparel','product_cat',$product->get_id())?'sp-gallery--apparel':'sp-gallery--objects').'" data-columns="4"><figure class="woocommerce-product-gallery__wrapper">';foreach($ids as$i=>$id)echo apply_filters('woocommerce_single_product_image_thumbnail_html',wc_get_gallery_image_html($id,0===$i),$id);echo'</figure>';
 if(count($ids)>1){echo'<ol class="sp-gallery-thumbs" aria-label="Product photos">';foreach($ids as$i=>$id)echo'<li><button type="button" data-sp-gallery-slide="'.absint($i).'" aria-label="'.esc_attr('View '.$product->get_name().' photo '.($i+1)).'" aria-pressed="'.(0===$i?'true':'false').'">'.sp_media($id,$product->get_name().' — photo '.($i+1),'100px').'</button></li>';echo'</ol>';}
 echo'</div>';
}
add_filter('woocommerce_single_product_carousel_options',function($options){$options['controlNav']=false;$options['animationSpeed']=0;return$options;});

add_filter('woocommerce_available_variation',function($data){
 static$map;if(null===$map)$map=json_decode((string)@file_get_contents(get_theme_file_path('/assets/media/index.json')),true)?:array();$images=$map[(string)($data['image_id']??0)]??array();if(!$images||empty($data['image']))return$data;$large=end($images);$small=$images[0];$url=get_theme_file_uri('/assets/media/'.$large['file']);$thumb=get_theme_file_uri('/assets/media/'.$small['file']);
 $data['image']['src']=$url;$data['image']['srcset']=implode(', ',array_map(function($image){return get_theme_file_uri('/assets/media/'.$image['file']).' '.$image['width'].'w';},$images));$data['image']['sizes']='(max-width:767px) calc(100vw - 32px), 50vw';$data['image']['src_w']=$large['width'];$data['image']['src_h']=$large['height'];$data['image']['full_src']=$url;$data['image']['full_src_w']=$large['width'];$data['image']['full_src_h']=$large['height'];foreach(array('thumb','gallery_thumbnail')as$prefix){$data['image'][$prefix.'_src']=$thumb;$data['image'][$prefix.'_src_w']=$small['width'];$data['image'][$prefix.'_src_h']=$small['height'];}return$data;
},25);

add_filter('woocommerce_catalog_orderby',function($options){$options['popularity']='Bestselling';$options['date']='Newest';$options['price']='Price: low to high';$options['price-desc']='Price: high to low';return$options;});

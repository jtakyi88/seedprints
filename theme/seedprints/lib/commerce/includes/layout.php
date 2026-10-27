<?php
defined( 'ABSPATH' ) || exit;
add_filter( 'theme_mod_mobile_scalable', '__return_true' );

/** Correct headings and image labels at render time; stored page content stays intact. */
add_filter( 'elementor/widget/render_content', function ( $html, $widget ) {
	if ( is_product() && 'woocommerce-etheme_breadcrumb' === $widget->get_name() ) {
		$html = preg_replace( '/<h1(\s[^>]*)?>/i', '<span$1>', $html );
		$html = str_ireplace( '</h1>', '</span>', $html );
	}
	if ( is_front_page() ) {
		$html = preg_replace( '/<h1(\s[^>]*)?>/i', '<h2$1>', $html );
		$html = str_ireplace( '</h1>', '</h2>', $html );
		if ( 'a2506a3' === $widget->get_id() ) {
			$html = preg_replace( '/<h2(\s[^>]*)?>/i', '<h1$1>', $html, 1 );
			$html = preg_replace( '/<\/h2>/i', '</h1>', $html, 1 );
		}
	}
	$html = preg_replace( '/Copyright\s*(?:©|&copy;|&#169;)\s*20\d{2}/u', 'Copyright © ' . wp_date( 'Y' ), $html );
	$labels = spfix_image_labels();
	$processor = new WP_HTML_Tag_Processor( $html );
	while ( $processor->next_tag() ) {
		$tag = $processor->get_tag();
		$class = (string) $processor->get_attribute( 'class' );
		$id = (string) $processor->get_attribute( 'id' );
		if ( is_product() && 'A' === $tag && str_contains( $class, 'woocommerce-main-image' ) && ! $processor->get_attribute( 'aria-label' ) ) {
			$index = (int) $processor->get_attribute( 'data-index' ) + 1;
			$processor->set_attribute( 'aria-label', sprintf( 'View %s photo %d', get_the_title( get_queried_object_id() ), $index ) );
		}
		if ( 'UL' === $tag && str_contains( $class, 'thumbnails-list' ) ) {
			$processor->set_attribute( 'role', 'group' );
			$processor->set_attribute( 'aria-label', 'Product image thumbnails' );
		}
		if ( 'DIV' === $tag && str_starts_with( $id, 'tab-title-' ) && 'tab' === $processor->get_attribute( 'role' ) ) {
			$processor->set_attribute( 'role', 'presentation' );
			$processor->remove_attribute( 'aria-controls' );
			$processor->remove_attribute( 'aria-selected' );
		}
		if ( 'IMG' !== $tag ) { continue; }
		if ( trim( (string) $processor->get_attribute( 'alt' ) ) !== '' ) {
			continue;
		}
		if ( preg_match( '/\bwp-image-(\d+)\b/', (string) $processor->get_attribute( 'class' ), $matches ) && isset( $labels[ (int) $matches[1] ] ) ) {
			$processor->set_attribute( 'alt', $labels[ (int) $matches[1] ] );
		}
	}
	return $processor->get_updated_html();
}, 50, 2 );

function spfix_image_labels() {
	return array(
		11863 => 'Seed Prints — Bible Made Simple',
		12743 => 'Seed Prints featured on ABC, NBC and CBS',
		12160 => 'Woman wearing the Jesus Died Once, Alive Forever tee',
		13197 => 'White handled tumbler with a clear lid',
		13145 => 'The twelve tribes of Israel Bible placemat',
	);
}

add_filter( 'wp_get_attachment_image_attributes', function ( $attributes, $attachment ) {
	$labels = spfix_image_labels();
	if ( empty( $attributes['alt'] ) && isset( $labels[ $attachment->ID ] ) ) {
		$attributes['alt'] = $labels[ $attachment->ID ];
	}
	$photos = array(
		'JCmanmockup' => 'Man wearing the black Jesus Died Once, Alive Forever tee',
		'JCcouplemockup' => 'Couple wearing Jesus Died Once, Alive Forever tees',
	);
	if ( empty( $attributes['alt'] ) || $attributes['alt'] === $attachment->post_title ) {
		foreach ( $photos as $filename => $description ) {
			if ( stripos( (string) ( $attributes['src'] ?? '' ), $filename ) !== false ) { $attributes['alt'] = $description; }
		}
	}
	return $attributes;
}, 20, 2 );

add_action( 'woocommerce_before_shop_loop', function () {
	if ( is_shop() || is_product_taxonomy() ) {
		echo '<h1 class="spfix-shop-title">' . esc_html( woocommerce_page_title( false ) ) . '</h1>';
	}
}, 5 );
add_filter( 'woocommerce_show_page_title', '__return_false' );

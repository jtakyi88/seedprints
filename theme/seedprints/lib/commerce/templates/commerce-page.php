<?php
defined( 'ABSPATH' ) || exit;
get_header( 'shop' );
?>
<main id="spfix-commerce-main" class="spfix-commerce-page container">
	<h1><?php echo esc_html( is_cart() ? 'Your bag' : ( is_order_received_page() ? 'Thank you' : 'Checkout' ) ); ?></h1>
	<?php echo do_shortcode( is_cart() ? '[woocommerce_cart]' : '[woocommerce_checkout]' ); ?>
</main>
<?php get_footer( 'shop' ); ?>

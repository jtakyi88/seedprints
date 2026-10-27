<?php
defined( 'ABSPATH' ) || exit;

function sp_ensure_cart() { if ( function_exists( 'WC' ) && ! WC()->cart && did_action( 'wp_loaded' ) ) { wc_load_cart(); } }
add_action( 'template_redirect', function () {
	if ( is_page( 'custom' ) ) {
		sp_ensure_cart();
		if ( WC()->session ) { WC()->session->set_customer_session_cookie( true ); }
		if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
		nocache_headers();
	}
} );
add_filter( 'nonce_user_logged_out', function ( $uid, $action ) {
	if ( 'sp_custom' === $action && function_exists( 'WC' ) && WC()->session ) { return abs( crc32( WC()->session->get_customer_id() ) ); }
	return $uid;
}, 10, 2 );

function sp_custom_products() { return array_values( array_filter( wc_get_products( array( 'status' => 'publish', 'category' => array( 'custom-prints-and-more' ), 'limit' => 24, 'type' => array( 'simple', 'variable' ) ) ), function ( $p ) { return sp_custom_product_allowed( $p->get_id() ); } ) ); }

function sp_custom_form() {
	sp_ensure_cart();
	$products = sp_custom_products();
	if ( ! $products ) { echo '<p>Tell us about the piece you have in mind at <a href="mailto:info@seedprints.com">info@seedprints.com</a>.</p>'; return; }
	$selected = isset( $_GET['product'] ) && is_scalar( $_GET['product'] ) ? absint( $_GET['product'] ) : $products[0]->get_id();
	if ( ! in_array( $selected, array_map( function ( $p ) { return $p->get_id(); }, $products ), true ) ) { $selected = $products[0]->get_id(); }
	wc_print_notices();
	$success = WC()->session ? WC()->session->get( 'sp_custom_success' ) : '';
	if ( $success ) { echo '<div class="woocommerce-message" role="status">' . esc_html( $success ) . '</div>'; WC()->session->__unset( 'sp_custom_success' ); }
	$data = array();
	foreach ( $products as $p ) {
		$variations = array();
		if ( $p->is_type( 'variable' ) ) {
			foreach ( $p->get_available_variations() as $v ) { $variations[] = array( 'id' => $v['variation_id'], 'attributes' => $v['attributes'], 'available' => $v['is_in_stock'] && $v['is_purchasable'] && $v['variation_is_active'], 'priceHtml' => wp_kses_post( sp_custom_price_html( $p, wc_get_product( $v['variation_id'] )->get_price() ) ), 'price' => $v['display_price'] ); }
		}
		$attributes = array();
		if ( $p->is_type( 'variable' ) ) { foreach ( $p->get_variation_attributes() as $name => $options ) { $values = array(); foreach ( $options as $value ) { $term = taxonomy_exists( $name ) ? get_term_by( 'slug', $value, $name ) : null; $values[] = array( 'value' => $value, 'label' => $term && ! is_wp_error( $term ) ? $term->name : $value ); } $attributes[] = array( 'name' => 'attribute_' . sanitize_title( $name ), 'label' => wc_attribute_label( $name ), 'options' => $values ); } }
		$data[] = array( 'id' => $p->get_id(), 'name' => $p->get_name(), 'type' => $p->get_type(), 'previewType' => str_contains( strtolower( $p->get_name() ), 'shirt' ) ? 'shirt' : ( str_contains( strtolower( $p->get_name() ), 'speaker' ) ? 'speaker' : 'tumbler' ), 'available' => $p->is_in_stock() && $p->is_purchasable(), 'priceHtml' => wp_kses_post( sp_custom_price_html( $p ) ), 'customizationFee' => is_wp_error( sp_custom_fee( $p->get_id() ) ) ? null : sp_custom_fee( $p->get_id() ), 'attributes' => $attributes, 'variations' => $variations );
	}
	$key = wp_generate_uuid4();
	$keys = WC()->session ? (array) WC()->session->get( 'sp_custom_keys', array() ) : array();
	$keys[] = $key;
	if ( WC()->session ) { WC()->session->set( 'sp_custom_keys', array_slice( $keys, -5 ) ); }
	?>
	<div class="sp-builder"><form id="sp-custom-form" class="sp-custom-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="sp_custom"><input type="hidden" name="sp_custom_key" value="<?php echo esc_attr( $key ); ?>"><?php wp_nonce_field( 'sp_custom', 'sp_custom_nonce' ); ?>
		<p class="sp-builder-progress" aria-live="polite">Step <span data-sp-step-number>1</span> of 4</p>
		<fieldset data-sp-step="0"><legend>Choose your piece</legend><label for="sp-custom-product">Product</label><select id="sp-custom-product" name="product_id" required><?php foreach ( $products as $p ) : ?><option value="<?php echo absint( $p->get_id() ); ?>" <?php selected( $selected, $p->get_id() ); ?>><?php echo esc_html( $p->get_name() ); ?></option><?php endforeach; ?></select><div id="sp-custom-attributes"></div><input type="hidden" name="variation_id" id="sp-custom-variation"><p class="sp-custom-price" id="sp-custom-price"></p></fieldset>
		<fieldset data-sp-step="1"><legend>Make it meaningful</legend><label for="sp-custom-reference">Verse reference (optional)</label><div class="sp-verse-lookup"><input id="sp-custom-reference" name="verse_reference" type="text" placeholder="John 3:16" maxlength="80"><button class="sp-button sp-button--secondary" type="button" data-sp-lookup>Look up verse</button></div><p class="sp-field-note">Verse lookup uses the King James Version. You can edit the words below.</p><p id="sp-lookup-feedback" role="status" aria-live="polite"></p><label for="sp-custom-text">Your verse or words</label><textarea id="sp-custom-text" name="design_text" rows="5" maxlength="1200" required></textarea><p class="sp-field-note"><span data-sp-character-count>0</span> / 1,200 characters</p></fieldset>
		<fieldset data-sp-step="2"><legend>Choose the details</legend><label for="sp-custom-font">Font</label><select id="sp-custom-font" name="font"><option value="Fraunces">Fraunces — a thoughtful serif</option><option value="Inter">Inter — clean and simple</option></select><label for="sp-custom-color">Print color</label><input id="sp-custom-color" type="color" name="color" value="#1f1b16"><label for="sp-custom-artwork">Add an image (optional)</label><input id="sp-custom-artwork" type="file" name="artwork" accept="image/jpeg,image/png,image/webp"><p class="sp-field-note">JPG, PNG or WebP, up to 5 MB.</p><p data-sp-artwork-feedback role="status"></p></fieldset>
		<fieldset data-sp-step="3"><legend>Review your design</legend><div class="sp-two-fields"><label for="sp-custom-quantity">Quantity<input id="sp-custom-quantity" type="number" name="quantity" value="1" min="1" max="999" step="1" required></label><label for="sp-custom-needed">Requested needed-by date<input id="sp-custom-needed" type="date" name="needed_by" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" required></label></div><p class="sp-field-note">Shown prices include the existing customization charge for your piece.</p><p class="sp-field-note">Have a firm deadline? Send a request so we can confirm availability.</p><label for="sp-custom-email">Email for your request</label><input id="sp-custom-email" name="email" type="email" autocomplete="email"><label class="sp-check-label"><input type="checkbox" name="design_final" value="1" required><span>I understand that designs are final after payment.</span></label><div class="sp-actions"><button class="sp-button" type="submit" name="submit_type" value="cart">Add design to bag</button><button class="sp-button sp-button--secondary" type="submit" name="submit_type" value="request">Send a request</button></div></fieldset>
		<p id="sp-custom-feedback" role="status" aria-live="polite"></p><div class="sp-builder-navigation"><button class="sp-text-button" type="button" data-sp-back>Back</button><button class="sp-button" type="button" data-sp-next>Continue</button></div>
	</form><aside class="sp-custom-preview" aria-label="Live design preview"><p class="sp-eyebrow">Your design</p><div class="sp-preview-product"><div class="sp-preview-lid"></div><div class="sp-preview-cup"><div class="sp-preview-print"><img data-sp-preview-image alt="Your uploaded artwork" hidden><p data-sp-preview-text>Your words go here</p></div></div></div><p class="sp-preview-caption" data-sp-preview-name></p><p class="sp-preview-price" data-sp-preview-price></p></aside></div>
	<script id="sp-custom-products" type="application/json"><?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
	<?php
}

function sp_validate_custom_data( $input ) {
	foreach ( array( 'product_id', 'variation_id', 'design_text', 'verse_reference', 'font', 'color', 'quantity', 'needed_by', 'design_final', 'email', 'submit_type' ) as $field ) { if ( isset( $input[ $field ] ) && ! is_scalar( $input[ $field ] ) ) { return new WP_Error( 'custom_field', 'Check your design details and try again.' ); } }
	$id = absint( $input['product_id'] ?? 0 );
	if ( ! sp_custom_product_allowed( $id ) ) { return new WP_Error( 'custom_product', 'Choose a customizable product.' ); }
	$fee = sp_custom_fee( $id ); if ( is_wp_error( $fee ) && 'cart' === ( $input['submit_type'] ?? '' ) ) { return $fee; }
	$p = wc_get_product( $id ); $variation_id = absint( $input['variation_id'] ?? 0 ); $variation = array();
	if ( ! $p->is_type( 'simple' ) && ! $p->is_type( 'variable' ) ) { return new WP_Error( 'custom_product', 'Choose a customizable product.' ); }
	if ( $p->is_type( 'variable' ) ) {
		$v = wc_get_product( $variation_id );
		if ( ! $v || ! $v->is_type( 'variation' ) || $v->get_parent_id() !== $id || ! $v->variation_is_active() || ! $v->is_purchasable() || ! $v->is_in_stock() ) { return new WP_Error( 'custom_variation', 'Choose an available size or option for your product.' ); }
		$fixed = $v->get_variation_attributes();
		foreach ( $p->get_variation_attributes() as $name => $options ) {
			$field = 'attribute_' . sanitize_title( $name );
			$value = isset( $input[ $field ] ) && is_string( $input[ $field ] ) ? wc_clean( wp_unslash( $input[ $field ] ) ) : '';
			if ( ! in_array( $value, $options, true ) || ( ! empty( $fixed[ $field ] ) && $fixed[ $field ] !== $value ) ) { return new WP_Error( 'custom_variation', 'Choose matching, available options for your product.' ); }
			$variation[ $field ] = $value;
		}
		$buy = $v;
	} else { if ( $variation_id ) { return new WP_Error( 'custom_variation', 'Choose the product again to reset its options.' ); } $buy = $p; }
	if ( ! $buy->is_purchasable() || ! $buy->is_in_stock() ) { return new WP_Error( 'custom_stock', 'This piece is not currently available.' ); }
	$text = sanitize_textarea_field( wp_unslash( (string) ( $input['design_text'] ?? '' ) ) );
	$reference = sanitize_text_field( wp_unslash( (string) ( $input['verse_reference'] ?? '' ) ) );
	if ( ! trim( $text ) || mb_strlen( $text ) > 1200 || mb_strlen( $reference ) > 80 ) { return new WP_Error( 'custom_text', 'Add your words, using up to 1,200 characters.' ); }
	$font = (string) ( $input['font'] ?? '' );
	if ( ! in_array( $font, array( 'Fraunces', 'Inter' ), true ) ) { return new WP_Error( 'custom_font', 'Choose one of the available fonts.' ); }
	$color = strtolower( (string) ( $input['color'] ?? '' ) );
	if ( ! preg_match( '/^#[a-f0-9]{6}$/', $color ) ) { return new WP_Error( 'custom_color', 'Choose a valid print color.' ); }
	$quantity_raw = (string) ( $input['quantity'] ?? '' );
	if ( ! preg_match( '/^[1-9]\d{0,2}$/', $quantity_raw ) ) { return new WP_Error( 'custom_quantity', 'Choose a quantity from 1 to 999.' ); }
	$quantity = (int) $quantity_raw;
	if ( ! $buy->has_enough_stock( $quantity ) || ( $buy->is_sold_individually() && $quantity > 1 ) ) { return new WP_Error( 'custom_quantity', 'That quantity is not available. Choose a smaller quantity.' ); }
	$date = (string) ( $input['needed_by'] ?? '' );
	$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
	if ( ! $parsed || $parsed->format( 'Y-m-d' ) !== $date || $date < wp_date( 'Y-m-d' ) ) { return new WP_Error( 'custom_date', 'Choose today or a future requested date.' ); }
	if ( '1' !== (string) ( $input['design_final'] ?? '' ) ) { return new WP_Error( 'custom_final', 'Confirm that designs are final after payment.' ); }
	$type = (string) ( $input['submit_type'] ?? '' );
	if ( ! in_array( $type, array( 'cart', 'request' ), true ) ) { return new WP_Error( 'custom_submit', 'Choose Add design to bag or Send a request.' ); }
	$email = sanitize_email( wp_unslash( (string) ( $input['email'] ?? '' ) ) );
	if ( 'request' === $type && ! is_email( $email ) ) { return new WP_Error( 'custom_email', 'Add a valid email so we can respond to your request.' ); }
	return array( 'product_id' => $id, 'variation_id' => $variation_id, 'variation' => $variation, 'text' => $text, 'reference' => $reference, 'font' => $font, 'color' => $color, 'quantity' => $quantity, 'needed_by' => $date, 'email' => $email, 'submit_type' => $type, 'final' => true, 'customization_fee' => is_wp_error( $fee ) ? null : $fee );
}

function sp_private_upload_dir() {
	$dir = dirname( untrailingslashit( ABSPATH ) ) . '/seedprints-private-uploads';
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) { return new WP_Error( 'upload_storage', 'The image could not be saved. Please try again.' ); }
	@chmod( $dir, 0700 );
	return $dir;
}

function sp_save_custom_upload( $file ) {
	if ( ! $file || UPLOAD_ERR_NO_FILE === ( $file['error'] ?? null ) ) { return array(); }
	if ( ! isset( $file['error'], $file['tmp_name'], $file['name'], $file['size'] ) || ! is_int( $file['error'] ) || ! is_string( $file['tmp_name'] ) || ! is_string( $file['name'] ) || UPLOAD_ERR_OK !== $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) { return new WP_Error( 'upload_failed', 'The image could not be uploaded. Choose it again.' ); }
	if ( $file['size'] > 5 * MB_IN_BYTES ) { return new WP_Error( 'upload_size', 'Choose an image smaller than 5 MB.' ); }
	$allowed = array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );
	$check = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ), $allowed );
	$size = @getimagesize( $file['tmp_name'] );
	if ( empty( $check['ext'] ) || ! in_array( $check['type'], array_values( $allowed ), true ) || ! $size || $size['mime'] !== $check['type'] || $size[0] * $size[1] > 20000000 ) { return new WP_Error( 'upload_type', 'Choose a JPG, PNG or WebP image, up to 20 megapixels.' ); }
	$dir = sp_private_upload_dir(); if ( is_wp_error( $dir ) ) { return $dir; }
	$name = bin2hex( random_bytes( 20 ) ) . '.' . $check['ext'];
	if ( ! move_uploaded_file( $file['tmp_name'], $dir . '/' . $name ) ) { return new WP_Error( 'upload_save', 'The image could not be saved. Please try again.' ); }
	@chmod( $dir . '/' . $name, 0600 );
	return array( 'file' => $name, 'mime' => $check['type'] );
}

function sp_custom_fail( $message ) { wc_add_notice( $message, 'error' ); wp_safe_redirect( home_url( '/custom/' ) ); exit; }

function sp_handle_custom() {
	sp_ensure_cart();
	$nonce = isset( $_POST['sp_custom_nonce'] ) && is_string( $_POST['sp_custom_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_custom_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'sp_custom' ) ) { sp_custom_fail( 'This design form expired. Please refresh the page and try again.' ); }
	$key = isset( $_POST['sp_custom_key'] ) && is_string( $_POST['sp_custom_key'] ) ? sanitize_text_field( wp_unslash( $_POST['sp_custom_key'] ) ) : '';
	if ( ! in_array( $key, (array) WC()->session->get( 'sp_custom_keys', array() ), true ) ) { sp_custom_fail( 'Refresh this page to start a new design.' ); }
	$validated = sp_validate_custom_data( $_POST );
	if ( is_wp_error( $validated ) ) { sp_custom_fail( $validated->get_error_message() ); }
	$rate_key = 'sp_custom_rate_' . substr( hash_hmac( 'sha256', ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_date( 'Y-m-d-H' ), wp_salt( 'nonce' ) ), 0, 32 );
	$requests = (int) get_transient( $rate_key );
	if ( $requests >= 12 ) { sp_custom_fail( 'Please wait a little before sending another design, or text us for help.' ); }
	$lock = 'sp_custom_once_' . hash( 'sha256', WC()->session->get_customer_id() . $key );
	if ( ! add_option( $lock, time(), '', false ) ) { sp_custom_fail( 'This design has already been submitted. Check your bag or start a new design.' ); }
	wp_schedule_single_event( time() + DAY_IN_SECONDS, 'sp_custom_clear_lock', array( $lock ) );
	$upload = sp_save_custom_upload( $_FILES['artwork'] ?? null );
	if ( is_wp_error( $upload ) ) { delete_option( $lock ); sp_custom_fail( $upload->get_error_message() ); }
	$validated['artwork'] = $upload;
	$validated['design_id'] = $key;
	set_transient( $rate_key, $requests + 1, HOUR_IN_SECONDS );
	if ( 'cart' === $validated['submit_type'] ) {
		$GLOBALS['sp_custom_authorized_cart'] = true;
		$added = WC()->cart->add_to_cart( $validated['product_id'], $validated['quantity'], $validated['variation_id'], $validated['variation'], array( 'sp_custom' => $validated ) );
		unset( $GLOBALS['sp_custom_authorized_cart'] );
		if ( ! $added ) { if ( $upload ) { @unlink( sp_private_upload_dir() . '/' . $upload['file'] ); } delete_option( $lock ); sp_custom_fail( 'This design could not be added. Check the product options and try again.' ); }
		WC()->session->set( 'sp_custom_analytics', array( 'type' => 'cart', 'product_id' => $validated['product_id'], 'quantity' => $validated['quantity'], 'unit_price' => (float) wc_get_price_to_display( wc_get_product( $validated['variation_id'] ?: $validated['product_id'] ), array( 'price' => (float) wc_get_product( $validated['variation_id'] ?: $validated['product_id'] )->get_price() + (float) $validated['customization_fee'] ) ) ) );
		wp_safe_redirect( wc_get_cart_url() ); exit;
	}
	$request_id = wp_insert_post( array( 'post_type' => 'sp_custom_request', 'post_status' => 'private', 'post_title' => 'Custom request — ' . wc_get_product( $validated['product_id'] )->get_name() . ' — ' . wp_date( 'Y-m-d' ), 'post_content' => wp_slash( 'Email: ' . $validated['email'] . "\n\nProduct ID: " . $validated['product_id'] . "\nQuantity: " . $validated['quantity'] . "\nRequested date: " . $validated['needed_by'] . "\nFont: " . $validated['font'] . "\nColor: " . $validated['color'] . "\nReference: " . $validated['reference'] . "\n\n" . $validated['text'] ) ), true );
	if ( is_wp_error( $request_id ) ) { if ( $upload ) { @unlink( sp_private_upload_dir() . '/' . $upload['file'] ); } delete_option( $lock ); sp_custom_fail( 'Your request could not be saved. Please try again or text us.' ); }
	update_post_meta( $request_id, '_sp_custom', $validated );
	wp_mail( get_option( 'admin_email' ), 'New Seed Prints custom request', 'A custom-design request is ready to review: ' . admin_url( 'post.php?post=' . $request_id . '&action=edit' ) );
	WC()->session->set( 'sp_custom_success', 'Your request is saved. We’ll follow up by email to confirm the details and timing.' );
	WC()->session->set( 'sp_custom_analytics', array( 'type' => 'request', 'product_id' => $validated['product_id'], 'quantity' => $validated['quantity'] ) );
	wp_safe_redirect( home_url( '/custom/' ) ); exit;
}
add_action( 'admin_post_sp_custom', 'sp_handle_custom' );
add_action( 'admin_post_nopriv_sp_custom', 'sp_handle_custom' );

add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $id ) {
	if ( sp_custom_product_allowed( $id ) && empty( $GLOBALS['sp_custom_authorized_cart'] ) ) { wc_add_notice( 'Start your design to personalize this piece before adding it to your bag.', 'error' ); return false; }
	return $passed;
}, 10, 2 );
add_action( 'woocommerce_store_api_validate_add_to_cart', function ( $product ) {
	if ( $product instanceof WC_Product && sp_custom_product_allowed( $product->get_id() ) ) {
		throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'sp_design_required', 'Start your design to personalize this piece.', 400 );
	}
} );
add_action( 'sp_custom_clear_lock', function ( $lock ) { if ( preg_match( '/^sp_custom_once_[a-f0-9]{64}$/', $lock ) ) { delete_option( $lock ); } } );

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) { return; }
	$result = WC()->session->get( 'sp_custom_analytics' );
	if ( $result ) { echo '<script id="sp-custom-result" type="application/json">' . wp_json_encode( $result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>'; WC()->session->__unset( 'sp_custom_analytics' ); }
}, 12 );

add_action( 'add_meta_boxes_sp_custom_request', function () {
	add_meta_box( 'sp-request-artwork', 'Customer artwork', function ( $post ) {
		$c = get_post_meta( $post->ID, '_sp_custom', true );
		if ( ! empty( $c['artwork']['file'] ) ) { echo '<a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sp_request_artwork&request=' . $post->ID ), 'sp_request_artwork_' . $post->ID ) ) . '">Download customer artwork</a>'; }
		else { echo '<p>No artwork was attached.</p>'; }
	}, 'sp_custom_request', 'side' );
} );
add_action( 'admin_post_sp_request_artwork', function () {
	$id = isset( $_GET['request'] ) && is_scalar( $_GET['request'] ) ? absint( $_GET['request'] ) : 0;
	if ( ! current_user_can( 'manage_woocommerce' ) || 'sp_custom_request' !== get_post_type( $id ) ) { wp_die( 'Not permitted.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sp_request_artwork_' . $id );
	$c = get_post_meta( $id, '_sp_custom', true ); $file = $c['artwork']['file'] ?? '';
	if ( ! preg_match( '/^[a-f0-9]{40}\.(?:jpg|jpeg|png|webp)$/', $file ) ) { wp_die( 'Artwork not found.', '', array( 'response' => 404 ) ); }
	$base = sp_private_upload_dir(); $path = is_wp_error( $base ) ? '' : $base . '/' . $file;
	if ( ! is_file( $path ) ) { wp_die( 'Artwork not found.', '', array( 'response' => 404 ) ); }
	nocache_headers(); header( 'X-Content-Type-Options: nosniff' ); header( 'Content-Type: application/octet-stream' ); header( 'Content-Disposition: attachment; filename="seedprints-request-' . $id . '.' . pathinfo( $file, PATHINFO_EXTENSION ) . '"' ); header( 'Content-Length: ' . filesize( $path ) ); readfile( $path ); exit;
} );

add_filter( 'woocommerce_get_item_data', function ( $items, $cart_item ) {
	$c = $cart_item['sp_custom'] ?? null;
	if ( ! $c ) { return $items; }
	foreach ( array( 'Your words' => $c['text'], 'Reference' => $c['reference'], 'Font' => $c['font'], 'Print color' => $c['color'], 'Requested date' => $c['needed_by'], 'Artwork' => ! empty( $c['artwork'] ) ? 'Attached' : '' ) as $label => $value ) { if ( $value !== '' ) { $items[] = array( 'key' => $label, 'value' => esc_html( $value ) ); } }
	return $items;
}, 10, 2 );
add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
	if ( empty( $values['sp_custom'] ) ) { return; }
	$c = $values['sp_custom']; $item->add_meta_data( '_sp_custom', $c, true );
	foreach ( array( 'Your words' => $c['text'], 'Verse reference' => $c['reference'], 'Font' => $c['font'], 'Print color' => $c['color'], 'Requested date' => $c['needed_by'], 'Designs final after payment' => 'Acknowledged' ) as $label => $value ) { if ( $value !== '' ) { $item->add_meta_data( $label, $value, true ); } }
}, 10, 3 );

add_action( 'woocommerce_after_order_itemmeta', function ( $item_id, $item ) {
	$c = $item->get_meta( '_sp_custom' );
	if ( ! empty( $c['artwork']['file'] ) && current_user_can( 'manage_woocommerce' ) ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=sp_custom_artwork&item=' . absint( $item_id ) ), 'sp_artwork_' . $item_id );
		echo '<p><a href="' . esc_url( $url ) . '">Download customer artwork</a></p>';
	}
}, 10, 2 );
add_action( 'admin_post_sp_custom_artwork', function () {
	$id = isset( $_GET['item'] ) && is_scalar( $_GET['item'] ) ? absint( $_GET['item'] ) : 0;
	if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Not permitted.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sp_artwork_' . $id );
	$item = WC_Order_Factory::get_order_item( $id ); $c = $item ? $item->get_meta( '_sp_custom' ) : array();
	$file = $c['artwork']['file'] ?? '';
	if ( ! preg_match( '/^[a-f0-9]{40}\.(?:jpg|jpeg|png|webp)$/', $file ) ) { wp_die( 'Artwork not found.', '', array( 'response' => 404 ) ); }
	$base = sp_private_upload_dir(); $path = is_wp_error( $base ) ? '' : $base . '/' . $file;
	if ( ! is_file( $path ) ) { wp_die( 'Artwork not found.', '', array( 'response' => 404 ) ); }
	nocache_headers(); header( 'X-Content-Type-Options: nosniff' ); header( 'Content-Type: application/octet-stream' ); header( 'Content-Disposition: attachment; filename="seedprints-artwork-' . $id . '.' . pathinfo( $file, PATHINFO_EXTENSION ) . '"' ); header( 'Content-Length: ' . filesize( $path ) ); readfile( $path ); exit;
} );

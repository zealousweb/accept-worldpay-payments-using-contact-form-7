<?php

$post_id = ( isset( $_REQUEST[ 'post' ] ) ? sanitize_text_field( $_REQUEST[ 'post' ] ) : '' );

if ( empty( $post_id ) ) {
	$wpcf7 = WPCF7_ContactForm::get_current();
	$post_id = $wpcf7->id();
}

if ( !function_exists( 'cf7wpa_inlineScript_select2' ) ) {
	function cf7wpa_inlineScript_select2() {
		ob_start();
		?>
		( function($) {
			jQuery('#cf7wpa_currency, #cf7wpa_success_returnurl, #cf7wpa_cancel_returnurl' ).select2();
		} )( jQuery );
		<?php
		return ob_get_clean();
	}
}

 wp_enqueue_style( 'wp-pointer' );
wp_enqueue_script( 'wp-pointer' );

 wp_enqueue_style( 'select2' );
wp_enqueue_script( 'select2' );
wp_add_inline_script( 'select2', cf7wpa_inlineScript_select2() );

wp_enqueue_style( CF7WPA_PREFIX . '_admin_css' );

$use_worldpay	        = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'use_worldpay', true );
$mode_sandbox            = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'mode_sandbox', true );
$debug_worldpay         = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'debug', true );
$installation_id        = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'installation_id', true );

$amount                  = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'amount', true );
$quantity                = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'quantity', true );
$email                   = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'email', true );
$description             = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'description', true );

$success_returnURL       = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'success_returnurl', true );
$cancel_returnURL        = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'cancel_returnurl', true );
$message                 = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'message', true );

$currency                = get_post_meta( $post_id, CF7WPA_META_PREFIX . 'currency', true );




$currency_code = array(
	'AUD' => 'Australian Dollar',
	'BRL' => 'Brazilian Real',
	'CAD' => 'Canadian Dollar',
	'CZK' => 'Czech Koruna',
	'DKK' => 'Danish Krone',
	'EUR' => 'Euro',
	'HKD' => 'Hong Kong Dollar',
	'HUF' => 'Hungarian Forint',
	'ILS' => 'Israeli New Sheqel',
	'JPY' => 'Japanese Yen',
	'MYR' => 'Malaysian Ringgit',
	'MXN' => 'Mexican Peso',
	'NOK' => 'Norwegian Krone',
	'NZD' => 'New Zealand Dollar',
	'PHP' => 'Philippine Peso',
	'PLN' => 'Polish Zloty',
	'GBP' => 'Pound Sterling',
	'RUB' => 'Russian Ruble',
	'SGD' => 'Singapore Dollar',
	'SEK' => 'Swedish Krona',
	'CHF' => 'Swiss Franc',
	'TWD' => 'Taiwan New Dollar',
	'THB' => 'Thai Baht',
	'TRY' => 'Turkish Lira',
	'USD' => 'U.S. Dollar'
);

$selected = '';


$args = array(
	'post_type'      => array( 'page' ),
	'orderby'        => 'title',
	'posts_per_page' => -1
);
$pages = get_posts( $args );
$all_pages = array();
if ( !empty( $pages ) ) {
	foreach ( $pages as $page ) {
		$all_pages[$page->ID] = $page->post_title;
	}
}

if ( !empty( $post_id ) ) {
	$cf7 = WPCF7_ContactForm::get_instance( sanitize_text_field( $_REQUEST['post'] ) );
	$tags = $cf7->collect_mail_tags();
}

echo '<div class="cf7wpa-settings">' .
	'<div class="left-box postbox">' .
		'<table class="form-table">' .
			'<tbody>';
				if( empty( $tags ) ) {

					echo '<tr class="form-field">' .
						'<td>' .
							__( 'To use 2Checkout option, first you need to create and save form tags.', 'accept-2checkout-payments-using-contact-form-7' ).
							' <a href="'.CF72CH_DOCUMENT.'" target="_blank">' . __( 'Document Link', 'accept-2checkout-payments-using-contact-form-7' ) . '</a>'.
						'</td>' .
					'</tr>';

				} else {

				echo '<tr class="form-field">' .
					'<th scope="row">' .
						'<label for="' . CF7WPA_META_PREFIX . 'use_worldpay">' .
							__( 'Enable Worldpay', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-enable-wp"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . CF7WPA_META_PREFIX . 'use_worldpay" name="' . CF7WPA_META_PREFIX . 'use_worldpay" type="checkbox" class="enable_required" value="1" ' . checked( $use_worldpay, 1, false ) . '/>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . CF7WPA_META_PREFIX . 'mode_sandbox">' .
							__( 'Enable WorldPay Sandbox', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-enable-test-api-mode"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . CF7WPA_META_PREFIX . 'mode_sandbox" name="' . CF7WPA_META_PREFIX . 'mode_sandbox" type="checkbox" value="1" ' . checked( $mode_sandbox, 1, false ) . ' />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th scope="row">' .
						'<label for="' . CF7WPA_META_PREFIX . 'debug">' .
							__( 'Enable Debug Mode', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-enable-debug"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . CF7WPA_META_PREFIX . 'debug" name="' . CF7WPA_META_PREFIX . 'debug" type="checkbox" value="1" ' . checked( $debug_worldpay, 1, false ) . '/>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . CF7WPA_META_PREFIX . 'installation_id">' .
							__( 'WorldPay Installation ID (required)', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-sandbox-login-id"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . CF7WPA_META_PREFIX . 'installation_id" name="' . CF7WPA_META_PREFIX . 'installation_id" type="text" class="large-text" value="' . esc_attr( $installation_id ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . CF7WPA_META_PREFIX . 'amount">' .
							__( 'Amount Field Name (required)', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-amount-field"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . CF7WPA_META_PREFIX . 'amount" name="' . CF7WPA_META_PREFIX . 'amount" type="text" value="' . esc_attr( $amount ) . '" ' . ( !empty( $use_worldpay ) ? 'required' : '' ) . ' />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . CF7WPA_META_PREFIX . 'quantity">' .
							__( 'Quantity Field Name (Optional)', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-quantity-field"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . CF7WPA_META_PREFIX . 'quantity" name="' . CF7WPA_META_PREFIX . 'quantity" type="text" value="' . esc_attr( $quantity ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . CF7WPA_META_PREFIX . 'email">' .
							__( 'Customer Email Field Name (Optional)', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-customer-email-field"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . CF7WPA_META_PREFIX . 'email" name="' . CF7WPA_META_PREFIX . 'email" type="text" value="' . esc_attr( $email ) . '" />' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . CF7WPA_META_PREFIX . 'description">' .
							__( 'Description Field Name (Optional)', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-desc-field"></span>' .
					'</th>' .
					'<td>' .
						'<input id="' . CF7WPA_META_PREFIX . 'description" name="' . CF7WPA_META_PREFIX . 'description" type="text" value="' . esc_attr( $description ) . '" />' .
					'</td>' .
				'</tr>' .
	 			'<tr class="form-field">' .
					'<th>' .
						'<label for="' . CF7WPA_META_PREFIX . 'currency">' .
							__( 'Select Currency', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-select-currency"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . CF7WPA_META_PREFIX . 'currency" name="' . CF7WPA_META_PREFIX . 'currency">';

							if ( !empty( $currency_code ) ) {
								foreach ( $currency_code as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $currency, $key, false ) . '>' . esc_attr( $value ) . '</option>';
								}
							}

						echo '</select>' .
					'</td>' .
				'</tr/>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . CF7WPA_META_PREFIX . 'success_returnurl">' .
							__( 'Success Return URL (Optional)', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-success-returnurl"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . CF7WPA_META_PREFIX . 'success_returnurl" name="' . CF7WPA_META_PREFIX . 'success_returnurl">' .
							'<option>' . __( 'Select page', 'contact-form-7-worldpay-addon' ) . '</option>';

							if( !empty( $all_pages ) ) {
								foreach ( $all_pages as $post_id => $title ) {
									echo '<option value="' . esc_attr( $post_id ) . '" ' . selected( $success_returnURL, $post_id, false )  . '>' . $title . '</option>';
								}
							}

						echo '</select>' .
					'</td>' .
				'</tr>' .
				'<tr class="form-field">' .
					'<th>' .
						'<label for="' . CF7WPA_META_PREFIX . 'cancel_returnurl">' .
							__( 'Cancel Return URL (Optional)', 'contact-form-7-worldpay-addon' ) .
						'</label>' .
						'<span class="cf7wpa-tooltip hide-if-no-js" id="cf7wpa-cancel-returnurl"></span>' .
					'</th>' .
					'<td>' .
						'<select id="' . CF7WPA_META_PREFIX . 'cancel_returnurl" name="' . CF7WPA_META_PREFIX . 'cancel_returnurl">' .
							'<option>' . __( 'Select page', 'contact-form-7-worldpay-addon' ) . '</option>';

							if( !empty( $all_pages ) ) {
								foreach ( $all_pages as $post_id => $title ) {
									echo '<option value="' . esc_attr( $post_id ) . '" ' . selected( $cancel_returnURL, $post_id, false )  . '>' . $title . '</option>';
								}
							}

						echo '</select>' .
					'</td>' .
				'</tr>';
				echo '<input type="hidden" name="post" value="' . esc_attr( $post_id ) . '">';
			}
			echo '</tbody>' .
		'</table>' .
	'</div>' .
	'<div class="right-box">';
		/**
		 * Add new post box to display the information.
		 */
		do_action( CF7WPA_PREFIX . '/postbox' );
		
	echo '</div>' .
'</div>';

add_action('admin_print_footer_scripts', function() {
	ob_start();
	?>
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
			//jQuery selector to point to
			jQuery( '#cf7wpa-enable-test-api-mode' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-enable-test-api-mode' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>TEST MODE</h3>' .
						'<p>Check the Worldpay testing guide <a href="http://support.worldpay.com/support/kb/bg/testandgolive/tgl5103.html" target="_blank">here</a>.This will display "This is not a live transaction." warning on payment page.</p>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7wpa-enable-wp' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-enable-wp' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Enable Wordpay Getway</h3>' .
						'<p>By Checking this checkbox WorldPay will enable into current contact Form</p>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7wpa-enable-debug' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-enable-debug' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Enable Debug mode</h3>' .
						'<p>By Checking this checkbox, you can see the Transaction response in an individual transaction detail page</p>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );
	
			jQuery( '#cf7wpa-quantity-field' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-quantity-field' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Add Quantity Field Name</h3>' .
						'<p>Add here the Field Name of Quantity field <br/>ex.[field-type <strong>field-name<strong> ]</p>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );
			
			jQuery( '#cf7wpa-amount-field' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-amount-field' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Add Amount Field Name</h3>' .
						'<p>Add here the Field Name of Amount field <br/>ex.[field-type <strong>field-name<strong> ]</p>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7wpa-customer-email-field' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-customer-email-field' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Add Customer Email Field Name</h3>' .
						'<p>Add here the Field Name of Email field <br/>ex.[email <strong>field-name<strong> ]</p>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7wpa-cancel-returnurl' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-cancel-returnurl' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Select Payment Cancel Page</h3>' .
						'<p> Select page where you want to redirect if a transaction cancel<br/>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7wpa-success-returnurl' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-success-returnurl' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Add Customer Email Field Name</h3>' .
						'<p>Select page where you want to redirect if a Worldpay Payment transaction successfully Or Failed, <br><br> If no selected then redirect to Thank Page which created with Shortcode <b>"[worldpay-transaction-details]"</b> when installed plugin.</p>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );
			
			jQuery( '#cf7wpa-desc-field' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-desc-field' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Add Description Field Name</h3>' .
						'<p>Add here the Field Name of Description field <br/>ex.[field-type <strong>field-name<strong> ]</p>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7wpa-sandbox-login-id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-sandbox-login-id' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Get Your Installation ID</h3>' .
						'<ul style="padding:10px"><li><strong>Step 1:</strong> Login from <a href="https://secure.worldpay.com/sso/public/auth/login.html?serviceIdentifier=merchantadmin" target="_blank">here</a> using your username and password </li><li><strong>Step 2:</strong> To know the Installation ID, find the screenshot <a href="https://prnt.sc/nqs21l" target="_blank">Here</a></li><li><strong>Step 3:</strong> Use the Installation ID in Plugin setting and try to make payment.</li></ul>', 'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7wpa-select-currency' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7wpa-select-currency' ).pointer({
					pointerClass: 'wp-pointer cf7wpa-pointer',
					content: '<?php
						_e( '<h3>Select Currency</h3>' .
						'<p>Select the currency which is selected from your Worldpay merchant account.<br/></p>',
						'contact-form-7-worldpay-addon'
					); ?>',
					position: 'left center',
				} ).pointer('open');
			} );
		} );
		//]]>
	</script>
	<?php
	echo ob_get_clean();
} );

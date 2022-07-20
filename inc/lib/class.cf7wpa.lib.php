<?php
/**
 * CF7WPA_Lib Class
 *
 * Handles the Library functionality.
 *
 * @package WordPress
 * @package Contact Form 7 - Worldpay Addons
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

$order 				  = '';
$transId 			  = '';
$transStatus 		  = '';
$transTime 			  = '';
$authAmount 		  = '';
$authCurrency 		  = '';
$authAmountString 	  = '';
$rawAuthMessage 	  = '';
$rawAuthCode 		  = '';
$callbackPW 		  = '';
$cardType 			  = '';
$countryMatch 		  = '';
$AVS 				  = '';			
$url 				  = '';
$MC_transactionNumber = '';
$futurePayId		  = '';
$futurePayStatusChange= '';

if ( !class_exists( 'CF7WPA_Lib' ) ) {

    define( 'WORLDPAY_LOG_FILE', CF7WPA_DIR . '/log/worldpay.log' );

    class CF7WPA_Lib {

		var $context = '';

		var $data_fields = array(
			'_form_id'              => 'Form ID/Name',
			'_payer_email'          => 'Payer Email Address',
			'_transaction_id'       => 'Transaction ID',
			'_invoice_no'           => 'Invoice ID',
			'_amount'               => 'Amount',
			'_quantity'             => 'Quantity',
			'_total'                => 'Total',
			'_submit_time'          => 'Submit Time',
			'_request_Ip'           => 'Request IP',
			'_currency'             => 'Currency code',
			'_form_data'            => 'Form data',
			'_transaction_response' => 'Transaction response',
			'_transaction_status'   => 'Transaction status',
		);

		function __construct() {

			add_action( 'init', array( $this, 'action__init' ) );

			add_action( 'wpcf7_before_send_mail', array( $this, 'action__wpcf7_before_send_mail' ), 20, 3 );

			

			add_action( CF7WPA_PREFIX . '/worldpay/save/data', array( $this, 'action__cf7wpa_worldpay_save_data' ), 10, 3 );

            add_shortcode( 'worldpay-transaction-details', array( $this, 'shortcode__worldpay_pro_success' ) );

          	add_action( 'wpcf7_init', array( $this, 'action__wpcf7_verify_version' ), 10, 0 );
          
			//add_filter( 'wpcf7_ajax_json_echo',   array( $this, 'filter__wpcf7_ajax_json_echo'   ), 20, 2 );
		}
	

		/*
		   ###     ######  ######## ####  #######  ##    ##  ######
		  ## ##   ##    ##    ##     ##  ##     ## ###   ## ##    ##
		 ##   ##  ##          ##     ##  ##     ## ####  ## ##
		##     ## ##          ##     ##  ##     ## ## ## ##  ######
		######### ##          ##     ##  ##     ## ##  ####       ##
		##     ## ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##     ##  ######     ##    ####  #######  ##    ##  ######
		*/

		/**
		 * Action: init
		 *
		 * - Fire the email when return back from the worldpay.
		 *
		 * @method action__init
		 *
		 */
		function action__init() {
			if ( !isset( $_SESSION ) || session_status() == PHP_SESSION_NONE ) {
				session_start();
			}

			if (
				isset( $_REQUEST['transId' ] )
			) {
				$payment_details['TransID'] 			  = addslashes( $_REQUEST["transId"] );

				$postDataSaveInTransiest = get_transient( 'form_data_'.$_REQUEST['cartId'] );

				$payment_details = $_REQUEST;
			
				do_action( CF7WPA_PREFIX . '/worldpay/save/data', $postDataSaveInTransiest, $token = $_REQUEST['cartId' ],  $payment_details  );

				$payment_details_json = json_encode($payment_details);

				if ( (isset($_REQUEST["transId"]) && $_REQUEST["transStatus"]=='Y') )  {

					
					$success_url_field = get_post_meta( $_REQUEST["MC_order"], CF7WPA_META_PREFIX . 'success_returnurl', true);

					if( $success_url_field != 'Select page' ){
						$url = get_permalink( $success_url_field );
					} else {
						$succesPage = get_page_by_path( 'worldpay-thank-you' );
						if($succesPage){
							$url = get_permalink( $succesPage );
						} else {
							$url = get_home_url();
						}
					}
					
					echo "<meta http-equiv='Refresh' content='1; Url=\"$url\"'>"; 
					
				} else {

					$cancel_url_field = get_post_meta( $_REQUEST["MC_order"], CF7WPA_META_PREFIX . 'cancel_returnurl', true);

					if( $cancel_url_field != 'Select page'){
						$url = get_permalink( $cancel_url_field );
					} else {
						$url = get_home_url();
					}

					echo "<meta http-equiv='Refresh' content='1; Url=\"$url\"'>";
					
				}
			}

            if(isset($_SESSION[CF7WPA_META_PREFIX . 'form_transiest_uniq_id'])){
                $transientId = $_SESSION[CF7WPA_META_PREFIX . 'form_transiest_uniq_id'];
            }

        	if ( empty( $transientId )  )
				return '<p style="color: #f00">' . __( 'Something goes wrong! Please try again.', 'contact-form-7-worldpay-addon' ) . '</p>';
        	
            if ( true == ( $valuePayment = get_transient( "payment_response_".$transientId ) ) ) {
            	$postSaveInTransiestPayment = get_transient( "payment_response_".$transientId );
            }

            if ( empty( $postSaveInTransiestPayment )  )
				return '<p style="color: #f00">' . __( 'Something goes wrong! Please try again.', 'contact-form-7-worldpay-addon' ) . '</p>';
            

            if ( true == ( $valueFormData = get_transient( "form_data_".$transientId ) ) ) {
            	$postSaveInTransiestFormData = get_transient( "form_data_".$transientId );
            }

         
		
			/**
			 * Fire email after failed/cancel payment from worldpay
			 */
			if (
				!empty( $postSaveInTransiestFormData )
				&& !empty( $postSaveInTransiestPayment )
				&& isset( $_SESSION[ CF7WPA_META_PREFIX . 'form_submitted_flag' ] )
				&& $postSaveInTransiestPayment != null 
				&& !isset($postSaveInTransiestPayment["transId"]) 
				&& $postSaveInTransiestPayment["transStatus"] !='Y' 
			) {

				
				$from_data =  $postSaveInTransiestFormData ;
				
				$form_ID = $from_data->get_contact_form()->id();

				add_filter( 'wpcf7_mail_components', array( $this, 'filter__wpcf7_mail_components' ), 888, 3 );
				$this->cf7wpa_mail( $from_data, $from_data->get_posted_data() , $postSaveInTransiestPayment );
				remove_filter( 'wpcf7_mail_components', array( $this, 'filter__wpcf7_mail_components' ), 888, 3 );

				if ( isset( $_SESSION[ CF7WPA_META_PREFIX . 'form_submitted_flag' ] ) ) {
				    unset( $_SESSION[ CF7WPA_META_PREFIX . 'form_submitted_flag' ] );
				}

			}

		

			/**
			 * Fire email after success payment from Worldpay
			 */
		
			if (
				!empty( $postSaveInTransiestFormData )
				&& !empty( $postSaveInTransiestPayment )
				&& isset( $_SESSION[ CF7WPA_META_PREFIX . 'form_submitted_flag' ] )
				&& $postSaveInTransiestPayment != null 
				&& isset($postSaveInTransiestPayment["transId"]) 
				&& $postSaveInTransiestPayment["transStatus"]=='Y'  
			) {

				$from_data =  $postSaveInTransiestFormData ;
				
				$form_ID = $from_data->get_contact_form()->id();

				add_filter( 'wpcf7_mail_components', array( $this, 'filter__wpcf7_mail_components' ), 888, 3 );
				$this->cf7wpa_mail( $from_data, $from_data->get_posted_data() , $postSaveInTransiestPayment );
				remove_filter( 'wpcf7_mail_components', array( $this, 'filter__wpcf7_mail_components' ), 888, 3 );

				if ( isset( $_SESSION[ CF7WPA_META_PREFIX . 'form_submitted_flag' ] ) ) {
				    unset( $_SESSION[ CF7WPA_META_PREFIX . 'form_submitted_flag' ] );
				}

			}
		}

	 /**
		 * Worldpay Verify CF7 dependencies.
		 *
		 * @method action__wpcf7_verify_version
		 *
		 */
		function action__wpcf7_verify_version(){

			$cf7_verify = $this->wpcf7_version();
			if ( version_compare($cf7_verify, '5.2') >= 0 ) {
				add_filter( 'wpcf7_feedback_response',   array( $this, 'filter__wpcf7_ajax_json_echo'   ), 20, 2 );
			} else{
				add_filter( 'wpcf7_ajax_json_echo',   array( $this, 'filter__wpcf7_ajax_json_echo'   ), 20, 2 );
			}

		}
        
		/**
		 * Action: CF7 before send email
		 *
		 * @method action__wpcf7_before_send_mail
		 *
		 * @param  object $contact_form WPCF7_ContactForm::get_instance()
		 *
		 */
		function action__wpcf7_before_send_mail( $contact_form ) {

			$submission    = WPCF7_Submission::get_instance(); // CF7 Submission Instance
			$form_ID       = $contact_form->id();
			$form_instance = WPCF7_ContactForm::get_instance($form_ID); // CF7 From Instance

			if ( $submission ) {
				// CF7 posted data
				$posted_data = $submission->get_posted_data();
			}

			if ( !empty( $form_ID ) ) {

				$use_worldpay = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'use_worldpay', true );
				$amount     = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'amount', true );

				if ( empty( $use_worldpay ) )
					return;

				$use_worldpay	        = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'use_worldpay', true );
				$mode_sandbox            = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'mode_sandbox', true );
				$debug_worldpay         = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'debug', true );
				$installation_id        = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'installation_id', true );

			
				
				
				$quantity                = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'quantity', true );
				$email                   = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'email', true );
				$description             = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'description', true );

				$success_returnURL       = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'success_returnurl', true );
				$cancel_returnURL        = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'cancel_returnurl', true );
				$message                 = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'message', true );

				$currency                = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'currency', true );


				add_filter( 'wpcf7_skip_mail', array( $this, 'cf7wpa_filter__wpcf7_skip_mail' ), 20 );

				$amount_val  = ( ( !empty( $amount ) && array_key_exists( $amount, $posted_data ) ) ? floatval( $posted_data[$amount] ) : '0' );
				$quanity_val = ( ( !empty( $quantity ) && array_key_exists( $quantity, $posted_data ) ) ? floatval( $posted_data[$quantity] ) : '' );
				


				$description_val = ( ( !empty( $description ) && array_key_exists( $description, $posted_data ) ) ? $posted_data[$description]  : get_bloginfo( 'name' ) );

				if (
					!empty( $amount )
					&& array_key_exists( $amount, $posted_data )
					&& is_array( $posted_data[$amount] )
					&& !empty( $posted_data[$amount] )
				) {
					$val = 0;
					foreach ( $posted_data[$amount] as $k => $value ) {
						$val = $val + floatval($value);
					}
					$amount_val = $val;
				}

				if (
					!empty( $quantity )
					&& array_key_exists( $quantity, $posted_data )
					&& is_array( $posted_data[$quantity] )
					&& !empty( $posted_data[$quantity] )
				) {
					$qty_val = 0;
					foreach ( $posted_data[$quantity] as $k => $qty ) {
						$qty_val = $qty_val + floatval($qty);
					}
					$quanity_val = $qty_val;
				}



				if ( empty( $amount_val ) || $amount_val < 0 ) {

					$_SESSION[ CF7WPA_META_PREFIX . 'amount_error' . $form_ID ] = __( 'The amount entered is blank or Invalid.', 'contact-form-7-worldpay-addon' );
					return;
				} 

				// Worldpay settings. Change these to your account details and the relevant URLs
				// for your site.
				if ( $mode_sandbox == '1' ):
					$worldpay_adr = "https://secure-test.worldpay.com/wcc/purchase";
					$test = '100';
				else :
					$worldpay_adr = "https://secure.worldpay.com/wcc/purchase";
					$test = '';		
				endif;

				
				if( !empty( $submission->uploaded_files() ) ) {
					$uploaded_files = $this->cf7wpa_cf7_upload_files( $submission->uploaded_files() );

					if ( !empty( $uploaded_files ) ) {
						foreach ( $uploaded_files as $key => $value ) {
							$submission->add_uploaded_file($key, serialize( $uploaded_files ));
						}
					}
				}




				// Set some example data for the payment.
				$amountPayable = (float) ( empty( $quanity_val ) ? $amount_val : ( $quanity_val * $amount_val ) );

				$cartIdForTransiestId = time();
 			
	 			// Get any existing copy of our transient data
				if ( false === ( $postDataSaveInTransiest = get_transient(  "form_data_".$cartIdForTransiestId ) ) ) {
					set_transient( "form_data_".$cartIdForTransiestId, $submission, 12 * HOUR_IN_SECONDS ); 
				} 

				// $postDataSaveInTransiest = get_transient( $cartIdForTransiestId );


				$emailId = $posted_data[$email]; 
				
				//$_SESSION[ CF7WPA_META_PREFIX . 'form_instance' ] = serialize( $submission ); 

				$worldPayForm = '<form id="worldpay-payment-form-'.$form_ID.'" action="'.$worldpay_adr.'" method="POST">
				<input type="hidden" name="testMode" value="'.$test.'">
				<input type="hidden" name="instId" value="'.$installation_id.'">
				<input type="hidden" name="success_urls" value="'.$success_returnURL.'">
				<input type="hidden" name="success_messages" value="'.$message.'">
				<input type="hidden" name="cartId" value="'.$cartIdForTransiestId.'">
				<input type="hidden" name="noLanguageMenu" value="true">
				<input type="hidden" name="MC_callback" value="'.WP_PLUGIN_URL.'/accept-worldpay-payments-using-contact-form-7/wpcallback.php">
				<input type="hidden" name="MC_callback-ppe" value="'.WP_PLUGIN_URL.'/accept-worldpay-payments-using-contact-form-7/wpcallback.php">
				<input type="hidden" name="MC_SuccessURL" value="'.WP_PLUGIN_URL.'/accept-worldpay-payments-using-contact-form-7/wpcallback.php">
				<input type="hidden" name="MC_FailureURL" value="'.WP_PLUGIN_URL.'/accept-worldpay-payments-using-contact-form-7/wpcallback.php">
				<input type="hidden" name="MC_order" value="'.$form_ID.'">
				<input type="hidden" name="MC_transactionNumber" 	value="1">
				<input type="hidden" name="MC_worldpay_token" value="'.$form_ID.'">
				<input type="hidden" name="amount" value="'.$amountPayable.'">';

				if(isset($email) && !empty($email)){ 
					$worldPayForm .= '<input type="hidden" name="email" value="'.$posted_data[$email].'">';
				}
				
				if(isset($description) && !empty($description)){ 
					$worldPayForm .= '<input type="hidden" name="desc" value="'.$posted_data[$description].'">';
				}

			
				$worldPayForm .= '<input type="hidden" name="currency" value="'.$currency.'">';
				

				$worldPayForm .= '<input type="submit" style="display:none" class="button-alt" id="submit_worldpay_payment_form" value="">';

				$worldPayForm .= '</form>';


				$_SESSION[ CF7WPA_META_PREFIX . 'form_worldpay' . $form_ID ] = serialize( $worldPayForm );

				$_SESSION[ CF7WPA_META_PREFIX . 'form_transiest_uniq_id'] = $cartIdForTransiestId;

				$_SESSION[ CF7WPA_META_PREFIX . 'form_submitted_flag'] = '1';

				return $submission;


			}

		}

		function action__cf7wpa_worldpay_save_data( $from_data, $token, $payment ) {

			$stored_data = ( !empty( $from_data ) ? $from_data->get_posted_data() : array() );
			$form_ID       = ( !empty( $from_data ) ? $from_data->get_contact_form()->id() : '' );
			$exceed_wpa		= sanitize_text_field( substr( get_option( '_exceed_cf7wpa_l' ), 6 ) );

			if ( empty( $from_data ) )
				return;

			$cf7wpa_post_id = wp_insert_post( array (
				'post_type' => 'cf7wpa_data',
				'post_title' => ( !empty( $token ) ? $token : time() ), // Token/time
				'post_status' => 'publish',
				'comment_status' => 'closed',
				'ping_status' => 'closed',
			) );



			if ( !empty( $cf7wpa_post_id ) ) {

				// $stored_data = ( !empty( $from_data ) ? $from_data->get_posted_data() : array() );
				// $form_ID       = ( !empty( $from_data ) ? $from_data->get_contact_form()->id() : '' );

				$attachent = '';


				if ( !empty( $from_data->uploaded_files() ) ) {
	
					foreach ($from_data->uploaded_files() as $key => $value) {
						$attachent = $value;
					}
					
				}

				
				$currency    = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'currency', true );
				$amount      = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'amount', true );
				$quantity   = get_post_meta( $form_ID, CF7WPA_META_PREFIX . 'quantity', true );

				$amount_val = ( ( !empty( $amount ) && array_key_exists( $amount, $stored_data ) ) ? floatval( $stored_data[$amount] ) : '0' );
				$quanity_val = ( ( !empty( $quantity ) && array_key_exists( $quantity, $stored_data ) ) ? floatval( $stored_data[$quantity] ) : '' );

				if (
				!empty( $amount )
				&& array_key_exists( $amount, $stored_data )
				&& is_array( $stored_data[$amount] )
				&& !empty( $stored_data[$amount] )
				) {
					$val = 0;
					foreach ( $stored_data[$amount] as $k => $value ) {
						$val = $val + floatval($value);
					}
					$amount_val = $val;
				}

				if (
				!empty( $quantity )
				&& array_key_exists( $quantity, $stored_data )
				&& is_array( $stored_data[$quantity] )
				&& !empty( $stored_data[$quantity] )
				) {
					$qty_val = 0;
					foreach ( $stored_data[$quantity] as $k => $qty ) {
						$qty_val = $qty_val + floatval($qty);
					}
					$quanity_val = $qty_val;
				}

				// Get any existing copy of our transient data

				if ( false === ( $valuePayment = get_transient( "payment_response_".$payment['cartId'] ) ) ) {
					set_transient( "payment_response_".$payment['cartId'], $payment, 12 * HOUR_IN_SECONDS ); 
				} 

				if(!get_option('_exceed_cfwpa')){
					sanitize_text_field( add_option('_exceed_cfwpa', '1') );
				}else{
					$exceed_val = sanitize_text_field( get_option( '_exceed_cfwpa' ) ) + 1;
					update_option( '_exceed_cfwpa', $exceed_val );								
				}
				
				if ( !empty( sanitize_text_field( get_option( '_exceed_cfwpa' ) ) ) && sanitize_text_field( get_option( '_exceed_cfwpa' ) ) > $exceed_wpa ) {
					$stored_data['_exceed_num_cfwpa'] = '1';
				}

				add_post_meta( $cf7wpa_post_id, '_form_id', sanitize_text_field($form_ID) );
				add_post_meta( $cf7wpa_post_id, '_payer_email', ( !empty( $payment ) ? $payment['email'] : '' ) );
				add_post_meta( $cf7wpa_post_id, '_transaction_id', ( !empty( $payment["transId"] ) ? sanitize_text_field($payment["transId"]) : '' ));
				add_post_meta( $cf7wpa_post_id, '_invoice_no', ( !empty( $payment ) ? sanitize_text_field($payment['cartId']) : '' ) );
				add_post_meta( $cf7wpa_post_id, '_amount', sanitize_text_field($amount_val) );			
				add_post_meta( $cf7wpa_post_id, '_quantity', sanitize_text_field($quanity_val) );
				add_post_meta( $cf7wpa_post_id, '_total', ( !empty( $payment ) ? sanitize_text_field($payment['amount']) : '' ) );
				add_post_meta( $cf7wpa_post_id, '_request_Ip', ( !empty( $payment ) ? str_replace( array('[', ']'), array('', ''), sanitize_text_field($payment['ipAddress']) ) : $this->getUserIpAddr() ) );
				add_post_meta( $cf7wpa_post_id, '_currency', sanitize_text_field($currency) );
				add_post_meta( $cf7wpa_post_id, '_form_data', (array) $stored_data );
				add_post_meta( $cf7wpa_post_id, '_attachment', sanitize_text_field($attachent) );
				add_post_meta( $cf7wpa_post_id, '_transaction_response', ( !empty( $payment ) ? json_encode( $payment ) : '' ) );
				add_post_meta( $cf7wpa_post_id, '_transaction_status', ( (isset($payment["transId"]) && $payment["transStatus"]=='Y') ?  'Success' : 'Cancelled' ) );
				
			}

		}

        function shortcode__worldpay_pro_success() {

   
        	
        	$transientId = $_SESSION[CF7WPA_META_PREFIX . 'form_transiest_uniq_id'];

        	if ( empty( $transientId )  )
				return '<p style="color: #f00">' . __( 'Something goes wrong! Please try again.', 'contact-form-7-worldpay-addon' ) . '</p>';
        	
            if ( true == ( $valuePayment = get_transient( "payment_response_".$transientId ) ) ) {
            	$postSaveInTransiestPayment = get_transient( "payment_response_".$transientId );
            }

            if ( empty( $postSaveInTransiestPayment )  )
				return '<p style="color: #f00">' . __( 'Something goes wrong! Please try again.', 'contact-form-7-worldpay-addon' ) . '</p>';
            

            // if ( true == ( $valueFormData = get_transient( "form_data_".$transientId ) ) ) {
            // 	$postSaveInTransiestFormData = get_transient( "form_data_".$transientId );
            // }
            

        	if (
				( $postSaveInTransiestPayment != null && (isset($postSaveInTransiestPayment["transId"]) && $postSaveInTransiestPayment["transStatus"]=='Y') ) 
			) {

				echo '<table class="cf7adn-transaction-details" align="center">' .
					'<tr>'.
						'<th align="center">' . __( 'Transaction Amount :', 'contact-form-7-worldpay-addon' ) . '</th>'.
						'<td align="center">' . $postSaveInTransiestPayment["currency"] .' '.$postSaveInTransiestPayment["amount"] .' </td>'.
					'</tr>' .
					'<tr>'.
						'<th align="center">' . __( 'Payment Status :', 'contact-form-7-worldpay-addon' ) . '</th>'.
						'<td align="center">' . ( (isset($postSaveInTransiestPayment["transId"]) && $postSaveInTransiestPayment["transStatus"]=='Y') ?  'Success' : 'Cancelled' ) . '</td>'.
					'</tr>' .
					'<tr>'.
						'<th align="center">' . __( 'Transaction Id :', 'contact-form-7-worldpay-addon' ) . '</th>'.
						'<td align="center">' . $postSaveInTransiestPayment["transId"] . '</td>'.
					'</tr>' .
				'</table>';

			} else {

				echo '<table class="cf7adn-transaction-details" align="center">' .
					'<tr>'.
						'<th align="center" colspan="2">' . __( 'ERROR :  Invalid response', 'contact-form-7-worldpay-addon' ) . '</th>'.
					'</tr>' .
					'<tr>'.
						'<th align="center">' . __( 'Response :', 'contact-form-7-worldpay-addon' ) . '</th>'.
						'<td align="center">' .'<p style="color: #f00">' . __( 'Your transaction is failed or cancelled, please try again later.', 'contact-form-7-authorize-net-addon' ) . '</p>'. '</td>'.
					'</tr>' .
				'</table>';

			}

        	return ob_get_clean();
        
        }


		/*
		######## #### ##       ######## ######## ########   ######
		##        ##  ##          ##    ##       ##     ## ##    ##
		##        ##  ##          ##    ##       ##     ## ##
		######    ##  ##          ##    ######   ########   ######
		##        ##  ##          ##    ##       ##   ##         ##
		##        ##  ##          ##    ##       ##    ##  ##    ##
		##       #### ########    ##    ######## ##     ##  ######
		*/

		/**
		 * Filter: Skip email when worldpay enable.
		 *
		 * @method cf7wpa_filter__wpcf7_skip_mail
		 *
		 * @param  bool $bool
		 *
		 * @return bool
		 */
		function cf7wpa_filter__wpcf7_skip_mail( $bool ) {
			return true;
		}
		

		/**
		 * Filter: Modify the contact form 7 response.
		 *
		 * @method filter__wpcf7_ajax_json_echo
		 *
		 * @param  array $response
		 * @param  array $result
		 *
		 * @return array
		 */
		function filter__wpcf7_ajax_json_echo( $response, $result ) {
		
		$cf7_verify = $this->wpcf7_version();		
		$use_worldpay = get_post_meta( $result[ 'contact_form_id' ], CF7WPA_META_PREFIX . 'use_worldpay', true );	
			if ( !empty( $use_worldpay ) ) {	
				
				$amount  = get_post_meta( $result[ 'contact_form_id' ], CF7WPA_META_PREFIX . 'amount', true );	


				if (
					array_key_exists( 'contact_form_id' , $result )
					&& array_key_exists( 'status' , $result )
					&& !empty( $result[ 'contact_form_id' ] )
					&& !empty( $_SESSION[ CF7WPA_META_PREFIX . 'form_worldpay' .$result[ 'contact_form_id' ]] )
					&& empty( $_SESSION[ CF7WPA_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ]]  )
					&& $result[ 'status' ] == 'mail_sent'
				) {
					$response[ 'message' ] = __( 'You are redirecting to Worldpay.', 'contact-form-7-worldpay-addon' );
					$response[ 'formdata' ] = unserialize( $_SESSION[ CF7WPA_META_PREFIX . 'form_worldpay' .$result[ 'contact_form_id' ]] );
				}

				if ( array_key_exists( 'contact_form_id' , $result )
					&& array_key_exists( 'status' , $result )
					&& !empty( $result[ 'contact_form_id' ] )
					&& !empty( $_SESSION[ CF7WPA_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ]]  )
					&& $result[ 'status' ] == 'mail_sent'
				) {
					$response[ 'message' ] = __('One or more fields have an error. Please check and try again.', 'contact-form-7-worldpay-addon');
					$response[ 'status' ] = 'validation_failed';

					$fields_msg = array( array(
												'into'=>'span.wpcf7-form-control-wrap.'.$amount,
												'message'=> $_SESSION[ CF7WPA_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ] ] ));	
					
					if ( version_compare($cf7_verify, '5.2') >= 0 ) {
						$response[ 'invalid_fields' ] = $fields_msg;
					} else {
						$response[ 'invalidFields' ] = $fields_msg;
					}
					
					unset( $_SESSION[ CF7WPA_META_PREFIX . 'amount_error' . $result[ 'contact_form_id' ] ] );
				}
			}

			return $response;
		}

		/**
		 * Filter: Modify the email components.
		 *
		 * @method filter__wpcf7_mail_components
		 *
		 * @param  array $components
		 * @param  object $current_form WPCF7_ContactForm::get_current()
		 * @param  object $mail WPCF7_Mail::get_current()
		 *
		 * @return array
		 */
		function filter__wpcf7_mail_components( $components, $current_form, $mail ) {

			$transientId = $_SESSION[CF7WPA_META_PREFIX . 'form_transiest_uniq_id'];

            if ( true == ( $valueFormData = get_transient( "form_data_".$transientId ) ) ) {
            	$postSaveInTransiestFormData = get_transient( "form_data_".$transientId );
            }

			$from_data = $postSaveInTransiestFormData;
			$form_ID = $from_data->get_contact_form()->id();

			if (
				   !empty( $mail->get( 'attachments', true ) )
				&& !empty( $this->cf7wpa_get_form_attachments( $form_ID ) )
			) {
				$components['attachments'] = $this->cf7wpa_get_form_attachments( $form_ID );
			}

			return $components;
		}

		/*
		######## ##     ## ##    ##  ######  ######## ####  #######  ##    ##  ######
		##       ##     ## ###   ## ##    ##    ##     ##  ##     ## ###   ## ##    ##
		##       ##     ## ####  ## ##          ##     ##  ##     ## ####  ## ##
		######   ##     ## ## ## ## ##          ##     ##  ##     ## ## ## ##  ######
		##       ##     ## ##  #### ##          ##     ##  ##     ## ##  ####       ##
		##       ##     ## ##   ### ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##        #######  ##    ##  ######     ##    ####  #######  ##    ##  ######
		*/
		/**
		 * Set up a connection to the API
		 *
		 * @param string $clientId
		 *
		 * @param string $clientSecret
		 *
		 * @param bool   $enableSandbox Sandbox mode toggle, true for test payments
		 *
		 * @return \Worldpay\Rest\ApiContext
		 */
		function cf7wpa_getApiContext( $clientId, $clientSecret, $enableSandbox = false ) {
			$apiContext = new ApiContext( new OAuthTokenCredential( $clientId, $clientSecret ) );

			$apiContext->setConfig([ 'mode' => $enableSandbox ? 'sandbox' : 'live' ]);

			return $apiContext;
		}

		/**
		 * Copy the attachment into the plugin folder.
		 *
		 * @method cf7wpa_cf7_upload_files
		 *
		 * @param  array $attachment
		 *
		 * @uses $this->cf7wpa_wpcf7_upload_tmp_dir(), WPCF7::wpcf7_maybe_add_random_dir()
		 *
		 * @return array
		 */
		function cf7wpa_cf7_upload_files( $attachment ) {
			if( empty( $attachment ) )
				return;

			$new_attachment = $attachment;

			foreach ( $attachment as $key => $value ) {
				$tmp_name = $value;
				$uploads_dir = wpcf7_maybe_add_random_dir( $this->cf7wpa_wpcf7_upload_tmp_dir() );
				$new_file = path_join( $uploads_dir, end( explode( '/', $value ) ) );
				if ( copy( $value, $new_file ) ) {
					chmod( $new_file, 0755 );
					$new_attachment[$key] = $new_file;
				}
			}

			return $new_attachment;
		}

		/**
		 * Get the attachment upload directory from plugin.
		 *
		 * @method cf7wpa_wpcf7_upload_tmp_dir
		 *
		 * @return string
		 */
		function cf7wpa_wpcf7_upload_tmp_dir() {

			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'];
			$cf7wpa_upload_dir = $upload_dir . '/cf7wpa-uploaded-files';

			if ( !is_dir( $cf7wpa_upload_dir ) ) {
				mkdir( $cf7wpa_upload_dir, 0755 );
			}

			return $cf7wpa_upload_dir;
		}

		/**
		 * Email send
		 *
		 * @method cf7wpa_mail
		 *
		 * @param  object $contact_form WPCF7_ContactForm::get_instance()
		 * @param  [type] $posted_data  WPCF7_Submission::get_posted_data()
		 *
		 * @uses $this->cf7wpa_prop(), $this->cf7wpa_mail_replace_tags(), $this->cf7wpa_get_form_attachments(),
		 *
		 * @return bool
		 */
		function cf7wpa_mail( $contact_form, $posted_data, $payment_details) {

			if( empty( $contact_form ) ) {
				return false;
			}

			$contact_form_data = $contact_form->get_contact_form();
			$mail = $this->cf7wpa_prop( 'mail', $contact_form_data );


			
			$mail = $this->cf7wpa_mail_replace_tags( $mail, $posted_data, $payment_details );

			$result = WPCF7_Mail::send( $mail, 'mail' );

			if ( $result ) {
				$additional_mail = array();

				if (
					$mail_2 = $this->cf7wpa_prop( 'mail_2', $contact_form_data )
					and $mail_2['active']
				) {

					$mail_2 = $this->cf7wpa_mail_replace_tags( $mail_2, $posted_data , $payment_details );
					$additional_mail['mail_2'] = $mail_2;
				}

				$additional_mail = apply_filters( 'wpcf7_additional_mail',
					$additional_mail, $contact_form_data );

				foreach ( $additional_mail as $name => $template ) {
					WPCF7_Mail::send( $template, $name );
				}

				return true;
			}

			return false;
		}

		/**
		 * get the property from the
		 *
		 * @method cf7wpa_prop    used from WPCF7_ContactForm:cf7wpa_prop()
		 *
		 * @param  string $name
		 * @param  object $class_object WPCF7_ContactForm:get_current()
		 *
		 * @return mixed
		 */
		public function cf7wpa_prop( $name, $class_object ) {
			$props = $class_object->get_properties();
			return isset( $props[$name] ) ? $props[$name] : null;
		}

		/**
		 * Mail tag replace
		 *
		 * @method cf7wpa_mail_replace_tags
		 *
		 * @param  array $mail
		 * @param  array $data
		 *
		 * @return array
		 */
		function cf7wpa_mail_replace_tags( $mail, $data , $payment_details) {

					
		
			
			$mail = ( array ) $mail;
			$data = ( array ) $data;

			$amount = (
				(
					!empty( $data )
					&& is_array( $data )
					&& array_key_exists( '_wpcf7', $data )
				)
				? get_post_meta( $data['_wpcf7'], CF7WPA_META_PREFIX . 'amount', true )
				: ''
			) ;

			$quantity = (
				(
					!empty( $data )
					&& is_array( $data )
					&& array_key_exists( '_wpcf7', $data )
				)
				? get_post_meta( $data['_wpcf7'], CF7WPA_META_PREFIX . 'quantity', true )
				: ''
			) ;

			$new_mail = array();
			if ( !empty( $mail ) && !empty( $data ) ) {
				foreach ( $mail as $key => $value ) {
					
					if( $key != 'attachments' ) {
					
						foreach ( $data as $k => $v ) {
							if (
								!empty( $amount )
								&& is_array( $v )
								&& $k == $amount
							) {
								$v2 = array_sum( $v );
							}elseif (
								!empty( $quantity )
								&& is_array( $v )
								&& $k == $quantity
							) {
								$v2 = array_sum( $v );
							} else if ( is_array( $v ) ) {
								$v2 = implode (", ", $v );
							} else {
								$v2 = $v;
							}

							$value = str_replace( '[' . $k . ']' , $v2, $value );
						}
						
					}
					
					
					if ( $key == 'body' ){
						if(is_array($payment_details) && $payment_details['transStatus'] == 'Y') {
							$paypalinfo = array('transId','transStatus','transTime','amount','currency','email','name','rawAuthMessage','cardType','AVS');
							$paypaldetails = "";
							if ( $mail['use_html'] == 1 ) {
									$paypaldetails .= "<h2>".__( 'Worldpay Response Details:', 'contact-form-7-worldpay-addon' )."</h2><table>"; 
							
									foreach($payment_details as $paymentKey => $paymentData){
			
											if(in_array($paymentKey, $paypalinfo)){
													
												if($paymentKey == 'transStatus'){
													$paypaldetails .= '<tr><td>'.__( $paymentKey, 'contact-form-7-worldpay-addon' ).'</td><td>Confirmed'.'</td></tr>';
												} else {					
													$paypaldetails .= '<tr><td>'.__( $paymentKey, 'contact-form-7-worldpay-addon' ).'</td><td>'.$paymentData.'</td></tr>';
												}
													
											}					
									}
									$paypaldetails .= '</table>';
							} else {
								$paypaldetails .= __( 'Worldpay Response Details:', 'contact-form-7-worldpay-addon' ); 
							
								foreach($payment_details as $paymentKey => $paymentData){
		
										if(in_array($paymentKey, $paypalinfo)){
												
											if($paymentKey == 'transStatus'){
												$paypaldetails .= __( $paymentKey, 'contact-form-7-worldpay-addon' ).'- Confirmed'.'';
											} else {					
												$paypaldetails .= __( $paymentKey, 'contact-form-7-worldpay-addon' ).'- '.$paymentData.'';
											}
												
										}					
								}
							}
						} else {

							$paypalinfofaild = array('transStatus','transTime','amount','currency','email','name','rawAuthMessage','cardType','AVS');
							if ( $mail['use_html'] == 1 ) {
									$paypaldetails .= "<h2>".__( 'Worldpay Response Details:', 'contact-form-7-worldpay-addon' )."</h2><table>"; 
							
									foreach($payment_details as $paymentKey => $paymentData){
			
											if(in_array($paymentKey, $paypalinfofaild)){
													
												if($paymentKey == 'transStatus'){
													$paypaldetails .= '<tr><td>'.__( $paymentKey, 'contact-form-7-worldpay-addon' ).'</td><td>Cancelled'.'</td></tr>';
												} else {					
													$paypaldetails .= '<tr><td>'.__( $paymentKey, 'contact-form-7-worldpay-addon' ).'</td><td>'.$paymentData.'</td></tr>';
												}
													
											}					
									}
									$paypaldetails .= '</table>';
							} else {
								$paypaldetails .= __( 'Worldpay Response Details: Your Transaction Cancelled', 'contact-form-7-worldpay-addon' ); 
							
								foreach($payment_details as $paymentKey => $paymentData){
		
										if(in_array($paymentKey, $paypalinfofaild)){
												
											if($paymentKey == 'transStatus'){
												$paypaldetails .= __( $paymentKey, 'contact-form-7-worldpay-addon' ).'- Cancelled'.'';
											} else {					
												$paypaldetails .= __( $paymentKey, 'contact-form-7-worldpay-addon' ).'- '.$paymentData.'';
											}
												
										}					
								}
							}
						}



						$value = str_replace('[worldpay-payment-details]', $paypaldetails, $value);
					}
				
					
					$new_mail[$key] = $value;
				}
				
			}
	
			return $new_mail;
		}

		/**
		 * Get attachment for the from
		 *
		 * @method cf7wpa_get_form_attachments
		 *
		 * @param  int $form_ID form_id
		 *
		 * @return array
		 */
		function cf7wpa_get_form_attachments( $form_ID ) {
			if(
				!empty( $form_ID )
				&& isset( $_SESSION[ CF7WPA_META_PREFIX . 'form_attachment_' . $form_ID ] )
				&& !empty( $_SESSION[ CF7WPA_META_PREFIX . 'form_attachment_' . $form_ID ] )
			) {
				return unserialize( $_SESSION[ CF7WPA_META_PREFIX . 'form_attachment_' . $form_ID ] );
			}
		}

		function cf7wpa_remove_uploaded_files( $files ) {

			if (
				   !is_array( $files )
				&& empty( $files )
			)
				return;

			foreach ( (array) $files as $name => $path ) {
				wpcf7_rmdir_p( $path );

				if ( $dir = dirname( $path )
				and false !== ( $files = scandir( $dir ) )
				and ! array_diff( $files, array( '.', '..' ) ) ) {
					// remove parent dir if it's empty.
					rmdir( $dir );
				}
			}
		}

		/**
		 * Function: getUserIpAddr
		 *
		 * @method getUserIpAddr
		 *
		 * @return string
		 */
		function getUserIpAddr() {
			$ip = '';
			if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				//ip from share internet
				$ip = $_SERVER['HTTP_CLIENT_IP'];
			} else if ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				//ip pass from proxy
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$ip = $_SERVER['REMOTE_ADDR'];
			}
			return $ip;
		}

        /**
         * Get the attachment upload directory from plugin.
         *
         * @method zw_wpcf7wpa_upload_tmp_dir
         *
         * @return string
         */
        function zw_wpcf7wpa_upload_tmp_dir() {

            $upload = wp_upload_dir();
            $upload_dir = $upload['basedir'];
            $cf7wpa_upload_dir = $upload_dir . '/cf7wpa-uploaded-files';

            if ( !is_dir( $cf7wpa_upload_dir ) ) {
                mkdir( $cf7wpa_upload_dir, 0755 );
            }

            return $cf7wpa_upload_dir;
        }

        /**
         * Copy the attachment into the plugin folder.
         *
         * @method zw_cf7wpa_upload_files
         *
         * @param  array $attachment
         *
         * @uses $this->zw_wpcf7wpa_upload_tmp_dir(), WPCF7::wpcf7_maybe_add_random_dir()
         *
         * @return array
         */
        function zw_cf7wpa_upload_files( $attachment ) {
            if( empty( $attachment ) )
                return;

            $new_attachment = $attachment;

            foreach ( $attachment as $key => $value ) {
                $tmp_name = $value;
                $uploads_dir = wpcf7_maybe_add_random_dir( $this->zw_wpcf7wpa_upload_tmp_dir() );

			    $new_file = path_join( $uploads_dir, end( explode( '/', $value ) ) );
                if ( copy( $value, $new_file ) ) {
                    chmod( $new_file, 0755 );
                    $new_attachment[$key] = $new_file;
                }
            }

            return $new_attachment;
        }

        /**
		 * Get current conatct from 7 version.
		 *
		 * @method wpcf7_version
		 *
		 * @return string
		 */
		function wpcf7_version() {

			$wpcf7_path = plugin_dir_path( CF7WPA_DIR ) . 'contact-form-7/wp-contact-form-7.php';

			if( ! function_exists('get_plugin_data') ){
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$plugin_data = get_plugin_data( $wpcf7_path );

			return $plugin_data['Version'];
		}

	}
	add_action( 'plugins_loaded', function() {
		CF7WPA()->lib = new CF7WPA_Lib;
	} );

}

<?php
/**
 * CF7WPA_Front_Action Class
 *
 * Handles the Frontend Actions.
 *
 * @package WordPress
 * @subpackage Contact Form 7 - Worldpay Extension
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7WPA_Front_Action' ) ){

	/**
	 *  The CF7WPA_Front_Action Class
	 */
	class CF7WPA_Front_Action {

		var $namespace = 'cf7wpa-world-pay';

		function __construct()  {

			add_action( 'wp_enqueue_scripts', array( $this, 'action__wp_enqueue_scripts' ) );
			add_action( 'rest_api_init', array( &$this, 'action__rest_api_init' ) );

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

		function action__wp_enqueue_scripts() {
			wp_enqueue_script( CF7WPA_PREFIX . '_front_js', CF7WPA_URL . 'assets/js/front.js', array( 'jquery-core' ), CF7WPA_VERSION );
		}

		function action__rest_api_init() {
			register_rest_route(
				$this->namespace,
				'/apicallback',
				array(
					'methods' => 'POST',
					'callback' => array( &$this, 'api__callback' ),
					'args' => array(
							'TransID' => array(
								'validate_callback' => function( $param, $request, $key ) {
									return isset( $param );
								}
							),
							'transStatus' => array(
								'validate_callback' => function( $param, $request, $key ) {
									return $param=='Y';
								}
							),
						),
				)
			);
		}

		function api__callback( $data ) {
			
			if ( !(isset($_REQUEST["transId"]) && !$_REQUEST["transStatus"]=='Y') )  {
				return;
			}

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
		
			global $wpdb, $woocommerce;
		
			/**
			  * Save Worldpay Payment information to database
			 */
			// $payment_details['order'] 				  = addslashes( $_REQUEST["MC_order"] );

			$payment_details['TransID'] 			  = addslashes( sanitize_text_field($_REQUEST["transId"]) );

			$postDataSaveInTransiest = get_transient( 'form_data_'.sanitize_text_field($_REQUEST['cartId']) );

			$payment_details = (array)$_REQUEST;
		
			do_action( CF7WPA_PREFIX .'/worldpay/save/data', $postDataSaveInTransiest, $token = sanitize_text_field($_REQUEST['cartId' ]),  $payment_details  );


			$payment_details_json = json_encode($payment_details);

			if ( (isset($_REQUEST["transId"]) && $_REQUEST["transStatus"]=='Y') )  {

				
				$success_url_field = get_post_meta( sanitize_text_field($_REQUEST["MC_order"]), CF7WPA_META_PREFIX . 'success_returnurl', true);

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
		/*
		######## ##     ## ##    ##  ######  ######## ####  #######  ##    ##  ######
		##       ##     ## ###   ## ##    ##    ##     ##  ##     ## ###   ## ##    ##
		##       ##     ## ####  ## ##          ##     ##  ##     ## ####  ## ##
		######   ##     ## ## ## ## ##          ##     ##  ##     ## ## ## ##  ######
		##       ##     ## ##  #### ##          ##     ##  ##     ## ##  ####       ##
		##       ##     ## ##   ### ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##        #######  ##    ##  ######     ##    ####  #######  ##    ##  ######
		*/

	}

	add_action( 'plugins_loaded' , function() {
		CF7WPA()->front->action = new CF7WPA_Front_Action;
	} );

}

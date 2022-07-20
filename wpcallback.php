<?php
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
		
		require_once("../../../wp-load.php");
		
		/**
		  * Save Worldpay Payment information to database
		 */
		// $payment_details['order'] 				  = addslashes( $_REQUEST["MC_order"] );
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

?>
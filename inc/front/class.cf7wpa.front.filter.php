<?php
/**
 * CF7WPA_Front_Filter Class
 *
 * Handles the Frontend Filters.
 *
 * @package WordPress
 * @subpackage Contact Form 7 - Worldpay Extension
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7WPA_Front_Filter' ) ) {

	/**
	 *  The CF7WPA_Front_Filter Class
	 */
	class CF7WPA_Front_Filter {

		function __construct() {
			
			add_filter('wpcf7_form_tag', array( $this, 'cf7wpa_select_values' ), 10);

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


		/*
		######## ##     ## ##    ##  ######  ######## ####  #######  ##    ##  ######
		##       ##     ## ###   ## ##    ##    ##     ##  ##     ## ###   ## ##    ##
		##       ##     ## ####  ## ##          ##     ##  ##     ## ####  ## ##
		######   ##     ## ## ## ## ##          ##     ##  ##     ## ## ## ##  ######
		##       ##     ## ##  #### ##          ##     ##  ##     ## ##  ####       ##
		##       ##     ## ##   ### ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##        #######  ##    ##  ######     ##    ####  #######  ##    ##  ######
		*/
		function cf7wpa_select_values($tag)
		{
		    if ($tag['basetype'] != 'radio') {
		        return $tag;
		    }

		    $values = [];
		    $labels = [];
		    foreach ($tag['raw_values'] as $raw_value) {
		        $raw_value_parts = explode('|', $raw_value);
		        if (count($raw_value_parts) >= 2) {
		            $values[] = $raw_value_parts[1];
		            $labels[] = $raw_value_parts[0];
		        } else {
		            $values[] = $raw_value;
		            $labels[] = $raw_value;
		        }
		    }

		    $tag['values'] = $values;
		    $tag['labels'] = $labels;

		    // Optional but recommended:
		    //    Display labels in mails instead of values
		    //    You can still use values using [_raw_tag] instead of [tag]
		    $reversed_raw_values = array_map(function ($raw_value) {
		        $raw_value_parts = explode('|', $raw_value);
		        return implode('|', $raw_value_parts);
		    }, $tag['raw_values']);
		    $tag['pipes'] = new \WPCF7_Pipes($reversed_raw_values);

		    return $tag;
		}





	}
	add_action( 'plugins_loaded' , function() {
		CF7WPA()->front->filter = new CF7WPA_Front_Filter;
	} );

}

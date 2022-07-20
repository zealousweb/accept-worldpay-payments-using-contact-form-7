<?php
/**
 * CF7WPA_Admin Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Contact Form 7 - Worldpay Extension
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7WPA_Admin' ) ) {

	/**
	 * The CF7WPA_Admin Class
	 */
	class CF7WPA_Admin {

		var $action = null,
		    $filter = null;

		function __construct() {
            add_action( 'admin_menu',    array( $this, 'action__cf7wpa_admin_menu' ) );

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
         * Action: admin_menu
         *
         * - Used for removing the "submitdiv" metabox "cf7wpa_data" CPT
         *
         * @method action__cf7wpa_admin_menu
         */
        function action__cf7wpa_admin_menu() {
            remove_meta_box( 'submitdiv', 'cf7wpa_data', 'side' );
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


	}

	add_action( 'plugins_loaded' , function() {
		CF7WPA()->admin = new CF7WPA_Admin;
	} );
}

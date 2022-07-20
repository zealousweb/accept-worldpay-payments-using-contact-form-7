<?php
/**
 * CF7WPA_Admin_Filter Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Contact Form 7 - Worldpay Extension
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7WPA_Admin_Filter' ) ) {

	/**
	 *  The CF7WPA_Admin_Filter Class
	 */
	class CF7WPA_Admin_Filter {

		function __construct() {

			// Adding Worldpay settings tab
			add_filter( 'wpcf7_editor_panels', array( $this, 'filter__wpcf7_editor_panels' ), 10, 3 );
            add_filter( 'post_row_actions',    array( $this, 'filter__post_row_actions' ), 10, 3 );
            add_filter( 'plugin_action_links',array( $this,'filter__admin_plugin_links'), 10, 2 ); 

            add_filter( 'manage_edit-cf7wpa_data_sortable_columns', array( $this, 'filter__manage_cf7wpa_data_sortable_columns' ), 10, 3 );
            add_filter( 'manage_cf7wpa_data_posts_columns',         array( $this, 'filter__manage_cf7wpa_data_posts_columns' ), 10, 3 );
            add_filter( 'bulk_actions-edit-cf7wpa_data',            array( $this, 'filter__bulk_actions_edit_cf7wpa_data' ) );

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
		 * Worldpay tab
		 * Adding tab in contact form 7
		 *
		 * @param $panels
		 *
		 * @return array
		 */
		public function filter__wpcf7_editor_panels( $panels ) {

			$panels[ 'worldpay-extension' ] = array(
				'title'    => __( 'Worldpay Addons', 'contact-form-7-worldpay-addon' ),
				'callback' => array( $this, 'cf7wpap_admin_after_additional_settings' )
			);

			return $panels;
		}

        /**
         * Filter: post_row_actions
         *
         * - Used to modify the post list action buttons.
         *
         * @method filter__post_row_actions
         *
         * @param  array $actions
         *
         * @return array
         */
        function filter__post_row_actions( $actions ) {

            if ( get_post_type() === 'cf7wpa_data' ) {
                unset( $actions['view'] );
                unset( $actions['inline hide-if-no-js'] );
            }

            return $actions;
        }

        /**
         * Filter: manage_edit-cf7wpa_data_sortable_columns
         *
         * - Used to add the sortable fields into "cf7wpa_data" CPT
         *
         * @method filter__manage_cf7wpa_data_sortable_columns
         *
         * @param  array $columns
         *
         * @return array
         */
        function filter__manage_cf7wpa_data_sortable_columns( $columns ) {
            $columns['form_id'] = '_form_id';
            $columns['transaction_status'] = '_transaction_status';
            $columns['total'] = '_total';
            return $columns;
        }

        /**
         * Filter: manage_cf7wpa_data_posts_columns
         *
         * - Used to add new column fields for the "cf7wpa_data" CPT
         *
         * @method filter__manage_cf7wpa_data_posts_columns
         *
         * @param  array $columns
         *
         * @return array
         */
        function filter__manage_cf7wpa_data_posts_columns( $columns ) {
            unset( $columns['date'] );
            $columns['form_id'] = __( 'Form ID', 'contact-form-7-worldpay-addon' );
            $columns['transaction_status'] = __( 'Transaction Status', 'contact-form-7-worldpay-addon' );
            $columns['total'] = __( 'Total Amount', 'contact-form-7-worldpay-addon' );
            $columns['date'] = __( 'Submitted Date', 'contact-form-7-worldpay-addon' );
            return $columns;
        }

        /**
         * Filter: bulk_actions-edit-cf7wpa_data
         *
         * - Add/Remove bulk actions for "cf7wpa_data" CPT
         *
         * @method filter__bulk_actions_edit_cf7wpa_data
         *
         * @param  array $actions
         *
         * @return array
         */
        function filter__bulk_actions_edit_cf7wpa_data( $actions ) {
            unset( $actions['edit'] );
            return $actions;
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
		 * Adding Worldpay fields in Worldpay tab
		 *
		 * @param $cf7
		 */
		public function cf7wpap_admin_after_additional_settings( $cf7 ) {

			wp_enqueue_script( CF7WPA_PREFIX . '_admin_js' );

			require_once( CF7WPA_DIR .  '/inc/admin/template/' . CF7WPA_PREFIX . '.template.php' );

		}

        /**
        * add documentation link in plugins
        */

        function filter__admin_plugin_links( $links, $file ) {
            if ( $file != CF7WPA_PLUGIN_BASENAME ) {
                return $links;
            }
        
            if ( ! current_user_can( 'wpcf7_read_contact_forms' ) ) {
                return $links;
            }

            $documentLink = '<a target="_blank" href="'.CF7WPA_DOCUMENT.'">' . __( 'Document Link', 'contact-form-7-worldpay-addon' ) . '</a>';
            array_unshift( $links , $documentLink);

            $Supportlink = '<a target="_blank" href="'.CF7WPA_SUPPORT.'">' . __( 'Support Link', 'contact-form-7-worldpay-addon' ) . '</a>';
            array_unshift( $links , $Supportlink);
        
            return $links;
        }

	}
    add_action( 'plugins_loaded' , function() {
        CF7WPA()->admin->filter = new CF7WPA_Admin_Filter;
    } );


}

<?php
/**
 * CF7WPA_Admin_Action Class
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @package Contact Form 7 - Worldpay Addons
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7WPA_Admin_Action' ) ){

	/**
	 *  The CF7WPA_Admin_Action Class
	 */
	class CF7WPA_Admin_Action {

		function __construct()  {

			add_action( 'init',  array( $this, 'action__init' ) );
            add_action( 'init',           array( $this, 'action__init_99' ), 99 );
            add_action( 'add_meta_boxes', array( $this, 'action__add_meta_boxes' ) );


			// Save settings of contact form 7 admin
			add_action( 'wpcf7_save_contact_form', array( $this, 'action__wpcf7_save_contact_form' ), 20, 2 );

            add_action( 'manage_cf7wpa_data_posts_custom_column', array( $this, 'action__manage_cf7wpa_data_posts_custom_column' ), 10, 2 );

            add_action( 'pre_get_posts',         array( $this, 'action__pre_get_posts' ) );
            add_action( 'restrict_manage_posts', array( $this, 'action__restrict_manage_posts' ) );
            add_action( 'parse_query',           array( $this, 'action__parse_query' ) );

            add_action( CF7WPA_PREFIX . '/postbox', array( $this, 'action__acf7wpa_postbox' ) );

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
		function action__init() {
			wp_register_script( CF7WPA_PREFIX . '_admin_js', CF7WPA_URL . 'assets/js/admin.min.js', array( 'jquery-core' ), CF7WPA_VERSION );
			 wp_register_style( CF7WPA_PREFIX . '_admin_css', CF7WPA_URL . 'assets/css/admin.min.css', array(), CF7WPA_VERSION );

             wp_register_style( 'select2', CF7WPA_URL . 'assets/css/select2.min.css', array(), '4.0.7' );
            wp_register_script( 'select2', CF7WPA_URL . 'assets/js/select2.min.js', array( 'jquery-core' ), '4.0.7' );
		}

        /**
         * Action: init 99
         *
         * - Used to perform the CSV export functionality.
         *
         */
        function action__init_99() {
            if (
                isset( $_REQUEST['export_csv'] )
                && isset( $_REQUEST['form-id'] )
                && !empty( $_REQUEST['form-id'] )
            ) {
                $form_id = sanitize_text_field( $_REQUEST['form-id'] );

                if ( 'all' == $form_id && $_REQUEST == 'cf7wpa_data') {
                    add_action( 'admin_notices', array( $this, 'action__cf7wp_admin_notices_export' ) );
                    return;
                }

                $args = array(
                    'post_type' => 'cf7wpa_data',
                    'posts_per_page' => -1
                );

                $exported_data = get_posts( $args );

                if ( empty( $exported_data ) )
                    return;

                /** CSV Export **/
                $filename = 'cf7wpa-' . $form_id . '-' . time() . '.csv';

                $header_row = array(
                    '_form_id'            => 'Form ID/Name',
                    '_payer_email'        => 'Payer Email Address',
                    '_transaction_id'     => 'Transaction ID',
                    '_invoice_no'         => 'Invoice ID',
                    '_amount'             => 'Amount',
                    '_quantity'           => 'Quantity',
                    '_total'              => 'Total',
                    '_currency'           => 'Currency code',
                    '_submit_time'        => 'Submit Time',
                    '_request_Ip'         => 'Request IP',
                    '_transaction_status' => 'Transaction status'
                );

                $data_rows = array();

                if ( !empty( $exported_data ) ) {
                    foreach ( $exported_data as $entry ) {

                        $row = array();

                        if ( !empty( $header_row ) ) {
                            foreach ( $header_row as $key => $value ) {

                                if (
                                    $key != '_transaction_status'
                                    && $key != '_submit_time'
                                ) {

                                    $row[$key] = __(
                                        (
                                        (
                                            '_form_id' == $key
                                            && !empty( get_the_title( get_post_meta( $entry->ID, $key, true ) ) )
                                        )
                                            ? get_the_title( get_post_meta( $entry->ID, $key, true ) )
                                            : get_post_meta( $entry->ID, $key, true )
                                        )
                                    );

                                } else if ( $key == '_transaction_status' ) {

                                    $row[$key] = __(
                                        (
                                        (
                                            !empty( CF7WPA()->lib->response_status )
                                            && array_key_exists( get_post_meta( $entry->ID , $key, true ), CF7WPA()->lib->response_status )
                                        )
                                            ? CF7WPA()->lib->response_status[get_post_meta( $entry->ID , $key, true )]
                                            : get_post_meta( $entry->ID , $key, true )
                                        )
                                    );

                                } else if ( '_submit_time' == $key ) {
                                    $row[$key] = __( get_the_date( 'd, M Y H:i:s', $entry->ID ) );
                                }
                            }
                        }

                        /* form_data */

                        $amount = (
                        (
                            !empty( $form_id )
                        )
                            ? get_post_meta( $form_id, CF7WPA_META_PREFIX . 'amount', true )
                            : ''
                        ) ;

                        $quantity = (
                        (
                            !empty( $form_id )
                        )
                            ? get_post_meta( $form_id, CF7WPA_META_PREFIX . 'quantity', true )
                            : ''
                        ) ;

                        $data = unserialize( get_post_meta( $entry->ID, '_form_data', true ) );
                        $hide_data = apply_filters( CF7WPA_PREFIX . '/hide-display', array( '_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post' ) );
                        foreach ( $hide_data as $key => $value ) {
                            if ( array_key_exists( $value, $data ) ) {
                                unset( $data[$value] );
                            }
                        }

                        if ( !empty( $data ) ) {
                            foreach ( $data as $key => $value ) {
                                if ( strpos( $key, 'authorize-' ) === false ) {

                                    if ( !in_array( $key, $header_row ) ) {
                                        $header_row[$key] = $key;
                                    }

                                    if(
                                        !empty( $amount )
                                        && is_array($value)
                                        && $key == $amount
                                    ) {
                                        $value2 = array_sum( $value );
                                    }elseif (
                                        !empty( $quantity )
                                        && is_array($value)
                                        && $key == $quantity
                                    ){
                                        $value2 = array_sum( $value );
                                    } else if ( is_array( $value ) ) {
                                        $value2 = implode (", ", $value );
                                    } else {
                                        $value2 = $value;
                                    }

                                    $row[$key] = __( $value2 );

                                }
                            }
                        }

                        $data_rows[] = $row;

                    }
                }

                ob_start();

                $fh = @fopen( 'php://output', 'w' );
                fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
                header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
                header( 'Content-Description: File Transfer' );
                header( 'Content-type: text/csv' );
                header( "Content-Disposition: attachment; filename={$filename}" );
                header( 'Expires: 0' );
                header( 'Pragma: public' );
                fputcsv( $fh, $header_row );
                foreach ( $data_rows as $data_row ) {
                    fputcsv( $fh, $data_row );
                }
                fclose( $fh );

                ob_end_flush();
                die();

            }
        }

        /**
         * Action: add_meta_boxes
         *
         * - Add mes boxes for the CPT "cf7wpa_data"
         */
        function action__add_meta_boxes() {
            add_meta_box( 'cfwpa-data', __( 'From Data', 'contact-form-7-worldpay-addon' ), array( $this, 'cfwpa_show_from_data' ), 'cf7wpa_data', 'normal', 'high' );
            add_meta_box( 'cfwpa-help', __( 'Do you need help for configuration?', 'contact-form-7-worldpay-addon' ), array( $this, 'cfwpa_show_help_data' ), 'cf7wpa_data', 'side', 'high' );
        }

      

		/**
		 * Save Worldpay field settings
		 */
		public function action__wpcf7_save_contact_form( $WPCF7_form ) {

            $wpcf7 = WPCF7_ContactForm::get_current();

            if ( !empty( $wpcf7 ) ) {
                $post_id = $wpcf7->id;
            }

            $form_fields = array(
                CF7WPA_META_PREFIX . 'use_worldpay',
                CF7WPA_META_PREFIX . 'mode_sandbox',
                CF7WPA_META_PREFIX . 'debug',
                CF7WPA_META_PREFIX . 'installation_id',
                CF7WPA_META_PREFIX . 'amount',
                CF7WPA_META_PREFIX . 'quantity',
                CF7WPA_META_PREFIX . 'email',
                CF7WPA_META_PREFIX . 'description',
                CF7WPA_META_PREFIX . 'currency',
                CF7WPA_META_PREFIX . 'success_returnurl',
                CF7WPA_META_PREFIX . 'cancel_returnurl',

            
            );

            /**
             * Save custom form setting fields
             *
             * @var array $form_fields
             */
            $form_fields = apply_filters( CF7WPA_PREFIX . '/save_fields', $form_fields );

            if(!get_option('_exceed_cf7wpa_l')){
                add_option('_exceed_cf7wpa_l', 'cf7wpa10');
            }

            if ( !empty( $form_fields ) ) {
                foreach ( $form_fields as $key ) {
                    $keyval = sanitize_text_field( $_REQUEST[ $key ] );
                    update_post_meta( $post_id, $key, $keyval );
                }
            }

        }


        /**
         * Action: manage_cf7wpa_data_posts_custom_column
         *
         * @method action__manage_cf7wpa_data_posts_custom_column
         *
         * @param  string  $column
         * @param  int     $post_id
         *
         * @return string
         */
        function action__manage_cf7wpa_data_posts_custom_column( $column, $post_id ) {
            $data_ct = $this->cfwpa_check_data_ct( sanitize_text_field( $post_id ) );

            switch ( $column ) {

                case 'form_id' :
                    echo (
                    !empty( get_post_meta( $post_id , '_form_id', true ) )
                        ? (
                    !empty( get_the_title( get_post_meta( $post_id , '_form_id', true ) ) )
                        ? get_the_title( get_post_meta( $post_id , '_form_id', true ) )
                        : get_post_meta( $post_id , '_form_id', true )
                    )
                        : ''
                    );
                    break;

                case 'transaction_status' :
                    if( $data_ct ){
                        echo '<a href='.CF7WPA_PRODUCT_LINK.' target="_blank">To unlock more features consider upgrading to PRO</a>';
                    }else{
                         echo (
                        !empty( get_post_meta( $post_id , '_transaction_status', true ) )
                            ? (
                        (
                            !empty( CF7WPA()->lib->response_status )
                            && array_key_exists( get_post_meta( $post_id , '_transaction_status', true ), CF7WPA()->lib->response_status)
                        )
                            ? CF7WPA()->lib->response_status[get_post_meta( $post_id , '_transaction_status', true )]
                            : get_post_meta( $post_id , '_transaction_status', true )
                        )
                            : ''
                        );
                    }
                   
                    break;

                case 'total' :
                    if( $data_ct ){
                        echo '<a href='.CF7WPA_PRODUCT_LINK.' target="_blank">To unlock more features consider upgrading to PRO</a>';
                    }else{
                        echo ( !empty( get_post_meta( $post_id , '_total', true ) ) ? get_post_meta( $post_id , '_total', true ) : '' ) .' ' .
                        ( !empty( get_post_meta( $post_id , '_currency', true ) ) ? get_post_meta( $post_id , '_currency', true ) : '' );
                    }
                  
                    break;

            }
        }

        /**
         * Action: pre_get_posts
         *
         * - Used to perform order by into CPT List.
         *
         * @method action__pre_get_posts
         *
         * @param  object $query WP_Query
         */
        function action__pre_get_posts( $query ) {

            if (
                ! is_admin()
                || !in_array ( $query->get( 'post_type' ), array( 'cf7wpa_data' ) )
            )
                return;

            $orderby = $query->get( 'orderby' );

            if ( '_transaction_status' == $orderby ) {
                $query->set( 'meta_key', '_transaction_status' );
                $query->set( 'orderby', 'meta_value_num' );
            }

            if ( '_form_id' == $orderby ) {
                $query->set( 'meta_key', '_form_id' );
                $query->set( 'orderby', 'meta_value_num' );
            }

            if ( '_total' == $orderby ) {
                $query->set( 'meta_key', '_total' );
                $query->set( 'orderby', 'meta_value_num' );
            }
        }

        /**
         * Action: restrict_manage_posts
         *
         * - Used to creat filter by form and export functionality.
         *
         * @method action__restrict_manage_posts
         *
         * @param  string $post_type
         */
        function action__restrict_manage_posts( $post_type ) {

            if ( 'cf7wpa_data' != $post_type ) {
                return;
            }

            $posts = get_posts(
                array(
                    'post_type'        => 'wpcf7_contact_form',
                    'post_status'      => 'publish',
                    'suppress_filters' => false,
                    'posts_per_page'   => -1
                )
            );

            if ( empty( $posts ) ) {
                return;
            }

            $selected = ( isset( $_GET['form-id'] ) ? sanitize_text_field($_GET['form-id']) : '' );

            echo '<select name="form-id" id="form-id">';
            echo '<option value="all">' . __( 'All Forms', 'contact-form-7-worldpay-addon' ) . '</option>';
            foreach ( $posts as $post ) {
                $isItWorldpay = get_post_meta( $post->ID, CF7WPA_META_PREFIX . 'use_worldpay', true); 
                if(!empty($isItWorldpay)){
                    echo '<option value="' . $post->ID . '" ' . selected( $selected, $post->ID, false ) . '>' . $post->post_title  . '</option>';
                }
            }
            echo '</select>';

            echo '<input type="submit" id="doaction2" name="export_csv" class="button action" value="Export CSV">';

        }

        /**
         * Action: parse_query
         *
         * - Filter data by form id.
         *
         * @method action__parse_query
         *
         * @param  object $query WP_Query
         */
        function action__parse_query( $query ) {
            if (
                ! is_admin()
                || !in_array ( $query->get( 'post_type' ), array( 'cf7wpa_data' ) )
            )
                return;

            if (
                is_admin()
                && isset( $_GET['form-id'] )
                && 'all' != $_GET['form-id']
            ) {
                $query->query_vars['meta_key']     = '_form_id';
                $query->query_vars['meta_value']   = sanitize_text_field($_GET['form-id']);
                $query->query_vars['meta_compare'] = '=';
            }

        }

        /**
         * Action: admin_notices
         *
         * - Added use notice when trying to export without selecting the form.
         *
         * @method action__cf7wp_admin_notices_export
         */
        function action__cf7wp_admin_notices_export() {
            echo '<div class="error">' .
                '<p>' .
                __( 'Please select Form to export.', 'contact-form-7-worldpay-addon' ) .
                '</p>' .
                '</div>';
        }

        /**
         * Action: CF7WPA_PREFIX /postbox
         *
         * - Added metabox for the setting fields in backend.
         *
         * @method action__acf7wpa_postbox
         */
        function action__acf7wpa_postbox() {

            echo '<div id="configuration-help" class="postbox">' .
                apply_filters(
                    CF7WPA_PREFIX . '/help/postbox',
                    '<h3>' . __( 'Do you need help for configuration?', 'contact-form-7-worldpay-addon' ) . '</h3>' .
                    '<p></p>' .
                    '<ol>' .
                    '<li><a href="https://www.zealousweb.com/documentation/wordpress-plugins-documentation/accept-worldpay-payments-using-contact-form-7/" target="_blank">Refer the document.</a></li>' .
                    '<li><a href="https://www.zealousweb.com/contact/" target="_blank">Contact Us</a></li>' .
                    '<li><a href="mailto:opensource@zealousweb.com" target="_blank">Support</a></li>' .
                    '</ol>'
                ) .
                '</div>';
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
         * - Used to display the form data in CPT detail page.
         *
         * @method cfwpa_show_from_data
         *
         * @param  object $post WP_Post
         */
        function cfwpa_show_from_data( $post ) {

            $fields = CF7WPA()->lib->data_fields;
            $form_id = get_post_meta( $post->ID, '_form_id', true );

            $data_ct = $this->cfwpa_check_data_ct( $post->ID );

            if( $data_ct ) {

                echo '<table><tbody>'.
                '<style>.inside-field th{ text-align: left; }</style>';
                    echo'<tr class="inside-field"><th scope="row">You are using Free Accept Worldpay Payments Using Contact Form 7 - no license needed. Enjoy! 🙂‚</th></tr>';
                    echo'<tr class="inside-field"><th scope="row"><a href='.CF7WPA_PRODUCT_LINK.' target="_blank">To unlock more features consider upgrading to PRO.</a></th></tr>';
                echo '</tbody></table>';

            }else{

                echo '<table class="cf7wpa-box-data form-table">' .
                    '<style>.inside-field td, .inside-field th{ padding-top: 5px; padding-bottom: 5px;}</style>';

                if ( !empty( $fields ) ) {
                    if ( array_key_exists( '_transaction_response', $fields ) && empty( get_post_meta( $form_id, CF7WPA_META_PREFIX . 'debug', true )  ) ) {
                        unset( $fields['_transaction_response'] );
                    }

                    $attachment = ( !empty( get_post_meta( $post->ID, '_attachment', true ) ) ? unserialize( get_post_meta( $post->ID, '_attachment', true ) ) : '' );
                    $root_path = get_home_path();

                    foreach ( $fields as $key => $value ) {

                        if (
                            !empty( get_post_meta( $post->ID, $key, true ) )
                            && $key != '_form_data'
                            && $key != '_transaction_response'
                            && $key != '_transaction_status'
                        ) {

                            $val = get_post_meta( $post->ID, $key, true );

                            echo '<tr class="form-field">' .
                                '<th scope="row">' .
                                '<label for="hcf_author">' . __( sprintf( '%s', $value ), 'contact-form-7-worldpay-addon' ) . '</label>' .
                                '</th>' .
                                '<td>' .
                                (
                                (
                                    '_form_id' == $key
                                    && !empty( get_the_title( get_post_meta( $post->ID, $key, true ) ) )
                                )
                                    ? get_the_title( get_post_meta( $post->ID, $key, true ) )
                                    : get_post_meta( $post->ID, $key, true )
                                ) .
                                '</td>' .
                                '</tr>';

                        } else if (
                            !empty( get_post_meta( $post->ID, $key, true ) )
                            && $key == '_transaction_status'
                        ) {

                            echo '<tr class="form-field">' .
                                '<th scope="row">' .
                                '<label for="hcf_author">' . __( sprintf( '%s', $value ), 'contact-form-7-worldpay-addon' ) . '</label>' .
                                '</th>' .
                                '<td>' .
                                (
                                (
                                    !empty( CF7WPA()->lib->response_status )
                                    && array_key_exists( get_post_meta( $post->ID , $key, true ), CF7WPA()->lib->response_status )
                                )
                                    ? CF7WPA()->lib->response_status[get_post_meta( $post->ID , $key, true )]
                                    : get_post_meta( $post->ID , $key, true )
                                ) .
                                '</td>' .
                                '</tr>';

                        } else if (
                            !empty( get_post_meta( $post->ID, $key, true ) )
                            && $key == '_form_data'
                        ) {

                            echo '<tr class="form-field">' .
                                '<th scope="row">' .
                                '<label for="hcf_author">' . __( sprintf( '%s', $value ), 'contact-form-7-worldpay-addon' ) . '</label>' .
                                '</th>' .
                                '<td>' .
                                '<table>';

                            $data = get_post_meta( $post->ID, $key, true );
                            $hide_data = apply_filters( CF7WPA_PREFIX . '/hide-display', array( '_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post' ) );

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

                            foreach ( $hide_data as $key => $value ) {
                                if ( array_key_exists( $value, $data ) ) {
                                    unset( $data[$value] );
                                }
                            }

                            if ( !empty( $data ) ) {
                                foreach ( $data as $key => $value ) {
                                    if ( strpos( $key, 'authorize-' ) === false ) {

                                        if(
                                            !empty( $amount )
                                            && is_array($value)
                                            && $key == $amount
                                        ) {
                                            $value2 = array_sum( $value );
                                        }elseif (
                                            !empty( $quantity )
                                            && is_array($value)
                                            && $key == $quantity
                                        ){
                                            $value2 = array_sum( $value );
                                        } else if ( is_array( $value ) ) {
                                            $value2 = implode (", ", $value );
                                        } else {
                                            $value2 = $value;
                                        }

                                        echo '<tr class="inside-field">' .
                                            '<th scope="row">' .
                                            __( sprintf( '%s', $key ), 'contact-form-7-worldpay-addon' ) .
                                            '</th>' .
                                            '<td>' .
                                            (
                                            (
                                                !empty( $attachment )
                                                && array_key_exists( $key, $attachment )
    											&& !empty( $attachment[$key] )
                                            )
                                                ? '<a href="' . esc_url( home_url( str_replace( $root_path, '/', $attachment[$key] ) ) ) . '" target="_blank" download>' . __( sprintf( '%s', $value ), 'contact-form-7-worldpay-addon' ) . '</a>'
                                                : __( sprintf( '%s', $value2 ), 'contact-form-7-worldpay-addon' )
                                            ) .
                                            '</td>' .
                                            '</tr>';
                                    }
                                }
                            }

                            echo '</table>' .
                                '</td>
    							</tr>';

                      
                             } 
                            else if (!empty( get_post_meta( $post->ID, $key, true ) ) && $key == '_transaction_response') {  
                                
                                echo '<tr class="form-field">' .
                                    '<th scope="row">' .
                                        '<label for="hcf_author">' . __( sprintf( '%s', $value ), 'contact-form-7-worldpay-addon' ) . '</label>' .
                                    '</th>' .
                                    '<td>' .
                                        '<code style="word-break: break-all;">' .
                                            (
                                                (
                                                    !empty(  get_post_meta( $post->ID , $key, true ) )
                                                    && (
                                                        is_array( get_post_meta( $post->ID , $key, true ) )
                                                        || is_object( get_post_meta( $post->ID , $key, true ) )
                                                    )
                                                )
                                                ? json_encode(  get_post_meta( $post->ID , $key, true ) )
                                                : get_post_meta( $post->ID , $key, true )
                                            ) .
                                        '</code>' .
                                    '</td>' .
                                '</tr>';

                            } 

                    }
                }

                echo '</table>';
            }
        }

        /**
         * - Used to add meta box in CPT detail page.
         */
        function cfwpa_show_help_data() {
            echo '<div id="cf7wpa-data-help">' .
                apply_filters(
                    CF7WPA_PREFIX . '/help/cf7wpa_data/postbox',
                    '<ol>' .
                    '<li><a href="https://www.zealousweb.com/documentation/wordpress-plugins-documentation/accept-worldpay-payments-using-contact-form-7/" target="_blank">Refer the document.</a></li>' .
                    '<li><a href="https://www.zealousweb.com/contact/" target="_blank">Contact Us</a></li>' .
                    '<li><a href="mailto:opensource@zealousweb.com" target="_blank">Support</a></li>' .
                    '</ol>'
                ) .
                '</div>';
        }

        function cfwpa_check_data_ct( $post_id ){
            $data = get_post_meta( $post_id, '_form_data', true );
            if( !empty( get_post_meta( $post_id, '_form_data', true ) ) && isset( $data['_exceed_num_cfwpa'] ) && !empty( $data['_exceed_num_cfwpa'] ) ){
                return $data['_exceed_num_cfwpa'];
            }else{
                return '';
            }
        }


	}

   add_action( 'plugins_loaded' , function() {
        CF7WPA()->admin->action = new CF7WPA_Admin_Action;
    } );

}

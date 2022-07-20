<?php
/**
 * CF7WPA Class
 *
 * Handles the plugin functionality.
 *
 * @package WordPress
 * @package Contact Form 7 - Worldpay Addons
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( !class_exists( 'CF7WPA' ) ) {

    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	/**
	 * The main CF7WPA class
	 */
	class CF7WPA {

		private static $_instance = null;
        private static $private_data = null;

		var $admin = null,
		    $front = null,
		    $lib   = null;

		public static function instance() {

			if ( is_null( self::$_instance ) )
				self::$_instance = new self();

			return self::$_instance;
		}

		function __construct() {
            add_action( 'plugins_loaded', array( $this, 'action__cf7wpa_plugins_loaded' ), 1 );

		}

		function action__cf7wpa_plugins_loaded() {

			if (!class_exists('WPCF7')) {
                add_action( 'admin_notices', array( $this, 'action__admin_notices_deactive' ) );
                deactivate_plugins( CF7WPA_PLUGIN_BASENAME );
                if ( isset( $_GET['activate'] ) ) {
                    unset( $_GET['activate'] );
                }
            }

			if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {


                add_action( 'init', array( $this, 'action__init' ) );

                global $wp_version;

                // Set filter for plugin's languages directory
                $cf7wpa_lang_dir = dirname( CF7WPA_PLUGIN_BASENAME ) . '/languages/';
                $cf7wpa_lang_dir = apply_filters( 'cf7wpa_languages_directory', $cf7wpa_lang_dir );

                // Traditional WordPress plugin locale filter.
                $get_locale = get_locale();

                if ( $wp_version >= 4.7 ) {
                    $get_locale = get_user_locale();
                }

                // Traditional WordPress plugin locale filter
                $locale = apply_filters( 'plugin_locale',  $get_locale, 'contact-form-7-worldpay-addon' );
                $mofile = sprintf( '%1$s-%2$s.mo', 'contact-form-7-worldpay-addon', $locale );

                // Setup paths to current locale file
                $mofile_global = WP_LANG_DIR . '/plugins/' . basename( CF7WPA_DIR ) . '/' . $mofile;

                if ( file_exists( $mofile_global ) ) {
                    // Look in global /wp-content/languages/plugin-name folder
                    load_textdomain( 'contact-form-7-worldpay-addon', $mofile_global );
                } else {
                    // Load the default language files
                    load_plugin_textdomain( 'contact-form-7-worldpay-addon', false, $cf7wpa_lang_dir );
                }

            }

		}

		/**
		 * Function to display admin notice of missing plugin.
		 */
		function action__admin_notices_deactive() {
			echo '<div class="error">' .
				'<p>' .
					sprintf(
						/* translators: Contact Form 7 - Worldpay Extension */
						__( '<p><strong><a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7</a></strong> is required to use <strong>%s</strong>.</p>', 'contact-form-7-worldpay-addon' ),
						'Contact Form 7 - Worldpay Addons'
					) .
				'</p>' .
			'</div>';
		}

        function action__init() {

		    add_rewrite_rule( '^cf7wpa-phpinfo(/(.*))?/?$', 'index.php?cf7wpa-phpinfo=$matches[2]', 'top' );
            flush_rewrite_rules();

            /**
             * Post Type: Worldpay Addons.
             */
            global $wpdb;

            $labels = array(
                'name' => __( 'Worldpay Addons', 'contact-form-7-worldpay-addon' ),
                'singular_name' => __( 'Worldpay Addons', 'contact-form-7-worldpay-addon' ),
            );

            $args = array(
                'label' => __( 'Worldpay Addons', 'contact-form-7-worldpay-addon' ),
                'labels' => $labels,
                'description' => '',
                'public' => false,
                'publicly_queryable' => false,
                'show_ui' => true,
                'delete_with_user' => false,
                'show_in_rest' => false,
                'rest_base' => '',
                'has_archive' => false,
                'show_in_menu' => 'wpcf7',
                'show_in_nav_menus' => false,
                'exclude_from_search' => true,
                'capability_type' => 'post',
                'capabilities' => array(
                    'read' => true,
                    'create_posts'  => false,
                    'publish_posts' => false,
                ),
                'map_meta_cap' => true,
                'hierarchical' => false,
                'rewrite' => false,
                'query_var' => false,
                'supports' => array( 'title' ),
            );

            register_post_type( 'cf7wpa_data', $args );

            $check = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name='worldpay-thank-you'");

            if ($check == '')
            {
                $post = array(
                    'post_name' => 'worldpay-thank-you',
                    'post_status' => 'publish',
                    'post_title' => 'Thank you',
                    'post_type' => 'page',
                    'post_content'   => '[worldpay-transaction-details]',
                    'post_date' => date('Y-m-d H:i:s'),
                );
                $page = wp_insert_post($post, false);
            }
        }

	}
}

function CF7WPA() {
	return CF7WPA::instance();
}

CF7WPA();

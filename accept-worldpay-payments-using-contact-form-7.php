<?php
/**
 * Plugin Name: Accept Worldpay Payments Using Contact Form 7
 * Plugin URL: https://wordpress.org/plugins/accept-worldpay-payments-using-contact-form-7/
 * Description:  This plugin will integrate Worldpay payment gateway for making your payments through Contact Form 7.
 * Version: 1.0
 * Author: ZealousWeb
 * Author URI: https://www.zealousweb.com
 * Developer: The Zealousweb Team
 * Support: opensource@zealousweb.com
 * Text Domain: accept-worldpay-payments-using-contact-form-7
 * Domain Path: /languages
 *
 * Copyright: © 2009-2019 ZealousWeb Technologies.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Basic plugin definitions
 *
 * @package Accept Worldpay Payments Using Contact Form 7
 * @since 1.0
 */



if ( !defined( 'CF7WPA_VERSION' ) ) {
	define( 'CF7WPA_VERSION', '1.0' ); // Version of plugin
}

if ( !defined( 'CF7WPA_FILE' ) ) {
	define( 'CF7WPA_FILE', __FILE__ ); // Plugin File
}

if ( !defined( 'CF7WPA_SITE_URL' ) ) {
	define( 'CF7WPA_SITE_URL', get_site_url() ); // Plugin File
}

if ( !defined( 'CF7WPA_DIR' ) ) {
	define( 'CF7WPA_DIR', dirname( __FILE__ ) ); // Plugin dir
}

if ( !defined( 'CF7WPA_URL' ) ) {
	define( 'CF7WPA_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}

if ( !defined( 'CF7WPA_PLUGIN_BASENAME' ) ) {
	define( 'CF7WPA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // Plugin base name
}

if ( !defined( 'CF7WPA_META_PREFIX' ) ) {
	define( 'CF7WPA_META_PREFIX', 'cf7wpa_' ); // Plugin metabox prefix
}

if ( !defined( 'CF7WPA_PREFIX' ) ) {
	define( 'CF7WPA_PREFIX', 'cf7wpa' ); // Plugin prefix
}

if ( !defined( 'CF7WPA_SUPPORT' ) ) {
	define( 'CF7WPA_SUPPORT', 'https://zealousweb.com/support/' ); // Plugin Support Link
}

if ( !defined( 'CF7WPA_DOCUMENT' ) ) {
	define( 'CF7WPA_DOCUMENT', 'https://www.zealousweb.com/documentation/wordpress-plugins/accept-worldpay-payments-using-contact-form7/' ); // Plugin Document Link
}

if ( !defined( 'CF7WPA_PRODUCT_LINK' ) ) {
	define( 'CF7WPA_PRODUCT_LINK', 'https://www.zealousweb.com/wordpress-plugins/accept-worldpay-payments-using-contact-form-7/' ); // Plugin Product Link
}

/**
 * Initialize the main class
 */
if ( !function_exists( 'CF7WPA' ) ) {

	if ( is_admin() ) {
		require_once( CF7WPA_DIR . '/inc/admin/class.' . CF7WPA_PREFIX . '.admin.php' );
		require_once( CF7WPA_DIR . '/inc/admin/class.' . CF7WPA_PREFIX . '.admin.action.php' );
		require_once( CF7WPA_DIR . '/inc/admin/class.' . CF7WPA_PREFIX . '.admin.filter.php' );
	} else {
		require_once( CF7WPA_DIR . '/inc/front/class.' . CF7WPA_PREFIX . '.front.php' );
		require_once( CF7WPA_DIR . '/inc/front/class.' . CF7WPA_PREFIX . '.front.action.php' );
		require_once( CF7WPA_DIR . '/inc/front/class.' . CF7WPA_PREFIX . '.front.filter.php' );
	}

	require_once( CF7WPA_DIR . '/inc/lib/class.' . CF7WPA_PREFIX . '.lib.php' );

    //Initialize all the things.
    require_once( CF7WPA_DIR . '/inc/class.' . CF7WPA_PREFIX . '.php' );
}

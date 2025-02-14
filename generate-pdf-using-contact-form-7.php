<?php
/**
 * The plugin file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also inc all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              #
 * @since             1.1
 * @package           Cf7_Pdf_Generation
 *
 * @wordpress-plugin
 * Plugin Name:     Generate PDF using Contact Form 7
 * Plugin URI:      https://wordpress.org/plugins/generate-pdf-using-contact-form-7/
 * Description:     Generate PDF using Contact Form 7 Plugin provides an easier way to download document files, open the document file or send as an attachment after the successful form submit.
 * Version:         4.1.3
 * Author:          ZealousWeb
 * Author URI:      https://www.zealousweb.com/
 * Developer: 		The ZealousWeb Team
 * Text Domain:     generate-pdf-using-contact-form-7
 * Domain Path:     /languages
 * Copyright: © 2009-2019 ZealousWeb Technologies.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
* Currently plugin version.
* Start at version 1.0.0 and use SemVer - https://semver.org
*/
if ( !defined( 'Cf7_Pdf_Generation_VERSION' ) ) {
	define( 'Cf7_Pdf_Generation_VERSION', '4.1.3' );
}

/**
* Currently plugin directory path.
*/
if ( !defined( 'WP_CF7_PDF_DIR' ) ) {
	define( 'WP_CF7_PDF_DIR', plugin_dir_path( __FILE__ ) );
}

/**
* Currently plugin file.
*/
if ( !defined( 'WP_CF7_PDF_FILE' ) ) {
	define( 'WP_CF7_PDF_FILE', __FILE__ );
}

/**
* Currently plugin URL.
*/
if ( !defined( 'WP_CF7_PDF_URL' ) ) {
	define( 'WP_CF7_PDF_URL', plugin_dir_url( __FILE__ ) );
}

/**
* Currently Plugin base name.
*/
if ( !defined( 'WP_CF7_PDF_PLUGIN_BASENAME' ) ) {
	define( 'WP_CF7_PDF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

/**
* Currently Plugin prefix.
*/
if ( !defined( 'WP_CF7_PDF_PREFIX' ) ) {
	define( 'WP_CF7_PDF_PREFIX', 'cf7-pdf-generation' );
}

/**
* Check main function is exist or not.
*/
if ( !function_exists( 'Cf7_Pdf_Generation' ) ) {
	/**
	* require include, filter and action files.
	*/	
	require_once( WP_CF7_PDF_DIR . '/inc/class.' . WP_CF7_PDF_PREFIX . '.php' );
	if(is_admin())
	{
		require_once( WP_CF7_PDF_DIR . '/inc/admin/class.' . WP_CF7_PDF_PREFIX . '.admin.php' );
		require_once( WP_CF7_PDF_DIR . '/inc/admin/class.' . WP_CF7_PDF_PREFIX . '.admin.action.php' );
		require_once( WP_CF7_PDF_DIR . '/inc/admin/class.' . WP_CF7_PDF_PREFIX . '.admin.filter.php' );
	}
	else
	{
		require_once( WP_CF7_PDF_DIR . '/inc/front/class.' . WP_CF7_PDF_PREFIX . '.front.php' );
		require_once( WP_CF7_PDF_DIR . '/inc/front/class.' . WP_CF7_PDF_PREFIX . '.front.action.php' );
		require_once( WP_CF7_PDF_DIR . '/inc/front/class.' . WP_CF7_PDF_PREFIX . '.front.filter.php' );
	}
}
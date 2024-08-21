<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.1
 *
 * @package    Cf7_Pdf_Generation
 * @subpackage Cf7_Pdf_Generation/admin
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Cf7_Pdf_Generation_Admin_Action' ) ){

	/**
	*  The Cf7_Pdf_Generation_Admin_Action Class
	*/
	class Cf7_Pdf_Generation_Admin_Action {

		/**
		* Construction
		*/
		function __construct()  {
			add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_styles' ));
			add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_scripts' ));
			add_action( 'admin_menu',array( $this, 'wp_cf7_pdf_add_admin' ));
			add_action( 'admin_print_styles',array( $this, 'wpcf7_pdf_admin_styles' ));
			add_action( 'admin_print_scripts',array( $this, 'wpcf7_pdf_admin_scripts' ));
		}

		/**
		* WP Enqueue Styles
		*/
		function enqueue_styles() {
			if (isset($_GET['page']) && $_GET['page'] === 'wp-cf7-send-pdf' || isset($_POST['cf7_send_form']) && wp_verify_nonce(sanitize_file_name(wp_unslash($_POST['cf7_send_form'])), 'security-cf7-send-pdf')) {
				wp_enqueue_style( 'main-admin-css', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-admin-min.css', array(), 1.1, 'all' );
				wp_enqueue_style( 'codemirror-css', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-codemirror-min.css', array(), 1.1, 'all' );
				wp_enqueue_style( 'codemirror-theme-3024-night', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-3024-night-min.css', array(), 1.1, 'all' );
				wp_enqueue_style( 'jquery-ui-resize', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-jquery-ui-min.css', array(), 1.1, 'all' );
			}
		}

		/**
		* WP Enqueue Scripts
		*/
		function enqueue_scripts() {
			
			if (isset($_GET['page']) && $_GET['page'] === 'wp-cf7-send-pdf' ||isset($_POST['cf7_send_form']) && wp_verify_nonce(sanitize_file_name(wp_unslash($_POST['cf7_send_form'])), 'security-cf7-send-pdf')) {
				wp_enqueue_script( 'codemirror', WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-codemirror-min.js', array( 'jquery' ), 1.1, false );
				wp_enqueue_script( 'codemirror-javascript',WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-codemirror-javascript-min.js', array( 'jquery' ), 1.1, false );
				wp_enqueue_script( 'admin-js', WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-admin-min.js', array( 'jquery' ), 1.2, false );
			}
		}

		/**
		* Function for add menu.
		*/
		function wp_cf7_pdf_add_admin() {
			$capability = apply_filters( 'wpcf7pdf_modify_capability', 'administrator' );

			$addPDF = add_submenu_page( 'wpcf7',
			esc_html(__('PDF with CF7', 'generate-pdf-using-contact-form-7')),
			esc_html(__('PDF with CF7', 'generate-pdf-using-contact-form-7')),
			$capability, 'wp-cf7-send-pdf',
			'wp_cf7_pdf_dashboard_html_page');
		}

		/**
		* WP enqueue script and style for media upload tool.
		*/
		function wpcf7_pdf_admin_scripts() {
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );

			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_register_script('my-upload', WP_CF7_PDF_URL .'assets/js/cf7-pdf-generation-admin-upload-script-min.js', array('jquery','media-upload','thickbox'),1.4,true);
			wp_enqueue_script('my-upload');
			wp_enqueue_script('jquery-ui-resizable');

		}

		/**
		* WP enqueue style thickbox.
		*/
		function wpcf7_pdf_admin_styles() {
			wp_enqueue_style('thickbox');
		}

	}

	/**
	* Function for to require HTML of setting page
	*/
	function wp_cf7_pdf_dashboard_html_page() {
		require  WP_CF7_PDF_DIR . 'inc/templates/cf7-pdf-generation.admin.html.php';
	}

	/**
	* Function for plugin loaded
	*/
	add_action( 'plugins_loaded' , function() {
		new Cf7_Pdf_Generation_Admin_Action;
	} );
}
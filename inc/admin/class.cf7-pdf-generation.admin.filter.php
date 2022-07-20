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

if ( !class_exists( 'Cf7_Pdf_Generation_Admin_Filter' ) ){
	
	/**
	*  The Cf7_Pdf_Generation_Admin_Filter Class
	*/
	class Cf7_Pdf_Generation_Admin_Filter {

		/**
		* Construction
		*/
		function __construct()  {
			add_filter( 'plugin_action_links',array( $this,'cf7_pdf_plugin_action_links'), 10, 2 );	
			add_filter('attachment_fields_to_edit', 'remove_media_upload_fields', 10000, 2);
		}

		

		/**
		* Plugin setting page URL.
		*/
		function cf7_pdf_plugin_action_links( $links, $file ) {
			if ( $file != WP_CF7_PDF_PLUGIN_BASENAME ) {
				return $links;
			}
		
			if ( ! current_user_can( 'wpcf7_read_contact_forms' ) ) {
				return $links;
			}
			
			$settings_link = wpcf7_link(
				menu_page_url( 'wp-cf7-send-pdf', false ),
				esc_html(__( 'Settings', 'Contact-Form-7-PDF-Generation' ))
			);
			array_unshift( $links, $settings_link );

			$documentlink = '<a target="_blank" href="https://www.zealousweb.com/documentation/wordpress-plugins/generate-pdf-using-contact-form-7/"> '. __( 'Document Link', 'cf7-pdf-generation') .'</a>';
			array_unshift( $links, $documentlink );
		
			return $links;
		}
	}

	/**
	*
	*/
	function remove_media_upload_fields( $form_fields, $post ) {
	        unset( $form_fields['url'] );
	        unset( $form_fields['align'] );
	    return $form_fields;
	}

	add_action( 'plugins_loaded' , function() {
		Cf7_Pdf_Generation()->admin->filter = new Cf7_Pdf_Generation_Admin_Filter;
	} );	
}
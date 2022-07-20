<?php
/**
 * Cf7_Pdf_Generation_Front_Filter Class
 *
 * Handles the Frontend Filters.
 *
 * @package WordPress
 * @subpackage 
 * @since 2.4
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Cf7_Pdf_Generation_Front_Filter' ) ) {

	/**
	*  The Cf7_Pdf_Generation_Front_Filter Class
	*/
	class Cf7_Pdf_Generation_Front_Filter {

		function __construct() {
			add_filter( 'upload_mimes', array( $this, 'wpcf7_pdf_add_custom_mime_types' ), 1, 1 );
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
		function wpcf7_pdf_add_custom_mime_types($mimes) {
			return array_merge($mimes, array (
				'pdf' => 'application/pdf'
			));
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

	}

	/**
	*  Load public filter.
	*/
	add_action( 'plugins_loaded' , function() {
		Cf7_Pdf_Generation()->front->filter = new Cf7_Pdf_Generation_Front_Filter;
	} );
}
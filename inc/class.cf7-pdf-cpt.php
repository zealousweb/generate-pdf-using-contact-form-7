<?php
/**
 * Registers the cf7pdf_data submission post type.
 *
 * @package Cf7_Pdf_Generation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Cf7_Pdf_Cpt' ) ) {

	/**
	 * Custom post type for PDF submissions.
	 */
	class Cf7_Pdf_Cpt {

		const POST_TYPE    = 'cf7pdf_data';
		const MENU_PARENT  = 'pdf-with-cf7';

		/**
		 * Bootstrap hooks.
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_post_type' ), 10 );
			add_action( 'init', array( __CLASS__, 'fix_post_type_args' ), 99 );
			add_action( 'admin_menu', array( __CLASS__, 'remove_from_contact_menu' ), 999 );
		}

		/**
		 * Keep CPT out of the Contact Form 7 menu if registered earlier with another parent.
		 */
		public static function fix_post_type_args() {
			global $wp_post_types;

			if ( ! isset( $wp_post_types[ self::POST_TYPE ] ) ) {
				return;
			}

			$wp_post_types[ self::POST_TYPE ]->show_in_menu = false;
			$wp_post_types[ self::POST_TYPE ]->show_ui       = true;
		}

		/**
		 * Register cf7pdf_data (submenus are added manually under PDF with CF7).
		 */
		public static function register_post_type() {
			if ( post_type_exists( self::POST_TYPE ) ) {
				return;
			}

			$labels = array(
				'name'          => __( 'PDF Submissions', 'generate-pdf-using-contact-form-7' ),
				'singular_name' => __( 'PDF Submission', 'generate-pdf-using-contact-form-7' ),
				'not_found'     => __( 'No PDF submissions found.', 'generate-pdf-using-contact-form-7' ),
				'search_items'  => __( 'Search PDF Submissions', 'generate-pdf-using-contact-form-7' ),
			);

			register_post_type(
				self::POST_TYPE,
				array(
					'labels'              => $labels,
					'public'              => false,
					'publicly_queryable'  => false,
					'show_ui'             => true,
					'show_in_menu'        => false,
					'show_in_nav_menus'   => false,
					'show_in_rest'        => false,
					'exclude_from_search' => true,
					'has_archive'         => false,
					'rewrite'             => false,
					'query_var'           => false,
					'hierarchical'        => false,
					'capability_type'     => 'post',
					'capabilities'        => array(
						'create_posts' => 'do_not_allow',
					),
					'map_meta_cap'        => true,
					'supports'            => array( 'title' ),
				)
			);
		}

		/**
		 * Remove cf7pdf_data from Contact Form 7 admin menu.
		 */
		public static function remove_from_contact_menu() {
			remove_submenu_page( 'wpcf7', 'edit.php?post_type=' . self::POST_TYPE );
		}
	}

	Cf7_Pdf_Cpt::init();
}

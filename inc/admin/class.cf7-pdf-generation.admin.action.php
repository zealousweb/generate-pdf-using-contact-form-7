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

		const SETTINGS_PAGE_SLUG = 'wp-cf7-send-pdf';

		/**
		* Construction
		*/
		function __construct()  {
			add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_styles' ));
			add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_scripts' ));
			add_action( 'admin_menu',array( $this, 'register_admin_menu' ), 9 );
			add_action( 'admin_menu',array( $this, 'finalize_admin_menu' ), 999 );
			add_filter( 'parent_file', array( $this, 'set_active_parent_menu' ) );
			add_filter( 'submenu_file', array( $this, 'set_active_submenu' ) );
			add_action( 'wp_ajax_cf7_pdf_live_preview', array( $this, 'ajax_live_preview' ) );
			add_action( 'wp_ajax_cf7_pdf_preview_file', array( $this, 'ajax_preview_file' ) );
			add_action( 'admin_print_styles',array( $this, 'wpcf7_pdf_admin_styles' ));
			add_action( 'admin_print_scripts',array( $this, 'wpcf7_pdf_admin_scripts' ));
		}

		/**
		 * Highlight PDF with CF7 when viewing submission list or edit screen.
		 *
		 * @param string $parent_file Parent menu file.
		 * @return string
		 */
		function set_active_parent_menu( $parent_file ) {
			if ( $this->is_submissions_screen() || $this->is_settings_screen() ) {
				return Cf7_Pdf_Cpt::MENU_PARENT;
			}

			return $parent_file;
		}

		/**
		 * Highlight the correct submenu (Submissions vs Settings).
		 *
		 * @param string $submenu_file Submenu file.
		 * @return string
		 */
		function set_active_submenu( $submenu_file ) {
			if ( $this->is_submissions_screen() ) {
				return 'edit.php?post_type=' . Cf7_Pdf_Cpt::POST_TYPE;
			}

			if ( $this->is_settings_screen() ) {
				return self::SETTINGS_PAGE_SLUG;
			}

			return $submenu_file;
		}

		/**
		 * PDF Submissions list, add, or edit screen.
		 *
		 * @return bool
		 */
		private function is_submissions_screen() {
			global $pagenow, $typenow;

			if ( Cf7_Pdf_Cpt::POST_TYPE === $typenow ) {
				return true;
			}

			if ( isset( $_GET['post_type'] ) && Cf7_Pdf_Cpt::POST_TYPE === sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) {
				return true;
			}

			if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				$post_id = 0;

				if ( isset( $_GET['post'] ) ) {
					$post_id = absint( wp_unslash( $_GET['post'] ) );
				} elseif ( isset( $_POST['post_ID'] ) ) {
					$post_id = absint( wp_unslash( $_POST['post_ID'] ) );
				}

				if ( $post_id && Cf7_Pdf_Cpt::POST_TYPE === get_post_type( $post_id ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * PDF with CF7 Settings page.
		 *
		 * @return bool
		 */
		private function is_settings_screen() {
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

			return self::SETTINGS_PAGE_SLUG === $page;
		}

		/**
		 * Whether current screen belongs to this plugin admin.
		 *
		 * @return bool
		 */
		private function is_plugin_admin_screen() {
			if ( ! is_admin() ) {
				return false;
			}

			return $this->is_submissions_screen() || $this->is_settings_screen();
		}

		/**
		* WP Enqueue Styles
		*/
		function enqueue_styles() {
			$nonce_ok = isset( $_POST['cf7_send_form'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cf7_send_form'] ) ), 'security-cf7-send-pdf' );

			if ( $this->is_plugin_admin_screen() || $nonce_ok ) {
				wp_enqueue_style( 'main-admin-css', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-admin-min.css', array(), Cf7_Pdf_Generation_VERSION, 'all' );
				wp_enqueue_style( 'codemirror-css', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-codemirror-min.css', array(), Cf7_Pdf_Generation_VERSION, 'all' );
				wp_enqueue_style( 'codemirror-theme-3024-night', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-3024-night-min.css', array(), Cf7_Pdf_Generation_VERSION, 'all' );
				wp_enqueue_style( 'jquery-ui-resize', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-jquery-ui-min.css', array(), Cf7_Pdf_Generation_VERSION, 'all' );
				wp_enqueue_style( 'cf7-pdf-admin-features', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-admin-features.css', array(), Cf7_Pdf_Generation_VERSION, 'all' );
			}
		}

		/**
		* WP Enqueue Scripts
		*/
		function enqueue_scripts() {
			$nonce_ok = isset( $_POST['cf7_send_form'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cf7_send_form'] ) ), 'security-cf7-send-pdf' );

			if ( $this->is_plugin_admin_screen() || $nonce_ok ) {
				wp_enqueue_script( 'codemirror', WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-codemirror-min.js', array( 'jquery' ), Cf7_Pdf_Generation_VERSION, false );
				wp_enqueue_script( 'codemirror-javascript',WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-codemirror-javascript-min.js', array( 'jquery' ), Cf7_Pdf_Generation_VERSION, false );
				wp_enqueue_script( 'admin-js', WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-admin-min.js', array( 'jquery' ), Cf7_Pdf_Generation_VERSION, false );

				if ( $this->is_settings_screen() ) {
					wp_enqueue_script(
						'cf7-pdf-admin-features',
						WP_CF7_PDF_URL . 'assets/js/cf7-pdf-admin-features.js',
						array( 'jquery', 'admin-js', 'my-upload' ),
						Cf7_Pdf_Generation_VERSION,
						true
					);

					$form_id = 0;
					if ( isset( $_POST['cf7_idform'] ) ) {
						$form_id = absint( wp_unslash( $_POST['cf7_idform'] ) );
					} elseif ( isset( $_GET['cf7_idform'] ) ) {
						$form_id = absint( wp_unslash( $_GET['cf7_idform'] ) );
					}

					wp_localize_script(
						'cf7-pdf-admin-features',
						'cf7PdfAdminFeatures',
						array(
							'previewNonce'       => wp_create_nonce( 'cf7_pdf_live_preview' ),
							'formId'             => $form_id,
							'i18n'               => array(
								'preview'      => __( 'Preview PDF', 'generate-pdf-using-contact-form-7' ),
								'generating'   => __( 'Generating preview…', 'generate-pdf-using-contact-form-7' ),
								'selectForm'   => __( 'Select a contact form first.', 'generate-pdf-using-contact-form-7' ),
								'error'        => __( 'Preview failed. Please try again.', 'generate-pdf-using-contact-form-7' ),
							),
						)
					);
				}
			}
		}

		/**
		 * AJAX: generate live PDF preview.
		 */
		function ajax_live_preview() {
			check_ajax_referer( 'cf7_pdf_live_preview', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'generate-pdf-using-contact-form-7' ) ), 403 );
			}

			$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;

			if ( ! $form_id ) {
				wp_send_json_error( array( 'message' => __( 'Invalid form.', 'generate-pdf-using-contact-form-7' ) ) );
			}

			$settings = array();
			if ( isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ) {
				$settings = wp_unslash( $_POST['settings'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$settings = $this->sanitize_preview_settings( $settings );
			}

			$plain_password = '';
			if ( isset( $_POST['preview_password'] ) ) {
				$plain_password = sanitize_text_field( wp_unslash( $_POST['preview_password'] ) );
			}

			$result = Cf7_Pdf_Pdf_Builder::generate_preview_pdf( $form_id, $settings, $plain_password );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			$file  = $result['file'];
			$nonce = wp_create_nonce( 'cf7_pdf_preview_file_' . $file );

			wp_send_json_success(
				array(
					'preview_url' => add_query_arg(
						array(
							'action' => 'cf7_pdf_preview_file',
							'file'   => rawurlencode( $file ),
							'nonce'  => $nonce,
						),
						admin_url( 'admin-ajax.php' )
					),
				)
			);
		}

		/**
		 * AJAX: stream preview PDF file.
		 */
		function ajax_preview_file() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'generate-pdf-using-contact-form-7' ), '', array( 'response' => 403 ) );
			}

			$file = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';

			if ( '' === $file || ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'cf7_pdf_preview_file_' . $file ) ) {
				wp_die( esc_html__( 'Invalid preview request.', 'generate-pdf-using-contact-form-7' ), '', array( 'response' => 403 ) );
			}

			$upload_dir = wp_upload_dir();
			$path       = $upload_dir['basedir'] . '/cf7-pdf-previews/' . $file;

			if ( ! file_exists( $path ) ) {
				wp_die( esc_html__( 'Preview file not found.', 'generate-pdf-using-contact-form-7' ), '', array( 'response' => 404 ) );
			}

			header( 'Content-Type: application/pdf' );
			header( 'Content-Disposition: inline; filename="preview.pdf"' );
			header( 'Content-Length: ' . (string) filesize( $path ) );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			readfile( $path );
			exit;
		}

		/**
		 * @param array $settings Raw settings from AJAX.
		 * @return array
		 */
		private function sanitize_preview_settings( $settings ) {
			$clean = array();

			foreach ( $settings as $key => $value ) {
				$key = preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $key ) );

				if ( '' === $key ) {
					continue;
				}

				if ( 'cf7_pdf_msg_body' === $key ) {
					$clean[ $key ] = sanitize_textarea_field( $value );
				} elseif ( is_array( $value ) ) {
					$clean[ $key ] = array_map( 'sanitize_text_field', $value );
				} else {
					$clean[ $key ] = sanitize_text_field( $value );
				}
			}

			return $clean;
		}

		/**
		 * Top-level PDF with CF7 menu and submenus.
		 */
		function register_admin_menu() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			add_menu_page(
				__( 'PDF with CF7', 'generate-pdf-using-contact-form-7' ),
				__( 'PDF with CF7', 'generate-pdf-using-contact-form-7' ),
				'manage_options',
				Cf7_Pdf_Cpt::MENU_PARENT,
				array( $this, 'redirect_to_submissions' ),
				'dashicons-media-document',
				58
			);

			add_submenu_page(
				Cf7_Pdf_Cpt::MENU_PARENT,
				__( 'PDF Submissions', 'generate-pdf-using-contact-form-7' ),
				__( 'PDF Submissions', 'generate-pdf-using-contact-form-7' ),
				'manage_options',
				'edit.php?post_type=' . Cf7_Pdf_Cpt::POST_TYPE
			);

			add_submenu_page(
				Cf7_Pdf_Cpt::MENU_PARENT,
				__( 'PDF with CF7 Settings', 'generate-pdf-using-contact-form-7' ),
				__( 'PDF with CF7 Settings', 'generate-pdf-using-contact-form-7' ),
				'manage_options',
				self::SETTINGS_PAGE_SLUG,
				'wp_cf7_pdf_dashboard_html_page'
			);

			remove_submenu_page( 'wpcf7', self::SETTINGS_PAGE_SLUG );
		}

		/**
		 * Parent menu opens submissions list; hide Add New on submissions.
		 */
		function finalize_admin_menu() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			remove_submenu_page( Cf7_Pdf_Cpt::MENU_PARENT, Cf7_Pdf_Cpt::MENU_PARENT );

			remove_submenu_page( 'wpcf7', self::SETTINGS_PAGE_SLUG );
			remove_submenu_page( 'wpcf7', 'edit.php?post_type=' . Cf7_Pdf_Cpt::POST_TYPE );

			global $submenu;

			if ( empty( $submenu[ Cf7_Pdf_Cpt::MENU_PARENT ] ) || ! is_array( $submenu[ Cf7_Pdf_Cpt::MENU_PARENT ] ) ) {
				return;
			}

			$order = array(
				'edit.php?post_type=' . Cf7_Pdf_Cpt::POST_TYPE,
				self::SETTINGS_PAGE_SLUG,
			);

			$items = array_values( $submenu[ Cf7_Pdf_Cpt::MENU_PARENT ] );
			$new   = array();

			foreach ( $order as $slug ) {
				foreach ( $items as $i => $row ) {
					if ( isset( $row[2] ) && $row[2] === $slug ) {
						$new[] = $row;
						unset( $items[ $i ] );
						break;
					}
				}
			}

			foreach ( $items as $row ) {
				$new[] = $row;
			}

			$submenu[ Cf7_Pdf_Cpt::MENU_PARENT ] = $new;

			remove_submenu_page( Cf7_Pdf_Cpt::MENU_PARENT, 'post-new.php?post_type=' . Cf7_Pdf_Cpt::POST_TYPE );
		}

		/**
		 * Default landing: PDF Submissions list.
		 */
		function redirect_to_submissions() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'generate-pdf-using-contact-form-7' ) );
			}

			wp_safe_redirect( admin_url( 'edit.php?post_type=' . Cf7_Pdf_Cpt::POST_TYPE ) );
			exit;
		}

		/**
		* WP enqueue script and style for media upload tool.
		*/
		function wpcf7_pdf_admin_scripts() {
			if ( ! $this->is_plugin_admin_screen() ) {
				return;
			}

			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_register_script('my-upload', WP_CF7_PDF_URL .'assets/js/cf7-pdf-generation-admin-upload-script-min.js', array('jquery','media-upload','thickbox'), Cf7_Pdf_Generation_VERSION, true);
			wp_enqueue_script('my-upload');
			wp_enqueue_script('jquery-ui-resizable');
		}

		/**
		* WP enqueue style thickbox.
		*/
		function wpcf7_pdf_admin_styles() {
			if ( ! $this->is_plugin_admin_screen() ) {
				return;
			}

			wp_enqueue_style('thickbox');
		}

	}

	/**
	* Function for to require HTML of setting page
	*/
	function wp_cf7_pdf_dashboard_html_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'generate-pdf-using-contact-form-7' ) );
		}

		require WP_CF7_PDF_DIR . 'inc/templates/cf7-pdf-generation.admin.html.php';
	}

	/**
	* Function for plugin loaded
	*/
	add_action( 'plugins_loaded' , function() {
		new Cf7_Pdf_Generation_Admin_Action;
	} );
}

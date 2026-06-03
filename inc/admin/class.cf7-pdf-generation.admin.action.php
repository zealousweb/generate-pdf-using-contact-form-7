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
		const HELP_PAGE_SLUG      = 'cf7_pdf_help_support';

		/**
		* Construction
		*/
		function __construct()  {
			add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_styles' ));
			add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_scripts' ));
			add_action( 'admin_menu',array( $this, 'register_admin_menu' ), 9 );
			add_action( 'admin_menu', array( $this, 'register_help_support_menu' ), 1000 );
			add_action( 'admin_menu',array( $this, 'finalize_admin_menu' ), 999 );
			add_action( 'admin_init', array( $this, 'redirect_legacy_help_support_page' ) );
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
			if ( $this->is_submissions_screen() || $this->is_settings_screen() || $this->is_help_screen() ) {
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

			if ( $this->is_help_screen() ) {
				return self::HELP_PAGE_SLUG;
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
		 * Help & Support admin page.
		 *
		 * @return bool
		 */
		private function is_help_screen() {
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

			return self::HELP_PAGE_SLUG === $page;
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

			return $this->is_submissions_screen() || $this->is_settings_screen() || $this->is_help_screen();
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
				wp_enqueue_style( 'dashicons' );
				wp_enqueue_style( 'cf7-pdf-admin-features', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-admin-features.css', array( 'dashicons' ), Cf7_Pdf_Generation_VERSION, 'all' );
			}

			if ( $this->is_help_screen() ) {
				$help_css = WP_CF7_PDF_DIR . 'assets/css/cf7-pdf-generation-help-support.css';
				wp_enqueue_style(
					'cf7-pdf-generation-help-support',
					WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-help-support.css',
					array( 'main-admin-css' ),
					is_readable( $help_css ) ? (string) filemtime( $help_css ) : Cf7_Pdf_Generation_VERSION,
					'all'
				);
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

					wp_localize_script(
						'cf7-pdf-admin-features',
						'cf7PdfAdminFeatures',
						array(
							'previewNonce' => wp_create_nonce( 'cf7_pdf_live_preview' ),
							'i18n'         => array(
								'selectForm'           => __( 'Select a contact form first.', 'generate-pdf-using-contact-form-7' ),
								'pdfDisabled'          => __( 'PDF file operation is disabled. Set "Enable PDF file operation?" to Yes and save settings.', 'generate-pdf-using-contact-form-7' ),
								'error'                => __( 'Preview failed. Please try again.', 'generate-pdf-using-contact-form-7' ),
								'showPassword'         => __( 'Show password', 'generate-pdf-using-contact-form-7' ),
								'hidePassword'         => __( 'Hide password', 'generate-pdf-using-contact-form-7' ),
								'enabled'              => __( 'Protection enabled', 'generate-pdf-using-contact-form-7' ),
								'disabled'             => __( 'Protection disabled', 'generate-pdf-using-contact-form-7' ),
								'statusActive'         => __( 'Active', 'generate-pdf-using-contact-form-7' ),
								'statusPending'        => __( 'Password required', 'generate-pdf-using-contact-form-7' ),
								'statusOff'            => __( 'Off', 'generate-pdf-using-contact-form-7' ),
								'passwordsMatch'       => __( 'Passwords match.', 'generate-pdf-using-contact-form-7' ),
								'passwordMismatch'     => __( 'Passwords do not match.', 'generate-pdf-using-contact-form-7' ),
								'passwordRequired'     => __( 'Enter and confirm a PDF password before saving.', 'generate-pdf-using-contact-form-7' ),
								'passwordTooShort'     => sprintf(
									/* translators: %d: minimum password length */
									__( 'Password must be at least %d characters.', 'generate-pdf-using-contact-form-7' ),
									Cf7_Pdf_Submissions::MIN_PDF_PASSWORD_LENGTH
								),
								'strengthWeak'         => __( 'Strength: weak', 'generate-pdf-using-contact-form-7' ),
								'strengthFair'         => __( 'Strength: fair', 'generate-pdf-using-contact-form-7' ),
								'strengthStrong'       => __( 'Strength: strong', 'generate-pdf-using-contact-form-7' ),
								'copiedPassword'       => __( 'Password copied to clipboard.', 'generate-pdf-using-contact-form-7' ),
								'copyFailed'           => __( 'Could not copy. Select the password and copy manually.', 'generate-pdf-using-contact-form-7' ),
								'previewProtected'     => __( 'This preview is password-protected. Enter the PDF password (in the section below) to open it in the viewer.', 'generate-pdf-using-contact-form-7' ),
								'previewNeedsPassword' => __( 'Password protection is on, but no password is available. Enter a password below or save settings first.', 'generate-pdf-using-contact-form-7' ),
								'removeBlocksEnable'   => __( 'Uncheck “Remove password” to set a new password.', 'generate-pdf-using-contact-form-7' ),
								'previewLoading'       => __( 'Generating preview…', 'generate-pdf-using-contact-form-7' ),
								'previewReady'         => __( 'Preview ready', 'generate-pdf-using-contact-form-7' ),
								'previewEmptyTitle'    => __( 'No preview yet', 'generate-pdf-using-contact-form-7' ),
								'previewEmptyText'     => __( 'Click “Generate Preview” to see how your PDF will look using the current settings.', 'generate-pdf-using-contact-form-7' ),
								'showPreview'          => __( 'Show preview', 'generate-pdf-using-contact-form-7' ),
								'hidePreview'          => __( 'Hide preview', 'generate-pdf-using-contact-form-7' ),
								'generatePreview'      => __( 'Generate Preview', 'generate-pdf-using-contact-form-7' ),
								'refreshPreview'       => __( 'Refresh', 'generate-pdf-using-contact-form-7' ),
								'openLivePreview'      => __( 'Open Live PDF Preview', 'generate-pdf-using-contact-form-7' ),
								'openInNewTab'         => __( 'Open in new tab', 'generate-pdf-using-contact-form-7' ),
								'downloadPreview'      => __( 'Download', 'generate-pdf-using-contact-form-7' ),
								'viewSubmissions'      => __( 'View submissions', 'generate-pdf-using-contact-form-7' ),
								'iframeError'          => __( 'The PDF could not be displayed in the browser. Try “Open in new tab” or “Download”.', 'generate-pdf-using-contact-form-7' ),
							),
							'submissionsUrl'  => admin_url( 'edit.php?post_type=' . Cf7_Pdf_Cpt::POST_TYPE ),
							'minPasswordLength' => Cf7_Pdf_Submissions::MIN_PDF_PASSWORD_LENGTH,
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

			$saved_meta = get_post_meta( $form_id, 'cf7_pdf', true );
			$merged     = wp_parse_args( $settings, is_array( $saved_meta ) ? $saved_meta : array() );

			if ( ! Cf7_Pdf_Pdf_Builder::is_pdf_operation_enabled( $merged ) ) {
				wp_send_json_error( array( 'message' => Cf7_Pdf_Pdf_Builder::pdf_operation_disabled_error()->get_error_message() ) );
			}

			$password_enabled = isset( $merged['cf7_opt_is_password_enable'] ) && 'true' === $merged['cf7_opt_is_password_enable'];

			if ( '' === $plain_password && $password_enabled ) {
				$plain_password = Cf7_Pdf_Pdf_Builder::get_password_from_settings( $merged );
			}

			$result = Cf7_Pdf_Pdf_Builder::generate_preview_pdf( $form_id, $settings, $plain_password );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			$file        = $result['file'];
			$preview_url = $this->build_preview_file_url( $file, false );
			$download_url = $this->build_preview_file_url( $file, true );
			$data_info   = isset( $result['data_info'] ) && is_array( $result['data_info'] )
				? $result['data_info']
				: Cf7_Pdf_Pdf_Builder::get_preview_data_info( $form_id, $merged );

			wp_send_json_success(
				array(
					'preview_url'        => $preview_url,
					'download_url'       => $download_url,
					'data_info'          => $data_info,
					'password_protected' => $password_enabled && '' !== $plain_password,
					'needs_password'     => $password_enabled && '' === $plain_password,
				)
			);
		}

		/**
		 * @param string $file     Preview filename.
		 * @param bool   $download Force download disposition.
		 * @return string
		 */
		private function build_preview_file_url( $file, $download = false ) {
			$nonce = wp_create_nonce( 'cf7_pdf_preview_file_' . $file );
			$args  = array(
				'action' => 'cf7_pdf_preview_file',
				'file'   => rawurlencode( $file ),
				'nonce'  => $nonce,
			);

			if ( $download ) {
				$args['download'] = '1';
			}

			return add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
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

			$download = isset( $_GET['download'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['download'] ) );

			header( 'Content-Type: application/pdf' );
			header(
				'Content-Disposition: ' . ( $download ? 'attachment' : 'inline' ) . '; filename="cf7-pdf-preview.pdf"'
			);
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
				Cf7_Pdf_Cpt::get_admin_menu_icon(),
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
		 * Register Help & Support submenu (admin.php?page=cf7_pdf_help_support).
		 */
		function register_help_support_menu() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			add_submenu_page(
				Cf7_Pdf_Cpt::MENU_PARENT,
				__( 'Help & Support', 'generate-pdf-using-contact-form-7' ),
				__( 'Help & Support', 'generate-pdf-using-contact-form-7' ),
				'manage_options',
				self::HELP_PAGE_SLUG,
				array( $this, 'render_help_support_page' )
			);
		}

		/**
		 * Help & Support admin page callback.
		 */
		function render_help_support_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'generate-pdf-using-contact-form-7' ) );
			}

			require WP_CF7_PDF_DIR . 'inc/templates/' . WP_CF7_PDF_PREFIX . '.help.support.php';
		}

		/**
		 * Redirect legacy Help & Support slug to the current page.
		 */
		function redirect_legacy_help_support_page() {
			if ( ! isset( $_GET['page'] ) || 'cf7_pdf-help-support' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=' . self::HELP_PAGE_SLUG ) );
			exit;
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
				self::HELP_PAGE_SLUG,
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

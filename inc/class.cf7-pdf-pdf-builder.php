<?php
/**
 * PDF generation helpers (preview + password protection).
 *
 * @package Cf7_Pdf_Generation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Cf7_Pdf_Pdf_Builder' ) ) {

	/**
	 * Builds preview PDFs and applies mPDF protection.
	 */
	class Cf7_Pdf_Pdf_Builder {

		/**
		 * Apply password protection when enabled in form settings.
		 *
		 * @param \Mpdf\Mpdf $mpdf           mPDF instance.
		 * @param array      $setting_data   Form PDF settings.
		 * @param string     $plain_password Optional plain password (preview).
		 */
		public static function apply_password_protection( $mpdf, $setting_data, $plain_password = '' ) {
			if ( ! self::is_pdf_operation_enabled( $setting_data ) ) {
				return;
			}

			$enabled = isset( $setting_data['cf7_opt_is_password_enable'] ) && 'true' === $setting_data['cf7_opt_is_password_enable'];

			if ( ! $enabled ) {
				return;
			}

			if ( '' === $plain_password ) {
				$plain_password = self::get_password_from_settings( $setting_data );
			}

			$plain_password = (string) $plain_password;

			if ( '' === $plain_password ) {
				return;
			}

			$mpdf->SetProtection( array(), $plain_password, $plain_password );
		}

		/**
		 * @param array $setting_data Form settings.
		 * @return string
		 */
		public static function get_password_from_settings( $setting_data ) {
			if ( empty( $setting_data['cf7_opt_password_pdf'] ) ) {
				return '';
			}

			return Cf7_Pdf_Submissions::decrypt_password( $setting_data['cf7_opt_password_pdf'] );
		}

		/**
		 * Whether PDF generation is enabled for a form ("Enable PDF file operation?" = Yes).
		 *
		 * @param array $settings Form PDF settings from post meta.
		 * @return bool
		 */
		public static function is_pdf_operation_enabled( $settings ) {
			if ( ! is_array( $settings ) || ! isset( $settings['cf7_opt_is_enable'] ) ) {
				return false;
			}

			$flag = $settings['cf7_opt_is_enable'];

			if ( true === $flag || 1 === $flag || '1' === $flag ) {
				return true;
			}

			return ( 'true' === $flag );
		}

		/**
		 * Load PDF settings for a CF7 form.
		 *
		 * @param int $form_id CF7 form post ID.
		 * @return array
		 */
		public static function get_form_pdf_settings( $form_id ) {
			$form_id  = absint( $form_id );
			$settings = $form_id ? get_post_meta( $form_id, 'cf7_pdf', true ) : array();

			return is_array( $settings ) ? $settings : array();
		}

		/**
		 * Whether PDF file operation is enabled for a form ID.
		 *
		 * @param int $form_id CF7 form post ID.
		 * @return bool
		 */
		public static function is_pdf_operation_enabled_for_form( $form_id ) {
			return self::is_pdf_operation_enabled( self::get_form_pdf_settings( $form_id ) );
		}

		/**
		 * @return WP_Error
		 */
		public static function pdf_operation_disabled_error() {
			return new WP_Error(
				'pdf_disabled',
				__( 'PDF file operation is disabled for this form. Set "Enable PDF file operation?" to Yes and save settings.', 'generate-pdf-using-contact-form-7' )
			);
		}

		/**
		 * Prepare HTML body using the same logic as the frontend PDF generator.
		 *
		 * @param array $settings    Form PDF settings.
		 * @param array $posted_data Posted field values.
		 * @param array $context     wpcf7, submission, uploaded_files, form_id.
		 * @return array{html:string,filename_prefix:string}
		 */
		public static function prepare_pdf_html( $settings, $posted_data, $context = array() ) {
			if ( ! self::is_pdf_operation_enabled( $settings ) ) {
				return self::pdf_operation_disabled_error();
			}

			$settings = self::normalize_settings( $settings );

			$wpcf7          = isset( $context['wpcf7'] ) ? $context['wpcf7'] : null;
			$submission     = isset( $context['submission'] ) ? $context['submission'] : null;
			$uploaded_files = isset( $context['uploaded_files'] ) ? (array) $context['uploaded_files'] : array();
			$form_id        = isset( $context['form_id'] ) ? absint( $context['form_id'] ) : 0;

			if ( ! $wpcf7 && $form_id && class_exists( 'WPCF7_ContactForm' ) ) {
				$wpcf7 = WPCF7_ContactForm::get_instance( $form_id );
			}

			$date = date_i18n( get_option( 'date_format' ) );
			$time = date_i18n( get_option( 'time_format' ) );

			$msg_body = ! empty( $settings['cf7_pdf_msg_body'] ) ? $settings['cf7_pdf_msg_body'] : '';

			$cf7_pdf_show_hide_label = ! empty( $settings['cf7_pdf_show_hide_label'] ) ? $settings['cf7_pdf_show_hide_label'] : '';

			if ( isset( $settings['cf7_pdf_filename_prefix'] ) ) {
				$cf7_pdf_filename_prefix = trim( $settings['cf7_pdf_filename_prefix'] );
				$cf7_pdf_filename_prefix = str_replace( ' ', '-', $cf7_pdf_filename_prefix );
				$cf7_pdf_filename_prefix = $cf7_pdf_filename_prefix ? $cf7_pdf_filename_prefix : 'CF7';
			} else {
				$cf7_pdf_filename_prefix = 'CF7';
			}

			$current_time = (string) microtime( true );
			$current_time = str_replace( '.', '-', $current_time );

			foreach ( (array) $posted_data as $key => $value ) {
				if ( ! strstr( $msg_body, (string) $key ) ) {
					continue;
				}

				if ( is_array( $value ) ) {
					$value = implode( '<br/>', $value );
				} else {
					$value = htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
				}

				if ( false !== strpos( $key, 'acceptance' ) ) {
					if ( 1 == $value ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
						$acceptance_value = __( 'accepted', 'generate-pdf-using-contact-form-7' );
					}
					if ( 0 == $value ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
						$acceptance_value = __( 'not accepted', 'generate-pdf-using-contact-form-7' );
					}
					$value = isset( $acceptance_value ) ? $acceptance_value : $value;
				}

				if ( false !== strpos( $msg_body, '[date]' ) ) {
					$msg_body                  = str_replace( '[date]', $date, $msg_body );
					$cf7_pdf_filename_prefix = str_replace( '[date]', $date, $cf7_pdf_filename_prefix );
				}

				if ( false !== strpos( $msg_body, '[time]' ) ) {
					$msg_body                  = str_replace( '[time]', $time, $msg_body );
					$cf7_pdf_filename_prefix = str_replace( '[time]', $time, $cf7_pdf_filename_prefix );
				}

				if ( false !== strpos( $msg_body, '[random-number]' ) ) {
					$msg_body = str_replace( '[random-number]', $current_time, $msg_body );
				}

				if ( false !== strpos( $msg_body, '[_site_url]' ) ) {
					$msg_body = str_replace( '[_site_url]', '<a href="' . esc_url( site_url() ) . '" target="_blank">' . esc_html( site_url() ) . '</a>', $msg_body );
				}

				if ( false !== strpos( $msg_body, '[_site_title]' ) ) {
					$site_title               = get_bloginfo( 'name' );
					$msg_body                 = str_replace( '[_site_title]', $site_title, $msg_body );
					$cf7_pdf_filename_prefix = str_replace( '[_site_title]', $site_title, $cf7_pdf_filename_prefix );
				}

				if ( false !== strpos( $msg_body, '[_site_description]' ) ) {
					$site_description = get_bloginfo( 'description' );
					$msg_body         = str_replace( '[_site_description]', $site_description, $msg_body );
				}

				if ( false !== strpos( $msg_body, '[remote_ip]' ) ) {
					$remote_ip                = self::get_submission_meta( $submission, 'remote_ip', '' );
					$msg_body                 = str_replace( '[remote_ip]', $remote_ip, $msg_body );
					$cf7_pdf_filename_prefix = str_replace( '[remote_ip]', $remote_ip, $cf7_pdf_filename_prefix );
				}

				if ( false !== strpos( $msg_body, '[_post_title]' ) ) {
					$post_id    = self::get_submission_meta( $submission, 'container_post_id', 0 );
					$post_title = $post_id ? get_the_title( $post_id ) : '';
					$msg_body   = str_replace( '[_post_title]', $post_title, $msg_body );
				}

				if ( '' === $value ) {
					if ( 'true' === $cf7_pdf_show_hide_label ) {
						$msg_body = str_replace( '[' . $key . ']', '', $msg_body );
					} else {
						$msg_body = str_replace( '[' . $key . ']', '[noreplace]', $msg_body );
					}
				} else {
					$msg_body                  = str_replace( '[' . $key . ']', $value, $msg_body );
					$cf7_pdf_filename_prefix = str_replace( '[' . $key . ']', $value, $cf7_pdf_filename_prefix );
				}

				if ( $uploaded_files ) {
					foreach ( (array) $uploaded_files as $name => $path ) {
						if ( ! empty( $path ) ) {
							$file_name = basename( is_array( $path ) ? $path[0] : $path );
							$msg_body  = str_replace( '[' . $name . ']', $file_name, $msg_body );
						}
					}
				}
			}

			$msgbody_array = explode( "\n", $msg_body );
			if ( $msgbody_array ) {
				$i = 0;
				foreach ( $msgbody_array as $a ) {
					if ( false !== strpos( $a, 'noreplace' ) ) {
						unset( $msgbody_array[ $i ] );
					}
					++$i;
				}
				$msg_body = implode( "\n", $msgbody_array );
			}

			$html = $msg_body;

			if ( $wpcf7 && $submission ) {
				$html = apply_filters( 'cf7_pdf_message_body', $html, $wpcf7, $submission );
			}

			if ( false === strpos( $html, '<table' ) ) {
				$html = nl2br( $html );
			}

			return array(
				'html'             => $html,
				'filename_prefix'  => $cf7_pdf_filename_prefix,
			);
		}

		/**
		 * Render PDF to disk (shared template + mPDF options).
		 *
		 * @param array  $settings       Form settings.
		 * @param string $html           Body HTML.
		 * @param string $output_path    Absolute file path.
		 * @param string $plain_password Optional preview password.
		 * @return true|WP_Error
		 */
		public static function render_pdf_to_file( $settings, $html, $output_path, $plain_password = '' ) {
			if ( ! self::is_pdf_operation_enabled( $settings ) ) {
				return self::pdf_operation_disabled_error();
			}

			try {
				if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
					require_once WP_CF7_PDF_DIR . 'inc/lib/mpdf/vendor/autoload.php';
				}

				$settings = self::normalize_settings( $settings );
				$mpdf     = self::create_mpdf( $settings );

				$mpdf->autoScriptToLang    = true;
				$mpdf->baseScript          = 1;
				$mpdf->autoVietnamese      = true;
				$mpdf->autoArabic          = true;
				$mpdf->autoLangToFont      = true;
				$mpdf->SetTitle( get_bloginfo( 'name' ) );
				$mpdf->SetCreator( get_bloginfo( 'name' ) );
				$mpdf->ignore_invalid_utf8 = true;

				$cf7_opt_header_pdf_image = isset( $settings['cf7_opt_header_pdf_image'] ) ? $settings['cf7_opt_header_pdf_image'] : '';
				$cf7_opt_max_width_logo   = isset( $settings['cf7_opt_max_width_logo'] ) ? $settings['cf7_opt_max_width_logo'] : '160px';
				$cf7_opt_min_width_logo   = isset( $settings['cf7_opt_min_width_logo'] ) ? $settings['cf7_opt_min_width_logo'] : '85px';
				$cf7_opt_header_text      = isset( $settings['cf7_opt_header_text'] ) ? $settings['cf7_opt_header_text'] : '';
				$cf7_opt_footer_text      = isset( $settings['cf7_opt_footer_text'] ) ? $settings['cf7_opt_footer_text'] : '';
				$cf7_pdf_bg_image         = isset( $settings['cf7_pdf_bg_image'] ) ? $settings['cf7_pdf_bg_image'] : '';
				$cf7_pdf_download_fp_text = isset( $settings['cf7_pdf_download_fp_text'] ) ? $settings['cf7_pdf_download_fp_text'] : __( 'Page', 'generate-pdf-using-contact-form-7' );
				$cf7_pdf_download_fp_pagenumSuffix = isset( $settings['cf7_pdf_download_fp_pagenumSuffix'] ) ? $settings['cf7_pdf_download_fp_pagenumSuffix'] : '';
				$cf7_pdf_download_fp_nbpgPrefix    = isset( $settings['cf7_pdf_download_fp_nbpgPrefix'] ) ? $settings['cf7_pdf_download_fp_nbpgPrefix'] : '';
				$cf7_pdf_download_fp_nbpgSuffix    = isset( $settings['cf7_pdf_download_fp_nbpgSuffix'] ) ? $settings['cf7_pdf_download_fp_nbpgSuffix'] : '';

				require WP_CF7_PDF_DIR . 'inc/templates/cf7-pdf-generation.public.html.php';

				$mpdf->SetHTMLHeader( $headerContent );
				$mpdf->SetHTMLFooter( $footerContent );

				if ( $cf7_pdf_bg_image ) {
					$mpdf->SetDefaultBodyCSS( 'background', "url('" . $cf7_pdf_bg_image . "')" );
					$mpdf->SetDefaultBodyCSS( 'background-image-resize', 6 );
				}

				$mpdf->WriteHTML( $html );
				self::apply_password_protection( $mpdf, $settings, $plain_password );

				$mpdf->Output( $output_path, 'F' );

				if ( ! file_exists( $output_path ) ) {
					return new WP_Error( 'write_failed', __( 'PDF could not be created.', 'generate-pdf-using-contact-form-7' ) );
				}

				return true;
			} catch ( Exception $e ) {
				return new WP_Error( 'mpdf_exception', $e->getMessage() );
			} catch ( Throwable $e ) {
				return new WP_Error( 'mpdf_exception', $e->getMessage() );
			}
		}

		/**
		 * Generate preview PDF for admin (same pipeline as frontend).
		 *
		 * @param int    $form_id        CF7 form ID.
		 * @param array  $settings_override Unsaved settings from admin form.
		 * @param string $plain_password Preview password (optional).
		 * @return array|WP_Error Keys: file, path.
		 */
		public static function generate_preview_pdf( $form_id, $settings_override = array(), $plain_password = '' ) {
			$form_id = absint( $form_id );

			if ( ! $form_id ) {
				return new WP_Error( 'invalid_form', __( 'Invalid contact form.', 'generate-pdf-using-contact-form-7' ) );
			}

			if ( ! class_exists( 'WPCF7_ContactForm' ) && defined( 'WPCF7_PLUGIN' ) ) {
				require_once WPCF7_PLUGIN . '/includes/contact-form.php';
			}

			if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
				return new WP_Error( 'invalid_form', __( 'Contact Form 7 is not available.', 'generate-pdf-using-contact-form-7' ) );
			}

			$saved    = get_post_meta( $form_id, 'cf7_pdf', true );
			$override = self::filter_settings_override( is_array( $settings_override ) ? $settings_override : array() );
			$settings = wp_parse_args( $override, is_array( $saved ) ? $saved : array() );

			if ( ! self::is_pdf_operation_enabled( $settings ) ) {
				return self::pdf_operation_disabled_error();
			}

			if ( isset( $settings['cf7_opt_is_attach_enable'] ) && 'true' === $settings['cf7_opt_is_attach_enable'] ) {
				$result = self::preview_attached_pdf( $settings );
				if ( ! is_wp_error( $result ) ) {
					$result['data_info'] = self::get_preview_data_info( $form_id, $settings );
				}
				return $result;
			}

			$posted_data = self::get_sample_posted_data( $form_id );
			$wpcf7       = WPCF7_ContactForm::get_instance( $form_id );

			$prepared = self::prepare_pdf_html(
				$settings,
				$posted_data,
				array(
					'wpcf7'          => $wpcf7,
					'submission'     => null,
					'uploaded_files' => array(),
					'form_id'        => $form_id,
				)
			);

			if ( is_wp_error( $prepared ) ) {
				return $prepared;
			}

			$upload_dir = wp_upload_dir();
			$subdir     = '/cf7-pdf-previews';

			if ( ! empty( $upload_dir['error'] ) ) {
				return new WP_Error( 'upload_dir', $upload_dir['error'] );
			}

			$dir = $upload_dir['basedir'] . $subdir;

			if ( ! wp_mkdir_p( $dir ) ) {
				return new WP_Error( 'mkdir', __( 'Could not create preview directory.', 'generate-pdf-using-contact-form-7' ) );
			}

			$filename = 'preview-' . $form_id . '-' . wp_generate_password( 8, false ) . '.pdf';
			$path     = $dir . '/' . $filename;

			$written = self::render_pdf_to_file( $settings, $prepared['html'], $path, $plain_password );

			if ( is_wp_error( $written ) ) {
				return $written;
			}

			return array(
				'file'      => $filename,
				'path'      => $path,
				'data_info' => self::get_preview_data_info( $form_id, $settings ),
			);
		}

		/**
		 * Describe which data the preview PDF will use.
		 *
		 * @param int   $form_id  CF7 form ID.
		 * @param array $settings Form PDF settings.
		 * @return array{source:string,label:string,message:string,submission_id:int,submission_date:string}
		 */
		public static function get_preview_data_info( $form_id, $settings = array() ) {
			$form_id  = absint( $form_id );
			$settings = is_array( $settings ) ? $settings : array();

			if ( isset( $settings['cf7_opt_is_attach_enable'] ) && 'true' === $settings['cf7_opt_is_attach_enable'] ) {
				return array(
					'source'           => 'attach',
					'label'            => __( 'Attached PDF', 'generate-pdf-using-contact-form-7' ),
					'message'          => __( 'Preview shows the uploaded PDF file attached to this form.', 'generate-pdf-using-contact-form-7' ),
					'submission_id'    => 0,
					'submission_date'  => '',
				);
			}

			$submission = self::get_last_submission_post( $form_id );

			if ( $submission ) {
				$date_raw = get_post_meta( $submission->ID, Cf7_Pdf_Submissions::META_SUBMITTED_DATE, true );
				if ( ! $date_raw ) {
					$date_raw = $submission->post_date;
				}
				$timestamp = strtotime( (string) $date_raw );
				$formatted = $timestamp ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : '';

				return array(
					'source'          => 'submission',
					'label'           => __( 'Latest submission', 'generate-pdf-using-contact-form-7' ),
					'message'         => $formatted
						? sprintf(
							/* translators: %s: submission date/time */
							__( 'Preview uses field values from your latest submission (%s).', 'generate-pdf-using-contact-form-7' ),
							$formatted
						)
						: __( 'Preview uses field values from your latest saved submission.', 'generate-pdf-using-contact-form-7' ),
					'submission_id'   => (int) $submission->ID,
					'submission_date' => $formatted,
				);
			}

			return array(
				'source'          => 'sample',
				'label'           => __( 'Sample data', 'generate-pdf-using-contact-form-7' ),
				'message'         => __( 'No submissions yet—preview uses placeholder sample text. Submit the form once to preview real entries.', 'generate-pdf-using-contact-form-7' ),
				'submission_id'   => 0,
				'submission_date' => '',
			);
		}

		/**
		 * Normalize margin/settings defaults to match frontend.
		 *
		 * @param array $settings Raw settings.
		 * @return array
		 */
		private static function normalize_settings( $settings ) {
			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			$defaults = array(
				'cf7_opt_margin_header' => '10',
				'cf7_opt_margin_footer' => '10',
				'cf7_opt_margin_top'    => '40',
				'cf7_opt_margin_bottom' => '40',
				'cf7_opt_margin_left'   => '15',
				'cf7_opt_margin_right'  => '15',
			);

			foreach ( $defaults as $key => $default ) {
				if ( empty( $settings[ $key ] ) ) {
					$settings[ $key ] = $default;
				}
			}

			return $settings;
		}

		/**
		 * @param object|null $submission WPCF7_Submission.
		 * @param string      $key        Meta key.
		 * @param mixed       $default    Default value.
		 * @return mixed
		 */
		private static function get_submission_meta( $submission, $key, $default = '' ) {
			if ( $submission && is_object( $submission ) && method_exists( $submission, 'get_meta' ) ) {
				$value = $submission->get_meta( $key );
				if ( null !== $value && '' !== $value ) {
					return $value;
				}
			}

			return $default;
		}

		/**
		 * Drop empty AJAX values so they do not overwrite saved settings.
		 *
		 * @param array $settings_override Settings from the browser.
		 * @return array
		 */
		private static function filter_settings_override( $settings_override ) {
			$skip_keys = array( 'cf7_opt_password_pdf', 'cf7_opt_password_pdf_confirm' );
			$filtered  = array();

			foreach ( $settings_override as $key => $value ) {
				if ( in_array( $key, $skip_keys, true ) ) {
					continue;
				}

				if ( is_string( $value ) && '' === trim( $value ) ) {
					continue;
				}

				$filtered[ $key ] = $value;
			}

			return $filtered;
		}

		/**
		 * Preview an uploaded PDF file in attach mode.
		 *
		 * @param array $settings Form settings.
		 * @return array|WP_Error
		 */
		private static function preview_attached_pdf( $settings ) {
			$filename = isset( $settings['cf7_opt_attach_pdf_image'] ) ? $settings['cf7_opt_attach_pdf_image'] : '';

			if ( '' === $filename ) {
				return new WP_Error( 'no_attach', __( 'No PDF file is attached. Upload a PDF or switch to customized PDF.', 'generate-pdf-using-contact-form-7' ) );
			}

			$path = WP_CF7_PDF_DIR . 'attachments/' . basename( $filename );

			if ( ! file_exists( $path ) ) {
				return new WP_Error( 'no_attach', __( 'Attached PDF file was not found on the server.', 'generate-pdf-using-contact-form-7' ) );
			}

			$upload_dir = wp_upload_dir();
			$subdir     = '/cf7-pdf-previews';
			$dir        = $upload_dir['basedir'] . $subdir;

			wp_mkdir_p( $dir );

			$new_name = 'preview-attach-' . wp_generate_password( 8, false ) . '.pdf';
			$dest     = $dir . '/' . $new_name;

			if ( ! copy( $path, $dest ) ) {
				return new WP_Error( 'copy_failed', __( 'Could not copy attached PDF for preview.', 'generate-pdf-using-contact-form-7' ) );
			}

			return array(
				'file' => $new_name,
				'path' => $dest,
			);
		}

		/**
		 * Writable temp directory for mPDF (required on Windows).
		 *
		 * @return string
		 */
		private static function get_mpdf_temp_dir() {
			$dir = WP_CF7_PDF_DIR . 'attachments/mpdf-tmp';

			if ( ! wp_mkdir_p( $dir ) || ! is_writable( $dir ) ) {
				$upload = wp_upload_dir();
				$dir    = $upload['basedir'] . '/cf7-pdf-tmp';
				wp_mkdir_p( $dir );
			}

			return trailingslashit( $dir );
		}

		/**
		 * @param array $settings Form settings.
		 * @return \Mpdf\Mpdf
		 */
		private static function create_mpdf( $settings ) {
			$cf7_opt_margin_header = isset( $settings['cf7_opt_margin_header'] ) ? $settings['cf7_opt_margin_header'] : '10';
			$cf7_opt_margin_footer = isset( $settings['cf7_opt_margin_footer'] ) ? $settings['cf7_opt_margin_footer'] : '10';
			$cf7_opt_margin_top    = isset( $settings['cf7_opt_margin_top'] ) ? $settings['cf7_opt_margin_top'] : '40';
			$cf7_opt_margin_bottom = isset( $settings['cf7_opt_margin_bottom'] ) ? $settings['cf7_opt_margin_bottom'] : '40';
			$cf7_opt_margin_left   = isset( $settings['cf7_opt_margin_left'] ) ? $settings['cf7_opt_margin_left'] : '15';
			$cf7_opt_margin_right  = isset( $settings['cf7_opt_margin_right'] ) ? $settings['cf7_opt_margin_right'] : '15';
			$cf7_pdf_default_font_size = isset( $settings['cf7_pdf_default_font_size'] ) ? $settings['cf7_pdf_default_font_size'] : '9';
			$cf7_pdf_download_fp_text = isset( $settings['cf7_pdf_download_fp_text'] ) ? $settings['cf7_pdf_download_fp_text'] : __( 'Page', 'generate-pdf-using-contact-form-7' );
			$cf7_pdf_download_fp_pagenumSuffix = isset( $settings['cf7_pdf_download_fp_pagenumSuffix'] ) ? $settings['cf7_pdf_download_fp_pagenumSuffix'] : '';
			$cf7_pdf_download_fp_nbpgPrefix    = isset( $settings['cf7_pdf_download_fp_nbpgPrefix'] ) ? $settings['cf7_pdf_download_fp_nbpgPrefix'] : '';
			$cf7_pdf_download_fp_nbpgSuffix    = isset( $settings['cf7_pdf_download_fp_nbpgSuffix'] ) ? $settings['cf7_pdf_download_fp_nbpgSuffix'] : '';

			return new \Mpdf\Mpdf(
				array(
					'tempDir'           => self::get_mpdf_temp_dir(),
					'default_font_size' => (float) $cf7_pdf_default_font_size,
					'mode'              => 'utf-8',
					'format'            => 'A4',
					'margin_header'     => (float) $cf7_opt_margin_header,
					'margin_top'        => (float) $cf7_opt_margin_top,
					'margin_footer'     => (float) $cf7_opt_margin_footer,
					'margin_bottom'     => (float) $cf7_opt_margin_bottom,
					'default_font'      => 'FreeSans',
					'margin_left'       => (float) $cf7_opt_margin_left,
					'margin_right'      => (float) $cf7_opt_margin_right,
					'pagenumPrefix'     => $cf7_pdf_download_fp_text,
					'pagenumSuffix'     => $cf7_pdf_download_fp_pagenumSuffix,
					'nbpgPrefix'        => $cf7_pdf_download_fp_nbpgPrefix,
					'nbpgSuffix'        => $cf7_pdf_download_fp_nbpgSuffix,
					'aliasNbPg'         => ' [pagetotal] ',
				)
			);
		}

		/**
		 * @param int $form_id Form ID.
		 * @return array
		 */
		private static function get_sample_posted_data( $form_id ) {
			$from_submission = self::get_last_submission_posted_data( $form_id );

			if ( ! empty( $from_submission ) ) {
				return $from_submission;
			}

			$form = WPCF7_ContactForm::get_instance( $form_id );
			$data = array();

			if ( ! $form ) {
				return array(
					'your-name'    => 'John Smith',
					'your-email'   => 'preview@example.com',
					'your-subject' => __( 'Sample subject for PDF preview', 'generate-pdf-using-contact-form-7' ),
					'your-message' => __( 'This is sample message text for layout preview only.', 'generate-pdf-using-contact-form-7' ),
				);
			}

			foreach ( $form->scan_form_tags() as $tag ) {
				if ( empty( $tag->name ) ) {
					continue;
				}

				$data[ $tag->name ] = self::sample_value_for_tag( $tag );
			}

			return $data;
		}

		/**
		 * @param int $form_id CF7 form ID.
		 * @return WP_Post|null
		 */
		private static function get_last_submission_post( $form_id ) {
			$posts = get_posts(
				array(
					'post_type'      => Cf7_Pdf_Cpt::POST_TYPE,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'orderby'        => 'date',
					'order'          => 'DESC',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'OR',
						array(
							'key'   => Cf7_Pdf_Submissions::META_FORM_ID,
							'value' => absint( $form_id ),
						),
						array(
							'key'   => Cf7_Pdf_Submissions::LEGACY_FORM_ID,
							'value' => absint( $form_id ),
						),
					),
				)
			);

			return ! empty( $posts ) ? $posts[0] : null;
		}

		/**
		 * @param int $form_id CF7 form ID.
		 * @return array
		 */
		private static function get_last_submission_posted_data( $form_id ) {
			$post = self::get_last_submission_post( $form_id );

			if ( ! $post ) {
				return array();
			}

			$form_data = get_post_meta( $post->ID, '_form_data', true );

			if ( is_string( $form_data ) ) {
				$form_data = maybe_unserialize( $form_data );
			}

			if ( is_array( $form_data ) && ! empty( $form_data ) ) {
				return $form_data;
			}

			return array();
		}

		/**
		 * @param object $tag CF7 form tag.
		 * @return string
		 */
		private static function sample_value_for_tag( $tag ) {
			$type = isset( $tag->basetype ) ? $tag->basetype : '';
			$name = isset( $tag->name ) ? strtolower( (string) $tag->name ) : '';

			switch ( $type ) {
				case 'email':
					return 'preview@example.com';
				case 'tel':
					return '555-0100';
				case 'number':
					return '42';
				case 'date':
					return gmdate( 'Y-m-d' );
				case 'acceptance':
					return '1';
				case 'textarea':
					return __( 'This is sample message text for layout preview only. After a visitor submits the form, their real answers appear in the PDF.', 'generate-pdf-using-contact-form-7' );
			}

			if ( preg_match( '/(your-name|^name$|first-name|last-name|fullname)/', $name ) ) {
				return 'John Smith';
			}

			if ( preg_match( '/(subject|asunto|topic)/', $name ) ) {
				return __( 'Sample subject for PDF preview', 'generate-pdf-using-contact-form-7' );
			}

			if ( preg_match( '/(message|msg|comment|inquiry|body)/', $name ) ) {
				return __( 'This is sample message text for layout preview only.', 'generate-pdf-using-contact-form-7' );
			}

			if ( preg_match( '/(company|organization)/', $name ) ) {
				return __( 'Sample Company', 'generate-pdf-using-contact-form-7' );
			}

			return __( 'Sample value', 'generate-pdf-using-contact-form-7' );
		}

	}
}

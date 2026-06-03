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

			return Cf7_Pdf_Global_Settings::decrypt_password( $setting_data['cf7_opt_password_pdf'] );
		}

		/**
		 * Generate a temporary preview PDF for admin.
		 *
		 * @param int    $form_id        CF7 form ID.
		 * @param array  $settings_override Unsaved settings from admin form.
		 * @param string $plain_password Preview password (optional).
		 * @return array|WP_Error Keys: file, path, url.
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

			if ( isset( $settings['cf7_opt_is_attach_enable'] ) && 'true' === $settings['cf7_opt_is_attach_enable'] ) {
				return self::preview_attached_pdf( $settings );
			}

			try {
				if ( ! class_exists( '\Mpdf\Mpdf' ) ) {
					require_once WP_CF7_PDF_DIR . 'inc/lib/mpdf/vendor/autoload.php';
				}

				$posted_data = self::get_sample_posted_data( $form_id );
				$date        = date_i18n( get_option( 'date_format' ) );
				$time        = date_i18n( get_option( 'time_format' ) );
				$mpdf        = self::create_mpdf( $settings );

				$mpdf->autoScriptToLang = true;
				$mpdf->baseScript       = 1;
				$mpdf->autoVietnamese   = true;
				$mpdf->autoArabic       = true;
				$mpdf->autoLangToFont   = true;
				$mpdf->SetTitle( get_bloginfo( 'name' ) );
				$mpdf->SetCreator( get_bloginfo( 'name' ) );
				$mpdf->ignore_invalid_utf8 = true;

				$msg_body = isset( $settings['cf7_pdf_msg_body'] ) ? $settings['cf7_pdf_msg_body'] : '';
				if ( '' === trim( $msg_body ) ) {
					$msg_body = __( "Your Name : [your-name]\nYour Email : [your-email]\nSubject : [your-subject]\nYour Message : [your-message]", 'generate-pdf-using-contact-form-7' );
				}

				$html = self::build_html_from_message( $msg_body, $posted_data, $settings, $date, $time, $form_id );

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

				$mpdf->Output( $path, 'F' );

				if ( ! file_exists( $path ) ) {
					return new WP_Error( 'write_failed', __( 'Preview PDF could not be created.', 'generate-pdf-using-contact-form-7' ) );
				}

				return array(
					'file' => $filename,
					'path' => $path,
					'url'  => $upload_dir['baseurl'] . $subdir . '/' . $filename,
				);
			} catch ( Exception $e ) {
				return new WP_Error( 'mpdf_exception', $e->getMessage() );
			} catch ( Throwable $e ) {
				return new WP_Error( 'mpdf_exception', $e->getMessage() );
			}
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
				'url'  => $upload_dir['baseurl'] . $subdir . '/' . $new_name,
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
			$form = WPCF7_ContactForm::get_instance( $form_id );
			$data = array();

			if ( ! $form ) {
				return array(
					'your-name'    => __( 'Sample Name', 'generate-pdf-using-contact-form-7' ),
					'your-email'   => 'preview@example.com',
					'your-subject' => __( 'Preview Subject', 'generate-pdf-using-contact-form-7' ),
					'your-message' => __( 'This is sample preview content.', 'generate-pdf-using-contact-form-7' ),
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
		 * @param object $tag CF7 form tag.
		 * @return string
		 */
		private static function sample_value_for_tag( $tag ) {
			$type = isset( $tag->basetype ) ? $tag->basetype : '';

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
				default:
					return sprintf(
						/* translators: %s: form field name. */
						__( 'Sample: %s', 'generate-pdf-using-contact-form-7' ),
						$tag->name
					);
			}
		}

		/**
		 * @param string $msg_body     Message template.
		 * @param array  $posted_data  Sample values.
		 * @param array  $settings     Form settings.
		 * @param string $date         Date string.
		 * @param string $time         Time string.
		 * @param int    $form_id      Form ID.
		 * @return string HTML body.
		 */
		private static function build_html_from_message( $msg_body, $posted_data, $settings, $date, $time, $form_id ) {
			$current_time = (string) microtime( true );
			$current_time = str_replace( '.', '-', $current_time );

			foreach ( $posted_data as $key => $value ) {
				if ( ! strstr( $msg_body, $key ) ) {
					continue;
				}

				if ( is_array( $value ) ) {
					$value = implode( '<br/>', $value );
				} else {
					$value = htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
				}

				if ( strpos( $key, 'acceptance' ) !== false ) {
					$value = ( '1' === $value || 1 === $value ) ? __( 'accepted', 'generate-pdf-using-contact-form-7' ) : __( 'not accepted', 'generate-pdf-using-contact-form-7' );
				}

				$msg_body = str_replace( '[' . $key . ']', $value, $msg_body );
			}

			$msg_body = str_replace( '[date]', $date, $msg_body );
			$msg_body = str_replace( '[time]', $time, $msg_body );
			$msg_body = str_replace( '[random-number]', $current_time, $msg_body );
			$msg_body = str_replace( '[_site_url]', '<a href="' . esc_url( site_url() ) . '" target="_blank">' . esc_html( site_url() ) . '</a>', $msg_body );
			$msg_body = str_replace( '[_site_title]', get_bloginfo( 'name' ), $msg_body );
			$msg_body = str_replace( '[_site_description]', get_bloginfo( 'description' ), $msg_body );

			$lines = explode( "\n", $msg_body );
			$clean = array();

			foreach ( $lines as $line ) {
				if ( strpos( $line, 'noreplace' ) === false ) {
					$clean[] = $line;
				}
			}

			$html = implode( "\n", $clean );

			if ( strpos( $html, '<table' ) === false ) {
				$html = nl2br( $html );
			}

			return $html;
		}
	}
}

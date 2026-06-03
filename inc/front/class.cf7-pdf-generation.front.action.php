<?php
/**
 * Cf7_Pdf_Generation_Front_Action Class
 *
 * Handles the Frontend Actions.
 *
 * @package WordPress
 * @subpackage
 * @since 2.4
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Cf7_Pdf_Generation_Front_Action' ) ){

	/**
	* The Cf7_Pdf_Generation_Front_Action Class
	*/

	class Cf7_Pdf_Generation_Front_Action {

		function __construct()  {
			add_action( 'wp_enqueue_scripts',  array( $this, 'enqueue_styles' ));
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));
			add_action( 'wpcf7_before_send_mail', array( $this, 'wpcf7_pdf_attachment_script' ));
		}

		/**
		 * Sanitize a PDF filename for safe filesystem paths and URLs.
		 *
		 * Replaces characters such as / and # that break file writes or URL resolution.
		 *
		 * @param string $filename Proposed filename (with .pdf extension).
		 * @return string Safe filename.
		 */
		private function sanitize_pdf_filename( $filename ) {
			$sanitized = sanitize_file_name( $filename );

			if ( '' === $sanitized ) {
				return 'cf7-document.pdf';
			}

			if ( ! preg_match( '/\.pdf$/i', $sanitized ) ) {
				$sanitized = preg_replace( '/\.[^.]+$/', '', $sanitized );
				$sanitized = ( '' !== $sanitized ) ? $sanitized . '.pdf' : 'cf7-document.pdf';
			}

			return $sanitized;
		}

		/**
		 * Resolve a public PDF URL to a local filesystem path when possible.
		 *
		 * @param string $pdf_url PDF URL.
		 * @return string
		 */
		private function pdf_url_to_path( $pdf_url ) {
			$upload = wp_upload_dir();

			if ( ! empty( $upload['baseurl'] ) && strpos( $pdf_url, $upload['baseurl'] ) === 0 ) {
				return str_replace( $upload['baseurl'], $upload['basedir'], $pdf_url );
			}

			if ( strpos( $pdf_url, WP_CF7_PDF_URL ) === 0 ) {
				return str_replace( WP_CF7_PDF_URL, WP_CF7_PDF_DIR, $pdf_url );
			}

			return '';
		}

		/**
		 * Store a PDF submission entry after successful generation.
		 *
		 * @param int    $form_id  CF7 form ID.
		 * @param string $pdf_url  PDF URL.
		 * @param string $pdf_path Optional filesystem path.
		 */
		/**
		 * Clear frontend PDF download cookies when PDF operation is off.
		 */
		private function clear_pdf_cookies() {
			$expire = time() - 3600;

			setcookie( 'wp-pdf_path', '', $expire, '/' );
			setcookie( 'wp-enable_pdf_link', '', $expire, '/' );
			setcookie( 'wp-pdf_download_link_txt', '', $expire, '/' );
			setcookie( 'wp-unit_tag', '', $expire, '/' );
		}

		private function log_pdf_submission( $form_id, $pdf_url, $pdf_path = '' ) {
			if ( ! class_exists( 'Cf7_Pdf_Submissions' ) || empty( $pdf_url ) ) {
				return;
			}

			if ( '' === $pdf_path ) {
				$pdf_path = $this->pdf_url_to_path( $pdf_url );
			}

			$title      = '';
			$form_data  = array();
			$submission = WPCF7_Submission::get_instance();

			if ( $submission ) {
				$posted = $submission->get_posted_data();
				if ( is_array( $posted ) ) {
					$form_data = $posted;
					foreach ( $posted as $value ) {
						if ( is_string( $value ) && is_email( $value ) ) {
							$title = $value;
							break;
						}
					}
				}
			}

			Cf7_Pdf_Submissions::create_submission( $form_id, $pdf_url, $pdf_path, $title, $form_data );
		}

		function wpcf7_pdf_create_attachment($filename)
		{
			// Check the type of file. We'll use this as the 'post_mime_type'.
			$attached_data = array();
			$filetype = wp_check_filetype(basename($filename), null);
			$filetype['type'] = 'application/pdf';

			// Get the path to the upload directory.
			$wp_upload_dir = wp_upload_dir();

			$safe_basename = $this->sanitize_pdf_filename( basename( $filename ) );
			$attachFileName = $wp_upload_dir['path'] . '/' . $safe_basename;
			copy($filename, $attachFileName);

			// Prepare an array of post data for the attachment.
			$attachment = array(
				'guid'           => $attachFileName,
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);


			// Insert the attachment.
			$attached_data['attach_id'] = wp_insert_attachment($attachment, $attachFileName);
			$attached_data['attach_url'] = wp_get_attachment_url( $attached_data['attach_id'] );

			$file = get_attached_file($attached_data['attach_id'], true);
			$size = 'full';
			$attached_data['absolute_path'] = realpath($file);

			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once(ABSPATH . 'wp-admin/includes/image.php');

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata($attached_data['attach_id'], $attachFileName);

			wp_update_attachment_metadata($attached_data['attach_id'], $attach_data);
			return $attached_data;
		}

		/**
		* Function for generate PDF file
		*/
		function wpcf7_pdf_attachment_script( $wpcf7 ){

			$cf7_pdf_link_is_enable = '';

			if ( ! $wpcf7 || ! method_exists( $wpcf7, 'id' ) ) {
				return $wpcf7;
			}

		    $submission = WPCF7_Submission :: get_instance();

			if ( ! $submission ) {
				return $wpcf7;
			}

			$unit_tag = $submission->get_meta('unit_tag');
			$posted_data = $submission->get_posted_data();

			$uploaded_files = $submission->uploaded_files();

            $contact_id = (int) $wpcf7->id();
		    $setting_data = get_post_meta( $contact_id, 'cf7_pdf', true );

			if ( ! is_array( $setting_data ) ) {
				$setting_data = array();
			}

			if ( ! Cf7_Pdf_Pdf_Builder::is_pdf_operation_enabled( $setting_data ) ) {
				$this->clear_pdf_cookies();
				return $wpcf7;
			}
			
            if ( isset($setting_data['cf7_opt_attach_pdf_image'])){
		        $attach_image = $setting_data['cf7_opt_attach_pdf_image'] ? $setting_data['cf7_opt_attach_pdf_image'] : "";
            }

            if ( isset($setting_data['cf7_pdf_link_is_enable'])){
		        $cf7_pdf_link_is_enable = $setting_data['cf7_pdf_link_is_enable'] ? $setting_data['cf7_pdf_link_is_enable'] : "";
            }

			if ( isset($setting_data['cf7_remove_pdf']) ){
		        $cf7_remove_pdf = trim($setting_data['cf7_remove_pdf']) ? $setting_data['cf7_remove_pdf'] : '';
            } else {
				$cf7_remove_pdf = '';
			}

            if ( isset($setting_data['cf7_pdf_download_link_txt']) ){
		        $cf7_pdf_download_link_txt = trim($setting_data['cf7_pdf_download_link_txt']) ? $setting_data['cf7_pdf_download_link_txt'] : __('Click here to download PDF','generate-pdf-using-contact-form-7');
            } else {
				$cf7_pdf_download_link_txt = __('Click here to download PDF','generate-pdf-using-contact-form-7');
			}

			if ( isset($setting_data['cf7_pdf_show_hide_label'])){
		        $cf7_pdf_show_hide_label = $setting_data['cf7_pdf_show_hide_label'] ? $setting_data['cf7_pdf_show_hide_label'] : "";
            }

			$attdataurl_array = array();

			$cf7_dettach_active = isset( $setting_data['cf7_dettach_pdf'] ) && 'true' === $setting_data['cf7_dettach_pdf'];
			$cf7_link_active    = isset( $setting_data['cf7_pdf_link_is_enable'] ) && 'true' === $setting_data['cf7_pdf_link_is_enable'];

			if ( $cf7_dettach_active || $cf7_link_active ) {
					if ( isset($setting_data['cf7_opt_is_attach_enable']) && $setting_data['cf7_opt_is_attach_enable'] == 'true') {
						
						if ( $attach_image ) {
		 					$pdf_file_path1 = WP_CONTENT_DIR .'/uploads/wpcf7_uploads/'.$attach_image;

		 					$pdf_file_path = WP_CF7_PDF_DIR .'attachments/'.$attach_image;
		 					$pdf_url_path = WP_CF7_PDF_URL.'attachments/'.$attach_image;

		 					$temp_name = sanitize_text_field(wp_rand());
							copy($pdf_file_path, $pdf_file_path1);
							$attdataurl_array = $this->wpcf7_pdf_create_attachment($pdf_url_path);

							$returnexist = file_exists( $attdataurl_array['absolute_path'] );
							if ( $returnexist && ($cf7_pdf_link_is_enable =='false' || $cf7_remove_pdf =='false') ) {
								$attdataurl = $attdataurl_array['attach_url'];
							} else {
								$attdataurl = $pdf_url_path;
							}

							if($setting_data['cf7_pdf_link_is_enable'] == 'true'){
			 					$cookie_name = "wp-pdf_path";
								$cookie_value = $attdataurl;
								//86400 = 1 day
								setcookie( $cookie_name, $cookie_value, time() + (86400 * 1), "/"); 
								//86400 = 1 day
								setcookie( 'wp-enable_pdf_link', $cf7_pdf_link_is_enable, time() + (86400 * 1), "/");
								//86400 = 1 day
								setcookie( 'wp-pdf_download_link_txt', $cf7_pdf_download_link_txt, time() + (86400 * 1), "/"); 
								//86400 = 1 day
								setcookie( 'wp-unit_tag', $unit_tag, time() + (86400 * 1), "/");
							}
							
							if ( ! empty( $attdataurl ) ) {
								$this->log_pdf_submission( $contact_id, $attdataurl, isset( $attdataurl_array['absolute_path'] ) ? $attdataurl_array['absolute_path'] : $pdf_file_path );
							}

							if($setting_data['cf7_dettach_pdf'] == 'true'){

								$mail = $wpcf7->prop('mail');
								$attachments_main = array();
								if( $mail['attachments'] ){
									$attachments_main = $mail['attachments']. PHP_EOL .$pdf_file_path;
								} else {
									$attachments_main = $pdf_file_path;
								}
								$mail['attachments'] = $attachments_main;
								$wpcf7->set_properties(array(
									"mail" => $mail
								));

								$mail_2 = $wpcf7->prop('mail_2');
								$attachments_main_2 = array();
								if( $mail_2['attachments'] ){
									$attachments_main_2 = $mail_2['attachments']. PHP_EOL .$pdf_file_path;
								} else {
									$attachments_main_2 = $pdf_file_path;
								}
								$mail_2['attachments'] = $attachments_main_2;
								$wpcf7->set_properties(array(
									"mail_2" => $mail_2
								));

							}

		 				}
		 			} else {
		 				/*
		 				* Generate PDF (shared builder — same as admin preview).
		 				*/
						$current_time = microtime( true );
						$current_time = str_replace( '.', '-', $current_time );

						$prepared = Cf7_Pdf_Pdf_Builder::prepare_pdf_html(
							$setting_data,
							$posted_data,
							array(
								'wpcf7'          => $wpcf7,
								'submission'     => $submission,
								'uploaded_files' => $uploaded_files,
								'form_id'        => $contact_id,
							)
						);

						if ( is_wp_error( $prepared ) ) {
							return $wpcf7;
						}

						$cf7_pdf_filename_prefix = $prepared['filename_prefix'];

						if ( '' !== $cf7_pdf_filename_prefix ) {
							$pdf_file_name = $this->sanitize_pdf_filename( $cf7_pdf_filename_prefix . '-' . $current_time . '.pdf' );
						} else {
							$pdf_file_name = $this->sanitize_pdf_filename( 'cf7-' . $contact_id . '-' . $current_time . '.pdf' );
						}

						$path_dir_cf7 = '';
						foreach ( (array) $uploaded_files as $name => $path ) {
							if ( ! empty( $path ) ) {
								$xml_file     = pathinfo( $path[0] );
								$path_dir_cf7 = $xml_file['dirname'];
							}
						}

						$pdf_file_path  = WP_CF7_PDF_DIR . 'attachments/' . $pdf_file_name;
						$pdf_file_path1 = $path_dir_cf7 . '/' . $pdf_file_name;
						$pdf_url_path   = WP_CF7_PDF_URL . 'attachments/' . $pdf_file_name;

						$written = Cf7_Pdf_Pdf_Builder::render_pdf_to_file( $setting_data, $prepared['html'], $pdf_file_path );

						if ( is_wp_error( $written ) ) {
							return $wpcf7;
						}

						if ( ! empty( $path_dir_cf7 ) && isset( $_SERVER['DOCUMENT_ROOT'] ) && file_exists( $_SERVER['DOCUMENT_ROOT'] . $pdf_file_path1 ) ) {
							copy( $pdf_file_path, $pdf_file_path1 );
						}

						//till this file upload in attachment folder

						$attdataurl_array = $this->wpcf7_pdf_create_attachment($pdf_url_path);
						$returnexist = file_exists( $attdataurl_array['absolute_path'] );
						if( $returnexist && ($cf7_pdf_link_is_enable =='false' || $cf7_remove_pdf =='false')) {
							$attdataurl = $attdataurl_array['attach_url'];
						} else {
							$attdataurl = $pdf_url_path;
						}

						if ( ! empty( $attdataurl ) ) {
							$log_path = '';
							if ( isset( $attdataurl_array['absolute_path'] ) ) {
								$log_path = $attdataurl_array['absolute_path'];
							} elseif ( ! empty( $pdf_file_path ) && file_exists( $pdf_file_path ) ) {
								$log_path = $pdf_file_path;
							}
							$this->log_pdf_submission( $contact_id, $attdataurl, $log_path );
						}

						if($setting_data['cf7_pdf_link_is_enable'] == 'true'){

							$cookie_name = "wp-pdf_path";
							$cookie_value = $attdataurl;
							//86400 = 1 day
							setcookie( $cookie_name, $cookie_value, time() + (86400 * 1), "/"); 
							//86400 = 1 day
							setcookie( 'wp-enable_pdf_link', $cf7_pdf_link_is_enable, time() + (86400 * 1), "/");
							//86400 = 1 day
							setcookie( 'wp-pdf_download_link_txt', $cf7_pdf_download_link_txt, time() + (86400 * 1), "/");
							//86400 = 1 day
							setcookie( 'wp-unit_tag', $unit_tag, time() + (86400 * 1), "/"); 
						}
						
						if($setting_data['cf7_dettach_pdf'] == 'true'){
							
							$attachments_main = array();
							$mail = $wpcf7->prop('mail');
							if( $mail['attachments'] ){
								$attachments_main = $mail['attachments']. PHP_EOL .$pdf_file_path;
							} else {
								$attachments_main = $pdf_file_path;
							}
							$mail['attachments'] = $attachments_main;
							$wpcf7->set_properties(array(
								"mail" => $mail
							));

							$attachments_main_2 = array();
							$mail_2 = $wpcf7->prop('mail_2');
							if( $mail_2['attachments'] ){
								$attachments_main_2 = $mail_2['attachments']. PHP_EOL .$pdf_file_path;
							} else {
								$attachments_main_2 = $pdf_file_path;
							}
							$mail_2['attachments'] = $attachments_main_2;
							$wpcf7->set_properties(array(
								"mail_2" => $mail_2
							));
						}

					}
				}

			if ( 'true' === $cf7_remove_pdf && isset( $attdataurl_array['attach_id'] ) ) {
				wp_delete_attachment( $attdataurl_array['attach_id'], true );
			}
			

			return $wpcf7;

		}
		/*
		   ###     ######  ######## ####  #######  ##    ##  ######
		  ## ##   ##    ##    ##     ##  ##     ## ###   ## ##    ##
		 ##   ##  ##          ##     ##  ##     ## ####  ## ##
		##     ## ##          ##     ##  ##     ## ## ## ##  ######
		######### ##          ##     ##  ##     ## ##  ####       ##
		##     ## ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##     ##  ######     ##    ####  #######  ##    ##  ######
		*/

		/**
		* WP Enqueue style for public CSS
		*/
		public function enqueue_styles() {
			wp_enqueue_style( 'cf7-pdf-generation-public-css', WP_CF7_PDF_URL . 'assets/css/cf7-pdf-generation-public-min.css', array(), 1.2, 'all' );
		}

		/**
		* WP Enqueue scripts for public JS
		*/
		public function enqueue_scripts() {
			wp_enqueue_script( 'cf7-pdf-generation-public-js', WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-public-min.js', array( 'jquery' ), 1.2, false );
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
	* Run plugins loaded
	*/
	add_action( 'plugins_loaded' , function() {
		new Cf7_Pdf_Generation_Front_Action;
	} );
}
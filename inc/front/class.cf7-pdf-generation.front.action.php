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

		/**
		 * Full server path queued for the current CF7 mail send.
		 *
		 * @var string
		 */
		private $cf7pdf_mail_attachment_path = '';

		/**
		 * WordPress media attachment ID to remove after mail is sent.
		 *
		 * @var int
		 */
		private $cf7pdf_remove_attachment_id = 0;

		/**
		 * Whether to remove the media-library PDF copy after mail is sent.
		 *
		 * @var bool
		 */
		private $cf7pdf_remove_after_mail = false;

		function __construct()  {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));
			add_action( 'wpcf7_before_send_mail', array( $this, 'wpcf7_pdf_attachment_script' ), 10, 1 );
			add_filter( 'wpcf7_mail_components', array( $this, 'inject_pdf_mail_attachments' ), 99, 3 );
			add_filter( 'wp_mail', array( $this, 'inject_wp_mail_attachments' ), 99, 1 );
			add_action( 'wpcf7_mail_sent', array( $this, 'cleanup_after_mail_sent' ), 10, 1 );
		}

		/**
		 * Resolve and validate an absolute PDF path for mail attachment.
		 *
		 * @param string $pdf_path Proposed absolute path.
		 * @return string
		 */
		private function resolve_mail_attachment_path( $pdf_path ) {
			$cf7pdf_path = wp_normalize_path( (string) $pdf_path );

			if ( '' === $cf7pdf_path ) {
				return '';
			}

			$cf7pdf_realpath = realpath( $cf7pdf_path );

			if ( false !== $cf7pdf_realpath ) {
				$cf7pdf_path = wp_normalize_path( $cf7pdf_realpath );
			}

			if ( ! file_exists( $cf7pdf_path ) || ! is_readable( $cf7pdf_path ) || ! is_file( $cf7pdf_path ) ) {
				return '';
			}

			if ( function_exists( 'wpcf7_is_file_path_in_content_dir' ) && ! wpcf7_is_file_path_in_content_dir( $cf7pdf_path ) ) {
				return '';
			}

			return $cf7pdf_path;
		}

		/**
		 * Queue a PDF filesystem path for attachment on the outgoing CF7 mail.
		 *
		 * @param string $pdf_path Absolute path to the PDF file.
		 * @return bool
		 */
		private function queue_pdf_for_cf7_mail( $pdf_path ) {
			$cf7pdf_path = $this->resolve_mail_attachment_path( $pdf_path );

			if ( '' === $cf7pdf_path ) {
				if ( class_exists( 'Cf7_Pdf_Submissions' ) ) {
					Cf7_Pdf_Submissions::debug_log(
						'queue_pdf_for_cf7_mail skipped',
						array(
							'path'        => wp_normalize_path( (string) $pdf_path ),
							'file_exists' => ( '' !== (string) $pdf_path && file_exists( (string) $pdf_path ) ),
						)
					);
				}
				return false;
			}

			$this->cf7pdf_mail_attachment_path = $cf7pdf_path;

			if ( class_exists( 'Cf7_Pdf_Submissions' ) ) {
				Cf7_Pdf_Submissions::debug_log(
					'Queued mail PDF attachment',
					array(
						'path'        => $cf7pdf_path,
						'file_exists' => true,
						'mime'        => function_exists( 'mime_content_type' ) ? mime_content_type( $cf7pdf_path ) : 'n/a',
					)
				);
			}

			return true;
		}

		/**
		 * Register a saved/uploaded PDF for CF7 mail when attach-in-mail is enabled.
		 *
		 * @param array $setting_data Form PDF settings.
		 * @return bool
		 */
		private function maybe_queue_saved_pdf_for_mail( array $setting_data ) {
			if ( empty( $setting_data['cf7_dettach_pdf'] ) || 'true' !== $setting_data['cf7_dettach_pdf'] ) {
				return false;
			}

			$cf7pdf_upload_mode = ! empty( $setting_data['cf7_opt_is_attach_enable'] ) && 'true' === $setting_data['cf7_opt_is_attach_enable'];

			if ( $cf7pdf_upload_mode ) {
				$cf7pdf_path = class_exists( 'Cf7_Pdf_Submissions' )
					? Cf7_Pdf_Submissions::get_attach_pdf_path_from_settings( $setting_data )
					: '';

				if ( '' === $cf7pdf_path ) {
					$cf7pdf_filename = isset( $setting_data['cf7_opt_attach_pdf_image'] ) ? sanitize_file_name( (string) $setting_data['cf7_opt_attach_pdf_image'] ) : '';
					if ( '' !== $cf7pdf_filename ) {
						$cf7pdf_path = wp_normalize_path( WP_CF7_PDF_DIR . 'attachments/' . $cf7pdf_filename );
					}
				}

				if ( class_exists( 'Cf7_Pdf_Submissions' ) ) {
					Cf7_Pdf_Submissions::debug_log(
						'maybe_queue_saved_pdf_for_mail upload mode',
						array(
							'stored_path' => isset( $setting_data['cf7_opt_attach_pdf_path'] ) ? $setting_data['cf7_opt_attach_pdf_path'] : '',
							'stored_url'  => isset( $setting_data['cf7_opt_attach_pdf_url'] ) ? $setting_data['cf7_opt_attach_pdf_url'] : '',
							'resolved'    => $cf7pdf_path,
							'file_exists' => ( '' !== $cf7pdf_path && file_exists( $cf7pdf_path ) ),
						)
					);
				}

				return $this->queue_pdf_for_cf7_mail( $cf7pdf_path );
			}

			return false;
		}

		/**
		 * Attach the queued PDF using CF7 mail components (full server path).
		 *
		 * @param array       $components    Mail components.
		 * @param WPCF7_ContactForm $contact_form Form instance.
		 * @param WPCF7_Mail  $mail          Mail instance.
		 * @return array
		 */
		public function inject_pdf_mail_attachments( $components, $contact_form, $mail ) {
			$cf7pdf_path = $this->resolve_mail_attachment_path( $this->cf7pdf_mail_attachment_path );

			if ( '' === $cf7pdf_path ) {
				return $components;
			}

			if ( ! isset( $components['attachments'] ) || ! is_array( $components['attachments'] ) ) {
				$components['attachments'] = array();
			}

			if ( ! in_array( $cf7pdf_path, $components['attachments'], true ) ) {
				$components['attachments'][] = $cf7pdf_path;
			}

			if ( class_exists( 'Cf7_Pdf_Submissions' ) ) {
				Cf7_Pdf_Submissions::debug_log(
					'Injected CF7 mail attachment paths',
					array(
						'attachments' => $components['attachments'],
						'file_exists' => file_exists( $cf7pdf_path ),
						'mime'        => function_exists( 'mime_content_type' ) ? mime_content_type( $cf7pdf_path ) : 'n/a',
					)
				);
			}

			return $components;
		}

		/**
		 * Ensure the queued PDF is passed to wp_mail() attachments (5th parameter).
		 *
		 * @param array $args wp_mail() arguments.
		 * @return array
		 */
		public function inject_wp_mail_attachments( $args ) {
			$cf7pdf_path = $this->resolve_mail_attachment_path( $this->cf7pdf_mail_attachment_path );

			if ( '' === $cf7pdf_path ) {
				return $args;
			}

			if ( ! isset( $args['attachments'] ) || ! is_array( $args['attachments'] ) ) {
				$args['attachments'] = array();
			}

			if ( ! in_array( $cf7pdf_path, $args['attachments'], true ) ) {
				$args['attachments'][] = $cf7pdf_path;
			}

			if ( class_exists( 'Cf7_Pdf_Submissions' ) ) {
				Cf7_Pdf_Submissions::debug_log(
					'wp_mail attachments parameter',
					array(
						'attachments' => $args['attachments'],
						'file_exists' => file_exists( $cf7pdf_path ),
					)
				);
			}

			return $args;
		}

		/**
		 * Remove temporary media attachments only after CF7 mail has been sent.
		 *
		 * @param WPCF7_ContactForm $contact_form Contact form instance.
		 */
		public function cleanup_after_mail_sent( $contact_form ) {
			if ( $this->cf7pdf_remove_after_mail && ! empty( $this->cf7pdf_remove_attachment_id ) ) {
				wp_delete_attachment( (int) $this->cf7pdf_remove_attachment_id, true );

				if ( class_exists( 'Cf7_Pdf_Submissions' ) ) {
					Cf7_Pdf_Submissions::debug_log(
						'Removed media attachment after mail sent',
						array( 'attach_id' => (int) $this->cf7pdf_remove_attachment_id )
					);
				}
			}

			$this->cf7pdf_mail_attachment_path = '';
			$this->cf7pdf_remove_attachment_id = 0;
			$this->cf7pdf_remove_after_mail   = false;
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
		 * Clear frontend PDF download cookies when PDF operation is off.
		 */
		private function clear_pdf_cookies() {
			$expire = time() - 3600;

			setcookie( 'wp-pdf_path', '', $expire, '/' );
			setcookie( 'wp-enable_pdf_link', '', $expire, '/' );
			setcookie( 'wp-pdf_download_link_txt', '', $expire, '/' );
			setcookie( 'wp-unit_tag', '', $expire, '/' );
		}

		/**
		 * Store a PDF submission entry after successful generation.
		 *
		 * @param int    $form_id  CF7 form ID.
		 * @param string $pdf_url  PDF URL.
		 * @param string $pdf_path Optional filesystem path.
		 */
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

		function wpcf7_pdf_create_attachment( $filename ) {
			$attached_data = array();
			$cf7pdf_source = $this->pdf_url_to_path( $filename );

			if ( '' === $cf7pdf_source || ! file_exists( $cf7pdf_source ) ) {
				$cf7pdf_source = wp_normalize_path( (string) $filename );
			}

			if ( '' === $cf7pdf_source || ! file_exists( $cf7pdf_source ) ) {
				if ( class_exists( 'Cf7_Pdf_Submissions' ) ) {
					Cf7_Pdf_Submissions::debug_log( 'wpcf7_pdf_create_attachment source missing', array( 'input' => $filename ) );
				}
				return array(
					'attach_id'      => 0,
					'attach_url'     => '',
					'absolute_path'  => '',
				);
			}

			$filetype = wp_check_filetype( basename( $cf7pdf_source ), null );
			$filetype['type'] = 'application/pdf';

			$wp_upload_dir = wp_upload_dir();

			$safe_basename  = $this->sanitize_pdf_filename( basename( $cf7pdf_source ) );
			$attachFileName = $wp_upload_dir['path'] . '/' . $safe_basename;
			copy( $cf7pdf_source, $attachFileName );

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
			$this->cf7pdf_mail_attachment_path = '';
			$this->cf7pdf_remove_attachment_id   = 0;
			$this->cf7pdf_remove_after_mail     = false;

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

			if ( ! is_array( $setting_data ) || empty( $setting_data ) ) {
				$setting_data = get_post_meta( $contact_id, '_wp_cf7_pdf', true );
			}

			if ( ! is_array( $setting_data ) ) {
				$setting_data = array();
			}

			if ( ! Cf7_Pdf_Pdf_Builder::is_pdf_operation_enabled( $setting_data ) ) {
				$this->clear_pdf_cookies();
				return $wpcf7;
			}

			$this->maybe_queue_saved_pdf_for_mail( $setting_data );
			
			$attach_image = '';
            if ( isset( $setting_data['cf7_opt_attach_pdf_image'] ) ) {
		        $attach_image = $setting_data['cf7_opt_attach_pdf_image'] ? $setting_data['cf7_opt_attach_pdf_image'] : '';
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

			$attdataurl_array = array();

			$cf7_dettach_active = isset( $setting_data['cf7_dettach_pdf'] ) && 'true' === $setting_data['cf7_dettach_pdf'];
			$cf7_link_active    = isset( $setting_data['cf7_pdf_link_is_enable'] ) && 'true' === $setting_data['cf7_pdf_link_is_enable'];

			if ( $cf7_dettach_active || $cf7_link_active ) {
					if ( isset($setting_data['cf7_opt_is_attach_enable']) && $setting_data['cf7_opt_is_attach_enable'] == 'true') {
						
						if ( $attach_image ) {
							$pdf_file_path = class_exists( 'Cf7_Pdf_Submissions' )
								? Cf7_Pdf_Submissions::get_attach_pdf_path_from_settings( $setting_data )
								: wp_normalize_path( WP_CF7_PDF_DIR . 'attachments/' . basename( $attach_image ) );
							$pdf_url_path = class_exists( 'Cf7_Pdf_Submissions' )
								? Cf7_Pdf_Submissions::get_attach_pdf_url_from_settings( $setting_data )
								: WP_CF7_PDF_URL . 'attachments/' . basename( $attach_image );

							if ( class_exists( 'Cf7_Pdf_Submissions' ) ) {
								Cf7_Pdf_Submissions::debug_log(
									'Uploaded PDF mode paths',
									array(
										'path'        => $pdf_file_path,
										'url'         => $pdf_url_path,
										'file_exists' => ( '' !== $pdf_file_path && file_exists( $pdf_file_path ) ),
									)
								);
							}

							if ( '' === $pdf_file_path || ! file_exists( $pdf_file_path ) ) {
								if ( class_exists( 'Cf7_Pdf_Submissions' ) ) {
									Cf7_Pdf_Submissions::debug_log( 'Uploaded PDF file missing for link/copy step', array( 'path' => $pdf_file_path ) );
								}
							} else {
							$pdf_file_path1 = WP_CONTENT_DIR . '/uploads/wpcf7_uploads/' . basename( $attach_image );
							if ( file_exists( $pdf_file_path ) ) {
								wp_mkdir_p( dirname( $pdf_file_path1 ) );
								copy( $pdf_file_path, $pdf_file_path1 );
							}

							$attdataurl_array = $this->wpcf7_pdf_create_attachment( $pdf_file_path );

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

						$cf7pdf_document_root = '';
						if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
							$cf7pdf_document_root = wp_normalize_path( sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) );
						}

						if ( ! empty( $path_dir_cf7 ) && '' !== $cf7pdf_document_root && file_exists( $cf7pdf_document_root . $pdf_file_path1 ) ) {
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
						
						if ( isset( $setting_data['cf7_dettach_pdf'] ) && 'true' === $setting_data['cf7_dettach_pdf'] ) {
							$this->queue_pdf_for_cf7_mail( $pdf_file_path );
						}

					}
				}

			if ( 'true' === $cf7_remove_pdf && ! empty( $attdataurl_array['attach_id'] ) ) {
				$this->cf7pdf_remove_after_mail     = true;
				$this->cf7pdf_remove_attachment_id = (int) $attdataurl_array['attach_id'];
			}
			

			return $wpcf7;

		}

		/**
		 * Enqueue public-facing scripts (PDF download link after submit).
		 */
		public function enqueue_scripts() {
			wp_enqueue_script(
				'cf7-pdf-generation-public-js',
				WP_CF7_PDF_URL . 'assets/js/cf7-pdf-generation-public-min.js',
				array( 'jquery' ),
				Cf7_Pdf_Generation_VERSION,
				false
			);
		}

	}

	/**
	* Run plugins loaded
	*/
	add_action( 'plugins_loaded' , function() {
		new Cf7_Pdf_Generation_Front_Action;
	} );
}
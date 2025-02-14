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

		function wpcf7_pdf_create_attachment($filename)
		{
			// Check the type of file. We'll use this as the 'post_mime_type'.
			$attached_data = array();
			$filetype = wp_check_filetype(basename($filename), null);
			$filetype['type'] = 'application/pdf';

			// Get the path to the upload directory.
			$wp_upload_dir = wp_upload_dir();

			$attachFileName = $wp_upload_dir['path'] . '/' . basename($filename);
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

			$wpcf = WPCF7_ContactForm::get_current();

		    $submission = WPCF7_Submission :: get_instance();
			$unit_tag = $submission->get_meta('unit_tag');
			$posted_data = $submission->get_posted_data();

			$uploaded_files = $submission->uploaded_files();

            $contact_id = $wpcf->id();
		    $setting_data = get_post_meta( $contact_id, 'cf7_pdf', true );
            if(isset($setting_data['cf7_opt_attach_pdf_image'])){
		        $attach_image = $setting_data['cf7_opt_attach_pdf_image'] ? $setting_data['cf7_opt_attach_pdf_image'] : "";
            }
            if(isset($setting_data['cf7_pdf_link_is_enable'])){
		        $cf7_pdf_link_is_enable = $setting_data['cf7_pdf_link_is_enable'] ? $setting_data['cf7_pdf_link_is_enable'] : "";
            }
			if( isset($setting_data['cf7_remove_pdf']) ){
		        $cf7_remove_pdf = trim($setting_data['cf7_remove_pdf']) ? $setting_data['cf7_remove_pdf'] : '';
            } else {
				$cf7_remove_pdf = '';
			}
            if( isset($setting_data['cf7_pdf_download_link_txt']) ){
		        $cf7_pdf_download_link_txt = trim($setting_data['cf7_pdf_download_link_txt']) ? $setting_data['cf7_pdf_download_link_txt'] : __('Click here to download PDF','generate-pdf-using-contact-form-7');
            } else {
				$cf7_pdf_download_link_txt = __('Click here to download PDF','generate-pdf-using-contact-form-7');
			}
			$attdata = array();
		    $date = date_i18n( get_option('date_format') );
			$time = date_i18n( get_option('time_format') );

			if(isset($setting_data['cf7_pdf_link_is_enable']) && $setting_data['cf7_pdf_link_is_enable'] == 'false') {
				$cookie_name = "wp-pdf_path";
				$cookie_value = $attdataurl;
				//86400 = 1 day
				setcookie( $cookie_name, $cookie_value, time() + (86400 * 1), "/"); 
				//86400 = 1 day
				setcookie( 'wp-enable_pdf_link', $cf7_pdf_link_is_enable, time() + (86400 * 1), "/");  
			}

			if( isset($setting_data['cf7_opt_is_enable']) && $setting_data['cf7_opt_is_enable'] == 'true'  )
			{
				if( $setting_data['cf7_dettach_pdf'] == 'true' ||  $setting_data['cf7_pdf_link_is_enable'] == 'true' )
				{
					if( isset($setting_data['cf7_opt_is_attach_enable']) && $setting_data['cf7_opt_is_attach_enable'] == 'true')
					{
		 			
						if($attach_image)
		 				{
		 					$pdf_file_path1 = WP_CONTENT_DIR .'/uploads/wpcf7_uploads/'.$attach_image;

		 					$pdf_file_path = WP_CF7_PDF_DIR .'attachments/'.$attach_image;
		 					$pdf_url_path = WP_CF7_PDF_URL.'attachments/'.$attach_image;

		 					$temp_name = sanitize_text_field(wp_rand());
							copy($pdf_file_path, $pdf_file_path1);
							$attdataurl_array = $this->wpcf7_pdf_create_attachment($pdf_url_path);

							$returnexist = file_exists( $attdataurl_array['absolute_path'] );
							if( $returnexist && ($cf7_pdf_link_is_enable =='false' || $cf7_remove_pdf =='false') ) {
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
		 			}
		 			else
		 			{

		 				/*
		 				* Code of generate PDF
		 				*/
		 				if (!class_exists('\Mpdf\Mpdf')) {

		 				require  WP_CF7_PDF_DIR . 'inc/lib/mpdf/vendor/autoload.php';

							$cf7_opt_margin_header = $setting_data['cf7_opt_margin_header'];
							$cf7_opt_margin_footer = $setting_data['cf7_opt_margin_footer'];
							$cf7_opt_margin_top = $setting_data['cf7_opt_margin_top'];
							$cf7_opt_margin_bottom = $setting_data['cf7_opt_margin_bottom'];
							$cf7_opt_margin_left = isset($setting_data['cf7_opt_margin_left']) ? $setting_data['cf7_opt_margin_left'] : 15;
							$cf7_opt_margin_right = isset($setting_data['cf7_opt_margin_right']) ? $setting_data['cf7_opt_margin_right'] : 15;
							$cf7_pdf_bg_image = isset($setting_data['cf7_pdf_bg_image']) ? $setting_data['cf7_pdf_bg_image'] : '';
							if(!$cf7_opt_margin_header){$cf7_opt_margin_header = '10';}
							if(!$cf7_opt_margin_footer){$cf7_opt_margin_footer = '10';}
							if(!$cf7_opt_margin_top){$cf7_opt_margin_top = '40';}
							if(!$cf7_opt_margin_bottom){$cf7_opt_margin_bottom = '40';}
							if(!$cf7_opt_margin_left){$cf7_opt_margin_left = '15';}
							if(!$cf7_opt_margin_right){$cf7_opt_margin_right = '15';}
							
							$cf7_pdf_download_fp_text = isset($setting_data['cf7_pdf_download_fp_text']) ? $setting_data['cf7_pdf_download_fp_text'] : __('Page','generate-pdf-using-contact-form-7');
							$cf7_pdf_download_fp_pagenumSuffix = isset($setting_data['cf7_pdf_download_fp_pagenumSuffix']) ? $setting_data['cf7_pdf_download_fp_pagenumSuffix'] : '';
							$cf7_pdf_download_fp_nbpgPrefix = isset($setting_data['cf7_pdf_download_fp_nbpgPrefix']) ? $setting_data['cf7_pdf_download_fp_nbpgPrefix'] : '';
							$cf7_pdf_download_fp_nbpgSuffix = isset($setting_data['cf7_pdf_download_fp_nbpgSuffix']) ? $setting_data['cf7_pdf_download_fp_nbpgSuffix'] : '';
							$cf7_pdf_default_font_size = isset($setting_data['cf7_pdf_default_font_size']) ? $setting_data['cf7_pdf_default_font_size'] : '9';

		 					$mpdf = new \Mpdf\Mpdf(['default_font_size' => $cf7_pdf_default_font_size,'mode' => 'utf-8', 'format' => 'A4', 'margin_header' => $cf7_opt_margin_header, 'margin_top' => $cf7_opt_margin_top,'margin_footer' => $cf7_opt_margin_footer, 'margin_bottom' => $cf7_opt_margin_bottom,'default_font' => 'FreeSans','margin_left' => $cf7_opt_margin_left,'margin_right' => $cf7_opt_margin_right, 
							'pagenumPrefix' => $cf7_pdf_download_fp_text, 'pagenumSuffix' => $cf7_pdf_download_fp_pagenumSuffix, 
							'nbpgPrefix' => $cf7_pdf_download_fp_nbpgPrefix, 'nbpgSuffix' => $cf7_pdf_download_fp_nbpgSuffix, 'aliasNbPg' => ' [pagetotal] ' ]);
		 				}

						$mpdf->autoScriptToLang = true;
						$mpdf->baseScript = 1;
						$mpdf->autoVietnamese = true;
						$mpdf->autoArabic = true;
						$mpdf->autoLangToFont = true;
						$mpdf->SetTitle(get_bloginfo( 'name' ));
						$mpdf->SetCreator(get_bloginfo('name'));
						$mpdf->ignore_invalid_utf8 = true;
						$msg_body = $setting_data['cf7_pdf_msg_body'] ? $setting_data['cf7_pdf_msg_body'] : '';
						$cf7_opt_header_pdf_image = $setting_data['cf7_opt_header_pdf_image'] ? $setting_data['cf7_opt_header_pdf_image'] : '';
						$cf7_opt_max_width_logo = $setting_data['cf7_opt_max_width_logo'] ? $setting_data['cf7_opt_max_width_logo'] : '160px';
						$cf7_opt_min_width_logo = $setting_data['cf7_opt_min_width_logo'] ? $setting_data['cf7_opt_min_width_logo'] : '85px';
						$cf7_opt_header_text = $setting_data['cf7_opt_header_text'] ? $setting_data['cf7_opt_header_text'] : '';
						$cf7_opt_footer_text = $setting_data['cf7_opt_footer_text'] ? $setting_data['cf7_opt_footer_text'] : '';

						if( isset($setting_data['cf7_pdf_filename_prefix']) ) {
							$cf7_pdf_filename_prefix = trim($setting_data['cf7_pdf_filename_prefix']);
							$cf7_pdf_filename_prefix = str_replace(' ', '-', $cf7_pdf_filename_prefix);
							$cf7_pdf_filename_prefix = $cf7_pdf_filename_prefix ? $cf7_pdf_filename_prefix : 'CF7';
						} else {
							$cf7_pdf_filename_prefix = 'CF7';
						}
						$current_time = microtime(true);
						$current_time = str_replace(".", "-", $current_time);

	                    foreach ($posted_data as $key => $value) {
							if ( strstr( $msg_body, $key ) ) {
								if(is_array($value)) {
									$value = implode('<br/>', $value);
								} else {
									$value = htmlspecialchars($value);
								}
								if (strpos($key, 'acceptance') !== false) {
									if( $value == 1 ) $acceptance_value =  __('accepted','generate-pdf-using-contact-form-7');
									if( $value == 0 ) $acceptance_value = __('not accepted','generate-pdf-using-contact-form-7');
									$value = $acceptance_value;
								}
								if (strpos($msg_body, '[date]') !== false) {
								    $msg_body = str_replace('[date]',$date,$msg_body);
								    $cf7_pdf_filename_prefix = str_replace('[date]',$date,$cf7_pdf_filename_prefix);
								} if (strpos($msg_body, '[time]') !== false) {
								    $msg_body = str_replace('[time]',$time,$msg_body);
								    $cf7_pdf_filename_prefix = str_replace('[time]',$time,$cf7_pdf_filename_prefix);
								} if (strpos($msg_body, '[random-number]') !== false) {
								    $msg_body = str_replace('[random-number]',$current_time,$msg_body);
								} if (strpos($msg_body, '[_site_url]') !== false) {
								    $msg_body = str_replace('[_site_url]','<a href="'.site_url().'" target="_blank">'.site_url().'</a>',$msg_body);
								} if (strpos($msg_body, '[_site_title]') !== false) {
									$site_title = get_bloginfo( 'name' );
								    $msg_body = str_replace('[_site_title]',$site_title,$msg_body);
								    $cf7_pdf_filename_prefix = str_replace('[_site_title]',$cf7_pdf_filename_prefix,$msg_body);
								} if (strpos($msg_body, '[_site_description]') !== false) {
									$site_description = get_bloginfo( 'description' );
								    $msg_body = str_replace('[_site_description]',$site_description,$msg_body);
								} if (strpos($msg_body, '[remote_ip]') !== false) {
								    $msg_body = str_replace('[remote_ip]',$submission->get_meta('remote_ip'),$msg_body);
								    $cf7_pdf_filename_prefix = str_replace('[remote_ip]',$submission->get_meta('remote_ip'),$cf7_pdf_filename_prefix);
						 		} if (strpos($msg_body, '[_post_title]') !== false) {
									$post_id = $submission->get_meta('container_post_id');
									$post_title = get_the_title($post_id);
									$msg_body = str_replace('[_post_title]', $post_title, $msg_body);
								}
								if( $value == '' ) {
									$msg_body = str_replace('['.$key.']','[noreplace]',$msg_body);
								} else {
									$msg_body = str_replace('['.$key.']',$value,$msg_body);
									$cf7_pdf_filename_prefix = str_replace('['.$key.']',$value,$cf7_pdf_filename_prefix);
								}
								if($uploaded_files){
									foreach ( (array) $uploaded_files as $name => $path ) {
										if (! empty( $path ) ) {
											$file_name = basename($path[0]);
											$msg_body = str_replace('['.$name.']',$file_name,$msg_body);
										}
									}
								}
							}
						}
						
						$msgbody_array = explode("\n", $msg_body);		
						if( $msgbody_array  ) {
							$i = 0;
							foreach($msgbody_array  as $a ) {
								if (strpos($a, 'noreplace') !== false) {
									unset( $msgbody_array[$i] );
								}		
								$i++;
							}
							$msg_body = implode("\n", $msgbody_array ); 
						}

						$html = $msg_body;
						$html = apply_filters( 'cf7_pdf_message_body', $html, $wpcf, $submission );
						if (strpos($html, '<table') === false) {
							$html = nl2br($html);
						}

						/*
						* Require PDF HTML file.
						*/
						require  WP_CF7_PDF_DIR . 'inc/templates/cf7-pdf-generation.public.html.php';

						$mpdf->SetHTMLHeader( $headerContent );
						$mpdf->SetHTMLFooter( $footerContent );

						if($cf7_pdf_bg_image){
							$mpdf->SetDefaultBodyCSS('background', "url('".$cf7_pdf_bg_image."')");
							$mpdf->SetDefaultBodyCSS('background-image-resize', 6);
						}

						$mpdf->WriteHTML($html);

						if( $cf7_pdf_filename_prefix!='' ) {
							$pdf_file_name = $cf7_pdf_filename_prefix.'-'.$current_time.'.pdf';
						} else {
							$pdf_file_name = 'cf7-'.$contact_id.'-'.$current_time.'.pdf';
						}

						$path_dir_cf7 = '';
						foreach ( (array) $uploaded_files as $name => $path ) {

							if (! empty( $path ) ) {
								$xmlFile = pathinfo($path[0]);
								$path_dir_cf7 =  $xmlFile['dirname'];
							}
						}

						$pdf_file_path = WP_CF7_PDF_DIR .'attachments/'.$pdf_file_name;
						$pdf_file_path1 = $path_dir_cf7.'/'.$pdf_file_name;

						$pdf_url_path = WP_CF7_PDF_URL.'attachments/'.$pdf_file_name;

						if (file_exists($_SERVER['DOCUMENT_ROOT'] . $pdf_file_path1)) {

							$mpdf->Output( $pdf_file_path , 'F');
							$mpdf->Output( $pdf_file_path1 , 'F');

						}
						else{
							$mpdf->Output( $pdf_file_path , 'F');
						}

						//till this file upload in attachment folder

						$attdataurl_array = $this->wpcf7_pdf_create_attachment($pdf_url_path);
						$returnexist = file_exists( $attdataurl_array['absolute_path'] );
						if( $returnexist && ($cf7_pdf_link_is_enable =='false' || $cf7_remove_pdf =='false')) {
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
			}

			
			if( $cf7_remove_pdf =='true' ) {
				if( isset( $attdataurl_array['attach_id'] ) ){
					wp_delete_attachment( $attdataurl_array['attach_id'], true );
				}
			}
			

			return $wpcf;

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
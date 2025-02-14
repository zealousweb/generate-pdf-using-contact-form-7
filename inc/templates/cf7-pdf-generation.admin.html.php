<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
/**
* HTML of setting page.
*/
?>

<div class="wrap cf7-pdf-generation-wrapper">
<h1><?php echo esc_html('Generate PDF using Contact Form 7 Settings', 'generate-pdf-using-contact-form-7'); ?></h1>
	<?php
	$args = array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1);
	$cf7Forms = get_posts( $args );

	if ( count($cf7Forms) == 0 ) {
		printf(
			esc_html('No forms have not been found. %s', 'send-pdf-for-contact-form-7'),'<a href="' . esc_url(admin_url('admin.php?page=wpcf7')) . '">' . esc_html('Create your first form here.', 'generate-pdf-using-contact-form-7') . '</a>'
		);
	}	
	else
	{
	?>
		<form method="post" enctype="multipart/form-data" autocomplete="false" action="<?php echo isset($_SERVER['REQUEST_URI']) ? esc_url($_SERVER['REQUEST_URI']) : ''; ?>" name="displayform" id="displayform" >
	
		<table class="form-table">
		<tr valign="top">
		<th scope="row">
		<?php echo esc_html('Select the contact form', 'generate-pdf-using-contact-form-7'); ?>
		<span class="cf7pap-tooltip hide-if-no-js " id="cf7_idform_tooltip_id"></span>
		</th>
		<td>
			<input type="hidden" name="page" value="wp-cf7-send-pdf"/>
			<?php wp_nonce_field('cf7_send_form', 'security-cf7-send-pdf'); ?>
			<select name="cf7_idform" id="cf7_idform" class="wpcf7-form-field" onchange="this.form.submit();">
				<option value="" ><?php echo (esc_html('-- Select a Contact Form --', 'generate-pdf-using-contact-form-7')); ?></option> 
				<?php
					$selected = '';
					foreach ($cf7Forms as $cf_form) {
						if(isset($_POST['cf7_send_form']) && wp_verify_nonce(sanitize_file_name(wp_unslash($_POST['cf7_send_form'])), 'security-cf7-send-pdf')){
							return '';
						}
						if( isset($_POST['cf7_idform']) && sanitize_file_name($_POST['cf7_idform'])!='') { 
							$selected = ($cf_form->ID == sanitize_file_name($_POST['cf7_idform']) ) ? "selected" : "";  
						}
						$form_name = htmlentities($cf_form->post_title, ENT_QUOTES, 'UTF-8');
						echo '<option value="'.esc_attr($cf_form->ID).'" '.esc_attr($selected).'>'.esc_html($form_name).'</option>';
					}
				?>
			</select>
		
		</td>
		</tr>
		</table>
		</form>
	
	<?php } 
	if( isset($_POST['cf7_idform']) &&  sanitize_file_name($_POST['cf7_idform'])!='' ) { 
		$cf7_idform = intval( sanitize_file_name($_POST['cf7_idform']) ); 
		$file = '';$temp = 1;

		if(isset($_POST['action']) && sanitize_file_name($_POST['action'])!='' && isset($_POST['security-cf7-send-pdf']) && wp_verify_nonce(sanitize_file_name(wp_unslash($_POST['security-cf7-send-pdf'])), 'cf7_send_form')) 
		{	
			if ( isset($_FILES['wp_cf7_pdf_settings']['name']['cf7_opt_attach_pdf_image']) && sanitize_file_name($_FILES['wp_cf7_pdf_settings']['name']['cf7_opt_attach_pdf_image']) != "" ) {
				$target_dir = WP_CF7_PDF_DIR . 'attachments/';
				$file = sanitize_file_name($_FILES['wp_cf7_pdf_settings']['name']['cf7_opt_attach_pdf_image']);
				$file = preg_replace('/\s+/', '', $file);
				$path = pathinfo($file);
				$filename = $path['filename'];
				
				// Check file type
				$file_type = wp_check_filetype($file);
			
				// Check if the file is a PDF
				if ($file_type['ext'] !== 'pdf' && $file_type['type'] !== 'application/pdf') {
					$temp = 0; // Set temp variable to indicate failure
					wp_die(esc_html__("File type is not allowed.", "generate-pdf-using-contact-form-7"));
				}
				else {
					// Load WordPress filesystem
					require_once ABSPATH . '/wp-admin/includes/file.php';
					WP_Filesystem();

					global $wp_filesystem;
					$ext = $path['extension'];
					$temp_name = $_FILES['wp_cf7_pdf_settings']['tmp_name']['cf7_opt_attach_pdf_image'];
					$path_filename_ext = $target_dir . $filename . "." . $ext;
					// Get uploaded file details
					if ( $wp_filesystem->move( $temp_name, $path_filename_ext, true ) ) {
						// File successfully uploaded
						$temp = 1;
						// Delete the old file if it exists
						if (isset($_POST['wp_cf7_pdf_settings']['cf7_opt_attach_pdf_old_url'])) {
							$old_file_path = $target_dir . sanitize_file_name( $_POST['wp_cf7_pdf_settings']['cf7_opt_attach_pdf_old_url'] );
							if (file_exists($old_file_path)) {
								wp_delete_file($old_file_path);
							}
						}
					} else {
						$temp = 0;
					}
					
				}
			} else {
				if ( sanitize_file_name($_POST['wp_cf7_pdf_settings']['cf7_opt_attach_pdf_old_url']) ) {
					$file = sanitize_file_name($_POST['wp_cf7_pdf_settings']['cf7_opt_attach_pdf_old_url']); 
				}
			}
			

		if( sanitize_file_name($_POST['wp_cf7_pdf_settings']['cf7_pdf_msg_body']) == '' ){ 
 
			$_POST['wp_cf7_pdf_settings']['cf7_pdf_msg_body'] = __('Your Name : [your-name]
Your Email : [your-email]
Subject : [your-subject]
Your Message : [your-message]','generate-pdf-using-contact-form-7');

		}
		
		if( sanitize_file_name($_POST['wp_cf7_pdf_settings']['cf7_pdf_download_link_txt']) == '' ){  
			$_POST['wp_cf7_pdf_settings']['cf7_pdf_download_link_txt'] = __('Click here to download PDF','generate-pdf-using-contact-form-7'); 
		}

		$before_post = filter_var_array($_POST["wp_cf7_pdf_settings"]); 
		if(!empty($file)){
			$before_post["cf7_opt_attach_pdf_image"] = $file;
		}
		update_post_meta( $cf7_idform, '_wp_cf7_pdf', $before_post );
		update_post_meta( $cf7_idform, 'cf7_pdf', $before_post );
		if($temp == 1) {
			echo '<div class="notice notice-success is-dismissible">
		        <p>'.esc_html(__('Setting saved successfully!', 'generate-pdf-using-contact-form-7')).'</p>
		      </div>';
		} else {
			echo '<div class="notice notice-error is-dismissible">
				<p>'.esc_html(__('There has been an error with uploading PDF.', 'generate-pdf-using-contact-form-7')).'</p>
			</div>';
		}
	}
	$meta_values = get_post_meta( $cf7_idform, '_wp_cf7_pdf', true );
	$meta_values = get_post_meta( $cf7_idform, 'cf7_pdf', true );

?>
	<form method="post" name="setting_form" action="" enctype="multipart/form-data">
		<?php wp_nonce_field('cf7_send_form', 'security-cf7-send-pdf'); ?>
		<input type="hidden" name="action" value="update" />
		<input type="hidden" name="cf7_idform" value="<?php echo esc_attr($cf7_idform); ?>"/>

	    <table class="form-table">
	    	<tr valign="top">
				<th scope="row">
				<?php esc_html_e( 'Enable PDF file operation ?', 'generate-pdf-using-contact-form-7'); ?>
				<span class="cf7pap-tooltip hide-if-no-js " id="cf7_opt_enable_yes_tooltip_id"></span>
				</th>
				<td>
					<?php
					$cf7_opt_is_enable = isset( $meta_values['cf7_opt_is_enable'] ) ? $meta_values['cf7_opt_is_enable'] : 'true';
					?>
					<input type="radio" id="cf7_opt_enable_yes" name="wp_cf7_pdf_settings[cf7_opt_is_enable]" class="cf7_opt_enable" value="true" <?php if( $cf7_opt_is_enable == 'true' ) { echo esc_html(' checked'); } ?> />
					<label for="cf7_opt_enable_yes"><?php echo esc_html__( 'Yes', 'generate-pdf-using-contact-form-7'); ?></label>
					
					<input type="radio" id="cf7_opt_enable_no" name="wp_cf7_pdf_settings[cf7_opt_is_enable]" class="cf7_opt_enable" value="false" <?php if( $cf7_opt_is_enable == 'false' ) { echo esc_html(' checked'); } ?> />
					<label for="cf7_opt_enable_no"><?php echo esc_html__( 'No', 'generate-pdf-using-contact-form-7'); ?></label>
				</td>
	        </tr>
	        <tr valign="top">
		        <td style="padding: 0" colspan="2">
		        	<table class="enable-pdf-link">
		        		<tr valign="top">
		        			<th scope="row">
							<?php echo esc_html__( 'Enable PDF Link with Form Success Message ?', 'generate-pdf-using-contact-form-7'); ?>
							<span class="cf7pap-tooltip hide-if-no-js " id="cf7_pdf_link_enable_yes_tooltip_id"></span>
							</th>
							<td>
								<?php
								$cf7_pdf_link_is_enable = isset( $meta_values['cf7_pdf_link_is_enable'] ) ? $meta_values['cf7_pdf_link_is_enable'] : 'true';
								?>
								<input type="radio" id="cf7_pdf_link_enable_yes" name="wp_cf7_pdf_settings[cf7_pdf_link_is_enable]" class="cf7_pdf_link_enable" value="true" <?php if( $cf7_pdf_link_is_enable == 'true' ) { echo esc_html(' checked'); } ?> />
								<label for="cf7_pdf_link_enable_yes"><?php echo esc_html('Yes', 'generate-pdf-using-contact-form-7'); ?></label>
								
								<input type="radio" id="cf7_pdf_link_enable_no" name="wp_cf7_pdf_settings[cf7_pdf_link_is_enable]" class="cf7_pdf_link_enable" value="false" <?php if( $cf7_pdf_link_is_enable == 'false' ) { echo esc_html(' checked'); } ?> />
								<label for="cf7_pdf_link_enable_no"><?php echo esc_html('No', 'generate-pdf-using-contact-form-7'); ?></label>
							</td>
						</tr>
						<tr valign="top" id="onsent_mail_pdfopt">
		        			<th scope="row">
							<?php echo esc_html('Do you want to remove PDF attachment after mail sent?', 'generate-pdf-using-contact-form-7'); ?>
							</th>
							<td>
								<?php
								$cf7_remove_pdf = isset( $meta_values['cf7_remove_pdf'] ) ? $meta_values['cf7_remove_pdf'] : 'false';
								?>
								<input type="radio" id="cf7_remove_pdf_yes" name="wp_cf7_pdf_settings[cf7_remove_pdf]" class="" value="true" <?php if( $cf7_remove_pdf == 'true' ) { echo esc_html(' checked'); } ?> />
								<label for="cf7_remove_pdf_yes"><?php echo esc_html('Yes', 'generate-pdf-using-contact-form-7'); ?></label>
								
								<input type="radio" id="cf7_remove_pdf_no" name="wp_cf7_pdf_settings[cf7_remove_pdf]" class="" value="false" <?php if( $cf7_remove_pdf == 'false' ) { echo esc_html(' checked'); } ?> />
								<label for="cf7_remove_pdf_no"><?php echo esc_html('No', 'generate-pdf-using-contact-form-7'); ?></label>
							</td>
						</tr>
					</table>
				</td>
	        </tr>
	        <tr valign="top">
		        <td style="padding: 0" colspan="2">
		        	<table class="disable-pdf-link">
		        		
						<tr valign="top" id="dettach_pdf_in_mail">
		        			<th scope="row">
							<?php echo esc_html('Do you want to attach pdf in mail ?', 'generate-pdf-using-contact-form-7'); ?>
							<span class="cf7pap-tooltip hide-if-no-js " id="cf7_pdf_link_disable_pdf_tooltip_id"></span>
							</th>
							<td>
								<?php
								$cf7_dettach_pdf = isset( $meta_values['cf7_dettach_pdf'] ) ? $meta_values['cf7_dettach_pdf'] : 'false';
								?>
								<input type="radio" id="cf7_dettach_pdf_yes" name="wp_cf7_pdf_settings[cf7_dettach_pdf]" class="remove_attach_pdf_k" value="true" <?php if( $cf7_dettach_pdf == 'true' ) { echo esc_html(' checked'); } ?> />
								<label for="cf7_dettach_pdf_yes"><?php echo esc_html('Yes', 'generate-pdf-using-contact-form-7'); ?></label>
								
								<input type="radio" id="cf7_dettach_pdf_no" name="wp_cf7_pdf_settings[cf7_dettach_pdf]" class="remove_attach_pdf_k" value="false" <?php if( $cf7_dettach_pdf == 'false' ) { echo esc_html(' checked'); } ?> />
								<label for="cf7_dettach_pdf_no"><?php echo esc_html('No', 'generate-pdf-using-contact-form-7'); ?></label>
							</td>
						</tr>

					</table>
				</td>
	        </tr>
	        <tr>
	        	<td style="padding: 0" colspan="2">
	        		<table class="enable-pdf" id="hsenablepdf">
				    	<tr valign="top">
				    		<th scope="row">
							<?php echo esc_html(__( 'Do you want to upload pdf or customize pdf?', 'generate-pdf-using-contact-form-7')); ?>
							<span class="cf7pap-tooltip hide-if-no-js " id="cf7_opt_is_attach_enable_tooltip_id"></span>
							</th>
							
				    		<td>
				    			<?php
								$cf7_opt_is_attach_enable = isset( $meta_values['cf7_opt_is_attach_enable'] ) ? $meta_values['cf7_opt_is_attach_enable'] : 'false';
								?>
								<input type="radio" class="cf7_opt_attach_enable" id="cf7_opt_attach_enable_yes" name="wp_cf7_pdf_settings[cf7_opt_is_attach_enable]" value="true" <?php if( $cf7_opt_is_attach_enable == 'true' ) { echo esc_html(' checked'); } ?> />
								<label for="cf7_opt_attach_enable_yes"><?php echo esc_html('Yes', 'generate-pdf-using-contact-form-7'); ?></label>
								<input type="radio" class="cf7_opt_attach_enable" id="cf7_opt_attach_enable_no" name="wp_cf7_pdf_settings[cf7_opt_is_attach_enable]" value="false" <?php if( $cf7_opt_is_attach_enable == 'false' ) { echo esc_html(' checked'); } ?> />
								<label for="cf7_opt_attach_enable_no"><?php echo esc_html('No', 'generate-pdf-using-contact-form-7'); ?></label>
				    		</td>
				    	</tr>
				    	<tr>
				    		<td style="padding: 0" colspan="2">
					    	<table class="pdf-genrate" style="display: none">

								<tr valign="top">
									<th scope="row">
									<?php echo esc_html('Do you want to customize header logo in PDF?', 'generate-pdf-using-contact-form-7'); ?>
									<span class="cf7pap-tooltip hide-if-no-js " id="cf7_opt_header_pdf_image_tooltip_id"></span>
									</th>
									<td class="upload-header-logo-row">
										<?php
										$cf7_opt_header_pdf_image = isset( $meta_values['cf7_opt_header_pdf_image'] ) ? $meta_values['cf7_opt_header_pdf_image'] : '';
										?>
										<div class="upload-header-logo-file">
										<input id="cf7_opt_upload_image" type="text" size="50" name="wp_cf7_pdf_settings[cf7_opt_header_pdf_image]" value="<?php echo esc_url($cf7_opt_header_pdf_image);?>" />
										
										<input id="cf7_opt_upload_image_current" type="hidden" value="<?php echo esc_url($cf7_opt_header_pdf_image);?>" />
																			
										<input id="cf7_opt_upload_image_button" class="button" type="button" value="<?php echo esc_attr('Select or Upload header logo', 'generate-pdf-using-contact-form-7'); ?>" />
										<span class="err-msg" id="upload-header-logo-err"></span>
										</div>
										<div id="cf7_opt_dis_img">
											<?php
											if($cf7_opt_header_pdf_image){
												echo '<img id="cf7_opt_display_image" src="'.esc_url_raw($cf7_opt_header_pdf_image).'" height="150px" width="200px" /><a class="close remove-upload-header-logo" href="#" ></a>';
											}
											?>
										</div>
										
									</td>
							    </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html('Max Width for logo', 'generate-pdf-using-contact-form-7'); ?></th>
									<td>
										<?php
										$cf7_opt_max_width_logo = isset( $meta_values['cf7_opt_max_width_logo'] ) ? $meta_values['cf7_opt_max_width_logo'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_max_width_logo]" id="cf7_opt_max_width_logo" value="<?php echo esc_attr($cf7_opt_max_width_logo); ?>" style="width: 100%;" placeholder="160px">
									</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Min Width for logo', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_opt_min_width_logo = isset( $meta_values['cf7_opt_min_width_logo'] ) ? $meta_values['cf7_opt_min_width_logo'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_min_width_logo]" id="cf7_opt_min_width_logo" value="<?php echo esc_attr($cf7_opt_min_width_logo); ?>" style="width: 100%;" placeholder="85px">
									</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Set PDF Margin of Header', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_opt_margin_header = isset( $meta_values['cf7_opt_margin_header'] ) ? $meta_values['cf7_opt_margin_header'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_margin_header]" id="cf7_opt_margin_header" value="<?php echo esc_attr($cf7_opt_margin_header); ?>" style="width: 100%;" placeholder="10">
									</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Set PDF Margin of Footer', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_opt_margin_footer = isset( $meta_values['cf7_opt_margin_footer'] ) ? $meta_values['cf7_opt_margin_footer'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_margin_footer]" id="cf7_opt_margin_footer" value="<?php echo esc_attr($cf7_opt_margin_footer); ?>" style="width: 100%;" placeholder="10">
									</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Set PDF Margin of Top', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_opt_margin_top = isset( $meta_values['cf7_opt_margin_top'] ) ? $meta_values['cf7_opt_margin_top'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_margin_top]" id="cf7_opt_margin_top" value="<?php echo esc_attr($cf7_opt_margin_top); ?>" style="width: 100%;" placeholder="40">
									</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Set PDF Margin of Bottom', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_opt_margin_bottom = isset( $meta_values['cf7_opt_margin_bottom'] ) ? $meta_values['cf7_opt_margin_bottom'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_margin_bottom]" id="cf7_opt_margin_bottom" value="<?php echo esc_attr($cf7_opt_margin_bottom); ?>" style="width: 100%;" placeholder="40">
									</td>
						        </tr>
								
								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Set PDF Margin of Left', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_opt_margin_left = isset( $meta_values['cf7_opt_margin_left'] ) ? $meta_values['cf7_opt_margin_left'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_margin_left]" id="cf7_opt_margin_left" value="<?php echo esc_attr($cf7_opt_margin_left); ?>" style="width: 100%;" placeholder="40">
									</td>
						        </tr>
								
								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Set PDF Margin of Right', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_opt_margin_right = isset( $meta_values['cf7_opt_margin_right'] ) ? $meta_values['cf7_opt_margin_right'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_margin_right]" id="cf7_opt_margin_right" value="<?php echo esc_attr($cf7_opt_margin_right); ?>" style="width: 100%;" placeholder="40">
									</td>
						        </tr>


							    <tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'PDF Top Right Header Texts', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_opt_header_text = isset( $meta_values['cf7_opt_header_text'] ) ? $meta_values['cf7_opt_header_text'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_header_text]" id="cf7_opt_header_text" value="<?php echo esc_attr($cf7_opt_header_text); ?>" style="width: 100%;">
									</td>
						        </tr>

						        <tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'PDF Bottom Left Footer Texts', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_opt_footer_text = isset( $meta_values['cf7_opt_footer_text'] ) ? $meta_values['cf7_opt_footer_text'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_opt_footer_text]" id="cf7_opt_footer_text" value="<?php echo esc_attr($cf7_opt_footer_text); ?>" style="width: 100%;">
									</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'PDF Text Font size', 'generate-pdf-using-contact-form-7')); ?>
									<span class="cf7pap-tooltip hide-if-no-js " id="cf7_pdf_font_body_tooltip_id"></span>
									</th>
									<td>
										<?php
										$cf7_pdf_default_font_size = isset( $meta_values['cf7_pdf_default_font_size'] ) ? $meta_values['cf7_pdf_default_font_size'] : '9';
										?>
										<input type="number" min="6" max="30" name="wp_cf7_pdf_settings[cf7_pdf_default_font_size]" id="cf7_pdf_default_font_size" value="<?php echo esc_attr($cf7_pdf_default_font_size); ?>" style="width: 30%;">
									</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Field tags', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$contact_form = WPCF7_ContactForm::get_instance($cf7_idform);
										$i = 0;
										foreach ( (array) $contact_form->collect_mail_tags() as $mail_tag ) {
											$pattern = sprintf( '/\[(_[a-z]+_)?%s([ \t]+[^]]+)?\]/',preg_quote( $mail_tag, '/' ) );
											echo '<span class="mail_tag" id="mail_tag_'.esc_html($i).'" style="cursor: pointer;"><strong> ['.esc_html($mail_tag).'] </strong></span>&nbsp;';
											
											$i++;
										}
										?>
									</td>
						        </tr>

						        <tr valign="top">
						        	<th scope="row">
									<?php echo esc_html(__( 'PDF Message body', 'generate-pdf-using-contact-form-7')); ?>
									<span class="cf7pap-tooltip hide-if-no-js " id="cf7_pdf_msg_body_tooltip_id"></span>
									</th>
									
						        	<td>
						        		<?php
										$cf7_pdf_msg_body = isset( $meta_values['cf7_pdf_msg_body'] ) && $meta_values['cf7_pdf_msg_body']!='' ? $meta_values['cf7_pdf_msg_body'] :
'Your Name : [your-name]
Your Email : [your-email]
Subject : [your-subject]
Your Message : [your-message]';
											?>
						        		<textarea id="code" name="wp_cf7_pdf_settings[cf7_pdf_msg_body]"><?php echo esc_html($cf7_pdf_msg_body); ?></textarea>
						        	</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'PDF File Name Prefix', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_pdf_filename_prefix = isset( $meta_values['cf7_pdf_filename_prefix'] ) ? $meta_values['cf7_pdf_filename_prefix'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_pdf_filename_prefix]" id="cf7_pdf_filename_prefix" value="<?php echo esc_attr($cf7_pdf_filename_prefix); ?>" style="width: 50%;" placeholder="CF7">
									</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'PDF File background Image', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_pdf_bg_image = isset( $meta_values['cf7_pdf_bg_image'] ) ? $meta_values['cf7_pdf_bg_image'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_pdf_bg_image]" id="cf7_pdf_bg_image" value="<?php echo esc_url($cf7_pdf_bg_image); ?>" style="width: 80%;" placeholder="<?php echo esc_attr__( 'PDF background Image (JPG, GIF, PNG, WMF and SVG) URL', 'generate-pdf-using-contact-form-7'); ?>">
									</td>
						        </tr>
								
								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Download file link text', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_pdf_download_link_txt = isset( $meta_values['cf7_pdf_download_link_txt'] ) ? $meta_values['cf7_pdf_download_link_txt'] : __('Click here to download PDF','generate-pdf-using-contact-form-7');
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_pdf_download_link_txt]" id="cf7_pdf_download_link_txt" value="<?php echo esc_attr($cf7_pdf_download_link_txt); ?>" style="width: 50%;">
									</td>
						        </tr>

								<tr valign="top">
									<th scope="row"><?php echo esc_html(__( 'Footer Pagination', 'generate-pdf-using-contact-form-7')); ?></th>
									<td>
										<?php
										$cf7_pdf_download_fp_text = isset( $meta_values['cf7_pdf_download_fp_text'] ) ? $meta_values['cf7_pdf_download_fp_text'] : '' ;
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_pdf_download_fp_text]" id="cf7_pdf_download_fp_text" value="<?php echo esc_attr($cf7_pdf_download_fp_text); ?>" style="width: 40%;" placeholder="<?php esc_attr('pagenumPrefix ','generate-pdf-using-contact-form-7'); ?>">
										
										<?php
										$cf7_pdf_download_fp_pagenumSuffix = isset( $meta_values['cf7_pdf_download_fp_pagenumSuffix'] ) ? $meta_values['cf7_pdf_download_fp_pagenumSuffix'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_pdf_download_fp_pagenumSuffix]" id="cf7_pdf_download_fp_pagenumSuffix" value="<?php echo esc_attr($cf7_pdf_download_fp_pagenumSuffix); ?>" style="width: 40%;" placeholder="<?php esc_attr('pagenumSuffix','generate-pdf-using-contact-form-7'); ?>">
										<br>
										<br>
										<?php
										$cf7_pdf_download_fp_nbpgPrefix = isset( $meta_values['cf7_pdf_download_fp_nbpgPrefix'] ) ? $meta_values['cf7_pdf_download_fp_nbpgPrefix'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_pdf_download_fp_nbpgPrefix]" id="cf7_pdf_download_fp_nbpgPrefix" value="<?php echo esc_attr($cf7_pdf_download_fp_nbpgPrefix); ?>" style="width: 40%;" placeholder="<?php esc_attr('nbpgPrefix','generate-pdf-using-contact-form-7'); ?>">
										
										<?php
										$cf7_pdf_download_fp_nbpgSuffix = isset( $meta_values['cf7_pdf_download_fp_nbpgSuffix'] ) ? $meta_values['cf7_pdf_download_fp_nbpgSuffix'] : '';
										?>
										<input type="text" name="wp_cf7_pdf_settings[cf7_pdf_download_fp_nbpgSuffix]" id="cf7_pdf_download_fp_nbpgSuffix" value="<?php echo esc_attr($cf7_pdf_download_fp_nbpgSuffix); ?>" style="width:40%;" placeholder="<?php esc_attr('nbpgSuffix','generate-pdf-using-contact-form-7'); ?>">
										<br><br>
										<?php echo esc_html__('For more information','generate-pdf-using-contact-form-7'); ?>: <a href="https://mpdf.github.io/reference/mpdf-variables/pagenumprefix.html" target="_blank">https://mpdf.github.io/reference/mpdf-variables/pagenumprefix.html</a> 
									</td>
						        </tr>

					    	</table>
					    	</td>
					    </tr>
					    <tr>
					    	<td style="padding: 0" colspan="2">
					    	<table class="pdf-attach" style="display: none">
						        <tr valign="top">
						        	<th scope="row">
									<?php echo esc_html(__( 'Attach PDF', 'generate-pdf-using-contact-form-7')); ?>
									<span class="cf7pap-tooltip hide-if-no-js " id="cf7_opt_attach_pdf_image_tooltip_id"></span>
									</th>
						        	<td class="upload-pdf-file-row">
										<?php
										$cf7_opt_attach_pdf_image = isset( $meta_values['cf7_opt_attach_pdf_image'] ) ? $meta_values['cf7_opt_attach_pdf_image'] : '';
										?>
										<div class="upload-pdf-file-input">
										<input type="file" onchange="ValidateSize(this)" name="wp_cf7_pdf_settings[cf7_opt_attach_pdf_image]" id="cf7_opt_attach_pdf_image" accept="application/pdf">
										<input type="hidden" name="wp_cf7_pdf_settings[cf7_opt_attach_pdf_old_url]" id="cf7_opt_attach_pdf_old_url" value="<?php echo esc_attr($cf7_opt_attach_pdf_image); ?>">
										
										<span class="err-msg" id="upload-pdf-err"></span>
										</div>
										
										<?php if( $cf7_opt_attach_pdf_image ) { 
										$pdf_logo = WP_CF7_PDF_URL .'assets/images/pdf-logo.png';
										$attachments_pdf = WP_CF7_PDF_URL.'attachments/'.$cf7_opt_attach_pdf_image;
										$icon_right_top = WP_CF7_PDF_URL.'assets/images/arrow-right-top.png';
										?>
										<div class="upload-pdf-file-block">
											<strong><?php echo esc_html(__( 'Attached PDF file', 'generate-pdf-using-contact-form-7')); ?>: </strong>
											
											<div class="pdf-remove-wrapper">
											<img class="pdf-logo-icon" src="<?php echo esc_url($pdf_logo); ?>">			
											<a class="close remove-upload-pdf" href="#"></a>
											</div>
											
											<div class="pdf-title-wrapper">
											<h4><a href="<?php echo esc_url($attachments_pdf); ?>" target="_blank"><?php echo esc_html($cf7_opt_attach_pdf_image); ?> <img class="pdf-logo-icon" src="<?php echo esc_url($icon_right_top); ?>">	
											</a></h4>
											</div>
										</div>
										<?php } ?>

									</td>
						        </tr>

					    	</table>
					    	</td>
					    </tr>
					</table>
				</td>
			</tr>
	    </table>

	    <?php submit_button('', ' button-primary cf7-pdf-submit'); ?>

	</form>
<?php
}
?>
</div>

<?php

add_action('admin_print_footer_scripts', function() { 
	?>
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {

			//jQuery selector to point to
			jQuery( '#cf7_idform_tooltip_id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7_idform_tooltip_id' ).pointer({
					pointerClass: 'wp-pointer cf7pap-pointer',
					content: '<?php
					 echo '<h3>' . esc_html__( 'Select the contact form', 'generate-pdf-using-contact-form-7' ) . '</h3>' . 
                     '<p>' . esc_html__( 'Select the form that you want to amend before sending as a PDF attachment', 'generate-pdf-using-contact-form-7' ) . '</p>'; ?>',
					position: 'left center',
				} ).pointer('open');
			} );
			
			jQuery( '#cf7_opt_enable_yes_tooltip_id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7_opt_enable_yes_tooltip_id' ).pointer({
					pointerClass: 'wp-pointer cf7pap-pointer',
					content: '<?php
					echo '<h3>'. esc_html__('Enable PDF file operation?', 'generate-pdf-using-contact-form-7' ).'</h3>'.
						'<p>' . esc_html__('You can disable / enable PDF attachment functionality for each form.','generate-pdf-using-contact-form-7'). '</p>';?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7_pdf_link_enable_yes_tooltip_id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7_pdf_link_enable_yes_tooltip_id' ).pointer({
					pointerClass: 'wp-pointer cf7pap-pointer',
					content: '<?php
					echo '<h3>'. esc_html__('Enable PDF Link with Form Success Message ?','generate-pdf-using-contact-form-7').'</h3>' .
						'<p>'. esc_html__('You can disable / enable PDF Link with Form Success Message for each form.', 'generate-pdf-using-contact-form-7'). '</p>';?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7_pdf_link_disable_pdf_tooltip_id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7_pdf_link_disable_pdf_tooltip_id' ).pointer({
					pointerClass: 'wp-pointer cf7pap-pointer',
					content: '<?php
					echo '<h3>'. esc_html__( 'Enable pdf attachment in mail ? ','generate-pdf-using-contact-form-7').'</h3>' .
						'<p>'. esc_html__('You can disable / enable PDF attachment in mail','generate-pdf-using-contact-form-7').'</p>'; ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			
			jQuery( '#cf7_opt_is_attach_enable_tooltip_id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7_opt_is_attach_enable_tooltip_id' ).pointer({
					pointerClass: 'wp-pointer cf7pap-pointer',
					content: '<?php
					echo '<h3>'. esc_html__('Want to attach own PDF in mail ?','generate-pdf-using-contact-form-7').'</h3>'.
						'<p>'. esc_html__('You can also attach any predefined PDF file from your system, e.g. brochure.','generate-pdf-using-contact-form-7').'</p>';?>',
					position: 'left center',
				} ).pointer('open');
			} );
			
			jQuery( '#cf7_opt_header_pdf_image_tooltip_id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7_opt_header_pdf_image_tooltip_id' ).pointer({
					pointerClass: 'wp-pointer cf7pap-pointer',
					content: '<?php
					echo '<h3>'. esc_html__('PDF header logo','generate-pdf-using-contact-form-7').'</h3>'.
						'<p>'. esc_html__('Customize header logo, upload logo of approx 160px X 85px the logo will automatically reflect on the top-left side of the PDF document.Only allow JPEG/PNG file format.','generate-pdf-using-contact-form-7').'</p>'; ?>',
					position: 'left center',
				} ).pointer('open');
			} );
			
			
			jQuery( '#cf7_pdf_msg_body_tooltip_id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7_pdf_msg_body_tooltip_id' ).pointer({
					pointerClass: 'wp-pointer cf7pap-pointer',
					content: '<?php
					echo '<h3>'. esc_html__('Message body','generate-pdf-using-contact-form-7').'</h3>'.
						'<p>'. esc_html__('You can manage body content of the message which will automatically reflect in the PDF attachement. For the new page you can use tag For acceptance checkbox cf7 tag prefix should be like [acceptance-123]','generate-pdf-using-contact-form-7') .'</p>';?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7_pdf_font_body_tooltip_id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7_pdf_font_body_tooltip_id' ).pointer({
					pointerClass: 'wp-pointer cf7pap-pointer',
					content: '<?php
					echo '<h3>'. esc_html__('PDF Text Font Size','generate-pdf-using-contact-form-7') .'</h3>' .
						'<p>'. esc_html__('You can set PDF font size from this option. For Proper outpup we have set Min - 6px and Max - 30px do not exceed from that. Default Font size is 9px.','generate-pdf-using-contact-form-7').'</p>'; ?>',
					position: 'left center',
				} ).pointer('open');
			} );

			jQuery( '#cf7_opt_attach_pdf_image_tooltip_id' ).on( 'mouseenter click', function() {
				jQuery( 'body .wp-pointer-buttons .close' ).trigger( 'click' );
				jQuery( '#cf7_opt_attach_pdf_image_tooltip_id' ).pointer({
					pointerClass: 'wp-pointer cf7pap-pointer',
					content: '<?php
					echo '<h3>'. esc_html__('Attach PDF','generate-pdf-using-contact-form-7').'</h3>' .
						'<p>'. esc_html__('Exceed limit of PDF file is 25MB.','generate-pdf-using-contact-form-7').'</p>'; ?>',
					position: 'left center',
				} ).pointer('open');
			} );
		} );
		//]]>
	</script>
	<?php
} );
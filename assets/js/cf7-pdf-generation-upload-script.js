jQuery(document).ready(function() {
	var cordemirror_int = 0;
	jQuery('#cf7_opt_upload_image_button').click(function() {
        tb_show( '', 'media-upload.php?type=image&amp;TB_iframe=true' );
        window.send_to_editor = function(html) {
		if(html.match(/<img/))
		{
			imgurl = jQuery(html).attr('src');
			jQuery('#cf7_opt_upload_image').val(imgurl);
			if( imgurl ) {
				var lastIndex  = imgurl.lastIndexOf(".");
				var file_ext = imgurl.substr(lastIndex + 1);
				if(file_ext == 'gif' || file_ext == 'svg') {
					jQuery('#upload-header-logo-err').show();
					var cf7_opt_upload_image_current =  jQuery('#cf7_opt_upload_image_current').val();
					jQuery('#cf7_opt_upload_image').val(cf7_opt_upload_image_current);
					jQuery('#upload-header-logo-err').text('Please select JPEG/PNG file only');
				} else {	
					jQuery('#upload-header-logo-err').hide();	
					jQuery('#cf7_opt_dis_img').show();	
					jQuery('#cf7_opt_upload_image_current').val(imgurl);
					jQuery('#cf7_opt_dis_img').html('<img id="cf7_opt_display_image" src="'+imgurl+'" height="150px" width="200px" /><a class="close remove-upload-header-logo" href="#" ></a>');				
				}
			} else {
				jQuery('#cf7_opt_dis_img').hide();
				jQuery('#cf7_opt_upload_image').val('');
				jQuery('#upload-header-logo-err').text('Please select JPEG/PNG file only.');
			}
			tb_remove();
	 	}
		else
		{
			jQuery('#upload-header-logo-err').show();
			var cf7_opt_upload_image_current =  jQuery('#cf7_opt_upload_image_current').val();
			jQuery('#cf7_opt_upload_image').val(cf7_opt_upload_image_current);
			jQuery('#upload-header-logo-err').text('Please select JPEG/PNG file only');
			tb_remove();
		}
	}
    });

	var enable_value = jQuery('input[type=radio].cf7_opt_enable:checked').val();
	if (enable_value == 'true') {
		jQuery('.enable-pdf').show();
		jQuery('.enable-pdf-link').show();
	}
	else if (enable_value == 'false') {
		jQuery('.enable-pdf').hide();
		jQuery('.enable-pdf-link').hide();
	}

  var radio_option = jQuery('input[type=radio].cf7_opt_attach_enable:checked');
    jQuery(radio_option).each(function(i){
    var radio_value = jQuery(this).val();
    if (radio_value == 'true') {
          jQuery('.pdf-attach').show();
      }
      else if (radio_value == 'false') {
         jQuery('.pdf-genrate').show();
      }
  });
  
   jQuery('.cf7_pdf_link_enable').change(function(){
	   	var radio_option_value = jQuery('input[type=radio].cf7_pdf_link_enable:checked').val();
		if (radio_option_value == 'true') {
			jQuery('#onsent_mail_pdfopt').hide();
		} else {
			jQuery('#onsent_mail_pdfopt').show();			
		}
	}).change(); 

  jQuery('.cf7_opt_attach_enable').change(function(){
  	var radio_option_value = jQuery('input[type=radio].cf7_opt_attach_enable:checked').val();

	if (radio_option_value == 'true') {
		jQuery('.pdf-attach').show();
		jQuery('.pdf-genrate').hide();

	}
	else if (radio_option_value == 'false') {
		if(cordemirror_int == 0)
		{
			cordemirror_int = 1;
			var myTextarea = document.getElementById("code");

			if(myTextarea != null){
				var editor = CodeMirror.fromTextArea(myTextarea, {
				    mode: "htmlmixed",
					lineNumbers: true,
					theme : '3024-night',
					autofocus: true,
				});
				
				editor.save();
				setTimeout(function() {
				    editor.refresh();
				},300);
				
				jQuery('.CodeMirror').resizable({
					resize: function() {
						editor.setSize(jQuery(this).width(), jQuery(this).height());
					}
				});
			}
		}
		else
		{

		}
		jQuery('.pdf-genrate').show();
		jQuery('.pdf-attach').hide();
		
	}
	
  }).change();

  jQuery('.cf7_opt_enable').change(function(){

	if (this.value == 'true') {
		jQuery('.enable-pdf').show();
		jQuery('.enable-pdf-link').show();
	}
	else if (this.value == 'false') {
		jQuery('.enable-pdf').hide();
		jQuery('.enable-pdf-link').hide();

	}

  });
  
 
  jQuery(document).on('click','.remove-upload-header-logo',function(){
	var r = confirm("Are you sure want to delete the header logo ?");
	if (r == true) {
		jQuery('#cf7_opt_dis_img').hide();
		jQuery('#cf7_opt_upload_image').val('');
	} else {
	  return false;
	}
  });

  jQuery(document).on('click','.remove-upload-pdf',function(){
	var r = confirm("Are you sure want to delete the PDF file ?");
	if (r == true) {
		jQuery('.upload-pdf-file-block').hide();
		jQuery('#cf7_opt_attach_pdf_old_url').val('');
	} else {
	  return false;
	}
  });
  
  jQuery(document).on('click','.cf7-pdf-submit',function(){
	
		var radio_option_value = jQuery('input[type=radio].cf7_opt_attach_enable:checked').val();
		if( radio_option_value == 'true' ){
			var cf7_opt_attach_pdf_old_url = jQuery('#cf7_opt_attach_pdf_old_url').val();			
			if( cf7_opt_attach_pdf_old_url=="" ) {
				jQuery("#cf7_opt_attach_pdf_image").attr("required", true);
			}
			var file = jQuery('#cf7_opt_attach_pdf_image').val();

			if( file ) {
 				var fup = document.getElementById('cf7_opt_attach_pdf_image');
				var fileName = fup.value;
				var ext = fileName.substring(fileName.lastIndexOf('.') + 1);
				
				var file_size = jQuery('#cf7_opt_attach_pdf_image')[0].files[0].size;
				 
				if( ext!="pdf")  {
					jQuery('.upload-pdf-err').text('Please attach PDF file only.');
					jQuery('#upload-pdf-err').show();
					return false;
				} else if ( file_size > 26214400) { // 25 mb for bytes.
					jQuery('#upload-pdf-err').text('File size should be less than 25MB!');
					jQuery('#upload-pdf-err').show();
					return false;
				} else {
					jQuery('.upload-pdf-err').hide();
					return true;
				}	
			}
		} else {
			jQuery("#cf7_opt_attach_pdf_image").attr("required", false);
			jQuery('#cf7_opt_attach_pdf_image').val('');
		}
			
  });
  
	jQuery(document).on('click','#wpbody',function(e){
		jQuery( ".cf7pap-pointer").hide();		 
	});

});

function ValidateSize(file) {

	var oFile = file.files[0];
	
	var fup = document.getElementById('cf7_opt_attach_pdf_image');
	var fileName = fup.value;
	var ext = fileName.substring(fileName.lastIndexOf('.') + 1);
	if( ext!="pdf")  {
	   jQuery('#upload-pdf-err').text('Please attach PDF file only.');
	   jQuery('#upload-pdf-err').show();
	   return false;
	} else if (oFile.size > 26214400) { // 25 mb for bytes.
		 jQuery('#upload-pdf-err').text('File size should be less than 25MB!');
		 	   jQuery('#upload-pdf-err').show();
		return false;
	} else {
		jQuery('#upload-pdf-err').hide();
		return true;
	}
}
(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	function getCookie(cname) {
		var name = cname + "=";
		var decodedCookie = decodeURIComponent(document.cookie);
		var ca = decodedCookie.split(';');
		for(var i = 0; i <ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') {
			  c = c.substring(1);
			}
			if (c.indexOf(name) == 0) {
			  return c.substring(name.length, c.length);
			}
		}
	  return "";
	}
	
	function setCookie(cname, cvalue) {
	  var expires = "expires=Thu, 01 Jan 1970 00:00:01 GMT"; 
	  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
	}
	
	document.addEventListener( 'wpcf7mailsent', function( event ) {

		var pdf_value = getCookie('wp-pdf_path');
		var enable_pdf_link = getCookie('wp-enable_pdf_link');
		var pdf_download_link_txt = getCookie('wp-pdf_download_link_txt');
		if(enable_pdf_link == 'true')
		{
			if( pdf_value ){ 
				setTimeout(function(){ 
					if ($(".wpcf7").hasClass("wpcf7-mail-sent-ok")) {
						$('.wpcf7-mail-sent-ok').append( '<br><a class="download-lnk-pdf" href="'+pdf_value+'" target="_blank">'+pdf_download_link_txt+'</a>' );	    
						setCookie("pdf_path", '');
					}
					else
					{
						$('.wpcf7-response-output').append( '<br><a class="download-lnk-pdf" href="'+pdf_value+'" target="_blank">'+pdf_download_link_txt+'</a>' );	    
						setCookie("pdf_path", '');
					}
	
				}, 250);
			}
		}
	}, false );

})( jQuery );
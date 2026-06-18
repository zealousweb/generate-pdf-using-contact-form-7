( function( $ ) {
	'use strict';

	function getCookie( name ) {
		var prefix = name + '=';
		var cookies = decodeURIComponent( document.cookie ).split( ';' );

		for ( var i = 0; i < cookies.length; i++ ) {
			var cookie = cookies[ i ];

			while ( ' ' === cookie.charAt( 0 ) ) {
				cookie = cookie.substring( 1 );
			}

			if ( 0 === cookie.indexOf( prefix ) ) {
				return cookie.substring( prefix.length, cookie.length );
			}
		}

		return '';
	}

	function expireCookie( name ) {
		document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/';
	}

	function clearPdfCookies() {
		expireCookie( 'wp-pdf_path' );
		expireCookie( 'wp-enable_pdf_link' );
		expireCookie( 'wp-pdf_download_link_txt' );
		expireCookie( 'wp-unit_tag' );
	}

	function appendPdfLink( $output, url, text ) {
		if ( ! url || ! $output || ! $output.length ) {
			return;
		}

		if ( ! text ) {
			text = 'Click here to download PDF';
		}

		$output.append(
			'<br><a class="download-lnk-pdf" href="' + url + '" target="_blank">' + text + '</a>'
		);
	}

	document.addEventListener( 'wpcf7mailsent', function( event ) {
		var apiResponse = event.detail && event.detail.apiResponse ? event.detail.apiResponse : {};
		var unitTag = event.detail && event.detail.unitTag ? event.detail.unitTag : getCookie( 'wp-unit_tag' );
		var $output = unitTag ? $( '#' + unitTag + ' .wpcf7-response-output' ) : $( '.wpcf7-mail-sent-ok .wpcf7-response-output' );

		if ( 'false' === apiResponse.cf7_pdf_download_link_enabled ) {
			clearPdfCookies();
			return;
		}

		if ( apiResponse.cf7_pdf_download_link && apiResponse.cf7_pdf_download_link.url ) {
			appendPdfLink(
				$output,
				apiResponse.cf7_pdf_download_link.url,
				apiResponse.cf7_pdf_download_link.text
			);
			clearPdfCookies();
			return;
		}

		var pdfPath = getCookie( 'wp-pdf_path' );
		var enablePdfLink = getCookie( 'wp-enable_pdf_link' );
		var linkText = getCookie( 'wp-pdf_download_link_txt' );

		if ( 'true' !== enablePdfLink || ! pdfPath ) {
			clearPdfCookies();
			return;
		}

		setTimeout( function() {
			appendPdfLink( $output, pdfPath, linkText );
			clearPdfCookies();
		}, 250 );
	}, false );
}( jQuery ) );

( function( $ ) {
	'use strict';

	function getCookie( name ) {
		var prefix = name + '=';
		var parts = decodeURIComponent( document.cookie ).split( ';' );

		for ( var i = 0; i < parts.length; i++ ) {
			var part = parts[ i ];
			while ( ' ' === part.charAt( 0 ) ) {
				part = part.substring( 1 );
			}
			if ( 0 === part.indexOf( prefix ) ) {
				return part.substring( prefix.length, part.length );
			}
		}

		return '';
	}

	function clearCookie( name ) {
		document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:01 GMT;path=/';
	}

	function clearPdfLinkCookies() {
		clearCookie( 'wp-pdf_path' );
		clearCookie( 'wp-enable_pdf_link' );
		clearCookie( 'wp-pdf_download_link_txt' );
		clearCookie( 'wp-unit_tag' );
	}

	function resolveLinkData( event ) {
		var apiResponse = event && event.detail && event.detail.apiResponse;

		if ( apiResponse && true === apiResponse.cf7_pdf_download_link_enabled && apiResponse.cf7_pdf_download_link ) {
			return {
				enabled: true,
				url: apiResponse.cf7_pdf_download_link.url || '',
				text: apiResponse.cf7_pdf_download_link.text || ''
			};
		}

		if ( apiResponse && false === apiResponse.cf7_pdf_download_link_enabled ) {
			return { enabled: false };
		}

		if ( 'true' !== getCookie( 'wp-enable_pdf_link' ) ) {
			return { enabled: false };
		}

		var pdfUrl = getCookie( 'wp-pdf_path' );
		if ( ! pdfUrl ) {
			return { enabled: false };
		}

		return {
			enabled: true,
			url: pdfUrl,
			text: getCookie( 'wp-pdf_download_link_txt' ) || 'Click here to download PDF'
		};
	}

	function getResponseOutput( event ) {
		var unitTag = getCookie( 'wp-unit_tag' );
		var $output = unitTag ? $( '#' + unitTag + ' .wpcf7-response-output' ) : $();

		if ( ! $output.length ) {
			$output = $( event.target ).find( '.wpcf7-response-output' );
		}

		if ( ! $output.length ) {
			$output = $( '.wpcf7-form.sent .wpcf7-response-output' );
		}

		return $output;
	}

	function appendPdfLink( event ) {
		var linkData = resolveLinkData( event );

		if ( ! linkData.enabled || ! linkData.url ) {
			clearPdfLinkCookies();
			return;
		}

		var $output = getResponseOutput( event );
		if ( ! $output.length || $output.find( '.download-lnk-pdf' ).length ) {
			return;
		}

		var $link = $( '<a>', {
			class: 'download-lnk-pdf',
			href: linkData.url,
			target: '_blank',
			rel: 'noopener noreferrer',
			text: linkData.text || 'Click here to download PDF'
		} );

		$output.append( '<br>' ).append( $link );
		clearPdfLinkCookies();
	}

	document.addEventListener( 'wpcf7mailsent', function( event ) {
		setTimeout( function() {
			appendPdfLink( event );
		}, 100 );
	}, false );
}( jQuery ) );

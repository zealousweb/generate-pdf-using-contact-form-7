( function ( $ ) {
	'use strict';

	function togglePasswordFields() {
		var enabled = $( 'input.cf7_opt_pass_enable:checked' ).val() === 'true';
		$( '.cf7pdf-password-fields' ).toggle( enabled );
	}

	function getFormId() {
		var id = $( 'input[name="cf7_idform"]' ).val();
		if ( id ) {
			return parseInt( id, 10 );
		}
		return cf7PdfAdminFeatures.formId || 0;
	}

	function syncCodeMirrorToTextarea() {
		var textarea = document.getElementById( 'code' );

		if ( ! textarea ) {
			return;
		}

		var sibling = textarea.nextElementSibling;

		if ( sibling && sibling.CodeMirror ) {
			sibling.CodeMirror.save();
			return;
		}

		if ( typeof window.CodeMirror !== 'undefined' ) {
			var nodes = document.getElementsByClassName( 'CodeMirror' );
			var i;

			for ( i = 0; i < nodes.length; i++ ) {
				if ( nodes[ i ].CodeMirror ) {
					nodes[ i ].CodeMirror.save();
				}
			}
		}
	}

	function collectSettings() {
		syncCodeMirrorToTextarea();

		var settings = {};
		$( 'form[name="setting_form"]' ).find( 'input, select, textarea' ).each( function () {
			var $el = $( this );
			var name = $el.attr( 'name' );

			if ( ! name || name.indexOf( 'wp_cf7_pdf_settings[' ) !== 0 ) {
				return;
			}

			var key = name.replace( 'wp_cf7_pdf_settings[', '' ).replace( '[]', '' );
			var bracket = key.indexOf( ']' );

			if ( bracket !== -1 ) {
				key = key.substring( 0, bracket );
			}

			if ( key === 'cf7_opt_password_pdf_confirm' ) {
				return;
			}

			if ( $el.attr( 'type' ) === 'radio' && ! $el.is( ':checked' ) ) {
				return;
			}

			if ( $el.attr( 'type' ) === 'checkbox' ) {
				if ( name.indexOf( '[]' ) !== -1 ) {
					if ( ! settings[ key ] ) {
						settings[ key ] = [];
					}
					if ( $el.is( ':checked' ) ) {
						settings[ key ].push( $el.val() );
					}
				} else {
					settings[ key ] = $el.is( ':checked' ) ? $el.val() : '';
				}
				return;
			}

			if ( $el.attr( 'type' ) === 'file' ) {
				return;
			}

			settings[ key ] = $el.val();
		} );

		if ( document.getElementById( 'code' ) ) {
			settings.cf7_pdf_msg_body = document.getElementById( 'code' ).value;
		}

		return settings;
	}

	function showPreviewNotice( message, type ) {
		var $n = $( '#cf7-pdf-preview-notice' );
		$n.removeClass( 'notice-error notice-success' ).addClass( 'notice-' + ( type || 'error' ) );
		$n.html( '<p>' + message + '</p>' ).show();
	}

	$( document ).on( 'change', 'input.cf7_opt_pass_enable', togglePasswordFields );
	$( document ).ready( togglePasswordFields );

	$( document ).on( 'click', '#cf7-pdf-preview-btn', function ( e ) {
		e.preventDefault();

		var formId = getFormId();
		if ( ! formId ) {
			showPreviewNotice( cf7PdfAdminFeatures.i18n.selectForm, 'error' );
			return;
		}

		var $btn = $( this );
		var $spinner = $( '.cf7-pdf-preview-spinner' );
		var previewPassword = '';

		if ( $( 'input.cf7_opt_pass_enable:checked' ).val() === 'true' ) {
			previewPassword = $( '#cf7_opt_password_pdf' ).val() || '';
		}

		$btn.prop( 'disabled', true );
		$spinner.addClass( 'is-active' );
		$( '#cf7-pdf-preview-notice' ).hide();
		$( '#cf7-pdf-preview-frame' ).hide();

		$.post(
			ajaxurl,
			{
				action: 'cf7_pdf_live_preview',
				nonce: cf7PdfAdminFeatures.previewNonce,
				form_id: formId,
				settings: collectSettings(),
				preview_password: previewPassword
			}
		)
			.done( function ( response ) {
				if ( response.success && response.data && response.data.preview_url ) {
					$( '#cf7-pdf-preview-frame' ).attr( 'src', response.data.preview_url ).show();
					showPreviewNotice( '', 'success' );
					$( '#cf7-pdf-preview-notice' ).hide();
				} else {
					var msg = ( response.data && response.data.message ) ? response.data.message : cf7PdfAdminFeatures.i18n.error;
					showPreviewNotice( msg, 'error' );
				}
			} )
			.fail( function ( xhr ) {
				var msg = cf7PdfAdminFeatures.i18n.error;

				if ( xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ) {
					msg = xhr.responseJSON.data.message;
				}

				showPreviewNotice( msg, 'error' );
			} )
			.always( function () {
				$btn.prop( 'disabled', false );
				$spinner.removeClass( 'is-active' );
			} );
	} );
}( jQuery ) );

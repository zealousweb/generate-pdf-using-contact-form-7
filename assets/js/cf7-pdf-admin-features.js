( function ( $ ) {
	'use strict';

	var cfg = window.cf7PdfAdminFeatures || {};
	var i18n = cfg.i18n || {};
	var minLen = parseInt( cfg.minPasswordLength, 10 ) || 4;

	function getPanel() {
		return $( '#cf7pdf-password-panel' );
	}

	function hasStoredPassword() {
		return '1' === getPanel().attr( 'data-has-stored' );
	}

	function isPasswordEnabled() {
		return $( '#cf7_opt_is_password_enable' ).val() === 'true';
	}

	function togglePasswordFields() {
		var enabled = isPasswordEnabled();
		var removing = $( '#cf7_opt_remove_password' ).is( ':checked' );
		var $fields = $( '#cf7pdf-password-panel .cf7pdf-password-fields' );

		if ( enabled && ! removing ) {
			$fields.removeAttr( 'hidden' );
		} else {
			$fields.attr( 'hidden', 'hidden' );
		}

		$( '#cf7pdf-password-toggle-label' ).text( enabled ? i18n.enabled : i18n.disabled );
		updatePasswordBadge();
		updatePreviewPasswordHint();
	}

	function updatePasswordBadge() {
		var $badge = $( '.cf7pdf-password-badge' );
		if ( ! $badge.length ) {
			return;
		}

		var enabled = isPasswordEnabled();
		var stored = hasStoredPassword();
		var pass = $( '#cf7_opt_password_pdf' ).val();
		var removing = $( '#cf7_opt_remove_password' ).is( ':checked' );

		$badge.removeClass( 'cf7pdf-password-badge--active cf7pdf-password-badge--pending cf7pdf-password-badge--off' );

		if ( removing ) {
			$badge.addClass( 'cf7pdf-password-badge--off' ).text( i18n.statusOff );
		} else if ( enabled && ( stored || pass.length >= minLen ) ) {
			$badge.addClass( 'cf7pdf-password-badge--active' ).text( i18n.statusActive );
		} else if ( enabled ) {
			$badge.addClass( 'cf7pdf-password-badge--pending' ).text( i18n.statusPending );
		} else {
			$badge.addClass( 'cf7pdf-password-badge--off' ).text( i18n.statusOff );
		}
	}

	function syncEnableToggle() {
		var on = $( '#cf7pdf-password-enable' ).is( ':checked' );
		$( '#cf7_opt_is_password_enable' ).val( on ? 'true' : 'false' );
		togglePasswordFields();
	}

	function getFormId() {
		var id = $( 'input[name="cf7_idform"]' ).val();
		return id ? parseInt( id, 10 ) : 0;
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

	var lastPreviewUrl = '';
	var lastDownloadUrl = '';

	function escapeHtml( text ) {
		return String( text )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function showPreviewNotice( message, type ) {
		var $n = $( '#cf7-pdf-preview-notice' );
		$n.removeClass( 'notice-error notice-success notice-warning' )
			.addClass( 'notice-' + ( type || 'error' ) )
			.html( '<p>' + escapeHtml( message ) + '</p>' )
			.removeAttr( 'hidden' );
	}

	function hidePreviewNotice() {
		$( '#cf7-pdf-preview-notice' ).attr( 'hidden', 'hidden' ).empty();
	}

	function setPreviewLoading( loading ) {
		var $loading = $( '#cf7pdf-preview-loading' );
		var $empty = $( '#cf7pdf-preview-empty' );
		var $frame = $( '#cf7-pdf-preview-frame' );

		if ( loading ) {
			$loading.removeAttr( 'hidden' );
			$empty.attr( 'hidden', 'hidden' );
			$frame.attr( 'hidden', 'hidden' );
		} else {
			$loading.attr( 'hidden', 'hidden' );
		}
	}

	function setPreviewActionsVisible( visible ) {
		$( '#cf7-pdf-preview-refresh, #cf7-pdf-preview-open' ).prop( 'hidden', ! visible );
		var $dl = $( '#cf7-pdf-preview-download' );
		if ( visible && lastDownloadUrl ) {
			$dl.attr( 'href', lastDownloadUrl ).removeAttr( 'hidden' );
		} else {
			$dl.attr( 'hidden', 'hidden' ).attr( 'href', '#' );
		}
	}

	function updatePreviewDataStatus( dataInfo ) {
		var $panel = $( '#cf7pdf-preview-panel' );
		var $badge = $( '#cf7pdf-preview-source-badge' );
		var $message = $( '#cf7pdf-preview-source-message' );
		var $link = $( '#cf7pdf-preview-submissions-link' );

		if ( ! dataInfo || ! $panel.length ) {
			return;
		}

		$panel
			.attr( 'data-source', dataInfo.source || 'sample' )
			.attr( 'data-label', dataInfo.label || '' )
			.attr( 'data-message', dataInfo.message || '' );

		$badge
			.removeClass( 'is-sample is-submission is-attach' )
			.addClass( 'is-' + ( dataInfo.source || 'sample' ) )
			.text( dataInfo.label || '' );

		$message.text( dataInfo.message || '' );

		if ( 'submission' === dataInfo.source && cfg.submissionsUrl ) {
			if ( ! $link.length ) {
				$link = $( '<a>', {
					id: 'cf7pdf-preview-submissions-link',
					class: 'cf7pdf-preview-status__link',
					href: cfg.submissionsUrl,
					text: i18n.viewSubmissions
				} );
				$( '#cf7pdf-preview-status' ).append( $link );
			} else {
				$link.attr( 'href', cfg.submissionsUrl ).show();
			}
		} else if ( $link.length ) {
			$link.hide();
		}
	}

	function initPreviewDataStatus() {
		var $panel = $( '#cf7pdf-preview-panel' );
		if ( ! $panel.length ) {
			return;
		}
		updatePreviewDataStatus( {
			source: $panel.attr( 'data-source' ) || 'sample',
			label: $panel.attr( 'data-label' ) || '',
			message: $panel.attr( 'data-message' ) || ''
		} );
	}

	function showPreviewPasswordHint( message ) {
		var $hint = $( '#cf7-pdf-preview-password-hint' );
		if ( ! $hint.length || ! message ) {
			return;
		}
		$hint.html( '<span class="dashicons dashicons-lock" aria-hidden="true"></span><span>' + escapeHtml( message ) + '</span>' ).removeAttr( 'hidden' );
	}

	function hidePreviewPasswordHint() {
		$( '#cf7-pdf-preview-password-hint' ).attr( 'hidden', 'hidden' ).empty();
	}

	function updatePreviewPasswordHint() {
		if ( ! isPasswordEnabled() || $( '#cf7_opt_remove_password' ).is( ':checked' ) ) {
			hidePreviewPasswordHint();
		}
	}

	function showPreviewFrame( url ) {
		var $frame = $( '#cf7-pdf-preview-frame' );
		$( '#cf7pdf-preview-empty' ).attr( 'hidden', 'hidden' );
		$frame.attr( 'src', url ).removeAttr( 'hidden' );
	}

	function runPreview() {
		var formId = getFormId();
		if ( ! formId ) {
			showPreviewNotice( i18n.selectForm, 'error' );
			return;
		}

		var previewPassword = '';
		if ( isPasswordEnabled() && ! $( '#cf7_opt_remove_password' ).is( ':checked' ) ) {
			previewPassword = $( '#cf7_opt_password_pdf' ).val() || '';
		}

		var $btn = $( '#cf7-pdf-preview-btn' );
		var $refresh = $( '#cf7-pdf-preview-refresh' );

		$btn.prop( 'disabled', true );
		$refresh.prop( 'disabled', true );
		$( '.cf7-pdf-preview-spinner' ).addClass( 'is-active' );
		hidePreviewNotice();
		hidePreviewPasswordHint();
		setPreviewLoading( true );
		setPreviewActionsVisible( false );

		$.post(
			ajaxurl,
			{
				action: 'cf7_pdf_live_preview',
				nonce: cfg.previewNonce,
				form_id: formId,
				settings: collectSettings(),
				preview_password: previewPassword
			}
		)
			.done( function ( response ) {
				if ( response.success && response.data && response.data.preview_url ) {
					lastPreviewUrl = response.data.preview_url;
					lastDownloadUrl = response.data.download_url || response.data.preview_url;

					if ( response.data.data_info ) {
						updatePreviewDataStatus( response.data.data_info );
					}

					showPreviewFrame( lastPreviewUrl + ( lastPreviewUrl.indexOf( '?' ) >= 0 ? '&' : '?' ) + '_=' + Date.now() );
					setPreviewActionsVisible( true );

					if ( response.data.needs_password ) {
						showPreviewNotice( i18n.previewNeedsPassword, 'warning' );
					} else if ( response.data.password_protected ) {
						showPreviewPasswordHint( i18n.previewProtected );
					} else {
						hidePreviewPasswordHint();
					}
				} else {
					var msg = ( response.data && response.data.message ) ? response.data.message : i18n.error;
					showPreviewNotice( msg, 'error' );
					$( '#cf7pdf-preview-empty' ).removeAttr( 'hidden' );
					$( '#cf7-pdf-preview-frame' ).attr( 'hidden', 'hidden' );
				}
			} )
			.fail( function ( xhr ) {
				var msg = i18n.error;
				if ( xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ) {
					msg = xhr.responseJSON.data.message;
				}
				showPreviewNotice( msg, 'error' );
				$( '#cf7pdf-preview-empty' ).removeAttr( 'hidden' );
				$( '#cf7-pdf-preview-frame' ).attr( 'hidden', 'hidden' );
			} )
			.always( function () {
				$btn.prop( 'disabled', false );
				$refresh.prop( 'disabled', false );
				$( '.cf7-pdf-preview-spinner' ).removeClass( 'is-active' );
				setPreviewLoading( false );
			} );
	}

	function passwordStrength( pass ) {
		if ( ! pass ) {
			return 0;
		}
		var score = 0;
		if ( pass.length >= minLen ) {
			score++;
		}
		if ( pass.length >= 8 ) {
			score++;
		}
		if ( /[a-z]/.test( pass ) && /[A-Z]/.test( pass ) ) {
			score++;
		}
		if ( /\d/.test( pass ) ) {
			score++;
		}
		if ( /[^a-zA-Z0-9]/.test( pass ) ) {
			score++;
		}
		return score;
	}

	function updatePasswordStrength() {
		var pass = $( '#cf7_opt_password_pdf' ).val() || '';
		var $el = $( '#cf7pdf-password-strength' );
		var $label = $el.find( '.cf7pdf-strength-label' );

		if ( ! pass ) {
			$el.attr( 'hidden', 'hidden' ).removeClass( 'is-weak is-fair is-strong' );
			$label.text( '' );
			$( '#cf7pdf-copy-password' ).attr( 'hidden', 'hidden' );
			updatePasswordBadge();
			return;
		}

		var score = passwordStrength( pass );
		var text = i18n.strengthWeak;
		var cls = 'is-weak';

		if ( score >= 4 ) {
			text = i18n.strengthStrong;
			cls = 'is-strong';
		} else if ( score >= 2 ) {
			text = i18n.strengthFair;
			cls = 'is-fair';
		}

		$el.removeAttr( 'hidden' ).removeClass( 'is-weak is-fair is-strong' ).addClass( cls );
		$label.text( text );
		$( '#cf7pdf-copy-password' ).removeAttr( 'hidden' );
		updatePasswordBadge();
	}

	function updatePasswordMatch() {
		var pass = $( '#cf7_opt_password_pdf' ).val() || '';
		var confirm = $( '#cf7_opt_password_pdf_confirm' ).val() || '';
		var $msg = $( '#cf7pdf-password-match-msg' );

		if ( ! pass && ! confirm ) {
			$msg.removeClass( 'is-match is-mismatch' ).empty();
			return;
		}

		if ( pass === confirm ) {
			$msg.removeClass( 'is-mismatch' ).addClass( 'is-match' ).text( i18n.passwordsMatch );
		} else {
			$msg.removeClass( 'is-match' ).addClass( 'is-mismatch' ).text( i18n.passwordMismatch );
		}
	}

	function validatePasswordBeforeSave() {
		if ( $( '#cf7_opt_remove_password' ).is( ':checked' ) ) {
			return true;
		}

		if ( ! isPasswordEnabled() ) {
			return true;
		}

		var pass = $( '#cf7_opt_password_pdf' ).val() || '';
		var confirm = $( '#cf7_opt_password_pdf_confirm' ).val() || '';

		if ( hasStoredPassword() && '' === pass && '' === confirm ) {
			return true;
		}

		if ( '' === pass || '' === confirm ) {
			window.alert( i18n.passwordRequired );
			$( '#cf7_opt_password_pdf' ).focus();
			return false;
		}

		if ( pass !== confirm ) {
			window.alert( i18n.passwordMismatch );
			$( '#cf7_opt_password_pdf_confirm' ).focus();
			return false;
		}

		if ( pass.length < minLen ) {
			window.alert( i18n.passwordTooShort );
			$( '#cf7_opt_password_pdf' ).focus();
			return false;
		}

		return true;
	}

	function generateSecurePassword() {
		var chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%&*';
		var length = 14;
		var pwd = '';
		var i;

		if ( window.crypto && window.crypto.getRandomValues ) {
			var array = new Uint32Array( length );
			window.crypto.getRandomValues( array );
			for ( i = 0; i < length; i++ ) {
				pwd += chars.charAt( array[ i ] % chars.length );
			}
		} else {
			for ( i = 0; i < length; i++ ) {
				pwd += chars.charAt( Math.floor( Math.random() * chars.length ) );
			}
		}

		return pwd;
	}

	function fillGeneratedPassword() {
		var pwd = generateSecurePassword();
		var $pass = $( '#cf7_opt_password_pdf' );
		var $confirm = $( '#cf7_opt_password_pdf_confirm' );

		$pass.val( pwd ).attr( 'type', 'text' );
		$confirm.val( pwd ).attr( 'type', 'text' );
		$( '.cf7pdf-toggle-password' ).attr( 'aria-pressed', 'true' );
		$( '.cf7pdf-toggle-password .dashicons' )
			.removeClass( 'dashicons-visibility' )
			.addClass( 'dashicons-hidden' );

		updatePasswordStrength();
		updatePasswordMatch();
		updatePasswordBadge();
	}

	function copyPasswordToClipboard() {
		var pass = $( '#cf7_opt_password_pdf' ).val() || '';

		if ( ! pass ) {
			return;
		}

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( pass ).then( function () {
				window.alert( i18n.copiedPassword );
			} ).catch( function () {
				window.alert( i18n.copyFailed );
			} );
			return;
		}

		var $tmp = $( '<textarea readonly style="position:absolute;left:-9999px;"></textarea>' ).val( pass ).appendTo( 'body' );
		$tmp[ 0 ].select();
		try {
			document.execCommand( 'copy' );
			window.alert( i18n.copiedPassword );
		} catch ( e ) {
			window.alert( i18n.copyFailed );
		}
		$tmp.remove();
	}

	function togglePasswordVisibility( e ) {
		e.preventDefault();

		var $btn = $( this );
		var targetId = $btn.data( 'target' );
		var $input = $( '#' + targetId );

		if ( ! $input.length ) {
			return;
		}

		var isHidden = 'password' === $input.attr( 'type' );

		$input.attr( 'type', isHidden ? 'text' : 'password' );
		$btn.attr( 'aria-pressed', isHidden ? 'true' : 'false' );
		$btn.attr( 'aria-label', isHidden ? i18n.hidePassword : i18n.showPassword );
		$btn.find( '.dashicons' )
			.toggleClass( 'dashicons-visibility', ! isHidden )
			.toggleClass( 'dashicons-hidden', isHidden );
	}

	function onRemovePasswordChange() {
		var removing = $( '#cf7_opt_remove_password' ).is( ':checked' );

		if ( removing ) {
			$( '#cf7pdf-password-enable' ).prop( 'checked', false );
			syncEnableToggle();
		}

		togglePasswordFields();
	}

	$( document ).on( 'change', '#cf7pdf-password-enable', syncEnableToggle );
	$( document ).on( 'change', '#cf7_opt_remove_password', onRemovePasswordChange );
	$( document ).on( 'input', '#cf7_opt_password_pdf, #cf7_opt_password_pdf_confirm', function () {
		updatePasswordStrength();
		updatePasswordMatch();
	} );
	$( document ).on( 'click', '.cf7pdf-toggle-password', togglePasswordVisibility );
	$( document ).on( 'click', '#cf7pdf-generate-password', function ( e ) {
		e.preventDefault();
		fillGeneratedPassword();
	} );
	$( document ).on( 'click', '#cf7pdf-copy-password', function ( e ) {
		e.preventDefault();
		copyPasswordToClipboard();
	} );
	$( document ).on( 'submit', 'form[name="setting_form"]', function ( e ) {
		if ( ! validatePasswordBeforeSave() ) {
			e.preventDefault();
		}
	} );

	$( document ).ready( function () {
		togglePasswordFields();
		updatePasswordStrength();
		updatePasswordMatch();
		initPreviewDataStatus();
	} );

	$( document ).on( 'click', '#cf7-pdf-preview-btn, #cf7-pdf-preview-refresh', function ( e ) {
		e.preventDefault();
		runPreview();
	} );

	$( document ).on( 'click', '#cf7-pdf-preview-open', function ( e ) {
		e.preventDefault();
		if ( lastPreviewUrl ) {
			window.open( lastPreviewUrl, '_blank', 'noopener,noreferrer' );
		}
	} );

	$( '#cf7-pdf-preview-download' ).on( 'click', function ( e ) {
		if ( ! lastDownloadUrl ) {
			e.preventDefault();
		}
	} );

	$( '#cf7-pdf-preview-frame' ).on( 'load', function () {
		setPreviewLoading( false );
	} );
}( jQuery ) );

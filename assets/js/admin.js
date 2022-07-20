( function($) {
	"use strict";

	function cf7wpa_sandbox_validate() {
		if ( jQuery( '.cf7wpa-settings #cf7wpa_use_worldpay' ).prop( 'checked' ) == true && jQuery( '.cf7wpa-settings #cf7wpa_mode_sandbox' ).prop( 'checked' ) != true ) {
			jQuery( '.cf7wpa-settings #cf7wpa_live_client_id, .cf7wpa-settings #cf7wpa_live_client_secret' ).prop( 'required', true );
		} else {
			jQuery( '.cf7wpa-settings #cf7wpa_live_client_id, .cf7wpa-settings #cf7wpa_live_client_secret' ).removeAttr( 'required' );
		}
	}

	function cf7wpa_live_validate() {
		if ( jQuery( '.cf7wpa-settings #cf7wpa_use_worldpay' ).prop( 'checked' ) == true && jQuery( '.cf7wpa-settings #cf7wpa_mode_sandbox' ).prop( 'checked' ) == true ) {
			jQuery( '.cf7wpa-settings #cf7wpa_sandbox_client_id, .cf7wpa-settings #cf7wpa_sandbox_client_secret' ).prop( 'required', true );
		} else {
			jQuery( '.cf7wpa-settings #cf7wpa_sandbox_client_id, .cf7wpa-settings #cf7wpa_sandbox_client_secret' ).removeAttr( 'required' );
		}
	}

	jQuery( document ).on( 'change', '.cf7wpa-settings .enable_required', function() {
		if ( jQuery( this ).prop( 'checked' ) == true ) {
			jQuery( '.cf7wpa-settings #cf7wpa_amount' ).prop( 'required', true );
		} else {
			jQuery( '.cf7wpa-settings #cf7wpa_amount' ).removeAttr( 'required' );
		}

		cf7wpa_live_validate();
		cf7wpa_sandbox_validate();

	} );

	jQuery( document ).on( 'change', '.cf7wpa-settings #cf7wpa_mode_sandbox', function() {
		cf7wpa_live_validate();
		cf7wpa_sandbox_validate();
	} );

	jQuery( document ).on( 'input', '.cf7wpa-settings .required', function() {
		cf7wpa_live_validate();
		cf7wpa_sandbox_validate();
	} );

} )( jQuery );

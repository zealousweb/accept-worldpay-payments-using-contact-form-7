jQuery( document ).ready( function( $ ) {

	document.addEventListener('wpcf7mailsent', function( event ) {
		setTimeout(function(){
			var contactform_id = event.detail.contactFormId;
			formdata = event.detail.apiResponse.formdata;
			if( formdata !== undefined) { 
				document.getElementById(event.detail.id).innerHTML += formdata;
				document.getElementById("worldpay-payment-form-"+event.detail.contactFormId).submit();
			}
			
		}, 2000);

	} );
} );

jQuery( document ).ready( function() {
	
	jQuery( '#qwc_quote_complete' ).click( function() {
		
		// update the DB quote status
		var data = {
			order_id: qwc_params.order_id,
			status: 'quote-complete',
			action: 'qwc_update_status'
		};
		
		jQuery.post( qwc_params.ajax_url, data, function( response ) {
			// reload the page
			location.reload( true );
		});
	});
	
	jQuery( '#qwc_send_quote' ).click( function() {
		// send an email
		var data = {
			order_id: qwc_params.order_id,
			action: 'qwc_send_quote'
		};
		
		jQuery.post( qwc_params.ajax_url, data, function( response ) {
			if ( 'quote-sent' === response ) {
				jQuery( '#qwc_msg' ).html( qwc_params.email_msg );
				jQuery( '#qwc_msg' ).attr( 'display', 'block' ).fadeOut( 5000 );
			}
		});
	});
});
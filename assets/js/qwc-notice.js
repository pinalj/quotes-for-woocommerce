jQuery( document ).ready( function() {
	
	jQuery( document ).on( 'click', '.qwc_menu_notice .notice-dismiss', function() {

		var data = {
			action: 'qwc_menu_notice_dismissed',
			notice: 'qwc_menu_notice',
			security: qwc_notice_params.nonce,
		};
        // Since WP 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php.
        jQuery.post( ajaxurl, data, function() {
        });
    });
	  
});
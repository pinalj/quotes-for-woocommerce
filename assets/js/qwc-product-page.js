jQuery( document ).ready( function() {
	
	var obj = {
		component_totals_changed_handler: function( component ) {
			if ( qwc_product_params.quotes ) {
				setTimeout(function() {
					jQuery('.composite_price').hide();
				}, 100);
			}
		}
	};

	jQuery( '.composite_data' ).on( 'wc-composite-initializing', function( event, composite ) {
		// Add actions for when component total is updated.
		composite.actions.add_action( 'component_totals_changed', obj.component_totals_changed_handler, 999, obj );

	});
});
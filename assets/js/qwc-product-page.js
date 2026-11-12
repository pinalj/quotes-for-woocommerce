jQuery( document ).ready( function($) {
	// WooCommerce composite Products.
	var obj = {
		component_totals_changed_handler: function( component ) {
			if ( qwc_product_params.quotes ) {
				setTimeout(function() {
					jQuery('.composite_price').hide();
				}, 100);
			}
		}
	};

	$( '.composite_data' ).on( 'wc-composite-initializing', function( event, composite ) {
		// Add actions for when component total is updated.
		composite.actions.add_action( 'component_totals_changed', obj.component_totals_changed_handler, 999, obj );

	});

	// WPClever composite Products.
	if ( qwc_product_params.quotes ) {
		jQuery( '.wooco-total' ).hide();
		jQuery( '.wooco-price-' + qwc_product_params.product_id ).hide();
	}

	jQuery(document).on( 'wooco_calc_price', function(event, total, total_regular, total_html, $wrap, context, $selected) {
		if ( qwc_product_params.quotes ) {
			setTimeout(function() {
				jQuery('.wooco-total').hide();
			}, 100);
		}
	});
});
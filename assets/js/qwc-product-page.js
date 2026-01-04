jQuery( document ).ready( function($) {
	
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

	// Product Addons compatibility - since 2.11
	const displayPrice = qwc_product_params.display_price;
    const hidePricesEnabled = displayPrice ? false : true;

    if ( !hidePricesEnabled ) {
        return;
    }

    function hidePrices() {
        $('#product-addons-total .amount').hide();
        $('.wc-pao-addon .amount').closest('.price, .amount').hide();
        $('.wc-pao-subtotal-line').hide();
    }

    // Initial enforcement
    hidePrices();

    // Observe DOM changes inside Product Add-Ons totals.
    const target = document.getElementById('product-addons-total');

    if ( !target ) {
        return;
    }

    const observer = new MutationObserver(function () {
        hidePrices();
    });

    observer.observe(target, {
        childList: true,
        subtree: true
    });
});
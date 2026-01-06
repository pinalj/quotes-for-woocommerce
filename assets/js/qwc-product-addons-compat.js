jQuery( document ).ready( function($) {
	// Product Addons compatibility - since 2.11
	const displayPriceCart = qwc_quote_params.display_price_cart;
    const displayPriceProduct = qwc_quote_params.display_price_product;

    const hidePricesEnabledCart = displayPriceCart ? false : true;
    const hidePricesEnabledProduct = displayPriceProduct ? false : true;

    if ( ! hidePricesEnabledCart ) {
        return;
    }

	function hideAddonPriceListings() {

		// Clean up leftover "(+ )" or "(+ )" spacing
		$('dd[class^="variation-"] p').each(function () {

			const text = $(this).text();

			// Remove "(+ ...)" regardless of currency/percent
			const cleaned = text.replace(/\(\s*\+.*?\)/g, '');

			$(this).text(cleaned.trim());
		});
	}

	// Run on page load
	$(document).ready(function () {
		hideAddonPriceListings();
	});

	// Run after every checkout refresh.
	$(document.body).on('updated_checkout', function () {
		hideAddonPriceListings();
	});

	// Single Product Page.
    function hidePricesProductPage() {
        // subtotals for each of the addons in the totals section at the bottom.
        $('#product-addons-total .amount').hide();
        // individual addon prices in the display.
        $('.wc-pao-addon-price').hide();
        // final product total in the totals section at the bottom.
        $('.wc-pao-subtotal-line').hide();
    }

    if ( ! hidePricesEnabledProduct ) {
        return;
    }

    // Initial enforcement
    hidePricesProductPage();

    // Observe DOM changes inside Product Add-Ons totals.
    const target = document.getElementById('product-addons-total');

    if ( !target ) {
        return;
    }

    const observer = new MutationObserver(function () {
        hidePricesProductPage();
    });

    observer.observe(target, {
        childList: true,
        subtree: true
    });
});
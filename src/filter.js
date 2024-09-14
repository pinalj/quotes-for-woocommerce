const { registerCheckoutFilters } = window.wc.blocksCheckout;

const cartContainsQuotes = filter_params.cartContainsQuotable;
const proceedButtonText = filter_params.qwcButtonText;

const modifyProceedToCheckoutButtonLabel = (
	defaultValue,
	extensions,
	args
) => {
	if ( ! args?.cart.items ) {
		return defaultValue;
	}

	if ( cartContainsQuotes ) {
		return proceedButtonText;
	}

	return defaultValue;
};

registerCheckoutFilters( 'quotes-for-woocommerce', {
	proceedToCheckoutButtonLabel: modifyProceedToCheckoutButtonLabel,
} );
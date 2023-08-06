
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'quotes-gateway_data', {} );
//console.log(settings );
const defaultLabel = __(
	'Ask for Quote',
	'quote-wc'
);

const label = decodeEntities( settings.title ) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	return decodeEntities( settings.description || '' );
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={ label } />;
};

/**
 * Dummy payment method config object.
 */
const QwcQuotesPaymentMethod = {
	name: "quotes-gateway",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	placeOrderButtonLabel: __(
		decodeEntities( settings.place_order_label),
		'quote-wc'
	),
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( QwcQuotesPaymentMethod );

<?php
/**
 * Quotes WC Blocks Payment Integration.
 *
 * @package Quotes for WooCommerce/Integration
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Quote Payments Blocks integration
 *
 * @since 2.0
 */
final class WC_Quotes_Gateway_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Quotes_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'quotes-gateway';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_quotes-gateway_settings', array() );
		$this->gateway  = new Quotes_Payment_Gateway();
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/build/index.js';
		$script_asset_path = QUOTES_PLUGIN_DIR . '/build/index.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => '2.0.0',
			);
		$script_url        = QUOTES_PLUGIN_URL . '/' . $script_path;

		wp_register_script(
			'wc-quotes-payments-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-quotes-payments-blocks', 'woocommerce-gateway-quotes', QUOTES_PLUGIN_DIR . '/languages/' );
		}

		return array( 'wc-quotes-payments-blocks' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'             => apply_filters( 'qwc_payment_method_name', __( 'Ask for Quote', 'quote-wc' ) ),
			'description'       => '',
			'place_order_label' => '' === get_option( 'qwc_place_order_text', '' ) ? __( 'Request Quote', 'quote-wc' ) : __( get_option( 'qwc_place_order_text' ), 'quote-wc' ), // phpcs:ignore
		);
	}
}

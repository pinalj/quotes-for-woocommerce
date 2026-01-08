<?php
/**
 * Payment Gateway Class.
 *
 * @package     Quotes For WooCommerce
 * @class       Quotes_Payment_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {

	/**
	 * Quotes_Payment_Gateway class.
	 */
	class Quotes_Payment_Gateway extends WC_Payment_Gateway {

		/**
		 * Gateway Instructions.
		 *
		 * @var   string
		 * @since 2.5.0
		 */
		public $instructions = '';

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {

			$this->id         = 'quotes-gateway';
			$this->icon       = '';
			$this->has_fields = false;

			$this->method_title = __( 'Ask for Quote', 'quote-wc' );
			$this->init_form_fields();
			$this->init_settings();
			// Get title from settings.
			$setting_title = $this->get_option( 'title', $this->method_title );

			// Allow filter to override setting (backward compatible).
			$this->title             = apply_filters( 'qwc_payment_method_name', $setting_title );
			$this->order_button_text = '' === get_option( 'qwc_place_order_text', '' ) ? __( 'Request Quote', 'quote-wc' ) : __( get_option( 'qwc_place_order_text' ), 'quote-wc' ); // phpcs:ignore

			$this->description        = '';
			$this->method_description = '';
			$this->instructions       = '';
			// Actions.
			add_filter( 'woocommerce_thankyou_order_received_text', array( &$this, 'thankyou_page' ), 10, 2 );
			// Save the settings.
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		/**
		 * Admin Settings.
		 */
		public function admin_options() {
			$title = ( ! empty( $this->method_title ) ) ? $this->method_title : esc_html__( 'Settings', 'quote-wc' );

			echo '<h3>' . esc_attr( $title ) . '</h3>';

			echo '<p>' . esc_html__( 'This is a fictitious payment method used for quotes. No payment is taken at Checkout.', 'quote-wc' ) . '</p>';

			parent::admin_options();
		}

		/**
		 *
		 * Process the payent gateway.
		 *
		 * @param int $order_id - Order ID.
		 * @return array - Payment processed status.
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			// Add meta.
			$order->update_meta_data( '_qwc_quote', '1' );

			// Add custom order note.
			$order->add_order_note( esc_html__( 'This order is awaiting quote.', 'quote-wc' ) );
			$order->save();
			// Remove cart.
			WC()->cart->empty_cart();

			// Return thankyou redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}

		/**
		 * Thank You page content.
		 *
		 * @param string $message - Thank You page msg.
		 * @param obj    $order - Order.
		 */
		public function thankyou_page( $message, $order ) {

			if ( $order ) {
				if ( '1' === $order->get_meta( '_qwc_quote' ) && 'pending' === $order->get_status() ) {
					$message = esc_html__( 'We have received your request for a quote. You will be notified via email soon.', 'quote-wc' );
				}
			}
			return $message;
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 *
		 * @since 2.11
		 */
		public function init_form_fields() {

			$this->form_fields = array(
				'title' => array(
					'title'       => __( 'Ask for Quote', 'quote-wc' ),
					'type'        => 'text',
					'description' => __( 'This sets the name of the payment method shown to customers.', 'quote-wc' ),
					'default'     => __( 'Ask for Quote', 'quote-wc' ),
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Return Gateway availability.
		 *
		 * @since 2.11
		 */
		public function is_available() {
			return true;
		}
	}
}

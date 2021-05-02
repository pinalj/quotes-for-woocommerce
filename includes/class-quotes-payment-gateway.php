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
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->id                = 'quotes-gateway';
			$this->icon              = '';
			$this->has_fields        = false;
			$this->method_title      = apply_filters( 'qwc_payment_method_name', __( 'Ask for Quote', 'quote-wc' ) );
			$this->title             = $this->method_title;
			$this->order_button_text = '' === get_option( 'qwc_place_order_text', '' ) ? __( 'Request Quote', 'quote-wc' ) : __( get_option( 'qwc_place_order_text' ), 'quote-wc' );

			// Actions.
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		}

		/**
		 * Admin Settings.
		 */
		public function admin_options() {
			$title = ( ! empty( $this->method_title ) ) ? $this->method_title : esc_html__( 'Settings', 'quote-wc' );

			echo '<h3>' . esc_attr( $title ) . '</h3>';

			echo '<p>' . esc_html__( 'This is fictitious payment method used for quotes.', 'quote-wc' ) . '</p>';
			echo '<p>' . esc_html__( 'This gateway requires no configuration.', 'quote-wc' ) . '</p>';

			// Hides the save button.
			echo '<style>p.submit input[type="submit"] { display: none }</style>';
		}

		/**
		 *
		 * Process the payent gateway.
		 *
		 * @param int $order_id - Order ID.
		 * @return array - Payment processed status.
		 */
		public function process_payment( $order_id ) {
			$order = new WC_Order( $order_id );

			// Add meta.
			update_post_meta( $order_id, '_qwc_quote', '1' );

			// Add custom order note.
			$order->add_order_note( esc_html__( 'This order is awaiting quote.', 'quote-wc' ) );

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
		 * @param int $order_id - Order ID.
		 */
		public function thankyou_page( $order_id ) {
			$order = new WC_Order( $order_id );

			if ( 'completed' === $order->get_status() ) {
				echo '<p>' . esc_html__( 'We have received your order. Thank you.', 'quote-wc' ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'We have received your request for a quote. You will be notified via email soon.', 'quote-wc' ) . '</p>';
			}
		}

	}
}

<?php
/**
 * Quote WC Blocks integration
 *
 * @since 2.0
 * @package Quotes for WooCommerce/Integrations.
 */

if ( ! class_exists( 'Quotes_WC_Blocks_Integration' ) ) {

	/**
	 * Integration with WC Blocks.
	 */
	class Quotes_WC_Blocks_Integration {

		/**
		 * Construct.
		 */
		public function __construct() {
			// Add Order Meta for Quote Status.
			add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( &$this, 'qwc_wc_blocks_update_order_meta_data' ), 10, 2 );
			// Order has been processed, initiate emails.
			add_action( 'woocommerce_store_api_checkout_order_processed', array( &$this, 'qwc_init_quote_emails' ), 90, 1 );
		}

		/**
		 * Add quote status Meta Data.
		 *
		 * @param object $order - WC Order Object.
		 * @param array  $request - Request Details.
		 */
		public function qwc_wc_blocks_update_order_meta_data( $order, $request ) {
			$payment_method = isset( $request['payment_method'] ) ? $request['payment_method'] : '';

			if ( 'quotes-gateway' === $payment_method ) {
				$quote_status = 'quote-pending';
			} else {
				$quote_status = 'quote-complete';
			}
			$order->update_meta_data( '_quote_status', $quote_status );
			$order->save();
		}

		/**
		 * Checks if the order is a quotation order.
		 * If yes, triggers the email to admin & customer.
		 *
		 * @param object $order - Order Object.
		 * @since 2.0
		 */
		public function qwc_init_quote_emails( $order ) {

			if ( $order ) {
				$order_id = $order->get_id();
				$order_id = apply_filters( 'qwc_init_quote_emails', $order_id );
				$order    = wc_get_order( $order_id ); // We refetch the order obj to ensure the correct obj is used if the order Id was modified by the filter.
				$quote    = order_requires_quote( $order );
				if ( $quote && isset( $order_id ) && $order_id > 0 ) {
					WC_Emails::instance();
					do_action( 'qwc_pending_quote_notification', $order_id );
					do_action( 'qwc_request_sent_notification', $order_id );
				}
			}
		}
	}
}
$quotes_wc_blocks_integration = new Quotes_WC_Blocks_Integration();

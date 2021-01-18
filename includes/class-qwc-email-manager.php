<?php
/**
 * Email Manager.
 *
 * @package     Quotes For WooCommerce
 * @class       QWC_Email_manager
 */

/**
 * Handles Emails
 */
class QWC_Email_Manager {

	/**
	 * Constructor.
	 */
	public function __construct() {

		add_action( 'woocommerce_checkout_order_processed', array( &$this, 'qwc_init_quote_emails' ), 10, 1 );
		add_filter( 'woocommerce_email_classes', array( &$this, 'qwc_init_emails' ) );

		// Email Actions.
		$email_actions = array(
			'qwc_pending_quote',
			// Send Quotes.
			'qwc_send_quote',
			// new request customer.
			'qwc_request_sent',
		);

		foreach ( $email_actions as $action ) {
			add_action( $action, array( 'WC_Emails', 'send_transactional_email' ), 10, 10 );
		}

		add_filter( 'woocommerce_template_directory', array( $this, 'qwc_template_directory' ), 10, 2 );
	}

	/**
	 * Checks if the order is a quotation order.
	 * If yes, triggers the email to admin.
	 *
	 * @param int $order_id - Order ID.
	 * @since 1.2
	 */
	public function qwc_init_quote_emails( $order_id ) {

		if ( isset( $order_id ) && 0 !== $order_id ) {
			$order = new WC_order( $order_id );
			$quote = order_requires_quote( $order );

			if ( $quote ) {
				WC_Emails::instance();
				do_action( 'qwc_pending_quote_notification', $order_id );
				do_action( 'qwc_request_sent_notification', $order_id );
			}
		}

	}

	/**
	 * Include Email class files.
	 *
	 * @param array $emails - List of email class files.
	 * @return array $emails - List of email class files.
	 * @since 1.0
	 */
	public function qwc_init_emails( $emails ) {

		if ( ! isset( $emails['QWC_Send_Quote'] ) ) {
			$emails['QWC_Send_Quote'] = include_once plugin_dir_path( __FILE__ ) . 'emails/class-qwc-send-quote.php';
		}
		if ( ! isset( $emails['QWC_Request_New_Quote'] ) ) {
			$emails['QWC_Request_New_Quote'] = include_once plugin_dir_path( __FILE__ ) . 'emails/class-qwc-request-new-quote.php';
		}
		if ( ! isset( $emails['QWC_Request_Sent'] ) ) {
			$emails['QWC_Request_Sent'] = include_once plugin_dir_path( __FILE__ ) . 'emails/class-qwc-request-sent.php';
		}
		return $emails;
	}

	/**
	 * Return the Location of the template.
	 *
	 * @param str $directory - Directory.
	 * @param str $template - Template Name.
	 * @return str $directory - Directory.
	 * @since 1.0
	 */
	public function qwc_template_directory( $directory, $template ) {
		if ( false !== strpos( $template, 'quote' ) ) {
			return 'quotes-for-wc';
		}

		return $directory;
	}

} // end of class
return new QWC_Email_Manager();


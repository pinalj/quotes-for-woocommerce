<?php
/**
 * Request New Quote EMail
 *
 * An email sent to admin when a new quote request arrives
 *
 * @package     Quotes for WooCommerce\Email Classes
 * @class       QWC_Request_Sent
 * @extends     WC_Email
 * @since       1.4
 */

/**
 * Email Class for Request Sent - Customer.
 */
class QWC_Request_Sent extends WC_Email {

	/**
	 * Construct.
	 */
	public function __construct() {

		$this->id             = 'qwc_request_sent';
		$this->customer_email = true;
		$this->title          = __( 'New Quote Request Sent', 'quote-wc' );
		$this->description    = __( 'This email is sent to the customer when a quote request is raised', 'quote-wc' );

		$this->heading = __( 'Quotation Request Sent for #{order_number}', 'quote-wc' );
		$this->subject = __( '[{blogname}] Quotation Request Sent (Order {order_number}) - {order_date}', 'quote-wc' );

		$this->template_html  = 'emails/new-quote-request-sent-customer.php';
		$this->template_plain = 'emails/plain/new-quote-request-sent-customer.php';

		// Triggers for this email.
		add_action( 'qwc_request_sent_notification', array( $this, 'trigger' ) );

		// Call parent constructor.
		parent::__construct();

		// Other settings.
		$this->template_base = QUOTES_TEMPLATE_PATH;
	}

	/**
	 * Send the Email.
	 *
	 * @param int $order_id - Order ID.
	 */
	public function trigger( $order_id ) {

		$send_email = true;

		// Add a filter using which an addon can modify the email send status.
		// Setting it to true will send the email.
		// Setting it to false will make sure that the email is not sent for the given item.
		$send_email = apply_filters( 'qwc_request_sent_email', $send_email, $order_id );

		if ( $order_id > 0 && $send_email ) {

			$this->object = wc_get_order( $order_id );
			$quote_status = $this->object->get_meta( '_quote_status' );

			// Allowed quote statuses.
			$_status = array(
				'quote-pending',
			);
			$_status = apply_filters( 'qwc_request_sent_allowed_status', $_status );
			if ( in_array( $quote_status, $_status, true ) && $this->is_enabled() ) {

				$this->recipient = $this->object->get_billing_email();
				if ( $this->object ) {

					$this->find[]    = '{order_date}';
					$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->get_date_created() ) );

					$this->find[]    = '{order_number}';
					$this->replace[] = $this->object->get_order_number();

					$this->find[]    = '{order_id}';
					$this->replace[] = $order_id;
				} else {

					$this->find[]    = '{order_date}';
					$this->replace[] = __( 'N/A', 'quote-wc' );

					$this->find[]    = '{order_number}';
					$this->replace[] = __( 'N/A', 'quote-wc' );

					$this->find[]    = '{order_id}';
					$this->replace[] = __( 'N/A', 'quote-wc' );
				}

				$this->find[]    = '{blogname}';
				$this->replace[] = get_option( 'blogname' );

				if ( ! $this->get_recipient() ) {
					return;
				}

				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}
		}
	}

	/**
	 * HTML Email content.
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'order'         => $this->object,
				'site_name'     => get_option( 'blogname' ),
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
				'show_sku'      => apply_filters( 'qwc_show_sku_email', false, $this->id ),
			),
			'quotes-for-wc/',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * Plain Email Content.
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template(
			$this->template_plain,
			array(
				'order'         => $this->object,
				'site_name'     => get_option( 'blogname' ),
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			),
			'quotes-for-wc/',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * Default Email Subject.
	 */
	public function get_default_subject() {
		return __( '[{blogname}] Quotation Request Sent (Order {order_number}) - {order_date}', 'quote-wc' );
	}

	/**
	 * Default Email Heading.
	 */
	public function get_default_heading() {
		return __( 'Quotation Request Sent for #{order_number}', 'quote-wc' );
	}

	/**
	 * Email Setting Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'quote-wc' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'quote-wc' ),
				'default' => 'yes',
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'quote-wc' ),
				'type'        => 'text',
				// translators: Email Subject.
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'quote-wc' ), $this->subject ),
				'placeholder' => '',
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email Heading', 'quote-wc' ),
				'type'        => 'text',
				// translators: Email Heading.
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'quote-wc' ), $this->heading ),
				'placeholder' => '',
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'quote-wc' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'quote-wc' ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'plain'     => __( 'Plain text', 'quote-wc' ),
					'html'      => __( 'HTML', 'quote-wc' ),
					'multipart' => __( 'Multipart', 'quote-wc' ),
				),
			),
		);
	}
}
return new QWC_Request_Sent();

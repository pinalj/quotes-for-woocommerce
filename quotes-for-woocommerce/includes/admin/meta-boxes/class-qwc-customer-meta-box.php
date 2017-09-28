<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * QWC_Customer_Meta_Box class.
 */
class QWC_Customer_Meta_Box {

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Meta box title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Meta box context.
	 *
	 * @var string
	 */
	public $context;

	/**
	 * Meta box priority.
	 *
	 * @var string
	 */
	public $priority;

	/**
	 * Meta box post types.
	 * @var array
	 */
	public $post_types;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'qwc-customer-data';
		$this->title      = __( 'Customer details', 'quote-wc' );
		$this->context    = 'side';
		$this->priority   = 'default';
		$this->post_types = array( 'quote_wc' );
	}

	/**
	 * Meta box content.
	 */
	public function meta_box_inner( $post ) {

		if ( get_post_type( $post->ID ) === 'quote_wc' ) {
			$quote = new Quotes_WC( $post->ID );
		}
		$has_data = false;
		?>
		<table class="quotes-customer-details">
			<?php
				if ( $quote->get_customer_id() && ( $user = get_user_by( 'id', $quote->get_customer_id() ) ) ) {
					?>
					<tr>
						<th><?php esc_html_e( 'Name:', 'quote-wc' ); ?></th>
						<td><?php echo esc_html( $user->last_name && $user->first_name ? $user->first_name . ' ' . $user->last_name : '&mdash;' ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Email:', 'quote-wc' ); ?></th>
						<td><?php echo make_clickable( sanitize_email( $user->user_email ) ); ?></td>
					</tr>
					<tr class="view">
						<th>&nbsp;</th>
						<td><a class="button button-small" target="_blank" href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . absint( $user->ID ) ) ); ?>"><?php echo esc_html( 'View User', 'quote-wc' ); ?></a></td>
					</tr>
					<?php
					$has_data = true;
				}

				if ( $quote->get_order_id() && ( $order = wc_get_order( $quote->get_order_id() ) ) ) {
					?>
					<tr>
						<th valign='top'><?php esc_html_e( 'Address:', 'quote-wc' ); ?></th>
						<td><?php echo wp_kses( $order->get_formatted_billing_address() ? $order->get_formatted_billing_address() : __( 'No billing address set.', 'quote-wc' ), array( 'br' => array() ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Email:', 'quote-wc' ); ?></th>
						<td><?php echo make_clickable( sanitize_email( is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email() : $order->billing_email ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Phone:', 'quote-wc' ); ?></th>
						<td><?php echo esc_html( is_callable( array( $order, 'get_billing_phone' ) ) ? $order->get_billing_phone() : $order->billing_phone ); ?></td>
					</tr>
					<tr class="view">
						<th>&nbsp;</th>
						<td><a class="button button-small" target="_blank" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $quote->get_order_id() ) . '&action=edit' ) ); ?>"><?php echo esc_html( 'View Order', 'quote-wc' ); ?></a></td>
					</tr>
					<?php
					$has_data = true;
				}

				if ( ! $has_data ) {
					?>
					<tr>
						<td colspan="2"><?php esc_html_e( 'N/A', 'quote-wc' ); ?></td>
					</tr>
					<?php
				}
			?>
		</table>
		<?php
	}
}

return new QWC_Customer_Meta_Box();
?>
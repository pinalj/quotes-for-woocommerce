<?php
/**
 * Request New Quote email
 *
 * @package Quotes for WooCommerce/Email Templates
 */

// translators: Billing Name.
$opening_paragraph = __( 'A request for quote has been made by %s and is awaiting your attention. The details of the order are as follows:', 'quote-wc' );
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
$billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->billing_first_name : $order->get_billing_first_name();
$billing_last_name  = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->billing_last_name : $order->get_billing_last_name();
if ( $order_details && $billing_first_name && $billing_last_name ) :
	?>
	<p><?php echo sprintf( esc_html( $opening_paragraph ), esc_html( $billing_first_name . ' ' . $billing_last_name ) ); ?></p>
<?php endif; ?>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Product', 'quote-wc' ); ?></th>
			<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Quantity', 'quote-wc' ); ?></th>
			<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Product Price', 'quote-wc' ); ?></th>

		</tr>
		<?php
		foreach ( $order->get_items() as $items ) {
			?>
			<tr>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo wp_kses_post( $items->get_name() ); ?></td>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_attr( $items->get_quantity() ); ?></td>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $items ) ); ?></td>
			</tr>
			<?php
		}
		do_action( 'qwc_new_quote_admin_row', $order_details->order_id, $order );
		?>
	</tbody>
</table>

<p><?php esc_html_e( 'This order is awaiting a quote.', 'quote-wc' ); ?></p>

<p>
	<?php
	// translators: Admin Url for payment.
	echo wp_kses_post( make_clickable( sprintf( __( 'You can view and edit this order in the dashboard here: %s', 'quote-wc' ), esc_url( admin_url( 'post.php?post=' . $order_details->order_id . '&action=edit' ) ) ) ) );
	?>
</p>
<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ); ?>
<?php do_action( 'woocommerce_email_footer' ); ?>

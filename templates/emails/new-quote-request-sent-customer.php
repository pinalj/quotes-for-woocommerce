<?php
/**
 * Request New Quote email
 *
 * @package Quotes for WooCommerce/Emails
 */

$display_price = false;

// translators: Site Name.
$opening_paragraph = __( 'You have made a request for a quote on %s. The details of the order are as follows:', 'quote-wc' );
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
if ( $order_details ) :
	?>
	<p><?php printf( esc_html( $opening_paragraph ), esc_attr( $site_name ) ); ?></p>
<?php endif; ?>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Product', 'quote-wc' ); ?></th>
			<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Quantity', 'quote-wc' ); ?></th>
			<?php
			if ( qwc_order_display_price( $order ) ) {
				$display_price = true;
				?>
			<th style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Product Price', 'quote-wc' ); ?></th>
			<?php } ?>

		</tr>
		<?php
		foreach ( $order->get_items() as $items ) {
			?>
			<tr>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo wp_kses_post( $items->get_name() ); ?></td>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo esc_attr( $items->get_quantity() ); ?></td>
				<?php if ( $display_price ) { ?>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $items ) ); ?></td>
				<?php } ?>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>

<p><?php esc_html_e( 'This order is awaiting a quote.', 'quote-wc' ); ?></p>

<p><?php esc_html_e( 'You shall receive a quote email from the site admin soon.', 'quote-wc' ); ?></p>

<?php do_action( 'woocommerce_email_footer' ); ?>

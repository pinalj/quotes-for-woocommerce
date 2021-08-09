<?php
/**
 * Request New Quote email
 *
 * @package Quotes for WooCommerce/Emails
 */

$display_price = false;
$text_align    = is_rtl() ? 'right' : 'left';
$margin_side   = is_rtl() ? 'left' : 'right';

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
			$item_id    = $items->get_id();
			$product_id = $items->get_variation_id() > 0 ? $items->get_variation_id() : $items->get_product_id();
			?>
			<tr>
				<td style="text-align:left; border: 1px solid #eee;">
					<a href='<?php echo esc_url( get_permalink( $product_id ) ); ?>' target='_blank'><?php echo wp_kses_post( $items->get_name() );?></a>
					<?php
					// allow other plugins to add additional product information here.
					do_action( 'woocommerce_order_item_meta_start', $item_id, $items, $order, $plain_text );

					wc_display_item_meta(
						$items,
						array(
							'label_before' => '<strong class="wc-item-meta-label" style="float: ' . esc_attr( $text_align ) . '; margin-' . esc_attr( $margin_side ) . ': .25em; clear: both">',
						)
					);

					// allow other plugins to add additional product information here.
					do_action( 'woocommerce_order_item_meta_end', $item_id, $items, $order, $plain_text );
					?>
				</td>
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

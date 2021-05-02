<?php
/**
 * Send Quote Email
 *
 * @package Quotes for WooCommerce/Email Templates
 */

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
if ( $order ) :
	$billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->billing_first_name : $order->get_billing_first_name();
	?>
	<p>
		<?php
		// translators: Billing First Name.
		echo sprintf( esc_html__( 'Hello %s', 'quote-wc' ), esc_attr( $billing_first_name ) );
		?>
	</p>
<?php endif; ?>

<p>
	<?php
	// translators: Site Name.
	echo sprintf( esc_html__( 'You have received a quotation for your order on %s. The details of the same are shown below.', 'quote-wc' ), esc_attr( $order_details->blogname ) );
	?>
</p>

<?php if ( $order ) : ?>

	<?php
		$order_status = $order->get_status();
	if ( 'pending' === $order_status ) :
		?>
		<p>
			<?php
			// translators: Payment Link Url.
			echo sprintf( esc_html__( 'To pay for this order please use the following link: %s', 'quote-wc' ), '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay for order', 'quote-wc' ) . '</a>' );
			?>
		</p>
	<?php endif; ?>

	<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

	<?php
	if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) {
		$order_date = $order->order_date;
	} else {
		$order_post = get_post( $order_details->order_id );
		$post_date  = strtotime( $order_post->post_date );
		$order_date = date( 'Y-m-d H:i:s', $post_date ); //phpcs:ignore
	}
	?>
	<h2><?php echo esc_html__( 'Order', 'quote-wc' ) . ': ' . esc_html( $order->get_order_number() ); ?> (<?php printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order_date ) ), date_i18n( wc_date_format(), strtotime( $order_date ) ) ); // phpcs:ignore ?>)</h2>
	<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
		<thead>
			<tr>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Product', 'quote-wc' ); ?></th>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Quantity', 'quote-wc' ); ?></th>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Price', 'quote-wc' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
				$downloadable = $order->is_download_permitted();

			switch ( $order_status ) {
				case 'completed':
					$args = array(
						'show_download_links' => $downloadable,
						'show_sku'            => false,
						'show_purchase_note'  => true,
					);
					if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) {
						echo wp_kses_post( $order->email_order_items_table( $args ) );
					} else {
						echo wp_kses_post( wc_get_email_order_items( $order, $args ) );
					}
					break;
				case 'processing':
					$args = array(
						'show_download_links' => $downloadable,
						'show_sku'            => true,
						'show_purchase_note'  => true,
					);
					if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) {
						echo wp_kses_post( $order->email_order_items_table( $args ) );
					} else {
						echo wp_kses_post( wc_get_email_order_items( $order, $args ) );
					}
					break;
				default:
					$args = array(
						'show_download_links' => $downloadable,
						'show_sku'            => true,
						'show_purchase_note'  => false,
					);
					if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) {
						echo wp_kses_post( $order->email_order_items_table( $args ) );
					} else {
						echo wp_kses_post( wc_get_email_order_items( $order, $args ) );
					}
					break;
			}
			?>
		</tbody>
		<tfoot>
			<?php
			if ( $order->get_order_item_totals() ) {
				$i = 0;
				foreach ( $order->get_order_item_totals() as $total ) {
					$i++;
					?>
						<tr>
							<th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; 
							<?php
							if ( 1 === $i ) {
								echo 'border-top-width: 4px;';}
							?>
							"><?php echo esc_html( $total['label'] ); ?></th>
							<td style="text-align:left; border: 1px solid #eee; 
							<?php
							if ( 1 === $i ) {
								echo 'border-top-width: 4px;';
							}
							?>
							"><?php echo wp_kses_post( $total['value'] ); ?></td>
						</tr>
						<?php
				}
			}
			?>
		</tfoot>
	</table>

	<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

	<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>

<?php endif; ?>

<?php do_action( 'woocommerce_email_footer' ); ?>

<?php
/**
 * Send Quote Email
 *
 * @package Quotes for WooCommerce/Email Templates
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

$text_align = is_rtl() ? 'right' : 'left';

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$heading_class              = $email_improvements_enabled ? 'email-order-detail-heading' : '';
$order_table_class          = $email_improvements_enabled ? 'email-order-details' : '';
$order_total_text_align     = $email_improvements_enabled ? 'right' : 'left';
$order_quantity_text_align  = $email_improvements_enabled ? 'right' : 'left';

do_action( 'woocommerce_email_header', $email_heading, $email );

if ( $order ) :
	$billing_first_name = $order->get_billing_first_name();
	?>
	<p>
		<?php
		echo $email_improvements_enabled ? '<div class="email-introduction">' : '';
		// translators: Billing First Name.
		echo esc_html( sprintf( __( 'Hello %s', 'quote-wc' ), esc_attr( $billing_first_name ) ) );
		echo $email_improvements_enabled ? '</div>' : '';
		?>
	</p>
<?php endif; ?>

<p>
	<?php
	// translators: Site Name.
	echo esc_html( sprintf( __( 'You have received a quotation for your quote request on %s. The details of the same are shown below.', 'quote-wc' ), esc_attr( $site_name ) ) );
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
			echo wp_kses_post( sprintf( __( 'To confirm the quotation and pay for this order please use the following link: %s', 'quote-wc' ), '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay for order', 'quote-wc' ) . '</a>' ) );
			?>
		</p>
	<?php endif; ?>

	<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

	<?php
	$post_date  = strtotime( $order->get_date_created() );
	$order_date = gmdate( 'Y-m-d H:i:s', $post_date );
	?>
	<h2 class="<?php echo esc_attr( $heading_class ); ?>">
		<?php
		if ( $email_improvements_enabled ) {
			echo wp_kses_post( __( 'Order summary', 'quote-wc' ) );
		}
		if ( $sent_to_admin ) {
			$before = '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">';
			$after  = '</a>';
		} else {
			$before = '';
			$after  = '';
		}
		if ( $email_improvements_enabled ) {
			echo '<br><span>';
		}
		/* translators: %s: Order ID. */
		$order_number_string = __( '[Order #%s]', 'quote-wc' );
		if ( $email_improvements_enabled ) {
			/* translators: %s: Order ID. */
			$order_number_string = __( 'Order #%s', 'quote-wc' );
		}
		echo wp_kses_post( $before . sprintf( $order_number_string . $after . ' (<time datetime="%s">%s</time>)', $order->get_order_number(), $order->get_date_created()->format( 'c' ), wc_format_datetime( $order->get_date_created() ) ) );
		if ( $email_improvements_enabled ) {
			echo '</span>';
		}
		?>
	</h2>
	<div style="margin-bottom: <?php echo $email_improvements_enabled ? '24px' : '40px'; ?>;">
		<table class="td font-family <?php echo esc_attr( $order_table_class ); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1">
			<thead>
				<tr>
					<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Product', 'quote-wc' ); ?></th>
					<th class="td" scope="col" style="text-align:<?php echo esc_attr( $order_quantity_text_align ); ?>;"><?php esc_html_e( 'Quantity', 'quote-wc' ); ?></th>
					<th class="td" scope="col" style="text-align:<?php echo esc_attr( $order_total_text_align ); ?>;"><?php esc_html_e( 'Price', 'quote-wc' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$downloadable = $order->is_download_permitted();

				switch ( $order_status ) {
					case 'completed':
						$args = array(
							'show_download_links' => $downloadable,
							'show_sku'            => $show_sku,
							'show_purchase_note'  => true,
							'show_image'          => $show_image,
						);
						break;
					case 'processing':
						$args = array(
							'show_download_links' => $downloadable,
							'show_sku'            => $show_sku,
							'show_purchase_note'  => true,
							'show_image'          => $show_image,
						);
						break;
					default:
						$args = array(
							'show_download_links' => $downloadable,
							'show_sku'            => $show_sku,
							'show_purchase_note'  => false,
							'show_image'          => $show_image,
						);
						break;
				}
				if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) {
					echo wp_kses_post( $order->email_order_items_table( $args ) );
				} else {
					echo wp_kses_post( wc_get_email_order_items( $order, $args ) );
				}
				?>
			</tbody>
		</table>
		<table class="td font-family <?php echo esc_attr( $order_table_class ); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1">
			<?php
			$item_totals       = $order->get_order_item_totals();
			$item_totals_count = count( $item_totals );

			if ( $item_totals ) {
				$i = 0;
				foreach ( $item_totals as $total ) {
					++$i;
					$last_class = ( $i === $item_totals_count ) ? ' order-totals-last' : '';
					?>
					<tr class="order-totals order-totals-<?php echo esc_attr( $total['type'] ?? 'unknown' ); ?><?php echo esc_attr( $last_class ); ?>">
						<th class="td text-align-left" scope="row" colspan="2" style="<?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>">
							<?php
							echo wp_kses_post( $total['label'] ) . ' ';
							if ( $email_improvements_enabled ) {
								echo isset( $total['meta'] ) ? wp_kses_post( $total['meta'] ) : '';
							}
							?>
						</th>
						<td class="td text-align-<?php echo esc_attr( $order_total_text_align ); ?>" style="<?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['value'] ); ?></td>
					</tr>
					<?php
				}
			}
			if ( $order->get_customer_note() && ! $email_improvements_enabled ) {
				?>
				<tr>
					<th class="td text-align-left" scope="row" colspan="2"><?php esc_html_e( 'Note:', 'quote-wc' ); ?></th>
					<td class="td text-align-left"><?php echo wp_kses( nl2br( wc_wptexturize_order_note( $order->get_customer_note() ) ), array() ); ?></td>
				</tr>
				<?php
			}
			?>
		</table>
		<?php if ( $order->get_customer_note() && $email_improvements_enabled ) { ?>
		<table class="td font-family <?php echo esc_attr( $order_table_class ); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1" role="presentation">
			<tr class="order-customer-note">
				<td class="td text-align-left">
					<b><?php esc_html_e( 'Customer note', 'quote-wc' ); ?></b><br>
					<?php echo wp_kses( nl2br( wc_wptexturize_order_note( $order->get_customer_note() ) ), array( 'br' => array() ) ); ?>
				</td>
			</tr>
		</table>
		<?php } ?>
		<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

		<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>
	</div>
<?php endif; ?>

<?php do_action( 'woocommerce_email_footer' ); ?>

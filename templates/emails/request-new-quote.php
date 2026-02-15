<?php
/**
 * Request New Quote email
 *
 * @package Quotes for WooCommerce/Email Templates
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
// translators: Billing Name.
$text_align                = is_rtl() ? 'right' : 'left';
$margin_side               = is_rtl() ? 'left' : 'right';
$image_size                = $email_improvements_enabled ? 48 : 32;
$price_text_align          = $email_improvements_enabled ? 'right' : 'left';
$order_total_text_align    = $email_improvements_enabled ? 'right' : 'left';
$order_quantity_text_align = $email_improvements_enabled ? 'right' : 'left';
$order_table_class         = $email_improvements_enabled ? 'email-order-details' : '';

// translators: Customer Name.
$opening_paragraph = __( 'A request for quote has been made by %s and is awaiting your attention. The details of the quote request are as follows:', 'quote-wc' );
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
$billing_first_name = $order->get_billing_first_name();
$billing_last_name  = $order->get_billing_last_name();
if ( $order && $billing_first_name && $billing_last_name ) :
	$order_id  = $order->get_id();
	$order_url = qwc_is_hpos_enabled() ? admin_url( 'admin.php?page=wc-orders&id=' . $order_id . '&action=edit' ) : admin_url( 'post.php?post=' . $order_id . '&action=edit' );

	echo $email_improvements_enabled ? '<div class="email-introduction">' : '';
	?>
	<p><?php echo esc_html( sprintf( $opening_paragraph, esc_attr( $billing_first_name . ' ' . $billing_last_name ) ) ); ?></p>
	<?php
	echo $email_improvements_enabled ? '</div>' : '';
endif;
?>

<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

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
		foreach ( $order->get_items() as $items ) {
			$item_id    = $items->get_id();
			$product_id = $items->get_variation_id() > 0 ? $items->get_variation_id() : $items->get_product_id();
			$_product   = wc_get_product( $product_id );
			$sku        = '';
			$image      = '';
			if ( $_product ) {
				$sku   = $_product->get_sku();
				$image = $_product->get_image(
					array( $image_size, $image_size ),
					array(
						'style' => "max-width:$image_size; height:auto; display:block;",
					)
				);
			}
			?>
			<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $items, $order ) ); ?>">
				<td class="td font-family text-align-left" style="vertical-align: middle; word-wrap:break-word;">
					<?php if ( $email_improvements_enabled ) { ?>
						<table class="order-item-data" role="presentation">
							<tr>
								<?php
								// Show title/image etc.
								if ( $show_image ) {
									// Email Order Item Thumbnail hook.
									echo '<td>' . wp_kses_post( apply_filters( 'woocommerce_order_item_thumbnail', $image, $items ) ) . '</td>';
								}
								?>
								<td>
									<?php
									$order_item_name = $items->get_name();
									$product_url     = esc_url( get_permalink( $product_id ) );
									echo wp_kses_post( "<h3 style='font-size: inherit;font-weight: inherit;'><a href='{$product_url}' target='_blank'>{$order_item_name}</a></h3>" );

									// SKU.
									if ( '' !== $sku && $show_sku ) {
										echo wp_kses_post( esc_html__( 'SKU', 'quote-wc' ) . ': #' . $sku );
									}

									// Allow other plugins to add additional product information.
									do_action( 'woocommerce_order_item_meta_start', $item_id, $items, $order, $plain_text );

									$item_meta = wc_display_item_meta(
										$items,
										array(
											'before'       => '',
											'after'        => '',
											'separator'    => '<br>',
											'echo'         => false,
											'label_before' => '<span>',
											'label_after'  => ':</span> ',
										)
									);
									echo '<div class="email-order-item-meta">';
									// Using wp_kses instead of wp_kses_post to remove all block elements.
									echo wp_kses(
										$item_meta,
										array(
											'br'   => array(),
											'span' => array(),
											'a'    => array(
												'href'   => true,
												'target' => true,
												'rel'    => true,
												'title'  => true,
											),
										)
									);
									echo '</div>';

									// Allow other plugins to add additional product information.
									do_action( 'woocommerce_order_item_meta_end', $item_id, $items, $order, $plain_text );

									?>
								</td>
							</tr>
						</table>
						<?php
					} else {

						// Show title/image etc.
						if ( $show_image ) {
							// Email Order Item Thumbnail hook.
							echo wp_kses_post( apply_filters( 'woocommerce_order_item_thumbnail', $image, $items ) );
						}
						?>
						<a href='<?php echo esc_url( get_permalink( $product_id ) ); ?>' target='_blank'><?php echo wp_kses_post( $items->get_name() ); ?></a>
						<br />
						<?php
						// SKU.
						if ( '' !== $sku && $show_sku ) {
							echo wp_kses_post( esc_html__( 'SKU', 'quote-wc' ) . ': #' . $sku );
						}

						// Allow other plugins to add additional product information.
						do_action( 'woocommerce_order_item_meta_start', $item_id, $items, $order, $plain_text );

						wc_display_item_meta(
							$items,
							array(
								'label_before' => '<strong class="wc-item-meta-label" style="float: ' . ( is_rtl() ? 'right' : 'left' ) . '; margin-' . esc_attr( $margin_side ) . ': .25em; clear: both">',
							)
						);

						// Allow other plugins to add additional product information.
						do_action( 'woocommerce_order_item_meta_end', $item_id, $items, $order, $plain_text );
					}
					?>
				</td>
				<td class="td font-family text-align-<?php echo esc_attr( $price_text_align ); ?>" style="vertical-align:middle;">
					<?php
					echo $email_improvements_enabled ? '&times;' : '';
					$qty          = $items->get_quantity();
					$refunded_qty = $order->get_qty_refunded_for_item( $item_id );

					if ( $refunded_qty ) {
						$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * -1 ) ) . '</ins>';
					} else {
						$qty_display = esc_html( $qty );
					}
					echo wp_kses_post( $qty_display );
					?>
				</td>
				<td class="td font-family text-align-<?php echo esc_attr( $price_text_align ); ?>" style="vertical-align:middle;">
					<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $items ) ); ?>
				</td>
			</tr>
			<?php
		}
		do_action( 'qwc_new_quote_admin_row', $order->get_id(), $order );
		?>
		</tbody>
	</table>
</div>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>

<p><?php esc_html_e( 'This request is awaiting a quote.', 'quote-wc' ); ?></p>

<p>
	<?php
	// translators: Admin Url for order.
	echo wp_kses_post( make_clickable( sprintf( __( 'You can view and edit this quote in the dashboard %s.', 'quote-wc' ), '<a href="' . esc_url( $order_url ) . '" target="_blanks">' . __( 'here', 'quote-wc' ) . '</a>' ) ) );
	?>
</p>
<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ); ?>
<?php do_action( 'woocommerce_email_footer' ); ?>

<?php
/**
 * Request New Quote email
 *
 * @package Quotes for WooCommerce/Emails
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

$display_price             = false;
$text_align                = is_rtl() ? 'right' : 'left';
$margin_side               = is_rtl() ? 'left' : 'right';
$image_size                = $email_improvements_enabled ? 48 : 32;
$price_text_align          = $email_improvements_enabled ? 'right' : 'left';
$order_total_text_align    = $email_improvements_enabled ? 'right' : 'left';
$order_quantity_text_align = $email_improvements_enabled ? 'right' : 'left';
$order_table_class         = $email_improvements_enabled ? 'email-order-details' : '';

// translators: Site Name.
$opening_paragraph = __( 'You have made a request for a quote on %s. The details of the quote request are as follows:', 'quote-wc' );
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
if ( $order ) :
	echo $email_improvements_enabled ? '<div class="email-introduction">' : '';
	?>
	<p><?php printf( esc_html( $opening_paragraph ), esc_attr( $site_name ) ); ?></p>
	<?php
	echo $email_improvements_enabled ? '</div>' : '';
endif;
?>

<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<div style="margin-bottom: <?php echo $email_improvements_enabled ? '24px' : '40px'; ?>;">
	<table class="td font-family <?php echo esc_attr( $order_table_class ); ?>" cellspacing="0" cellpadding="6" style="width: 100%;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $order_quantity_text_align ); ?>;"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
				<?php
				if ( qwc_order_display_price( $order ) ) {
					$display_price = true;
					?>
				<th class="td" scope="col" style="text-align:<?php echo esc_attr( $order_total_text_align ); ?>;"><?php esc_html_e( 'Product Price', 'woocommerce' ); ?></th>
				<?php } ?>				
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
				<?php if ( $display_price ) { ?>
				<td class="td font-family text-align-<?php echo esc_attr( $price_text_align ); ?>" style="vertical-align:middle;">
					<?php echo wp_kses_post( $order->get_formatted_line_subtotal( $items ) ); ?>
				</td>
				<?php } ?>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
</div>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>

<p><?php esc_html_e( 'This request is awaiting a quote.', 'quote-wc' ); ?></p>

<p><?php esc_html_e( 'You shall receive a quote email from the site admin soon.', 'quote-wc' ); ?></p>

<?php do_action( 'woocommerce_email_footer' ); ?>

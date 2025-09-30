<?php
/**
 * Request New Quote email
 *
 * @package Quotes for WooCommerce/Emails/Plain
 */

$display_price = false;
$text_align    = is_rtl() ? 'right' : 'left';
$margin_side   = is_rtl() ? 'left' : 'right';

// translators: Site Name.
$opening_paragraph = __( 'You have made a request for a quote on %s. The details of the order are as follows:', 'quote-wc' );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
do_action( 'woocommerce_email_header', $email_heading, $email );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
if ( $order ) {
	echo esc_html( sprintf( $opening_paragraph, esC_attr( $site_name ) ) );
}

if ( $order ) {
	do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );
	echo "\n----------------------------------------\n\n";
	echo esc_html( sprintf( __( 'Product', 'quote-wc' ) ) );
	echo "\t";
	echo esc_html( sprintf( __( 'Quantity', 'quote-wc' ) ) );
	if ( qwc_order_display_price( $order ) ) {
		$display_price = true;
		echo "\t";
		echo esc_html( sprintf( __( 'Product Price', 'quote-wc' ) ) );
	}

	echo "\n";

	foreach ( $order->get_items() as $items ) {
		$item_id    = $items->get_id();
		$product_id = $items->get_variation_id() > 0 ? $items->get_variation_id() : $items->get_product_id();
		$_product   = wc_get_product( $product_id );
		$sku        = $_product ? $_product->get_sku() : '';
		echo wp_kses_post( $items->get_name() );
		echo "\t\t";
		echo esc_attr( $items->get_quantity() );
		echo "\t\t";
		if ( $display_price ) {
			echo wp_kses_post( $order->get_formatted_line_subtotal( $items ) );
		}
		if ( '' !== $sku && $show_sku ) {
			echo "\n";
			echo esc_html( __( 'SKU', 'quote-wc' ) ) . ': #' . esc_html( $sku );
		}
		// allow other plugins to add additional product information here.
		do_action( 'woocommerce_order_item_meta_start', $item_id, $items, $order, $plain_text );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo esc_attr(
			wp_strip_all_tags(
				wc_display_item_meta(
					$items,
					array(
						'before'    => "\n- ",
						'separator' => "\n- ",
						'after'     => '',
						'echo'      => false,
						'autop'     => false,
					)
				)
			)
		);

		// allow other plugins to add additional product information here.
		do_action( 'woocommerce_order_item_meta_end', $item_id, $items, $order, $plain_text );
		echo "\n";

	}
	do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text );

	echo "\n----------------------------------------\n\n";
	echo esc_html( sprintf( __( 'This order is awaiting a quote.', 'quote-wc' ) ) );

	echo esc_html( sprintf( __( 'You shall receive a quote email from the site admin soon.', 'quote-wc' ) ) );

	do_action( 'woocommerce_email_footer' );
}

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
if ( $order_details ) {
	echo sprintf( esc_html( $opening_paragraph ), esc_attr( $site_name ) );
}

if ( $order ) {
	do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );
	echo "\n----------------------------------------\n\n";
	echo sprintf( esc_html__( 'Product', 'quote-wc' ) );
	echo sprintf( esc_html__( 'Quantity', 'quote-wc' ) );
	if ( qwc_order_display_price( $order ) ) {
		$display_price = true;
		echo sprintf( esc_html__( 'Product Price', 'quote-wc' ) );
	}

	echo "\n";

	foreach ( $order->get_items() as $items ) {
		$item_id = $items->get_id();
		echo wp_kses_post( $items->get_name() );
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
		echo esc_attr( $items->get_quantity() );
		if ( $display_price ) {
			echo wp_kses_post( $order->get_formatted_line_subtotal( $items ) );
		}
		echo "\n";

	}
	do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text );

	echo "\n----------------------------------------\n\n";
	echo sprintf( esc_html__( 'This order is awaiting a quote.', 'quote-wc' ) );

	echo sprintf( esc_html__( 'You shall receive a quote email from the site admin soon.', 'quote-wc' ) );

	do_action( 'woocommerce_email_footer' );
}

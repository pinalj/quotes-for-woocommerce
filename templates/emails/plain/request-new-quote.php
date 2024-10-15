<?php
/**
 * Request New Quote email
 *
 * @package Quotes for WooCommerce/Email Templates/Plain
 */

$text_align  = is_rtl() ? 'right' : 'left';
$margin_side = is_rtl() ? 'left' : 'right';

// translators: Customer Name.
$opening_paragraph = __( 'A request for quote has been made by %s and is awaiting your attention. The details of the order are as follows:', 'quote-wc' );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
do_action( 'woocommerce_email_header', $email_heading, $email );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
$billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->billing_first_name : $order->get_billing_first_name();
$billing_last_name  = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->billing_last_name : $order->get_billing_last_name();
if ( $order_details && $billing_first_name && $billing_last_name ) :
	$order_id  = $order_details->order_id;
	$order_url = qwc_is_hpos_enabled() ? admin_url( 'admin.php?page=wc-orders&id=' . $order_id . '&action=edit' ) : admin_url( 'post.php?post=' . $order_id . '&action=edit' );
	echo sprintf( esc_html( $opening_paragraph ), esc_attr( $billing_first_name . ' ' . $billing_last_name ) );
endif;

if ( $order ) {
	do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );
	echo "\n----------------------------------------\n\n";
	echo sprintf( esc_html__( 'Product', 'quote-wc' ) );
	echo sprintf( esc_html__( 'Quantity', 'quote-wc' ) );
	echo sprintf( esc_html__( 'Product Price', 'quote-wc' ) );

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
		echo wp_kses_post( $order->get_formatted_line_subtotal( $items ) );
		echo "\n";

	}
	do_action( 'qwc_new_quote_admin_row', $order_details->order_id, $order );
	do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );
	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text );

	echo "\n----------------------------------------\n\n";
	echo sprintf( esc_html__( 'This order is awaiting a quote.', 'quote-wc' ) );
	// translators: Admin Url for order.
	echo wp_kses_post( make_clickable( sprintf( __( 'You can view and edit this order in the dashboard here: %s', 'quote-wc' ), $order_url ) ) );

	do_action( 'woocommerce_email_footer' );
}

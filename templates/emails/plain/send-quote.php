<?php
/**
 * Send Quote Email
 *
 * @package Quotes for WooCommerce/Email Templates/Plain
 */

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
do_action( 'woocommerce_email_header', $email_heading, $email );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
if ( $order ) :
	$billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->billing_first_name : $order->get_billing_first_name();
	// translators: Billing First Name.
	echo sprintf( esc_html__( 'Hello %s', 'quote-wc' ), esc_attr( $billing_first_name ) ) . "\n\n";
endif;
// translators: Site Name.
echo sprintf( esc_html__( 'You have received a quotation for your order on %s. The details of the same are shown below.', 'quote-wc' ), esc_attr( $order_details->blogname ) );

if ( $order ) :

	$order_status = $order->get_status();
	if ( 'pending' === $order_status ) :
		// translators: Payment Link Url.
		echo sprintf( esc_html__( 'To pay for this order please use the following link: %s', 'quote-wc' ), esc_url( $order->get_checkout_payment_url() ) );
	endif;

	do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );

	if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) {
		$order_date = $order->order_date;
	} else {
		$post_date  = strtotime( $order->get_date_created() );
		$order_date = gmdate( 'Y-m-d H:i:s', $post_date );
	}
	echo "\n----------------------------------------\n\n";
	// translators: order ID.
	echo sprintf( esc_html__( 'Order number: %s', 'quote-wc' ), esc_attr( $order->get_order_number() ) ) . "\n";
	// translators: order date.
	echo sprintf( esc_html__( 'Order date: %s', 'quote-wc' ), wp_kses_post( date_i18n( wc_date_format(), strtotime( $order_date ) ) ) ) . "\n";
	echo "\n----------------------------------------\n\n";
	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text );

	echo "\n";

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

	echo "\n----------------------------------------\n\n";

	if ( $order->get_order_item_totals() ) {
		$i = 0;
		foreach ( $order->get_order_item_totals() as $total ) {
			$i++;
			if ( 1 == $i ) { // phpcs:ignore
				echo esc_html( $total['label'] ) . "\t " . wp_kses_post( $total['value'] ) . "\n";
			}
		}
	}

	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text );
endif;

do_action( 'woocommerce_email_footer' );

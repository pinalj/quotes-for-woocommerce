<?php
/**
 * Send Quote Email
 *
 * @package Quotes for WooCommerce/Email Templates/Plain
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

$text_align  = is_rtl() ? 'right' : 'left';
$margin_side = is_rtl() ? 'left' : 'right';

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) ) . "\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

if ( $order ) :
	$billing_first_name = $order->get_billing_first_name();
	// translators: Billing First Name.
	echo esc_html( sprintf( __( 'Hello %s', 'quote-wc' ), esc_attr( $billing_first_name ) ) ) . "\n\n";

	// translators: Site Name.
	echo esc_html( sprintf( __( 'You have received a quotation for your order on %s. The details of the same are shown below.', 'quote-wc' ), esc_attr( $site_name ) ) );

	$order_status = $order->get_status();
	if ( 'pending' === $order_status ) :
		// translators: Payment Link Url.
		echo wp_kses_post( make_clickable( sprintf( __( 'To pay for this order please use the following link: %s', 'quote-wc' ), esc_url( $order->get_checkout_payment_url() ) ) ) );
	endif;

	do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );

	$post_date  = strtotime( $order->get_date_created() );
	$order_date = gmdate( 'Y-m-d H:i:s', $post_date );

	echo "\n----------------------------------------\n\n";
	// translators: order ID.
	echo sprintf( esc_html__( 'Order number: %s', 'quote-wc' ), esc_attr( $order->get_order_number() ) ) . "\n";
	// translators: order date.
	echo sprintf( esc_html__( 'Order date: %s', 'quote-wc' ), wp_kses_post( date_i18n( wc_date_format(), strtotime( $order_date ) ) ) ) . "\n";
	echo "\n----------------------------------------\n\n";

	$downloadable = $order->is_download_permitted();

	switch ( $order_status ) {
		case 'completed':
			$args = array(
				'show_download_links' => $downloadable,
				'show_sku'            => $show_sku,
				'show_purchase_note'  => true,
				'plain_text'          => $plain_text,
			);
			echo wp_kses_post( wc_get_email_order_items( $order, $args ) );
			break;
		case 'processing':
			$args = array(
				'show_download_links' => $downloadable,
				'show_sku'            => $show_sku,
				'show_purchase_note'  => true,
				'plain_text'          => $plain_text,
			);
			echo wp_kses_post( wc_get_email_order_items( $order, $args ) );
			break;
		default:
			$args = array(
				'show_download_links' => $downloadable,
				'show_sku'            => $show_sku,
				'show_purchase_note'  => false,
				'plain_text'          => $plain_text,
			);
			echo wp_kses_post( wc_get_email_order_items( $order, $args ) );
			break;
	}

	echo "\n----------------------------------------\n\n";

	if ( $order->get_order_item_totals() ) {
		$i = 0;
		foreach ( $order->get_order_item_totals() as $total ) {
			++$i;
			if ( 1 == $i ) { // phpcs:ignore
				echo esc_html( $total['label'] ) . "\t " . wp_kses_post( $total['value'] ) . "\n";
			}
		}
	}

	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text );
endif;

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

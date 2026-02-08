<?php
/**
 * Request New Quote email
 *
 * @package Quotes for WooCommerce/Emails/Plain
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

$display_price = qwc_order_display_price( $order ) ? true : false;
$text_align    = is_rtl() ? 'right' : 'left';
$margin_side   = is_rtl() ? 'left' : 'right';

// translators: Site Name.
$opening_paragraph = __( 'You have made a request for a quote on %s. The details of the order are as follows:', 'quote-wc' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) ) . "\n";
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

if ( $order ) :
	printf( esc_html( $opening_paragraph ), esc_html( $site_name ) );

	do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );

	echo "\n=============================================\n";
	echo esc_html__( 'Quote Items:', 'quote-wc-pro' );
	echo "\n=============================================\n";

	foreach ( $order->get_items() as $item ) {
		$item_id    = $item->get_id();
		$product_id = $item->get_variation_id() > 0 ? $item->get_variation_id() : $item->get_product_id();
		$_product   = wc_get_product( $product_id );
		$sku        = $_product ? $_product->get_sku() : '';
		$item_name  = $item->get_name();
		$qty        = $item->get_quantity();
		$line_total = $order->get_formatted_line_subtotal( $item );

		if ( $email_improvements_enabled ) {

			$item_name .= ' × ' . $qty;
			echo wp_kses_post( str_pad( wp_kses_post( $item_name ), 40 ) );

			if ( $display_price ) {
				echo $line_total > 0 ? ' = ' . esc_html( str_pad( wp_kses( wc_price( $line_total ), array() ), 20, ' ', STR_PAD_LEFT ) ) : '';
			}
			// SKU.
			if ( '' !== $sku && $show_sku ) {
					echo "\n";
					echo esc_html__( 'SKU', 'quote-wc-pro' ) . ': #' . esc_html( $sku );
			}
		} else {
			echo wp_kses_post( $item_name );
			// SKU.
			if ( '' !== $sku && $show_sku ) {
				echo "\n";
				echo esc_html__( 'SKU', 'quote-wc-pro' ) . ': #' . esc_html( $sku );
			}
			echo "\n";
			echo wp_kses_post( __( 'Quantity: ', 'quote-wc-pro' ) . esc_html( $qty ) );

			if ( $display_price ) {
				echo "\n";
				echo $line_total > 0 ? wp_kses_post( __( 'Price: ', 'quote-wc-pro' ) . wc_price( $line_total ) ) : '';
			}
		}
		// allow other plugins to add additional product information here.
		do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, $plain_text );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wp_strip_all_tags(
			wc_display_item_meta(
				$item,
				array(
					'before'    => "\n- ",
					'separator' => "\n- ",
					'after'     => '',
					'echo'      => false,
					'autop'     => false,
				)
			)
		);

		// allow other plugins to add additional product information here.
		do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, $plain_text );

		echo "\n=============================================\n";
	}
endif;

do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n";
echo esc_html( sprintf( __( 'This order is awaiting a quote.', 'quote-wc' ) ) );
echo "\n\n";
echo esc_html( sprintf( __( 'You shall receive a quote email from the site admin soon.', 'quote-wc' ) ) );
echo "\n\n";
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

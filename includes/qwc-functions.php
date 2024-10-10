<?php
/**
 * Common Functions.
 *
 * @package Quotes for WooCommerce
 */

use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * Returns whether quotes are enabled.
 *
 * @param int $product_id - Product ID.
 * @param int $qty - Product Qty.
 * @return bool $quotes_enabled - Quote Status.
 */
function product_quote_enabled( $product_id, $qty = 1 ) {
	$quote_enabled = false;

	if ( ! is_admin() || wp_doing_ajax() ) {
		$quote_setting = get_post_meta( $product_id, 'qwc_enable_quotes', true );

		if ( 'on' === $quote_setting ) {
			$quote_enabled = true;
		}
	}
	$quote_enabled = apply_filters( 'qwc_product_quote_enabled', $quote_enabled, $product_id, $qty );
	return $quote_enabled;
}

/**
 * Return parent product ID for Variation ID.
 *
 * @param int $var_id - Variation ID.
 * @return int|false $product_id - Product ID | false.
 */
function qwc_get_product_id_by_variation_id( $var_id ) {
	$post = get_post( $var_id );
	if ( $post ) {
		return $post->post_parent;
	} else {
		return false;
	}
}

/**
 * Returns whether the cart contains products with quotes.
 *
 * @return bool Cart has quote products or no.
 */
function cart_contains_quotable() {

	$quotable = false;

	if ( isset( WC()->cart ) ) {
		foreach ( WC()->cart->cart_contents as $item ) {

			if ( 0 === $item['product_id'] ) {
				$item['product_id'] = qwc_get_product_id_by_variation_id( $item['variation_id'] );
			}
			$product_id    = apply_filters( 'qwc_cart_check_item_product_id', $item['product_id'], $item );
			$quantity      = isset( $item['quantity'] ) ? $item['quantity'] : 1;
			$quote_enabled = product_quote_enabled( $product_id, $quantity );

			if ( $quote_enabled ) {
				$quotable = true;
				break;
			}
		}
	}
	return $quotable;
}

/**
 * Returns whether order contains quote products or no.
 *
 * @param object $order - WC_Order.
 * @return bool|false - Order contains quot products | false when no data found.
 */
function order_requires_quote( $order ) {

	$requires = false;

	if ( $order ) {
		foreach ( $order->get_items() as $item ) {
			$quantity      = isset( $item['quantity'] ) ? $item['quantity'] : 1;
			$product_quote = product_quote_enabled( $item['product_id'], $quantity );
			if ( $product_quote ) {
				$requires = true;
				break;
			}
		}
	}

	return $requires;
}

/**
 * Return whether product price should be displayed or no.
 *
 * @param int $product_id - Product ID to be checked.
 * @return bool $display - true|false
 */
function product_price_display( $product_id ) {

	$display_price = false;

	// check if price should be displayed or no.
	$display_enabled = get_post_meta( $product_id, 'qwc_display_prices', true );

	if ( 'on' === $display_enabled ) {
		$display_price = true;
	}

	return $display_price;
}

/**
 * Returns whether price should be displayed for carts or no.
 *
 * @return bool $display - true|false
 */
function qwc_cart_display_price() {

	$display = false;

	if ( isset( WC()->cart ) ) {
		foreach ( WC()->cart->cart_contents as $item ) {
			$price_enabled = product_price_display( $item['product_id'] );

			if ( $price_enabled ) {
				$display = true;
				break;
			}
		}
	}
	return $display;
}

/**
 * Returns whether price should be displayed or no for Order.
 *
 * @param object $order - WC_Order.
 * @return bool $display - true|false
 */
function qwc_order_display_price( $order ) {

	$display = false;

	if ( $order ) {
		foreach ( $order->get_items() as $item ) {
			$product_display = product_price_display( $item['product_id'] );
			if ( $product_display ) {
				$display = true;
				break;
			}
		}
	}

	return $display;
}

/**
 * Returns if HPOS is enabled.
 *
 * @return bool
 * @since 1.10
 */
function qwc_is_hpos_enabled() {

	if ( defined( 'WOOCOMMERCE_VERSION' ) ) {
		if ( version_compare( WOOCOMMERCE_VERSION, '7.1.0' ) < 0 ) {
			return false;
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return true;
		}
	}

	return false;
}

/**
 * Modify product level settings in bulk.
 *
 * @param array  $products_list - List of products.
 * @param string $quote_setting_name - Setting meta key.
 * @param string $quote_setting_value - Settings meta value.
 *
 * @since 2.2
 */
function qwc_bulk_edit_setting_by_id( $products_list, $quote_setting_name, $quote_setting_value ) {

	if ( '' === $quote_setting_name ) {
		return;
	}

	if ( is_array( $products_list ) && count( $products_list ) > 0 ) {
		foreach ( $products_list as $k => $value ) {

			// Product ID.
			$theid = isset( $value->ID ) ? $value->ID : $value;
			update_post_meta( $theid, $quote_setting_name, $quote_setting_value );
		}
	}
}

/**
 * Returns whether the cart contains products with quotes.
 *
 * @return bool Cart has quote products or no.
 * @since 2.6
 */
function cart_contains_purchasable() {

	$purchasable = false;

	if ( isset( WC()->cart ) ) {
		foreach ( WC()->cart->cart_contents as $item ) {

			if ( 0 === $item['product_id'] ) {
				$item['product_id'] = qwc_get_product_id_by_variation_id( $item['variation_id'] );
			}
			$product_id    = apply_filters( 'qwc_cart_check_item_product_id', $item['product_id'], $item );
			$quantity      = isset( $item['quantity'] ) ? $item['quantity'] : 1;
			$quote_enabled = product_quote_enabled( $product_id, $quantity );

			if ( ! $quote_enabled ) {
				$purchasable = true;
				break;
			}
		}
	}
	return $purchasable;
}

/**
 * Returns whether order contains non-quote (purchase) products or no.
 *
 * @param object $order - WC_Order.
 * @return bool|false - Order contains quot products | false when no data found.
 */
function order_contains_purchase( $order ) {

	$purchasable = false;

	if ( $order ) {
		foreach ( $order->get_items() as $item ) {
			$quantity      = isset( $item['quantity'] ) ? $item['quantity'] : 1;
			$product_quote = product_quote_enabled( $item['product_id'], $quantity );
			if ( ! $product_quote ) {
				$purchasable = true;
				break;
			}
		}
	}

	return $purchasable;
}

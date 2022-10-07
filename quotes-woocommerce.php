<?php
/**
 * Plugin Name: Quotes for WooCommerce
 * Description: This plugin allows you to convert your WooCommerce store into a quote only store. It will hide the prices for the products and not take any payment at Checkout. You can then setup prices for the items in the order and send a notification to the Customer.
 * Version: 1.9
 * Author: Pinal Shah
 * Requires at least: 4.5
 * WC Requires at least: 3.0
 * WC tested up to: 6.5.1
 * Text Domain: quote-wc
 * Domain Path: /languages/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package Quotes For WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Quotes_WC' ) ) {
	include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/qwc-functions.php';
	include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/class-quotes-wc.php';
}

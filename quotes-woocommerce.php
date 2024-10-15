<?php
/**
 * Plugin Name: Quotes for WooCommerce
 * Description: This plugin allows you to convert your WooCommerce store into a quote only store. It will hide the prices for the products and not take any payment at Checkout. You can then setup prices for the items in the order and send a notification to the Customer.
 * Version: 2.6
 * Author: TechnoVama
 * Requires at least: 4.5
 * WC Requires at least: 4.0
 * WC tested up to: 9.3.1
 * Text Domain: quote-wc
 * Domain Path: /languages/
 * Author URI: https://woocommerce.com/vendor/technovama/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Quotes For WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if (
	! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ), true ) &&
	! ( is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) )
) {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	add_action(
		'admin_notices',
		function () {
			// translators: plugin name with link.
			$msg = sprintf( __( 'Please install and activate %s before activating Quotes for WooCommerce.', 'quote-wc' ), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>' );
			?>
			<div class="notice notice-error">
				<p><?php echo wp_kses_post( $msg ); ?></p>
			</div>
			<?php
		}
	);
	deactivate_plugins( 'quotes-for-woocommerce/quotes-woocommerce.php' );
	return;
}

if ( ! class_exists( 'Quotes_WC' ) ) {
	include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/qwc-functions.php';
	include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/class-quotes-wc.php';
}

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	},
	999
);

// Initialize settings.
register_activation_hook( __FILE__, array( 'Quotes_WC', 'qwc_activate' ) );
// Deactivation actions.
register_deactivation_hook( __FILE__, array( 'Quotes_WC', 'qwc_deactivate' ) );
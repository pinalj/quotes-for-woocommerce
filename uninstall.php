<?php
/**
 * Quotes for WooCommerce Uninstall
 *
 * Uninstalling the post meta records for the Plugin
 *
 * @author      TechnoVama
 * @package     Quotes For WooCommerce/uninstall
 * @version     2.5.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete the settings at product level for all the products.
delete_post_meta_by_key( 'qwc_enable_quotes' );
delete_post_meta_by_key( 'qwc_display_prices' );

// delete the quote statuses.
delete_post_meta_by_key( '_quote_status' );
// delete the payment meta.
delete_post_meta_by_key( '_qwc_quote' );

// delete the plugin version.
delete_option( 'quotes_for_wc' );

// delete the global settings in options table.
delete_option( 'qwc_enable_global_prices' );
delete_option( 'qwc_enable_global_quote' );
delete_option( 'qwc_cart_page_name' );
delete_option( 'qwc_hide_address_fields' );
delete_option( 'qwc_add_to_cart_button_text' );
delete_option( 'qwc_place_order_text' );
delete_option( 'qwc_checkout_page_name' );
delete_option( 'qwc_proceed_checkout_btn_label' );

// Menu notice.
delete_option( 'qwc_menu_notice' );
// Payment Settings.
delete_option( 'woocommerce_quotes-gateway_settings' );

// tracking settings.
delete_option( 'qwc_allow_tracking' );
delete_option( 'qwc_tracker_last_send' );
// Clear any cached data that has been removed.
wp_cache_flush();

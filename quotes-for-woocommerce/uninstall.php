<?php
/**
 * Quotes for WooCommerce Uninstall
 *
 * Uninstalling the post meta records for the Plugin
 *
 * @author      Pinal Shah
 * @package     quotes-for-wc/uninstall
 * @version     1.1
 */
    
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete the settings at product level for all the products
delete_post_meta_by_key( 'qwc_enable_quotes' );
// delete the quote statuses
delete_post_meta_by_key( '_quote_status' );
// delete the payment meta
delete_post_meta_by_key( '_qwc_quote' );

// delete the plugin version
delete_option( 'quotes_for_wc' );
// Clear any cached data that has been removed
wp_cache_flush();

?>
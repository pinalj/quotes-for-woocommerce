<?php
/**
 * Product Settings.
 *
 * @package Quotes for WooCommerce/Admin
 */

if ( ! class_exists( 'Quotes_Product_Settings' ) ) {

	/**
	 * Admin settings.
	 */
	class Quotes_Product_Settings {

		/**
		 * Construct.
		 */
		public function __construct() {
			// Add setting to hide wc prices.
			add_action( 'woocommerce_product_options_inventory_product_data', array( &$this, 'qwc_setting' ) );
			// Hook in to save the quote settings.
			add_action( 'woocommerce_process_product_meta', array( &$this, 'qwc_save_setting' ), 10, 1 );
		}

		/**
		 * Add a setting to enable/disabe quotes
		 * in the Inventory tab.
		 *
		 * @since 1.0
		 */
		public function qwc_setting() {

			global $post;

			$post_id = ( isset( $post->ID ) && $post->ID > 0 ) ? $post->ID : 0;

			if ( $post_id > 0 ) {

				$enable_quotes  = get_post_meta( $post_id, 'qwc_enable_quotes', true );
				$quotes_checked = ( 'on' === $enable_quotes ) ? 'yes' : 'no';

				woocommerce_wp_checkbox(
					array(
						'id'          => 'qwc_enable_quotes',
						'label'       => __( 'Enable Quotes', 'quote-wc' ),
						'description' => __( 'Enable this to allow customers to ask for a quote for the product.', 'quote-wc' ),
						'value'       => $quotes_checked,
					)
				);

				$display        = get_post_meta( $post_id, 'qwc_display_prices', true );
				$prices_enabled = ( 'on' === $display ) ? 'yes' : 'no';

				woocommerce_wp_checkbox(
					array(
						'id'          => 'qwc_display_prices',
						'label'       => __( 'Display Product Price', 'quote-wc' ),
						'description' => __( 'Enable this to display the product price on the Shop & Product pages.', 'quote-wc' ),
						'value'       => $prices_enabled,
					)
				);

			}
		}

		/**
		 * Save the quotes setting.
		 *
		 * @param int $post_id - Product ID.
		 * @since 1.0
		 */
		public function qwc_save_setting( $post_id ) {
			$variations_setup = false;

			$display = ( isset( $_POST['qwc_display_prices'] ) ) ? 'on' : ''; //phpcS:ignore WordPress.Security.NonceVerification
			update_post_meta( $post_id, 'qwc_display_prices', $display );

			// Get the variation IDs.
			$_product = wc_get_product( $post_id );
			if ( $_product->is_type( 'variable' ) ) {
				$variations    = $_product->get_available_variations();
				$variations_id = wp_list_pluck( $variations, 'variation_id' );
				$quotes_val    = isset( $_POST['variation_enable_quote'] ) ? $_POST['variation_enable_quote'] : array(); //phpcS:ignore
				if ( count( $quotes_val ) > 0 ) {
					foreach ( $variations_id as $key => $var ) {
						$quote_s = isset( $quotes_val[ $key ] ) && 'on' === $quotes_val[ $key ] ? 'on' : '';
						update_post_meta( $var, 'qwc_enable_quotes', $quote_s );
					}
					if ( in_array( 'on', $quotes_val, true ) ) {
						$variations_setup = true;
					}
				}
			}
			if ( ! $variations_setup ) {
				$enable_quotes = ( isset( $_POST['qwc_enable_quotes'] ) ) ? 'on' : ''; //phpcS:ignore WordPress.Security.NonceVerification
				update_post_meta( $post_id, 'qwc_enable_quotes', $enable_quotes );
			} else {
				update_post_meta( $post_id, 'qwc_enable_quotes', '' );
			}

		}

	}
}
$quotes_product_settings = new Quotes_Product_Settings();

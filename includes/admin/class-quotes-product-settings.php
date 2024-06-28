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
			// Hook in to save the quote settings.
			add_action( 'woocommerce_process_product_meta', array( &$this, 'qwc_save_setting' ), 10, 1 );
		}

		/**
		 * Save the quotes setting.
		 *
		 * @param int $post_id - Product ID.
		 * @since 1.0
		 */
		public function qwc_save_setting( $post_id ) {

			if ( ! class_exists( 'Quotes_Product_Settings_Pro' ) ) { // Save only if Pro is not being used.
				$display = 'on' === get_option( 'qwc_enable_global_prices', '' ) ? 'on' : '';
				update_post_meta( $post_id, 'qwc_display_prices', $display );

				$enable_quotes = 'on' === get_option( 'qwc_enable_global_quote', '' ) ? 'on' : '';
				update_post_meta( $post_id, 'qwc_enable_quotes', $enable_quotes );
			}

		}

	}
}
$quotes_product_settings = new Quotes_Product_Settings();

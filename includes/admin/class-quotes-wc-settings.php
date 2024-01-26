<?php
/**
 * Global Settings.
 *
 * @package Quotes for WooCommerce/Admin
 */

if ( ! class_exists( 'Quotes_WC_Settings_T' ) && class_exists( 'WC_Settings_Page' ) ) {

	/**
	 * Global settings.
	 */
	class Quotes_WC_Settings_T extends WC_Settings_Page {

		/**
		 * Construct.
		 */
		public function __construct() {

			$this->id    = 'qwc_quotes_tab';
			$this->label = __( 'Quotes', 'quote-wc' );

			parent::__construct();
		}

		/**
		 * Add Settings tab in WC Settings.
		 *
		 * @param array $settings_tab - List of tabs.
		 * @return array $settings_tab - List of tabs.
		 */
		public static function add_settings_tab( $settings_tab ) {
			$settings_tab['qwc_quotes_tab'] = __( 'Quotes', 'quote-wc' );
			return $settings_tab;
		}

		/**
		 * Return Settings.
		 */
		public function get_settings() {

			global $current_section;
			return apply_filters( 'woocommerce_get_settings_' . $this->id . '_' . $current_section, array() );
		}

	}
	new Quotes_WC_Settings_T();
}

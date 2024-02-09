<?php
/**
 * Global Settings Section.
 *
 * @package Quotes for WooCommerce/Admin
 */

if ( ! class_exists( 'Quotes_WC_Settings_Section' ) ) {

	/**
	 * Admin settings.
	 */
	class Quotes_WC_Settings_Section {

		/**
		 * Construct.
		 */
		public function __construct() {
			add_filter( 'woocommerce_get_sections_qwc_quotes_tab', array( $this, 'settings_section' ) );
			add_action( 'init', array( $this, 'add_settings_hook' ) );
		}

		/**
		 * Hook for new settings.
		 */
		public function add_settings_hook() {
			add_filter( 'qwc_quotes_tab_settings_' . $this->id, array( $this, 'add_settings' ) );
		}

		/**
		 * Add Section.
		 *
		 * @param array $sections - Sections list.
		 */
		public function settings_section( $sections ) {
			$sections[ $this->id ] = $this->desc;
			return $sections;
		}

	}
}

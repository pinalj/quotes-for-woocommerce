<?php
/**
 * Global Settings.
 *
 * @package Quotes for WooCommerce/Admin
 */

if ( ! class_exists( 'Quotes_Global_Settings' ) ) {

	/**
	 * Admin settings.
	 */
	class Quotes_Global_Settings {

		/**
		 * Construct.
		 */
		public function __construct() {

			// WordPress settings API.
			add_action( 'admin_init', array( &$this, 'qwc_plugin_settings' ) );

			// update product setting when global settings are added/updated.
			add_action( 'add_option_qwc_enable_global_quote', array( &$this, 'qwc_update_global_quotes_callback' ), 10, 2 );
			add_action( 'add_option_qwc_enable_global_prices', array( &$this, 'qwc_update_global_prices_callback' ), 10, 2 );
			add_action( 'update_option_qwc_enable_global_quote', array( &$this, 'qwc_update_global_quotes_callback' ), 10, 2 );
			add_action( 'update_option_qwc_enable_global_prices', array( &$this, 'qwc_update_global_prices_callback' ), 10, 2 );

		}
		/**
		 * Adds the Section and fields to Quotes->Settings page.
		 *
		 * @since 1.5
		 */
		public function qwc_plugin_settings() {

			// First, we register a section. This is necessary since all future options must belong to a section.
			add_settings_section(
				'qwc_general_settings_section',                    // ID used to identify this section and with which to register options.
				__( 'Global Settings', 'quote-wc' ),                      // Title to be displayed on the administration page.
				array( $this, 'qwc_general_options_callback' ),    // Callback used to render the description of the section.
				'qwc_bulk_page'                                         // Page on which to add this section of options.
			);

			add_settings_field(
				'qwc_enable_global_quote',
				__( 'Enable Quotes:', 'quote-wc' ),
				array( $this, 'qwc_enable_global_quote_callback' ),
				'qwc_bulk_page',
				'qwc_general_settings_section',
				array( __( 'Select if you wish to enable quotes for all the products.', 'quote-wc' ) )
			);

			add_settings_field(
				'qwc_enable_global_prices',
				__( 'Enable Price Display:', 'quote-wc' ),
				array( $this, 'qwc_enable_global_price_callback' ),
				'qwc_bulk_page',
				'qwc_general_settings_section',
				array( __( 'Select to display the product price on the Shop & Product pages for all quotable products.', 'quote-wc' ) )
			);

			add_settings_section(
				'qwc_shop_product_settings_section',                    // ID used to identify this section and with which to register options.
				__( 'Shop & Product Page Settings', 'quote-wc' ),   // Title to be displayed on the administration page.
				array( $this, 'qwc_shop_product_settings_callback' ),    // Callback used to render the description of the section.
				'qwc_page'                                         // Page on which to add this section of options.
			);

			add_settings_field(
				'qwc_add_to_cart_button_text',
				__( 'Add to Cart button text:', 'quote-wc' ),
				array( $this, 'qwc_add_to_cart_button_text_callback' ),
				'qwc_page',
				'qwc_shop_product_settings_section',
				array( __( 'Text that should be displayed on the Add to Cart button for quotable products.', 'quote-wc' ) )
			);

			add_settings_section(
				'qwc_cart_settings_section',                    // ID used to identify this section and with which to register options.
				__( 'Cart & Checkout Settings', 'quote-wc' ),   // Title to be displayed on the administration page.
				array( $this, 'qwc_cart_settings_callback' ),   // Callback used to render the description of the section.
				'qwc_page'                                      // Page on which to add this section of options.
			);

			add_settings_field(
				'qwc_place_order_text',
				__( 'Place Order button text for Quotable Products:', 'quote-wc' ),
				array( $this, 'qwc_place_order_text_callback' ),
				'qwc_page',
				'qwc_cart_settings_section',
				array( __( 'Place Order button text for Quotable products at Checkout.', 'quote-wc' ) )
			);

			add_settings_field(
				'qwc_cart_page_name',
				__( 'Cart page Name for Quotable Products:', 'quote-wc' ),
				array( $this, 'qwc_cart_page_name_callback' ),
				'qwc_page',
				'qwc_cart_settings_section',
				array( __( 'Display a custom name for Cart page when cart contains only quotable products.', 'quote-wc' ) )
			);

			add_settings_field(
				'qwc_hide_address_fields',
				__( 'Hide Address fields at Checkout:', 'quote-wc' ),
				array( $this, 'qwc_hide_address_fields_callback' ),
				'qwc_page',
				'qwc_cart_settings_section',
				array( __( 'Hide Billing & Shipping Address fields at Checkout if the Cart contains only quotable products.', 'quote-wc' ) )
			);

			register_setting(
				'qwc_bulk_settings',
				'qwc_enable_global_quote'
			);

			register_setting(
				'qwc_bulk_settings',
				'qwc_enable_global_prices'
			);

			register_setting(
				'quote_settings',
				'qwc_add_to_cart_button_text'
			);

			register_setting(
				'quote_settings',
				'qwc_cart_page_name'
			);

			register_setting(
				'quote_settings',
				'qwc_place_order_text'
			);

			register_setting(
				'quote_settings',
				'qwc_hide_address_fields'
			);

		}

		/**
		 * Updates the product level quote settings when global settings
		 * are updated in Quotes->Settings for Enable Quotes
		 *
		 * @param string $old_value - Old Setting Value.
		 * @param string $new_value - New Setting Value.
		 * @since 1.5
		 */
		public function qwc_update_global_quotes_callback( $old_value, $new_value ) {

			// Get all the list of products & save in there.
			$number_of_batches = $this->qwc_get_post_count();

			for ( $i = 1; $i <= $number_of_batches; $i++ ) {
				$this->qwc_all_quotes( 'qwc_enable_quotes', $new_value, $i );
			}
		}

		/**
		 * Updates the product level quote settings when global settings
		 * are updated in Quotes->Settings for Enable Price Display.
		 *
		 * @param string $old_value - Old Setting Value.
		 * @param string $new_value - New Setting Value.
		 * @since 1.5
		 */
		public function qwc_update_global_prices_callback( $old_value, $new_value ) {

			// Get all the list of products & save in there.
			$number_of_batches = $this->qwc_get_post_count();

			for ( $i = 1; $i <= $number_of_batches; $i++ ) {
				$this->qwc_all_quotes( 'qwc_display_prices', $new_value, $i );
			}
		}

		/**
		 * Section callback
		 *
		 * @since 1.5
		 */
		public function qwc_general_options_callback() {}

		/**
		 * Displays the Enable Quotes field in Quotes->Settings
		 *
		 * @param array $args - Arguments.
		 * @since 1.5
		 */
		public function qwc_enable_global_quote_callback( $args ) {

			$enable_quotes_global = get_option( 'qwc_enable_global_quote', '' );

			printf(
				'<input type="checkbox" id="qwc_enable_global_quote" name="qwc_enable_global_quote" value="on" ' . checked( 'on', $enable_quotes_global, false ) . ' />'
			);

			$html = '<label for="qwc_enable_global_quote"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Displays the Enable Price Display field in
		 * Quotes->Settings
		 *
		 * @param array $args - Arguments.
		 * @since 1.5
		 */
		public function qwc_enable_global_price_callback( $args ) {

			$enable_prices_global = get_option( 'qwc_enable_global_prices', '' );

			printf(
				'<input type="checkbox" id="qwc_enable_global_prices" name="qwc_enable_global_prices" value="on" ' . checked( 'on', $enable_prices_global, false ) . ' />'
			);

			$html = '<label for="qwc_enable_global_prices"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Section Callback.
		 */
		public function qwc_shop_product_settings_callback(){}

		/**
		 * Callback for setting to modify the add to cart button text
		 *
		 * @param array $args - Arguments.
		 */
		public function qwc_add_to_cart_button_text_callback( $args ) {

			$add_to_cart_button_text = get_option( 'qwc_add_to_cart_button_text', '' );

			$add_to_cart_button_text = '' === $add_to_cart_button_text ? esc_html__( 'Request Quote', 'quote-wc' ) : $add_to_cart_button_text;

			echo sprintf(
				'<input type="text" id="qwc_add_to_cart_button_text" name="qwc_add_to_cart_button_text" value="%s" />',
				esc_attr( $add_to_cart_button_text )
			);

			$html = '<label for="qwc_add_to_cart_button_text"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Settings Section Callback.
		 */
		public function qwc_cart_settings_callback() {}

		/**
		 * Displays the setting for the Cart page name change
		 *
		 * @param array $args - Arguments.
		 * @since 1.7
		 */
		public function qwc_cart_page_name_callback( $args ) {

			$cart_page_name = get_option( 'qwc_cart_page_name', '' );

			if ( '' === $cart_page_name ) {
				$cart_page_name = esc_html__( 'Cart', 'quote-wc' );
			}
			echo sprintf(
				'<input type="text" id="qwc_cart_page_name" name="qwc_cart_page_name" value="%s" />',
				esc_attr( $cart_page_name )
			);

			$html = '<label for="qwc_cart_page_name"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Callback function for Global Settings - Place Order text button field.
		 *
		 * @param array $args - Arguments.
		 */
		public function qwc_place_order_text_callback( $args ) {
			$place_order_button_text = get_option( 'qwc_place_order_text', '' );

			if ( '' === $place_order_button_text ) {
				$place_order_button_text = esc_html__( 'Request Quote', 'quote-wc' );
			}
			echo sprintf(
				'<input type="text" id="qwc_place_order_text" name="qwc_place_order_text" value="%s" />',
				esc_attr( $place_order_button_text )
			);

			$html = '<label for="qwc_place_order_text"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Callback function for Global Settings - Hide Address field.
		 *
		 * @param array $args - Arguments.
		 */
		public function qwc_hide_address_fields_callback( $args ) {

			$enable_hide_address = get_option( 'qwc_hide_address_fields', '' );

			printf(
				'<input type="checkbox" id="qwc_hide_address_fields" name="qwc_hide_address_fields" value="on" ' . checked( 'on', $enable_hide_address, false ) . ' />'
			);

			$html = '<label for="qwc_hide_address_fields"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Gets the count of batches that need to be run to update the
		 * settings for all the published and draft products.
		 *
		 * @return int $number_of_batches - Number of batches (each batch consists of 500 products).
		 * @since 1.5
		 */
		public function qwc_get_post_count() {

			$args         = array(
				'post_type'        => 'product',
				'numberposts'      => -1,
				'post_status'      => array( 'draft', 'publish' ),
				'suppress_filters' => false,
			);
			$product_list = get_posts( $args );

			$count = count( $product_list );

			$number_of_batches = ceil( $count / 500 );
			wp_reset_postdata();
			return $number_of_batches;
		}

		/**
		 * Updates the Quote Settings for all published and draft products
		 *
		 * @param str $quote_setting_name - Setting to be updated in Post Meta.
		 * @param str $quote_setting_value - Setting Value to be updated.
		 * @param int $loop - Batch Number of Products to be fetched (Only 500 products are updated at one go).
		 * @since 1.5
		 */
		public function qwc_all_quotes( $quote_setting_name, $quote_setting_value, $loop ) {

			$quote_setting_value = null === $quote_setting_value ? '' : $quote_setting_value;

			// Get the products.
			$args         = array(
				'post_type'        => 'product',
				'numberposts'      => 500, // phpcs:ignore.
				'suppress_filters' => false,
				'post_status'      => array( 'publish', 'draft' ),
				'paged'            => $loop,
			);
			$product_list = get_posts( $args );

			foreach ( $product_list as $k => $value ) {

				// Product ID.
				$theid = $value->ID;
				update_post_meta( $theid, $quote_setting_name, $quote_setting_value );
			}

			wp_reset_postdata();

		}

	}
}
$quotes_global_settings = new Quotes_Global_Settings();

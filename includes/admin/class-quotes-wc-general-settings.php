<?php
/**
 * Global Settings.
 *
 * @package Quotes for WooCommerce/Admin
 */

if ( ! class_exists( 'Quotes_WC_General_Settings' ) ) {

	/**
	 * Admin settings.
	 */
	class Quotes_WC_General_Settings extends Quotes_WC_Settings_Section {

		/**
		 * Tab ID.
		 *
		 * @var   string
		 * @since 2.5.0
		 */
		public $id = '';

		/**
		 * Tab Description.
		 *
		 * @var   string
		 * @since 2.5.0
		 */
		public $desc = '';

		/**
		 * Construct.
		 */
		public function __construct() {
			$this->id   = '';
			$this->desc = __( 'General', 'quote-wc' );
			parent::__construct();
			add_filter( 'woocommerce_get_settings_qwc_quotes_tab_' . $this->id, array( $this, 'get_settings' ) );
			add_action( 'add_option_qwc_enable_global_quote', array( &$this, 'qwc_update_global_quotes_callback' ), 10, 2 );
			add_action( 'update_option_qwc_enable_global_quote', array( &$this, 'qwc_update_global_quotes_callback' ), 10, 2 );
			add_action( 'add_option_qwc_enable_global_prices', array( &$this, 'qwc_update_global_prices_callback' ), 10, 2 );
			add_action( 'update_option_qwc_enable_global_prices', array( &$this, 'qwc_update_global_prices_callback' ), 10, 2 );

			add_action( 'woocommerce_admin_settings_sanitize_option_qwc_enable_global_quote', array( &$this, 'gen_page_sanitize_checkbox' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_qwc_enable_global_prices', array( &$this, 'gen_page_sanitize_checkbox' ), 10, 3 );
			add_action( 'woocommerce_admin_settings_sanitize_option_qwc_hide_address_fields', array( &$this, 'gen_page_sanitize_checkbox' ), 10, 3 );
		}

		/**
		 * Filter settings.
		 *
		 * @param array $settings - Settings Array.
		 */
		public function get_settings( $settings ) {
			return array_merge(
				apply_filters( 'qwc_quotes_tab_settings_' . $this->id, array() ),
				array()
			);
		}

		/**
		 * Return settings for section.
		 *
		 * @param array $settings - Settings Array.
		 */
		public function add_settings( $settings ) {
			global $current_section;

			$enable_bulk_quotes  = 'on' === get_option( 'qwc_enable_global_quote', '' ) ? 'yes' : '';
			$enable_bulk_display = 'on' === get_option( 'qwc_enable_global_prices', '' ) ? 'yes' : '';
			$hide_address        = 'on' === get_option( 'qwc_hide_address_fields', '' ) ? 'yes' : '';

			$place_order_button_text = '' === get_option( 'qwc_place_order_text', '' ) ? __( 'Request Quote', 'quote-wc' ) : get_option( 'qwc_place_order_text' );
			$cart_button_text        = '' === get_option( 'qwc_add_to_cart_button_text', '' ) ? __( 'Request Quote', 'quote-wc' ) : get_option( 'qwc_add_to_cart_button_text' );
			$cart_page_name          = '' === get_option( 'qwc_cart_page_name', '' ) ? __( 'Cart', 'quote-wc' ) : get_option( 'qwc_cart_page_name' );
			$checkout_page_name      = '' === get_option( 'qwc_checkout_page_name', '' ) ? __( 'Checkout', 'quote-wc' ) : get_option( 'qwc_checkout_page_name' );
			$proceed_checkout_label  = '' === get_option( 'qwc_proceed_checkout_btn_label', '' ) ? __( 'Proceed to Checkout', 'quote-wc' ) : get_option( 'qwc_proceed_checkout_btn_label' );

			$settings = array(

				array(
					'title' => __( 'Global Settings', 'quote-wc' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'qwc_general_settings_section',
				),
				array(
					'title' => __( 'Enable Quotes:', 'quote-wc' ),
					'desc'  => __( 'Select if you wish to enable quotes for all the products.', 'quote-wc' ),
					'id'    => 'qwc_enable_global_quote',
					'type'  => 'checkbox',
					'value' => $enable_bulk_quotes,
				),
				array(
					'title' => __( 'Enable Price Display:', 'quote-wc' ),
					'desc'  => __( 'Select to display the product price on the Shop & Product pages for all quotable products.', 'quote-wc' ),
					'id'    => 'qwc_enable_global_prices',
					'type'  => 'checkbox',
					'value' => $enable_bulk_display,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'qwc_general_settings_section',
				),
				array(
					'title' => __( 'Shop & Product Page Settings', 'quote-wc' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'qwc_shop_product_settings_section',
				),
				array(
					'title' => __( 'Add to Cart button text:', 'quote-wc' ),
					'type'  => 'text',
					'desc'  => __( 'Text that should be displayed on the Add to Cart button for quotable products.', 'quote-wc' ),
					'id'    => 'qwc_add_to_cart_button_text',
					'value' => $cart_button_text,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'qwc_shop_product_settings_section',
				),
				array(
					'title' => __( 'Cart & Checkout Settings', 'quote-wc' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'qwc_cart_settings_section',
				),
				array(
					'title' => __( 'Checkout page Name for Quotable Products:', 'quote-wc' ),
					'type'  => 'text',
					'desc'  => __( 'Display a custom name for Checkout page when cart contains only quotable products.', 'quote-wc' ),
					'id'    => 'qwc_checkout_page_name',
					'css'   => 'min-width:300px;',
					'value' => $checkout_page_name,
				),
				array(
					'title' => __( 'Place Order button text for Quotable Products:', 'quote-wc' ),
					'type'  => 'text',
					'desc'  => __( 'Place Order button text for Quotable products at Checkout.', 'quote-wc' ),
					'id'    => 'qwc_place_order_text',
					'value' => $place_order_button_text,
				),
				array(
					'title' => __( 'Cart page Name for Quotable Products:', 'quote-wc' ),
					'type'  => 'text',
					'desc'  => __( 'Display a custom name for Cart page when cart contains only quotable products.', 'quote-wc' ),
					'id'    => 'qwc_cart_page_name',
					'css'   => 'min-width:300px;',
					'value' => $cart_page_name,
				),
				array(
					'title' => __( 'Proceed to Checkout button text:', 'quote-wc' ),
					'type'  => 'text',
					'desc'  => __( 'Proceed to Checkout button label on the Cart page when cart contains only quotable products.', 'quote-wc' ),
					'id'    => 'qwc_proceed_checkout_btn_label',
					'css'   => 'min-width:300px;',
					'value' => $proceed_checkout_label,
				),
				array(
					'title'    => __( 'Hide Address fields at Checkout:', 'quote-wc' ),
					'desc'     => 'Hide Billing & Shipping Address fields at Checkout if the Cart contains only quotable products. ',
					'desc_tip' => __( 'Works only for the traditional Checkout page built using WooCommerce shortcodes.', 'quote-wc' ),
					'id'       => 'qwc_hide_address_fields',
					'type'     => 'checkbox',
					'value'    => $hide_address,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'qwc_cart_settings_section',
				),
			);
			$settings = apply_filters( 'qwc_lite_general_settings', $settings );
			return $settings;
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
			$new_value_db      = 'on' === $new_value ? 'on' : '';
			for ( $i = 1; $i <= $number_of_batches; $i++ ) {
				$this->qwc_all_quotes( 'qwc_enable_quotes', $new_value_db, $i );
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
			$new_value_db      = 'on' === $new_value ? 'on' : '';
			for ( $i = 1; $i <= $number_of_batches; $i++ ) {
				$this->qwc_all_quotes( 'qwc_display_prices', $new_value_db, $i );
			}
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

			switch ( $quote_setting_name ) {
				case 'qwc_enable_quotes':
					$product_list = apply_filters( 'qwc_enable_quote_bulk', $product_list, $quote_setting_value );
					break;
				case 'qwc_display_prices':
					$product_list = apply_filters( 'qwc_enable_price_display_bulk', $product_list, $quote_setting_value );
					break;
				default:
					break;
			}
			qwc_bulk_edit_setting_by_id( $product_list, $quote_setting_name, $quote_setting_value );

			wp_reset_postdata();
		}

		/**
		 * Save checkbox value as on and off.
		 *
		 * @param string $value - Checkbox value.
		 * @param string $option - Option Name.
		 * @param string $raw_value - Unsanitized value.
		 */
		public function gen_page_sanitize_checkbox( $value, $option, $raw_value ) {
			$value = '';
			if ( 'yes' === $raw_value || '1' === $raw_value ) {
				$value = 'on';
			}
			return $value;
		}
	}
}
return new Quotes_WC_General_Settings();

<?php
/**
 * Payment Gateway Class.
 *
 * @package     Quotes For WooCommerce
 * @class       QWC_Data_Tracking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'QWC_Data_Tracking' ) ) {

	/**
	 * QWC_Data_Tracking class.
	 */
	class QWC_Data_Tracking {

		/**
		 * Constructor for the tracker.
		 */
		public function __construct() {
			add_action( 'admin_footer', array( __CLASS__, 'qwc_admin_notices_scripts' ) );
			// Send Tracker Data.
			add_action( 'qwc_init_tracker_completed', array( __CLASS__, 'init_tracker_completed' ), 10, 2 );
			add_filter( 'qwc_tracker_display_notice', array( __CLASS__, 'qwc_tracker_display_notice' ), 10, 1 );
			// Add plugin tracking data.
			add_filter( 'qwc_tracker_data', array( __CLASS__, 'qwc_add_plugin_tracking_data' ), 10, 1 );
			// Custom query for product quotes.
			add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array( __CLASS__, 'qwc_handle_custom_query_var' ), 10, 2 );
		}

		/**
		 * Add tracker completed.
		 */
		public static function init_tracker_completed() {
			header( 'Location: ' . admin_url( 'admin.php?page=wc-settings&tab=qwc_quotes_tab' ) );
			exit;
		}

		/**
		 * Display admin notice on specific page.
		 *
		 * @param array $is_flag Is Flag default value true.
		 */
		public static function qwc_tracker_display_notice( $is_flag ) {
			global $current_section;
			if ( isset( $_GET['page'], $_GET['tab'] ) && 'wc-settings' === $_GET['page'] && 'qwc_quotes_tab' === $_GET['tab'] ) { // phpcs:ignore
				$is_flag = true;
			}
			return $is_flag;
		}

		/**
		 * Add admin notice script.
		 */
		public static function qwc_admin_notices_scripts() {
			$nonce = wp_create_nonce( 'tracking_notice' );

			wp_enqueue_script(
				'qwc_dismiss_notice',
				QUOTES_PLUGIN_URL . '/assets/js/vama-dismiss-tracking-notice.js',
				'',
				QUOTES_PLUGIN_VERSION . '_' . time(),
				false
			);

			wp_localize_script(
				'qwc_dismiss_notice',
				'qwc_dismiss_notice_params',
				array(
					'prefix_of_plugin' => 'qwc',
					'admin_url'        => admin_url( 'admin-ajax.php' ),
					'tracking_notice'  => $nonce,
				)
			);
		}

		/**
		 * Add plugin data to the tracking request.
		 *
		 * @param array $data - Data to be sent.
		 * @return array $data - Updated data.
		 */
		public static function qwc_add_plugin_tracking_data( $data ) {

			$plugin_data = array(
				'vama_meta_data_table_name' => 'vama_tracking_qwc_meta_data',
				'vama_plugin_name'          => 'Quotes for WooCommerce',
			);

			$plugin_data['plugin_version'] = QUOTES_PLUGIN_VERSION;

			// get the count of published products.
			$args = array(
				'limit'  => -1,
				'status' => 'publish',
				'return' => 'ids',
			);

			$plugin_data['count_published_products'] = count( wc_get_products( $args ) );
			// get the count of draft products.
			$args = array(
				'limit'  => -1,
				'status' => 'draft',
				'return' => 'ids',
			);

			$plugin_data['count_draft_products'] = count( wc_get_products( $args ) );

			// get count of products for which quotes are enabled.
			$args = array(
				'limit'             => -1,
				'status'            => 'publish',
				'return'            => 'ids',
				'qwc_enable_quotes' => 'on',
			);

			$plugin_data['count_product_quotes'] = count( wc_get_products( $args ) );
			// get count of products where price display is on.
			$args = array(
				'limit'              => -1,
				'status'             => 'publish',
				'return'             => 'ids',
				'qwc_display_prices' => 'on',
			);

			$plugin_data['count_product_prices'] = count( wc_get_products( $args ) );

			// get global settings.
			$plugin_data['global_settings'] = array(
				'cart_page'           => get_option( 'qwc_cart_page_name', '' ),
				'button_text'         => get_option( 'qwc_add_to_cart_button_text', '' ),
				'place_order_text'    => get_option( 'qwc_place_order_text', '' ),
				'hide_address_fields' => get_option( 'qwc_hide_address_fields', '' ),
			);
			// global quotes enabled?
			$plugin_data['global_quotes'] = get_option( 'qwc_enable_global_quote', '' );
			// global price display on?
			$plugin_data['global_prices'] = get_option( 'qwc_enable_global_prices', '' );
			// get site language.
			$plugin_data['site_lang'] = get_bloginfo( 'language' );
			// get count of orders with quote pending.
			$pending_args = array(
				'status'       => 'pending',
				'numberposts'  => -1,
				'meta_key'     => '_quote_status', // phpcs:ignore
				'meta_value'   => 'quote-pending', // phpcs:ignore
				'meta_compare' => '=',
				'return'       => 'ids',
			);

			$plugin_data['pending_quote_count'] = count( wc_get_orders( $pending_args ) );
			// get count of orders with quote complete.
			$complete_args = array(
				'status'       => 'pending',
				'numberposts'  => -1,
				'meta_key'     => '_quote_status', // phpcs:ignore
				'meta_value'   => 'quote-complete', // phpcs:ignore
				'meta_compare' => '=',
				'return'       => 'ids',
			);

			$plugin_data['complete_quote_count'] = count( wc_get_orders( $complete_args ) );
			// get count of orders with quote sent.
			$sent_args = array(
				'status'       => 'pending',
				'numberposts'  => -1,
				'meta_key'     => '_quote_status', // phpcs:ignore
				'meta_value'   => 'quote-sent', // phpcs:ignore
				'meta_compare' => '=',
				'return'       => 'ids',
			);

			$plugin_data['sent_quote_count'] = count( wc_get_orders( $sent_args ) );
			// get count of orders with quotes and payment received.
			$wc_statuses = wc_get_order_statuses();
			$wc_statuses = array_diff_key( $wc_statuses, array_flip( array( 'wc-completed', 'wc-pending', 'wc-cancelled', 'wc-refunded', 'wc-failed' ) ) );
			$wc_statuses = array_keys( $wc_statuses );

			$paid_args = array(
				'numberposts'  => -1,
				'status'       => $wc_statuses,
				'meta_key'     => '_qwc_quote', // phpcs:ignore
				'meta_value'   => '1', // phpcs:ignore
				'meta_compare' => '=',
				'return'       => 'ids',
			);
			$plugin_data['paid_quote_count'] = count( wc_get_orders( $paid_args ) );

			$data['plugin_data'] = $plugin_data;
			return $data;
		}

		/**
		 * Add custom conditions to wc_get_product for plugin meta data.
		 *
		 * @param array $query - Query parameters.
		 * @param array $query_vars - Input parameters.
		 */
		public static function qwc_handle_custom_query_var( $query, $query_vars ) {
			if ( ! empty( $query_vars['qwc_enable_quotes'] ) ) {
				$query['meta_query'][] = array(
					'key'   => 'qwc_enable_quotes',
					'value' => esc_attr( $query_vars['qwc_enable_quotes'] ),
				);
			} elseif ( ! empty( $query_vars['qwc_display_prices'] ) ) {
				$query['meta_query'][] = array(
					'key'   => 'qwc_display_prices',
					'value' => esc_attr( $query_vars['qwc_display_prices'] ),
				);
			}
			return $query;
		}
	}
}
$qwc_data_tracking = new QWC_Data_Tracking();

<?php
/**
 * Main Plugin Class file.
 *
 * @package Quotes For WooCommerce
 */

load_plugin_textdomain( 'quote-wc', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( ! class_exists( 'Quotes_WC' ) ) {

	/**
	 * Main Class File.
	 */
	class Quotes_WC {

		/**
		 * Settings tabs.
		 *
		 * @var   array
		 * @since 2.5.0
		 */
		public $settings = array();

		/**
		 * Plugin version.
		 *
		 * @var   string
		 * @since 1.0.0
		 */
		public $version = '2.6';

		/**
		 * Class instance.
		 *
		 * @var $ins
		 */
		public static $ins = null;

		/**
		 * Construct.
		 */
		public function __construct() {

			$this->qwc_define_constants();

			// Update DB as needed.
			if ( get_option( 'quotes_for_wc' ) !== QUOTES_PLUGIN_VERSION ) {
				add_action( 'admin_init', array( &$this, 'qwc_update_db_check' ) );
			}
			$this->includes();
			add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );
			// Hide the prices.
			add_filter( 'woocommerce_variable_sale_price_html', array( $this, 'qwc_remove_prices' ), 10, 2 );
			add_filter( 'woocommerce_variable_price_html', array( &$this, 'qwc_remove_prices' ), 10, 2 );
			add_filter( 'woocommerce_get_price_html', array( &$this, 'qwc_remove_prices' ), 10, 2 );
			// Composite products - individual price display in dropdown.
			add_filter( 'woocommerce_composited_product_price_string', array( &$this, 'qwc_remove_prices' ), 10, 2 );

			// Modify the 'add to cart' button text.
			add_filter( 'woocommerce_product_add_to_cart_text', array( &$this, 'qwc_change_button_text' ), 99, 1 );
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( &$this, 'qwc_change_button_text' ), 99, 1 );

			// Hide price on the cart & checkout pages.
			add_filter( 'wp_enqueue_scripts', array( &$this, 'qwc_css' ) );
			// Hide Price on the Thank You page.
			add_action( 'woocommerce_thankyou', array( &$this, 'qwc_thankyou_css' ), 10, 1 );

			// Hide Price on the My Account->View Orders page.
			add_action( 'woocommerce_view_order', array( &$this, 'qwc_thankyou_css' ), 10, 1 );
			// Hide prices on the cart widget.
			add_filter( 'woocommerce_cart_item_price', array( &$this, 'qwc_cart_widget_prices' ), 10, 2 );

			// Stop WC from displaying the price in the cart widget.
			add_action( 'woocommerce_widget_shopping_cart_total', array( &$this, 'qwc_remove_wc_function' ), 1 );
			// Hide the subtotal on the cart widget.
			add_action( 'woocommerce_widget_shopping_cart_total', array( &$this, 'qwc_widget_subtotal' ), 999 );

			// Cart Validations.
			add_filter( 'woocommerce_add_to_cart_validation', array( &$this, 'qwc_cart_validations' ), 10, 3 );
			// Check if Cart contains any quotable product.
			add_filter( 'woocommerce_cart_needs_payment', array( &$this, 'qwc_cart_needs_payment' ), 10, 2 );

			// Prevent pending orders being cancelled.
			add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'qwc_prevent_cancel' ), 10, 2 );

			// Add payment gateway to override the usual ones.
			add_action( 'init', array( &$this, 'qwc_include_files' ), 1 );
			add_action( 'admin_init', array( &$this, 'qwc_include_files_admin' ), 1 );
			add_action( 'woocommerce_payment_gateways', array( &$this, 'qwc_add_gateway' ), 10, 1 );

			// Checkout Payment Gateway load.
			add_filter( 'woocommerce_available_payment_gateways', array( &$this, 'qwc_remove_payment_methods' ), 10, 1 );

			// Add order meta.
			add_action( 'woocommerce_checkout_update_order_meta', array( &$this, 'qwc_order_meta' ), 10, 2 );
			// Control the my orders actions.
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'qwc_my_orders_actions' ), 10, 2 );

			// Once admin sets the price, send a notification, add a button for the same.
			add_action( 'woocommerce_order_item_add_action_buttons', array( &$this, 'qwc_add_buttons' ), 10, 1 );

			// Load JS files.
			add_action( 'admin_enqueue_scripts', array( &$this, 'qwc_load_js' ) );
			// Frontend JS files.
			add_action( 'wp_enqueue_scripts', array( &$this, 'qwc_load_js_frontend' ) );
			// Admin ajax.
			add_action( 'admin_init', array( &$this, 'qwc_ajax_admin' ) );

			// Added to Cart messages.
			add_filter( 'wc_add_to_cart_message_html', array( &$this, 'add_to_cart_message' ), 10, 2 );

			// Page titles.
			add_filter( 'the_title', array( &$this, 'woocommerce_title' ), 99, 2 );

			// Disable shipping for quotes.
			add_filter( 'woocommerce_cart_needs_shipping', array( &$this, 'cart_needs_shipping' ) );

			// Disable address fields on checkout for quotes.
			add_filter( 'woocommerce_billing_fields', array( &$this, 'billing_fields' ), 999 );
			add_filter( 'woocommerce_checkout_fields', array( &$this, 'checkout_fields' ), 9999 );

			// Add Settings link in Plugins page.
			$plugin = dirname( plugin_basename( __FILE__ ) ) . '/quotes-woocommerce.php';
			add_action( 'plugin_action_links_' . $plugin, array( &$this, 'qwc_plugin_settings_link' ), 10, 1 );

			// Checkout Blocks Payment Gateway Integration.
			add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'qwc_quotes_gateway_woocommerce_block_support' ) );

			// Proceed to Checkout button text edit.
			add_action( 'woocommerce_proceed_to_checkout', array( &$this, 'qwc_change_proceed_checkout_btn_text' ), 10 );

			// Add dismissible notice informing users about the change in menu placement.
			add_action( 'admin_notices', array( &$this, 'qwc_menu_change_notice' ), 1 );

			add_action( 'init', array( &$this, 'qwc_include_files_tracking' ) );
		}

		/**
		 * Define plugin constants.
		 *
		 * @since 2.3
		 */
		public function qwc_define_constants() {
			if ( ! defined( 'QUOTES_TEMPLATE_PATH' ) ) {
				define( 'QUOTES_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
			}

			if ( ! defined( 'QUOTES_PLUGIN_DIR' ) ) {
				define( 'QUOTES_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
			}

			if ( ! defined( 'QUOTES_PLUGIN_URL' ) ) {
				define( 'QUOTES_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
			}

			if ( ! defined( 'QUOTES_PLUGIN_VERSION' ) ) {
				define( 'QUOTES_PLUGIN_VERSION', $this->version );
			}

			if ( ! defined( 'QUOTES_PLUGIN_PATH' ) ) {
				define( 'QUOTES_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
			}
		}

		/**
		 * Get instance of the class.
		 *
		 * @since 2.0
		 */
		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self; // phpcs:ignore
			}

			return self::$ins;
		}
		/**
		 * Runs when the plugin is activated.
		 *
		 * @since 1.1
		 */
		public static function qwc_activate() {
			if ( ! get_option( 'qwc_menu_notice' ) ) {
				update_option( 'qwc_menu_notice', 'dismissed' );
			}
			update_option( 'quotes_for_wc', QUOTES_PLUGIN_VERSION );
		}

		/**
		 * Runs when plugin is deactivated.
		 *
		 * @since 2.3
		 */
		public static function qwc_deactivate() {
			if ( false !== as_next_scheduled_action( 'qwc_tracker_send_event' ) ) {
				as_unschedule_action( 'qwc_tracker_send_event' ); // Remove the scheduled action.
			}
			do_action( 'qwc_deactivate' );
		}

		/**
		 * Used for DB or any other changes when an
		 * update is released.
		 *
		 * @since 1.1
		 */
		public function qwc_update_db_check() {
			update_option( 'quotes_for_wc', QUOTES_PLUGIN_VERSION );
		}

		/**
		 * Include files in the admin side.
		 *
		 * @since 1.0
		 */
		public function qwc_include_files_admin() {
			include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/class-quotes-payment-gateway.php';
			include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/class-qwc-email-manager.php';
			include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/admin/class-quotes-product-settings.php';
		}

		/**
		 * Include files in the front end.
		 *
		 * @since 1.0
		 */
		public function qwc_include_files() {
			include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/class-quotes-payment-gateway.php';
			include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/class-qwc-email-manager.php';
		}

		/**
		 * Include the WC Settings files.
		 */
		public function includes() {
			include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/admin/class-quotes-wc-settings-section.php';
			$this->settings     = array();
			$this->settings[''] = include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/admin/class-quotes-wc-general-settings.php';
			return apply_filters( 'qwc_add_section_files', $this->settings );
		}

		/**
		 * Include tracking files.
		 *
		 * @since 2.3.0
		 */
		public function qwc_include_files_tracking() {
			require_once QUOTES_PLUGIN_PATH . '/includes/tracking/class-vama-plugin-tracking.php';
			new Vama_Plugin_Tracking(
				array(
					'plugin_name'       => 'Quotes for WooCommerce',
					'plugin_locale'     => 'quote-wc',
					'plugin_short_name' => 'qwc',
					'version'           => QUOTES_PLUGIN_VERSION,
					'blog_link'         => 'https://www.technovama.com/quotes-for-woocommerce-usage-tracking/',
				)
			);
			require_once QUOTES_PLUGIN_PATH . '/includes/class-qwc-data-tracking.php';
		}
		/**
		 * Add Settings tab in WC > Settings.
		 *
		 * @param array $settings - Settings tabs list.
		 */
		public function add_woocommerce_settings_tab( $settings ) {
			$settings[] = include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/admin/class-quotes-wc-settings.php';
			return $settings;
		}

		/**
		 * Remove prices being displayed on the Product/Shop pages.
		 *
		 * @param float  $price - Product price.
		 * @param object $product - WC Product Object.
		 * @since 1.0
		 */
		public function qwc_remove_prices( $price, $product ) {

			global $post;

			$enable_quote = product_quote_enabled( $post->ID );

			if ( $enable_quote ) {
				// Check if price should be displayed or no.
				$display = get_post_meta( $post->ID, 'qwc_display_prices', true );
				if ( ( isset( $display ) && 'on' !== $display ) || ! isset( $display ) ) {
					$price = '';
				}
			}
			return $price;
		}

		/**
		 * Modify the Add to Cart button text based on settings.
		 *
		 * @param string $cart_text - Add to Cart button text.
		 * @since 1.0
		 */
		public function qwc_change_button_text( $cart_text ) {

			global $post;
			$post_id = $post->ID;
			// check if setting is enabled.
			$enable_quote = product_quote_enabled( $post_id );

			if ( $enable_quote ) {
				$cart_text = '' === get_option( 'qwc_add_to_cart_button_text', '' ) ? esc_html__( 'Request Quote', 'quote-wc' ) : __( get_option( 'qwc_add_to_cart_button_text' ), 'quote-wc' ); // phpcs:ignore
			}

			return $cart_text;
		}

		/**
		 * Add CSS file to hide the prices on Cart , Checkout
		 * & My Account pages.
		 *
		 * @since 1.0
		 */
		public function qwc_css() {

			if ( is_cart() || is_checkout() ) {
				// Add css file only if cart contains products that require quotes.
				if ( is_checkout_pay_page() || is_order_received_page() ) { // The file should not be enqueued if the order pay or order received page is loaded and quotation has been sent.
					// Find the order ID and fetch the quote status.
					$order_id = absint( get_query_var( 'order-pay' ) );
					if ( is_numeric( $order_id ) && $order_id > 0 ) {
						$_order = wc_get_order( $order_id );
						if ( $_order ) {
							$quote_status = $_order->get_meta( '_quote_status' );
							if ( 'quote-pending' === $quote_status ) {
								wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', QUOTES_PLUGIN_VERSION, false );
							}
						}
					}
				} elseif ( ( cart_contains_quotable() && ! qwc_cart_display_price() ) || ( 'on' === get_option( 'qwc_enable_global_quote', '' ) && 'on' !== get_option( 'qwc_enable_global_prices', '' ) ) ) {
					// enqueue only if Pro is not active - needed as Pro has settings which makes it possible to override the global settings.
					if ( ! class_exists( 'Quotes_WC_Pro' ) ) {
						wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', QUOTES_PLUGIN_VERSION, false );
					}
				}
			}

			// Add css file only if cart contains products that require quotes.
			if ( ! is_checkout_pay_page() && ! is_order_received_page() && ( ( cart_contains_quotable() && ! qwc_cart_display_price() ) || ( 'on' === get_option( 'qwc_enable_global_quote', '' ) && 'on' !== get_option( 'qwc_enable_global_prices', '' ) ) ) ) {
				if ( ! class_exists( 'Quotes_WC_Pro' ) ) { // enqueue only if Pro is not active - needed as Pro has settings which makes it possible to override the global settings.
					wp_enqueue_style( 'qwc-mini-cart', plugins_url( '/assets/css/qwc-shop.css', __FILE__ ), '', QUOTES_PLUGIN_VERSION, false );
				}
			}

			// My Account page - Orders List.
			if ( is_wc_endpoint_url( 'orders' ) ) {
				global $wpdb;

				$display = true;

				// Check if any products allow for quotes.
				$results_quotes = $wpdb->get_results( $wpdb->prepare( 'SELECT meta_value FROM `' . $wpdb->prefix . 'postmeta` WHERE meta_key = %s', 'qwc_enable_quotes' ) ); //phpcs:ignore

				if ( isset( $results_quotes ) && count( $results_quotes ) > 0 ) {
					$found = current(
						array_filter(
							$results_quotes,
							function ( $value ) {
								return isset( $value->meta_value ) && 'on' === $value->meta_value;
							}
						)
					);

					if ( isset( $found->meta_value ) && 'on' === $found->meta_value ) {
						// if quote products are present, check if price display is set to on for any of them.
						$results_price = $wpdb->get_results($wpdb->prepare( 'SELECT meta_value FROM `' . $wpdb->prefix . 'postmeta` WHERE meta_key = %s', 'qwc_display_prices' ) ); // phpcs:ignore

						if ( isset( $results_price ) && count( $results_price ) > 0 ) {

							$found_price = current(
								array_filter(
									$results_price,
									function ( $value ) {
										return isset( $value->meta_value ) && 'on' === $value->meta_value;
									}
								)
							);

							$display = ( isset( $found_price->meta_value ) && 'on' === $found_price->meta_value ) ? true : false;

						} else {
							$display = false;
						}
					}
				}

				// Hide the prices.
				if ( ! $display ) {
					wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', QUOTES_PLUGIN_VERSION, false );
				}
			}
		}

		/**
		 * Hide prices on the Thank You page.
		 *
		 * @param int $order_id - Order ID.
		 * @since 1.0
		 */
		public function qwc_thankyou_css( $order_id ) {
			$order        = wc_get_order( $order_id );
			$quote_status = $order->get_meta( '_quote_status' );

			if ( 'quote-pending' === $quote_status && ! qwc_order_display_price( $order ) ) {
				wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', QUOTES_PLUGIN_VERSION, false );
			}
		}

		/**
		 * Load JS files.
		 *
		 * @since 1.0
		 */
		public function qwc_load_js() {

			// File to send Quote Email.
			$include  = false;
			$order_id = 0;
			if ( qwc_is_hpos_enabled() ) {
				if ( isset( $_GET['page'], $_GET['id'] ) && 'wc-orders' === $_GET['page'] && $_GET['id'] > 0 ) { //phpcs:ignore WordPress.Security.NonceVerification
					$include  = true;
					$order_id = sanitize_text_field( wp_unslash( $_GET['id'] ) ); //phpcs:ignore WordPress.Security.NonceVerification
				}
			} else {
				global $post;
				if ( isset( $post->post_type ) && 'shop_order' === $post->post_type ) {
					$include  = true;
					$order_id = $post->ID;
				}
			}

			if ( $include && $order_id > 0 ) {
				wp_register_script( 'qwc-admin', plugins_url( '/assets/js/qwc-admin.js', __FILE__ ), '', QUOTES_PLUGIN_VERSION, false );

				$ajax_url = get_admin_url() . 'admin-ajax.php';

				wp_localize_script(
					'qwc-admin',
					'qwc_params',
					array(
						'ajax_url'         => $ajax_url,
						'order_id'         => $order_id,
						'email_msg'        => __( 'Quote emailed', 'quote-wc' ),
						'qwc_status_nonce' => wp_create_nonce( 'qwc-update-status-security' ),
						'qwc_send_nonce'   => wp_create_nonce( 'qwc-send-quote-security' ),
					)
				);
				wp_enqueue_script( 'qwc-admin' );
			}
			// File to dismiss admin notice.
			$notice_dismissed = get_option( 'qwc_menu_notice', '' );
			if ( 'dismissed' !== $notice_dismissed ) {
				wp_register_script( 'qwc-notice', plugins_url( '/assets/js/qwc-notice.js', __FILE__ ), '', QUOTES_PLUGIN_VERSION, array( 'in_footer' => true ) );
				wp_localize_script(
					'qwc-notice',
					'qwc_notice_params',
					array(
						'nonce' => wp_create_nonce( 'qwc-dismiss' ),
					)
				);
				wp_enqueue_script( 'qwc-notice' );
			}
		}

		/**
		 * Front end JS files.
		 *
		 * @since 2.4
		 */
		public function qwc_load_js_frontend() {

			if ( is_product() ) {
				global $post;

				$product_id = isset( $post->ID ) ? $post->ID : 0;

				if ( $product_id > 0 ) {
					$enable_quote = product_quote_enabled( $product_id );

					if ( $enable_quote ) {

						wp_register_script( 'qwc-product-js', plugins_url( '/assets/js/qwc-product-page.js', __FILE__ ), '', QUOTES_PLUGIN_VERSION, array( 'in_footer' => true ) );

						wp_localize_script(
							'qwc-product-js',
							'qwc_product_params',
							array(
								'product_id' => $product_id,
								'quotes'     => $enable_quote,
							)
						);
						wp_enqueue_script( 'qwc-product-js' );

					}
				}
			}

			if ( is_cart() ) {
				$proceed_checkout_label = '' === get_option( 'qwc_proceed_checkout_btn_label', '' ) ? __( 'Proceed to Checkout', 'quote-wc' ) : get_option( 'qwc_proceed_checkout_btn_label' );

				wp_register_script( 'qwc-filter-js', plugins_url( '/build/filter.js', __FILE__ ), array( 'wp-blocks', 'wc-blocks-checkout' ), QUOTES_PLUGIN_VERSION, array( 'in_footer' => true ) );

				wp_localize_script(
					'qwc-filter-js',
					'filter_params',
					array(
						'cartContainsQuotable' => cart_contains_quotable(),
						'qwcButtonText'        => $proceed_checkout_label,
					)
				);
				wp_enqueue_script( 'qwc-filter-js' );
			}
		}

		/**
		 * Ajax calls
		 *
		 * @since 1.0
		 */
		public function qwc_ajax_admin() {
			add_action( 'wp_ajax_qwc_update_status', array( &$this, 'qwc_update_status' ) );
			add_action( 'wp_ajax_qwc_send_quote', array( &$this, 'qwc_send_quote' ) );
			add_action( 'wp_ajax_qwc_menu_notice_dismissed', array( &$this, 'qwc_menu_notice_dismissed' ) );
		}

		/**
		 * Hide product prices in the Cart widget.
		 *
		 * @param float $price - Product Price.
		 * @param array $cart_item - Cart Item Data.
		 * @return float $price - Product Price.
		 * @since 1.0
		 */
		public function qwc_cart_widget_prices( $price, $cart_item ) {

			$product_id = apply_filters( 'qwc_cart_check_item_product_id', $cart_item['product_id'], $cart_item );
			$quantity   = isset( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1;

			$quotes = product_quote_enabled( $product_id, $quantity );

			if ( $quotes && ! qwc_cart_display_price() ) {
				$price = '';
			}
			return $price;
		}

		/**
		 * Remove WC cart widget total display hook, if cart contains quotable products.
		 *
		 * @since 1.7.0
		 */
		public static function qwc_remove_wc_function() {
			if ( isset( WC()->cart ) ) {

				$cart_quotes = cart_contains_quotable();
				if ( $cart_quotes && ! qwc_cart_display_price() ) {
					remove_action( 'woocommerce_widget_shopping_cart_total', 'woocommerce_widget_shopping_cart_subtotal', 10 );
				}
			}
		}

		/**
		 * Hide Cart Widget subtotal.
		 *
		 * @since 1.0
		 */
		public function qwc_widget_subtotal() {

			if ( isset( WC()->cart ) ) {

				$cart_quotes = cart_contains_quotable();

				if ( $cart_quotes && ! qwc_cart_display_price() ) {
					$price = '';
					// translators: Leave price blanks as its not be displayed.
					echo wp_kses_post( sprintf( __( "<strong>Subtotal:</strong> <span class='amount'>%s</span>", 'quote-wc' ), esc_attr( $price ) ) );
				}
			}
		}

		/**
		 * Run validations to ensure products that require quotes
		 * are not present in the cart with ones that do not.
		 * This is necessary as the Payment Gateways are different.
		 *
		 * @param bool $passed - Validations passed or no.
		 * @param int  $product_id - Product ID being validated.
		 * @param int  $qty - Wuantity being added to the cart.
		 * @since 1.0
		 */
		public function qwc_cart_validations( $passed, $product_id, $qty ) {

			// Check if the product being added is quotable.
			$product_quotable = product_quote_enabled( $product_id, $qty );

			// Check if the cart contains a product that is quotable.
			$cart_contains_quotable = cart_contains_quotable();

			$conflict = 'NO';

			if ( isset( WC()->cart ) && count( WC()->cart->cart_contents ) > 0 ) {
				// If product requires confirmation and cart contains product that does not.
				if ( $product_quotable && ! $cart_contains_quotable ) {
					$conflict = 'YES';
				}
				// If product does not need confirmation and cart contains a product that does.
				if ( ! $product_quotable && $cart_contains_quotable ) {
					$conflict = 'YES';
				}
				// If conflict.
				if ( 'YES' === $conflict ) {
					// Remove existing products.
					WC()->cart->empty_cart();
					$message = apply_filters( 'qwc_cart_conflict_msg', __( 'It is not possible to add products that require quotes to the Cart along with ones that do not. Hence, the existing products have been removed from the Cart.', 'quote-wc' ) );
					wc_add_notice( $message, $notice_type = 'notice' );
				}
			}

			return $passed;
		}

		/**
		 * Sets whether payment is needed for the Cart or no.
		 *
		 * @param bool   $needs_payment - Whether order needs payment.
		 * @param object $cart - WC_Cart.
		 * @since 1.0
		 */
		public function qwc_cart_needs_payment( $needs_payment, $cart ) {

			if ( ! $needs_payment ) {
				foreach ( $cart->cart_contents as $cart_item ) {
					$quantity = isset( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1;

					$requires_quotes = product_quote_enabled( $cart_item['product_id'], $quantity );

					if ( $requires_quotes ) {
						$needs_payment = true;
						break;
					}
				}
			}

			return $needs_payment;
		}

		/**
		 * Make sure the Pending payment orders awaiting quotes
		 * and/or payments are not cancelled by WooCommerce
		 * even though the Inventory Hold Stock Limit is reached.
		 *
		 * @param bool   $qwc_return - Whether the order should be cancelled since its unpaid.
		 * @param object $order - WC_Order.
		 * @since 1.0
		 */
		public function qwc_prevent_cancel( $qwc_return, $order ) {
			if ( '1' === $order->get_meta( '_qwc_quote' ) ) {
				return false;
			}

			return $qwc_return;
		}

		/**
		 * Add the payment gateway to WooCommerce->Settings->Checkout.
		 *
		 * @param array $gateways - List of Gateways.
		 * @return array $gateways - List of Gateways with the plugin gateway included.
		 * @since 1.0
		 */
		public function qwc_add_gateway( $gateways ) {

			$gateways[] = 'Quotes_Payment_Gateway';
			return $gateways;
		}

		/**
		 * Add Payment Gateway on Checkout.
		 *
		 * @param array $available_gateways - List of available Payment Gateways.
		 * @return array $available_gateways - List of available gateways with changes as needed.
		 * @since 1.0
		 */
		public function qwc_remove_payment_methods( $available_gateways ) {

			if ( cart_contains_quotable() ) {

				// Remove all existing gateways & add the Quotes Payment Gateway.
				unset( $available_gateways );

				$available_gateways                   = array();
				$available_gateways['quotes-gateway'] = new Quotes_Payment_Gateway();
			} else {
				unset( $available_gateways['quotes-gateway'] ); // remove the Quotes Payment Gateway.
			}

			return $available_gateways;
		}

		/**
		 * Add order meta for quote status.
		 *
		 * @param int $order_id - Order ID.
		 * @since 1.0
		 */
		public function qwc_order_meta( $order_id ) {

			// Check the payment gateway.
			if ( isset( WC()->session ) && WC()->session !== null && 'quotes-gateway' === WC()->session->get( 'chosen_payment_method' ) ) {
				$quote_status = 'quote-pending';
			} else {
				$quote_status = 'quote-complete';
			}
			$order = wc_get_order( $order_id );
			$order->update_meta_data( '_quote_status', $quote_status );
			$order->save();
		}

		/**
		 * Unset the Pay option in My Accounts if Quotes are pending.
		 *
		 * @param array  $actions - List of actions available for the Order.
		 * @param object $order - WC_Order.
		 * @since 1.0
		 */
		public function qwc_my_orders_actions( $actions, $order ) {
			global $wpdb;

			$order_payment_method = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->payment_method : $order->get_payment_method();
			$order_id             = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->id : $order->get_id();

			if ( $order->has_status( 'pending' ) && 'quotes-gateway' === $order_payment_method ) {

				// Get the order meta to check if quote has been sent or no.
				$quote_status = $order->get_meta( '_quote_status' );

				// Check the order actions.
				if ( 'quote-pending' === $quote_status && isset( $actions['pay'] ) ) {
					unset( $actions['pay'] );
				} elseif ( 'quote-cancelled' === $quote_status && isset( $actions['pay'] ) ) {
					unset( $actions['pay'] );
				}
			}

			return $actions;
		}

		/**
		 * Add buttons in Edit Order page to allow the admin
		 * to setup quotes and send them to the users.
		 *
		 * @param object $order - WC_Order.
		 * @since 1.0
		 */
		public function qwc_add_buttons( $order ) {

			$order_id     = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->id : $order->get_id();
			$order_status = ( version_compare( WOOCOMMERCE_VERSION, '3.0.0' ) < 0 ) ? $order->status : $order->get_status();

			$quote_status = $order->get_meta( '_quote_status' );

			if ( in_array( $order_status, apply_filters( 'qwc_edit_allowed_order_statuses_for_sending_quotes', array( 'pending' ) ), true ) ) {
				if ( 'quote-pending' === $quote_status ) {
					?>
					<button id='qwc_quote_complete' type="button" class="button"><?php esc_html_e( 'Quote Complete', 'quote-wc' ); ?></button>
					<?php
				} else {
					if ( 'quote-complete' === $quote_status ) {
						$button_text = esc_html__( 'Send Quote', 'quote-wc' );
					} elseif ( 'quote-sent' === $quote_status ) {
						$button_text = esc_html__( 'Resend Quote', 'quote-wc' );
					}
					?>
					<button id='qwc_send_quote' type="button" class="button"><?php echo esc_html( $button_text ); ?></button>
					<text style='margin-left:0px; font-weight: bold; font-size: 20px;' type='hidden' id='qwc_msg'></text>
					<?php
				}
			}
		}

		/**
		 * Modify Quote status once admin has finished setting it.
		 *
		 * @since 1.0
		 */
		public function qwc_update_status() {
			if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['security_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security_nonce'] ) ), 'qwc-update-status-security' ) ) {
				wp_send_json_error( 'Invalid security token sent.' );
				wp_die();
			}
			$order_id     = ( isset( $_POST['order_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : 0;
			$quote_status = ( isset( $_POST['status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

			if ( $order_id > 0 && '' !== $quote_status ) {
				self::quote_status_update( $order_id, $quote_status );
			}

			// Add order note that quote has been completed.
			$order = new WC_Order( $order_id );
			$order->add_order_note( __( 'Quote Complete.', 'quote-wc' ) );
			die();
		}

		/**
		 * Update quote status in DB.
		 *
		 * @param int    $order_id - Order ID.
		 * @param string $_status - Quote Status.
		 * @since 1.0
		 */
		public static function quote_status_update( $order_id, $_status ) {
			$order = wc_get_order( $order_id );
			$order->update_meta_data( '_quote_status', $_status );
			$order->save();
		}

		/**
		 * Send quote email to user.
		 *
		 * @since 1.0
		 */
		public function qwc_send_quote() {

			if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['security_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security_nonce'] ) ), 'qwc-send-quote-security' ) ) {
				wp_send_json_error( 'Invalid security token sent.' );
				wp_die();
			}
			$order_id = ( isset( $_POST['order_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : 0;

			if ( $order_id > 0 ) {

				$qwc_lite = self::get_instance();
				$status   = $qwc_lite->qwc_send_quote_email( $order_id );
				if ( $status ) {
					echo 'quote-sent';
				}
			}
			die();
		}

		/**
		 * Send quote email to user.
		 *
		 * @param int $order_id - Order ID.
		 * @return boolean true|false.
		 *
		 * @since 2.0
		 */
		public function qwc_send_quote_email( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$quote_status = $order->get_meta( '_quote_status' );
				// Allowed quote statuses.
				$_status = array(
					'quote-complete',
					'quote-sent',
				);

				// Create an instance of the WC_Emails class , so emails are sent out to customers.
				WC_Emails::instance();
				if ( in_array( $quote_status, $_status, true ) ) {
					do_action( 'qwc_send_quote_notification', $order_id );
				}

				// Update the quote status.
				$order->update_meta_data( '_quote_status', 'quote-sent' );
				// Add an order note.
				$billing_email = $order->get_billing_email();
				$note          = __( 'Quote email sent to ', 'quote-wc' ) . $billing_email;
				$order->add_order_note( $note );
				$order->save();
				return true;
			}
			return false;
		}

		/**
		 * Change "Cart" to User Selected name after adding a quote-only product to the cart.
		 *
		 * @param string $message  - Added to cart message HTML.
		 * @param array  $products - WC_Product.
		 * @return string
		 * @since 1.6
		 */
		public function add_to_cart_message( $message, $products ) {
			$cart_name = get_option( 'qwc_cart_page_name', '' );
			$cart_name = '' === $cart_name ? __( 'Cart', 'quote-wc' ) : $cart_name;

			if ( is_array( $products ) && count( $products ) > 0 ) {
				foreach ( $products as $product_id => $quantity ) {
					if ( product_quote_enabled( $product_id, $quantity ) ) {
						$message = str_replace( 'added to your cart', "added to your $cart_name", $message );
						$message = str_replace( 'View cart', "View $cart_name", $message );
						break;
					}
				}
			}

			return $message;
		}

		/**
		 * Update the Cart title if is a quote
		 *
		 * @param string $title The post tile.
		 * @param int    $id    The post ID.
		 * @return string
		 * @since 1.6
		 */
		public function woocommerce_title( $title, $id ) {
			if ( cart_contains_quotable() && wc_get_page_id( 'cart' ) === $id ) {
				$cart_name = get_option( 'qwc_cart_page_name' );
				$cart_name = '' === $cart_name ? __( 'Cart', 'quote-wc' ) : $cart_name; //phpcs:ignore

				$title = esc_attr__( $cart_name, 'quote-wc' ); //phpcs:ignore
			}
			if ( cart_contains_quotable() && wc_get_page_id( 'checkout' ) === $id ) {
				$checkout_name = get_option( 'qwc_checkout_page_name' );
				$checkout_name = '' === $checkout_name ? __( 'Checkout', 'quote-wc' ) : $checkout_name; //phpcs:ignore

				$title = esc_attr__( $checkout_name, 'quote-wc' ); //phpcs:ignore
			}
			if ( is_wc_endpoint_url( 'order-received' ) && wc_get_page_id( 'checkout' ) === $id ) {
				global $wp;

				$order_id     = absint( $wp->query_vars['order-received'] );
				$order        = new WC_Order( $order_id );
				$order_status = $order->get_status();
				if ( in_array( $order_status, array( 'completed', 'on-hold' ) ) ) { // phpcs:ignore
					$title = apply_filters( 'qwc_change_checkout_page_title', $title, 'order-received', $order_status );
				} elseif ( $order->get_status() === 'pending' ) {
					$title = apply_filters( 'qwc_change_checkout_page_title', $title, 'order-received', $order_status );
				}
			} elseif ( is_wc_endpoint_url( 'order-pay' ) && wc_get_page_id( 'checkout' ) === $id ) {
				$title = apply_filters( 'qwc_change_checkout_page_title', $title, 'order-pay', 'pending' );
			}
			return $title;
		}

		/**
		 * Modify the Proceed to Checkout button text when cart contains quote products.
		 *
		 * @since 2.5
		 */
		public function qwc_change_proceed_checkout_btn_text() {
			$modify_text = apply_filters( 'qwc_modify_proceed_checkout_button_text', true );
			if ( cart_contains_quotable() && $modify_text ) {
				remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
				$proceed_checkout_label = '' === get_option( 'qwc_proceed_checkout_btn_label', '' ) ? __( 'Proceed to Checkout', 'quote-wc' ) : get_option( 'qwc_proceed_checkout_btn_label' );
				?>
				<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
					<?php echo esc_html__( $proceed_checkout_label, 'quote-wc' ); ?>
				</a>
				<?php
			}
		}

		/**
		 * Disable shipping for quotes
		 *
		 * @param bool $needs_shipping Whether cart needs shipping or not.
		 * @return bool
		 * @since 1.6
		 */
		public function cart_needs_shipping( $needs_shipping ) {
			// Override lite verison shipping removal for quote orders by using this filter.
			$shipping_choice = apply_filters(
				'qwc_override_shipping_quotes',
				array(
					'override'       => false,
					'needs_shipping' => $needs_shipping,
				)
			);
			// Override should be returned true, if u want to override the default behaviour.
			if ( isset( $shipping_choice['override'] ) && $shipping_choice['override'] ) {
				if ( isset( $shipping_choice['needs_shipping'] ) ) {
					return $shipping_choice['needs_shipping'];
				} else {  // if user choice not found after overriding, return as is.
					return $needs_shipping;
				}
			} elseif ( cart_contains_quotable() && 'on' === get_option( 'qwc_hide_address_fields' ) ) { // default, free verison removes shipping for quote orders.
				return false;
			} else {
				return $needs_shipping;
			}
		}

		/**
		 * Remove the billing fields if the cart is a quote.
		 *
		 * @param array $fields Billing fields.
		 * @return array
		 * @since 1.6
		 */
		public function billing_fields( $fields = array() ) {
			if ( cart_contains_quotable() && 'on' === get_option( 'qwc_hide_address_fields' ) ) {
				$qwc_hide_fields_list = apply_filters(
					'qwc_hide_billing_fields',
					array(
						'billing_company'   => 'billing_company',
						'billing_address_1' => 'billing_address_1',
						'billing_address_2' => 'billing_address_2',
						'billing_state'     => 'billing_state',
						'billing_city'      => 'billing_city',
						'billing_postcode'  => 'billing_postcode',
						'billing_country'   => 'billing_country',
					)
				);

				if ( is_array( $qwc_hide_fields_list ) && count( $qwc_hide_fields_list ) > 0 ) {
					foreach ( $qwc_hide_fields_list as $field_name ) {
						unset( $fields[ $field_name ] );
					}
				}
			}

			return $fields;
		}

		/**
		 * Remove the billing fields at checkout if the cart is a quote.
		 *
		 * @param array $fields Billing fields.
		 * @return array
		 * @since 1.6
		 */
		public function checkout_fields( $fields ) {
			if ( cart_contains_quotable() && 'on' === get_option( 'qwc_hide_address_fields' ) ) {
				$qwc_hide_fields_list = apply_filters(
					'qwc_hide_billing_fields_at_checkout',
					array(
						'billing_company'   => 'billing_company',
						'billing_address_1' => 'billing_address_1',
						'billing_address_2' => 'billing_address_2',
						'billing_state'     => 'billing_state',
						'billing_city'      => 'billing_city',
						'billing_postcode'  => 'billing_postcode',
						'billing_country'   => 'billing_country',
					)
				);

				if ( is_array( $qwc_hide_fields_list ) && count( $qwc_hide_fields_list ) > 0 ) {
					foreach ( $qwc_hide_fields_list as $field_name ) {
						unset( $fields['billing'][ $field_name ] );
					}
				}
			}

			return $fields;
		}

		/**
		 * Add Settings link on Plugins page.
		 *
		 * @param array $links - List of links in array.
		 * @return array $links - List of links including the Settings link.
		 * @since 1.7
		 */
		public static function qwc_plugin_settings_link( $links ) {
			$settings_link = array(
				'settings' => '<a href="admin.php?page=wc-settings&tab=qwc_quotes_tab">' . __( 'Settings', 'quote-wc' ) . '</a>',
				'premium'  => '<b><a href="https://woocommerce.com/products/quotes-for-woocommerce-pro/" target="_blank">' . __( 'Get Quotes Pro', 'quote-wc' ) . '</a></b>',
			);
			return array_merge( $settings_link, $links );
		}

		/**
		 * Add menu placement change notice.
		 *
		 * @since 2.1.0
		 */
		public static function qwc_menu_change_notice() {
			if ( 'dismissed' === get_option( 'qwc_menu_notice', '' ) ) {
				return;
			}
			$class   = 'notice notice-info is-dismissible qwc_menu_notice';
			$heading = __( 'Quotes menu has moved!', 'quote-wc' );
			$message = __( 'The Quotes settings can now be managed from WooCommerce > Settings > Quotes.', 'quote-wc' );

			printf( '<div class="%1$s"><p><h3>%2$s</h3>%3$s</p></div>', esc_attr( $class ), esc_html( $heading ), esc_html( $message ) );
		}

		/**
		 * Update DB with notice status.
		 *
		 * @since 2.1.0
		 */
		public function qwc_menu_notice_dismissed() {

			if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'qwc-dismiss' ) ) {
				die( 'Security check' );
			}

			if ( isset( $_POST['notice'] ) && '' !== sanitize_text_field( wp_unslash( $_POST['notice'] ) ) ) {
				$notice_name = sanitize_text_field( wp_unslash( $_POST['notice'] ) );
				update_option( 'qwc_menu_notice', 'dismissed' );
			}
			die();
		}

		/**
		 * Registers WooCommerce Blocks integration.
		 *
		 * @since 2.0
		 */
		public static function qwc_quotes_gateway_woocommerce_block_support() {
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				require_once 'includes/blocks/class-quotes-payment-gateway-blocks.php';
				include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/blocks/class-quotes-wc-blocks-integration.php';
				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
						$payment_method_registry->register( new WC_Quotes_Gateway_Blocks_Support() );
					}
				);
			}
		}
	} // end of class
}
Quotes_WC::get_instance();

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
		 * Plugin version.
		 *
		 * @var   string
		 * @since 1.0.0
		 */
		public $version = '1.9';

		/**
		 * Construct.
		 */
		public function __construct() {

			define( 'QUOTES_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );

			// Initialize settings.
			register_activation_hook( __FILE__, array( &$this, 'qwc_activate' ) );
			// Update DB as needed.
			if ( get_option( 'quotes_for_wc' ) !== $this->version ) {
				add_action( 'admin_init', array( &$this, 'qwc_update_db_check' ) );
			}

			// Add setting to hide wc prices.
			add_action( 'woocommerce_product_options_inventory_product_data', array( &$this, 'qwc_setting' ) );
			// Hook in to save the quote settings.
			add_action( 'woocommerce_process_product_meta', array( &$this, 'qwc_save_setting' ), 10, 1 );

			// Hide the prices.
			add_filter( 'woocommerce_variable_sale_price_html', array( $this, 'qwc_remove_prices' ), 10, 2 );
			add_filter( 'woocommerce_variable_price_html', array( &$this, 'qwc_remove_prices' ), 10, 2 );
			add_filter( 'woocommerce_get_price_html', array( &$this, 'qwc_remove_prices' ), 10, 2 );

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

			// Admin ajax.
			add_action( 'admin_init', array( &$this, 'qwc_ajax_admin' ) );

			// Admin Menu for Quotes.
			add_action( 'admin_menu', array( &$this, 'qwc_admin_menu' ), 10 );

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
		}

		/**
		 * Runs when the plugin is activated.
		 *
		 * @since 1.1
		 */
		public function qwc_activate() {
			update_option( 'quotes_for_wc', $this->version );
		}

		/**
		 * Used for DB or any other changes when an
		 * update is released.
		 *
		 * @since 1.1
		 */
		public function qwc_update_db_check() {
			update_option( 'quotes_for_wc', $this->version );
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
			$enable_quotes = ( isset( $_POST['qwc_enable_quotes'] ) ) ? 'on' : ''; //phpcS:ignore WordPress.Security.NonceVerification
			update_post_meta( $post_id, 'qwc_enable_quotes', $enable_quotes );

			$display = ( isset( $_POST['qwc_display_prices'] ) ) ? 'on' : ''; //phpcS:ignore WordPress.Security.NonceVerification
			update_post_meta( $post_id, 'qwc_display_prices', $display );
		}

		/**
		 * Include files in the admin side.
		 *
		 * @since 1.0
		 */
		public function qwc_include_files_admin() {
			include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/class-quotes-payment-gateway.php';
			include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/class-qwc-email-manager.php';
			include_once WP_PLUGIN_DIR . '/quotes-for-woocommerce/includes/admin/class-quotes-global-settings.php';
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
			$plugin_version = get_option( 'quotes_for_wc' );

			if ( is_cart() || is_checkout() ) {
				// Add css file only if cart contains products that require quotes.
				if ( cart_contains_quotable() && ! qwc_cart_display_price() ) {
					wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', $plugin_version, false );
				}
			}

			// Add css file only if cart contains products that require quotes.
			if ( cart_contains_quotable() && ! qwc_cart_display_price() ) {
				wp_enqueue_style( 'qwc-mini-cart', plugins_url( '/assets/css/qwc-shop.css', __FILE__ ), '', $plugin_version, false );
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
							function( $value ) {
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
									function( $value ) {
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
					wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', $plugin_version, false );
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
			$quote_status = get_post_meta( $order_id, '_quote_status', true );

			$order = new WC_Order( $order_id );
			if ( 'quote-pending' === $quote_status && ! qwc_order_display_price( $order ) ) {
				$plugin_version = get_option( 'quotes_for_wc' );
				wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', $plugin_version, false );
			}
		}

		/**
		 * Load JS files.
		 *
		 * @since 1.0
		 */
		public function qwc_load_js() {

			global $post;
			if ( isset( $post->post_type ) && 'shop_order' === $post->post_type ) {
				$plugin_version = get_option( 'quotes_for_wc' );
				wp_register_script( 'qwc-admin', plugins_url( '/assets/js/qwc-admin.js', __FILE__ ), '', $plugin_version, false );

				$ajax_url = get_admin_url() . 'admin-ajax.php';

				wp_localize_script(
					'qwc-admin',
					'qwc_params',
					array(
						'ajax_url'  => $ajax_url,
						'order_id'  => $post->ID,
						'email_msg' => __( 'Quote emailed', 'quote-wc' ),
					)
				);
				wp_enqueue_script( 'qwc-admin' );
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

			$product_id = $cart_item['product_id'];

			$quotes = product_quote_enabled( $product_id );

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
			$product_quotable = product_quote_enabled( $product_id );

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
					$requires_quotes = product_quote_enabled( $cart_item['product_id'] );

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
		 * @param bool   $return - Whether the order should be cancelled since its unpaid.
		 * @param object $order - WC_Order.
		 * @since 1.0
		 */
		public function qwc_prevent_cancel( $return, $order ) {
			if ( '1' === get_post_meta( $order->get_id(), '_qwc_quote', true ) ) {
				return false;
			}

			return $return;
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
			update_post_meta( $order_id, '_quote_status', $quote_status );
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
				$quote_status = get_post_meta( $order_id, '_quote_status', true );

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

			$quote_status = get_post_meta( $order_id, '_quote_status', true );

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
			$order_id     = ( isset( $_POST['order_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			$quote_status = ( isset( $_POST['status'] ) ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

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
			update_post_meta( $order_id, '_quote_status', $_status );
		}

		/**
		 * Send quote email to user.
		 *
		 * @since 1.0
		 */
		public function qwc_send_quote() {

			$order_id = ( isset( $_POST['order_id'] ) ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

			if ( $order_id > 0 ) {

				$quote_status = get_post_meta( $order_id, '_quote_status', true );
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
				update_post_meta( $order_id, '_quote_status', 'quote-sent' );
				// Add an order note.
				$order         = wc_get_order( $order_id );
				$billing_email = $order->get_billing_email();
				$note          = __( 'Quote email sent to ', 'quote-wc' ) . $billing_email;
				$order->add_order_note( $note );
				echo 'quote-sent';
			}
			die();
		}

		/**
		 * Adds the Quotes menu to WordPress Dashboard
		 *
		 * @since 1.5
		 */
		public function qwc_admin_menu() {

			add_menu_page( 'Quotes', 'Quotes', 'manage_woocommerce', 'qwc_settings', array( &$this, 'qwc_settings' ) );
			$page = add_submenu_page( 'qwc_settings', __( 'Settings', 'quote-wc' ), __( 'Settings', 'quote-wc' ), 'manage_woocommerce', 'quote_settings', array( &$this, 'qwc_settings' ) );
			remove_submenu_page( 'qwc_settings', 'qwc_settings' );

		}

		/**
		 * Adds the content to Quotes->Settings page
		 *
		 * @since 1.5
		 */
		public function qwc_settings() {

			if ( is_user_logged_in() ) {
				global $wpdb;
				// Check the user capabilities.
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'quote-wc' ) );
				}

				?>

				<h1><?php esc_html_e( 'Quote Settings' ); ?></h1>
				<br>
				<div>
				<form method="post" action="options.php">
					<?php settings_errors(); ?>
					<?php settings_fields( 'qwc_bulk_settings' ); ?>
					<?php do_settings_sections( 'qwc_bulk_page' ); ?>
					<?php submit_button(); ?>    
				</form>
				<form method="post" action="options.php">
					<?php settings_fields( 'quote_settings' ); ?>
					<?php do_settings_sections( 'qwc_page' ); ?>
					<?php submit_button(); ?>    
				</form>

				</div>
				<?php
			}
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
				foreach ( $products as $product_id => $value ) {
					if ( product_quote_enabled( $product_id ) ) {
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
		 * Disable shipping for quotes
		 *
		 * @param bool $needs_shipping Whether cart needs shipping or not.
		 * @return bool
		 * @since 1.6
		 */
		public function cart_needs_shipping( $needs_shipping ) {
			if ( cart_contains_quotable() && 'on' === get_option( 'qwc_hide_address_fields' ) ) {
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

				foreach( $qwc_hide_fields_list as $field_name ) {
					unset( $fields[ $field_name ] );
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

				foreach( $qwc_hide_fields_list as $field_name ) {
					unset( $fields['billing'][ $field_name ] );
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
				'settings' => '<a href="admin.php?page=quote_settings">' . __( 'Settings', 'quote-wc' ) . '</a>',
			);
			return array_merge( $settings_link, $links );
		}


	} // end of class
}
$quotes_wc = new Quotes_WC();

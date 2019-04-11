<?php 
/*
Plugin Name: Quotes for WooCommerce
Description: This plugin allows you to convert your WooCommerce store into a quote only store. It will hide the prices for the products and not take any payment at Checkout. You can then setup prices for the items in the order and send a notification to the Customer. 
Version: 1.6.1
Author: Pinal Shah 
WC Requires at least: 3.0.0
WC tested up to: 3.5.7
*/

if ( ! class_exists( 'quotes_for_wc' ) ) {
    class quotes_for_wc {
        
        /**
         * Plugin version.
         *
         * @var   string
         * @since 1.0.0
         */
        public $version = '1.6.1';
    
        public function __construct() {
            
            define( 'QUOTES_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
            
            // Initialize settings
            register_activation_hook( __FILE__, array( &$this, 'qwc_activate' ) );
            // Update DB as needed
            if( $this->version != get_option( 'quotes_for_wc' ) ) {
                add_action( 'admin_init', array( &$this, 'qwc_update_db_check' ) );
            }
            
            // add setting to hide wc prices
            add_action( 'woocommerce_product_options_inventory_product_data', array( &$this, 'qwc_setting' ) );
            // hook in to save the quote settings
            add_action( 'woocommerce_process_product_meta', array( &$this, 'qwc_save_setting' ), 10, 1 );

            // hide the prices
            add_filter( 'woocommerce_variable_sale_price_html', array( $this, 'qwc_remove_prices' ), 10, 2 );
            add_filter( 'woocommerce_variable_price_html', array( &$this, 'qwc_remove_prices' ), 10, 2 );
            add_filter( 'woocommerce_get_price_html', array( &$this, 'qwc_remove_prices' ), 10, 2 );
            
            // modify the 'add to cart' button text
            add_filter( 'woocommerce_product_add_to_cart_text', array( &$this, 'qwc_change_button_text' ) );
            add_filter( 'woocommerce_product_single_add_to_cart_text', array( &$this, 'qwc_change_button_text' ), 99 );
            
            // hide price on the cart & checkout pages
            add_filter( 'wp_enqueue_scripts', array( &$this, 'qwc_css' ) );
            // Hide Price on the Thank You page
            add_action( 'woocommerce_thankyou', array( &$this, 'qwc_thankyou_css' ), 10, 1);
            
            // Hide Price on the My Account->View Orders page
            add_action( 'woocommerce_view_order', array( &$this, 'qwc_thankyou_css' ), 10, 1 );
            // hide prices on the cart widget
            add_filter( 'woocommerce_cart_item_price', array( &$this, 'qwc_cart_widget_prices' ), 10, 2 );
            // hide the subtotal on the cart widget
            add_filter( 'woocommerce_add_to_cart_fragments', array( &$this, 'qwc_widget_subtotal' ), 10, 1 );
            // Cart Validations
            add_filter( 'woocommerce_add_to_cart_validation', array( &$this, 'qwc_cart_validations' ), 10, 3 );
            // Check if Cart contains any quotable product
            add_filter( 'woocommerce_cart_needs_payment', array( &$this, 'qwc_cart_needs_payment' ), 10, 2 );
            
            // Prevent pending orders being cancelled
            add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'qwc_prevent_cancel' ), 10, 2 );
            
            // add payment gateway to override the usual ones
            add_action( 'init', array( &$this, 'qwc_include_files' ), 1 );
            add_action( 'admin_init', array( &$this, 'qwc_include_files_admin' ), 1 );
            add_action( 'woocommerce_payment_gateways', array( &$this, 'qwc_add_gateway' ), 10, 1 );

            // Checkout Payment Gateway load
            add_filter( 'woocommerce_available_payment_gateways', array( &$this, 'qwc_remove_payment_methods' ), 10, 1 );
            
            // Add order meta 
            add_action( 'woocommerce_checkout_update_order_meta',   array( &$this, 'qwc_order_meta' ), 10, 2);
            // Control the my orders actions.
            add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'qwc_my_orders_actions' ), 10, 2 );
            
            // once admin sets the price, send a notification, add a button for the same
            add_action( 'woocommerce_order_item_add_action_buttons', array( &$this, 'qwc_add_buttons' ), 10, 1 );
            
            // load JS files
            add_action( 'admin_enqueue_scripts', array( &$this, 'qwc_load_js' ) );
            
            // admin ajax 
            add_action( 'admin_init', array( &$this, 'qwc_ajax_admin' ) );

            // Admin Menu for Quotes
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

        }
        
        /**
         * Runs when the plugin is activated
         * @since 1.1
         */
        function qwc_activate() {
            update_option( 'quotes_for_wc', '1.6.1' );
        }
        
        /**
         * Used for DB or any other changes when an
         * update is released.
         * @since 1.1
         */
        function qwc_update_db_check() {
            update_option( 'quotes_for_wc', '1.6.1' );
        }
        
        /**
         * Add a setting to enable/disabe quotes
         * in the Inventory tab.
         * @since 1.0
         */
        function qwc_setting() {
            
            global $post;
            
            $post_id = ( isset( $post->ID ) && $post->ID > 0 ) ? $post->ID : 0;
            
            if ( $post_id > 0 ) {
                
                $enable_quotes = get_post_meta( $post_id, 'qwc_enable_quotes', true );
                $quotes_checked = ( $enable_quotes === 'on' ) ? 'yes' : 'no';
                
                woocommerce_wp_checkbox( 
                            array( 'id' => 'qwc_enable_quotes', 
                                   'label' => __( 'Enable Quotes', 'quote-wc' ), 
                                   'description' => __( 'Enable this to allow customers to ask for a quote for the product.', 'quote-wc' ),
                                   'value'  => $quotes_checked
                            ) 
                );

                $display = get_post_meta( $post_id, 'qwc_display_prices', true );
                $prices_enabled = ( $display === 'on' ) ? 'yes' : 'no';
                
                woocommerce_wp_checkbox(
                            array( 'id' => 'qwc_display_prices',
                                    'label' => __( 'Display Product Price', 'quote-wc' ),
                                    'description' => __( 'Enable this to display the product price on the Shop & Product pages.', 'quote-wc' ),
                                    'value'  => $prices_enabled
                            )
                );
                
            }
        }
        
        /**
         * Save the quotes setting.
         * @since 1.0
         */
        function qwc_save_setting( $post_id ) {
            $enable_quotes = ( isset( $_POST[ 'qwc_enable_quotes' ] ) ) ? 'on' : '';
            update_post_meta( $post_id, 'qwc_enable_quotes', $enable_quotes );
            
            $display = ( isset( $_POST[ 'qwc_display_prices' ] ) ) ? 'on' : '';
            update_post_meta( $post_id, 'qwc_display_prices', $display );
        }

        /**
         * Include files in the admin side.
         * @since 1.0
         */
        function qwc_include_files_admin() {
            include_once( 'includes/class-qwc-gateway.php' );
            include_once( 'includes/class-email-manager.php' );
            include_once( 'includes/admin/qwc-global-settings.php' );   
        }
        
        /**
         * Include files in the front end.
         * @since 1.0
         */
        function qwc_include_files() {
            include_once( 'includes/class-qwc-gateway.php' );
            include_once( 'includes/qwc-functions.php' );
            include_once( 'includes/class-email-manager.php' );
        }        
        
        /**
         * Remove prices being displayed on the Product/Shop pages.
         * @since 1.0
         */
        function qwc_remove_prices( $price, $product ) {
            
            global $post;
            
            $enable_quote = get_post_meta( $post->ID, 'qwc_enable_quotes', true );
            
            if ( isset( $enable_quote ) && 'on' === $enable_quote ) {
                // check if price should be displayed or no
                $display = get_post_meta( $post->ID, 'qwc_display_prices', true );
                if( ( isset( $display ) && 'on' != $display ) || ! isset( $display ) ) {
                    $price = '';
                }
            }
            return $price;
        }
        
        /**
         * Modify the Add to Cart button text based on settings.
         * @since 1.0
         */
        function qwc_change_button_text() {
            
            global $post;
            $post_id = $post->ID;
            // check if setting is enabled
            $enable_quote = product_quote_enabled( $post_id );
            
            if ( $enable_quote ) {
                $cart_text = __( 'Request Quote', 'quote-wc' );
            } else {
                $cart_text = __( 'Add to Cart', 'quote-wc' );
            }
            
            return $cart_text;
        }
        
        /**
         * Add CSS file to hide the prices on Cart , Checkout 
         * & My Account pages.
         * @since 1.0
         */
        function qwc_css() {
            $plugin_version = get_option( 'quotes_for_wc' );
            // load only on Cart, Checkout pages
            if ( is_cart() || is_checkout() ) {
                
                // add css file only if cart contains products that require quotes
                if ( cart_contains_quotable() && ! qwc_cart_display_price() ) {
                    wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', $plugin_version, false );
                }
            }
            
            // My Account page - Orders List
            if ( is_wc_endpoint_url( 'orders' ) ) {
                global $wpdb;
                
                $display = true;
                
                // check if any products allow for quotes
                $quote_query = "SELECT meta_value FROM `" . $wpdb->prefix . "postmeta`
                                WHERE meta_key = %s";

                $results_quotes = $wpdb->get_results( $wpdb->prepare( $quote_query, 'qwc_enable_quotes' ) );
                
                if ( isset( $results_quotes ) && count( $results_quotes ) > 0 ) {
                    $found = current( array_filter( $results_quotes, function( $value ) {
                        return isset( $value->meta_value ) && 'on' == $value->meta_value;
                    }));
                    
                    if ( isset( $found->meta_value ) && $found->meta_value === 'on' ) {
                        // if quote products are present, check if price display is set to on for any of them
                        $price_query = "SELECT meta_value FROM `" . $wpdb->prefix . "postmeta`
                                WHERE meta_key = %s";
                        
                        $results_price = $wpdb->get_results( $wpdb->prepare( $price_query, 'qwc_display_prices' ) );
                        
                        if ( isset( $results_price ) && count( $results_price ) > 0 ) {
                            
                            $found_price = current( array_filter( $results_price, function( $value ) {
                                return isset( $value->meta_value ) && 'on' == $value->meta_value;
                            }));
                            
                            $display = ( isset( $found->meta_value ) && $found->meta_value === 'on' ) ? true : false;
                                
                        } else {
                            $display = false;
                        }
                    }
                }
                
                // hide the prices
                if( ! $display ) {
                    wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', $plugin_version, false );
                }
            } 
        }

        /**
         * Hide prices on the Thank You page.
         * @since 1.0
         */
        function qwc_thankyou_css( $order_id ) {
            $quote_status = get_post_meta( $order_id, '_quote_status', true );
            
            $order = new WC_Order( $order_id );
            if ( 'quote-pending' === $quote_status && ! qwc_order_display_price( $order ) ) {
                $plugin_version = get_option( 'quotes_for_wc' );
                wp_enqueue_style( 'qwc-frontend', plugins_url( '/assets/css/qwc-frontend.css', __FILE__ ), '', $plugin_version, false );
            }
        }
        
        /**
         * Load JS files.
         * @since 1.0
         */
        function qwc_load_js() {

            global $post;
            if ( isset( $post->post_type ) && $post->post_type === 'shop_order' ) {
                $plugin_version = get_option( 'quotes_for_wc' );
                wp_register_script( 'qwc-admin', plugins_url( '/assets/js/qwc-admin.js', __FILE__ ), '', $plugin_version, false );
                
                $ajax_url = get_admin_url() . 'admin-ajax.php';
                
                wp_localize_script( 'qwc-admin', 'qwc_params', array(
                            'ajax_url' => $ajax_url,
                            'order_id' => $post->ID,
                            'email_msg' => __( 'Quote emailed.', 'quote-wc' ),
                            )
                );
                wp_enqueue_script( 'qwc-admin' );
            }
        }
        
        /**
         * Ajax calls
         * @since 1.0
         */
        function qwc_ajax_admin() {
            add_action( 'wp_ajax_qwc_update_status', array( &$this, 'qwc_update_status' ) );
            add_action( 'wp_ajax_qwc_send_quote', array( &$this, 'qwc_send_quote' ) );
        }
        
        /**
         * Hide product prices in the Cart widget.
         * @since 1.0
         */
        function qwc_cart_widget_prices( $price, $cart_item ) {
            
            $product_id = $cart_item[ 'product_id' ];
            
            $quotes = product_quote_enabled( $product_id );
            
            if ( $quotes && ! qwc_cart_display_price() ) {
                $price = '';
            }
            return $price;
        }
        
        /**
         * Hide Cart Widget subtotal.
         * @since 1.0
         */
        function qwc_widget_subtotal( $fragments ) {
            
            if ( isset( WC()->cart ) ) {
            
                $cart_quotes = cart_contains_quotable();
                
                if ( $cart_quotes && ! qwc_cart_display_price() ) {
                    $price = '';
                    ob_start();
                    
                    print( '<p class="total"><strong>Subtotal:</strong> <span class="amount">'.$price.'</span></p>' );
                     
                    $fragments['p.total'] = ob_get_clean();
                    
                }
                
            }
            return $fragments;
        }
        
        /**
         * Run validations to ensure products that require quotes
         * are not present in the cart with ones that do not.
         * This is necessary as the Payment Gateways are different.
         * @since 1.0
         */
        function qwc_cart_validations( $passed, $product_id, $qty ) {
            
            // check if the product being added is quotable
            $product_quotable = product_quote_enabled( $product_id );
            
            // check if the cart contains a product that is quotable
            $cart_contains_quotable = cart_contains_quotable();
            
            $conflict = 'NO';
            
            if ( count( WC()->cart->cart_contents ) > 0 ) {
                // if product requires confirmation and cart contains product that does not
                if ( $product_quotable && ! $cart_contains_quotable ) {
                    $conflict = 'YES';
                }
                // if product does not need confirmation and cart contains a product that does
                if ( ! $product_quotable && $cart_contains_quotable ) {
                    $conflict = 'YES';
                }
                // if conflict
                if ( 'YES' == $conflict ) {
                    // remove existing products
                    WC()->cart->empty_cart();
            
                    // add a notice
                    $message = 'It is not possible to add products that require quotes to the Cart along with ones that do not. Hence, the existing products have been removed from the Cart.';
                    wc_add_notice( __( $message, 'quote-wc' ), $notice_type = 'notice' );
                }
            }
            
            return $passed; 
        }

        /**
         * Sets whether payment is needed for the Cart or no.
         * @since 1.0
         */
        function qwc_cart_needs_payment( $needs_payment, $cart ) {
            
            if ( ! $needs_payment ) {
                foreach ( $cart->cart_contents as $cart_item ) {
                    $requires_quotes = product_quote_enabled( $cart_item[ 'product_id' ] );
            
                    if( $requires_quotes ) {
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
         * @since 1.0
         */
        function qwc_prevent_cancel( $return, $order ) {
            if ( '1' === get_post_meta( $order->get_id(), '_qwc_quote', true ) ) {
                return false;
            }
        
            return $return;
        }
        
        /**
         * Add the payment gateway to WooCommerce->Settings->Checkout.
         * @since 1.0
         */
        function qwc_add_gateway( $gateways ) {
            
            $gateways[] = 'Quotes_Payment_Gateway';
            return $gateways;
        }
        
        /**
         * Add Payment Gateway on Checkout.
         * @since 1.0
         */
        function qwc_remove_payment_methods( $available_gateways ) {
            
            if ( cart_contains_quotable() ) {

                // Remove all existing gateways & add the Quotes Payment Gateway
                unset( $available_gateways );
            
                $available_gateways = array(); 
                $available_gateways[ 'quotes-gateway' ] = new Quotes_Payment_Gateway();
            } else {
                unset( $available_gateways[ 'quotes-gateway' ] ); // remove the Quotes Payment Gateway
            }
            
            return $available_gateways;
        }
        
        /**
         * Add order meta for quote status.
         * @since 1.0
         */
        function qwc_order_meta( $order_id ) {
            
            // check the payment gateway
            if ( isset( WC()->session ) && WC()->session !== null && WC()->session->get( 'chosen_payment_method' ) === 'quotes-gateway' ) {
                $quote_status = 'quote-pending';
            } else {
                $quote_status = 'quote-complete';
            }
            update_post_meta( $order_id, '_quote_status', $quote_status );
        }
        
        /**
         * Unset the Pay option in My Accounts if Quotes are pending.
         * @since 1.0
         */
        function qwc_my_orders_actions( $actions, $order ) {
            global $wpdb;
        
            $order_payment_method = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->payment_method : $order->get_payment_method();
            $order_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->id : $order->get_id();
            
            if ( $order->has_status( 'pending' ) && 'quotes-gateway' === $order_payment_method ) {
                
                // get the order meta to check if quote has been sent or no 
                $quote_status = get_post_meta( $order_id, '_quote_status', true );
                
                // check the order actions
    			if ( $quote_status === 'quote-pending' && isset( $actions['pay'] ) ) {
    				unset( $actions['pay'] );
    			} else if ( $quote_status === 'quote-cancelled' && isset( $actions['pay'] ) ) {
    			    unset( $actions['pay'] );
    			}
    		}
        
    		return $actions;
    	}
    	
    	/**
    	 * Add buttons in Edit Order page to allow the admin
    	 * to setup quotes and send them to the users.
    	 * @since 1.0
    	 */
    	function qwc_add_buttons( $order ) {
    	    
    	    $order_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->id : $order->get_id();
    	    $order_status = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->status : $order->get_status();
    	    
    	    $quote_status = get_post_meta( $order_id, '_quote_status', true );
    	    
    	    if ( 'pending' === $order_status ) {
        	    if ( $quote_status === 'quote-pending' ) {
        	        ?>
        	        <button id='qwc_quote_complete' type="button" class="button"><?php _e( 'Quote Complete', 'quote-wc' ); ?></button>
        	        <?php 
        	    } else {
        	        if ( $quote_status === 'quote-complete' ) {
                        $button_text = __( 'Send Quote', 'quote-wc' );
        	        } else if ( $quote_status === 'quote-sent' ) {
        	            $button_text = __( 'Resend Quote', 'quote-wc' );
        	        }
                    ?>
                    <button id='qwc_send_quote' type="button" class="button"><?php echo $button_text; ?></button>
                    <text style='margin-left:0px;' type='hidden' id='qwc_msg'></text>
                    <?php
        	    }
    	    } 
    	}

    	/**
    	 * Modify Quote status once admin has finished setting it.
    	 * @since 1.0
    	 */
    	function qwc_update_status() {
    	    $order_id = ( isset( $_POST[ 'order_id' ] ) ) ? $_POST[ 'order_id' ] : 0;
    	    $quote_status = ( isset( $_POST[ 'status' ] ) ) ? $_POST[ 'status' ] : '';
    	    
    	    if ( $order_id > 0 && $quote_status !== '' ) {
    	        quotes_for_wc::quote_status_update( $order_id, $quote_status );
    	    }
    	    
    	    // Add order note that quote has been completed
    	    $order = new WC_Order( $order_id );
    	    $order->add_order_note( __( 'Quote Complete.', 'quote-wc' ) );
    	    die();
    	}
    	
    	/**
    	 * Update quote status in DB.
    	 * @since 1.0
    	 */
    	static function quote_status_update( $order_id, $_status ) {
    	    update_post_meta( $order_id, '_quote_status', $_status );
    	}
    	
    	/**
    	 *  Send quote email to user.
    	 *  @since 1.0
    	 */
    	function qwc_send_quote() {
    	    
    	    $order_id = ( isset( $_POST[ 'order_id' ] ) ) ? $_POST[ 'order_id' ] : 0;
    	    
    	    if ( $order_id > 0 ) {
    	        
    	        $quote_status = get_post_meta( $order_id, '_quote_status', true );
    	        // allowed quote statuses
    	        $_status = array(
    	            'quote-complete',
    	            'quote-sent',
    	        );
    	        
    	        // create an instance of the WC_Emails class , so emails are sent out to customers
    	        new WC_Emails();
    	        if ( in_array( $quote_status, $_status ) ) {
    	            do_action( 'qwc_send_quote_notification', $order_id );
    	        }
    	        
    	        // update the quote status
    	        update_post_meta( $order_id, '_quote_status', 'quote-sent' );
    	        echo 'quote-sent'; 
    	    }
    	    die();
    	}

    	/**
    	 * Adds the Quotes menu to WordPress Dashboard
    	 * @since 1.5
    	 */
    	function qwc_admin_menu() {
    	
    	    add_menu_page( 'Quotes', 'Quotes', 'manage_woocommerce', 'qwc_settings', array(&$this, 'qwc_settings' ) );
    	    $page = add_submenu_page( 'qwc_settings', __( 'Settings', 'quote-wc' ), __( 'Settings', 'quote-wc' ), 'manage_woocommerce', 'quote_settings',  array( &$this, 'qwc_settings' ) );
    	    remove_submenu_page( 'qwc_settings', 'qwc_settings' );
    	
    	}
    	 
    	/**
    	 * Adds the content to Quotes->Settings page
    	 * @since 1.5
    	 */
    	function qwc_settings() {
    	
    	    if ( is_user_logged_in() ) {
    	        global $wpdb;
    	        // Check the user capabilities
    	        if ( ! current_user_can( 'manage_woocommerce' ) ) {
    	            wp_die( __( 'You do not have sufficient permissions to access this page.', 'quote-wc' ) );
    	        }
    	         
    	        // $action = isset( $_GET['action'] ) ? $_GET['action'] : '';
    	        ?>
    	                
                <h1><?php _e( 'Quote Settings' );?></h1>
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
         * @param string $message    Added to cart message HTML.
         * @param int    $product_id Current product ID.
         * @return string
         * @since 1.6
         */
        public function add_to_cart_message( $message, $products ) {
            $cart_name = get_option( 'qwc_cart_page_name' );
            $cart_name = $cart_name == '' ? 'Cart' : $cart_name;

            if( is_array( $products ) && count( $products ) > 0 ) {
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
        public function woocommerce_title( $title,  $id ) {
            if ( cart_contains_quotable() && $id === wc_get_page_id( 'cart' ) ) {
                $cart_name = get_option( 'qwc_cart_page_name' );
                $cart_name = '' == $cart_name ? 'Cart' : $cart_name;
            
                $title = __( $cart_name, 'quote-wc' );
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
            if ( cart_contains_quotable() && 'on' == get_option( 'qwc_hide_address_fields' ) ) {
                return false;
            } else {
                return true;
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
            if ( cart_contains_quotable() && 'on' == get_option( 'qwc_hide_address_fields' ) ) {
                unset( $fields['billing_company'] );
                unset( $fields['billing_address_1'] );
                unset( $fields['billing_address_2'] );
                unset( $fields['billing_state'] );
                unset( $fields['billing_city'] );
                unset( $fields['billing_postcode'] );
                unset( $fields['billing_country'] );
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
            if ( cart_contains_quotable() && 'on' == get_option( 'qwc_hide_address_fields' ) ) {
                unset( $fields['billing']['billing_company'] );
                unset( $fields['billing']['billing_country'] );
                unset( $fields['billing']['billing_address_1'] );
                unset( $fields['billing']['billing_address_2'] );
                unset( $fields['billing']['billing_city'] );
                unset( $fields['billing']['billing_state'] );
                unset( $fields['billing']['billing_postcode'] );
            }

            return $fields;
        }

    } // end of class
} 
$quotes_for_wc = new quotes_for_wc();
?>
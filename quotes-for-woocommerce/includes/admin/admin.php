<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * QWC_Admin.
 */
class QWC_Admin {

	public function __construct() {
	    
	    // add setting to hide wc prices
	    add_action( 'woocommerce_product_options_inventory_product_data', array( &$this, 'qwc_setting' ) );
	    // hook in to save the quote settings
	    add_action( 'woocommerce_process_product_meta', array( &$this, 'qwc_save_setting' ), 10, 1 );

	    // load JS files
	    add_action( 'admin_enqueue_scripts', array( &$this, 'qwc_load_js' ) );
	     
		add_action( 'admin_enqueue_scripts', array( $this, 'qwc_css_edit_cpt' ) );
		
		add_action( 'wp_insert_post_data', array( &$this, 'qwc_save_post' ), 10, 2 );
	}

	/**
	 * Load JS files.
	 * @since 1.0
	 */
	function qwc_load_js() {
	
	    global $post;
	    if ( isset( $post->post_type ) && ( $post->post_type === 'shop_order' || $post->post_type = 'quote_wc' ) ) {
	        $plugin_version = get_option( 'quotes_for_wc' );
	        wp_register_script( 'qwc-admin', plugins_url() . '/quotes-for-woocommerce/assets/js/qwc-admin.js', '', $plugin_version, false );
	
	        $ajax_url = get_admin_url() . 'admin-ajax.php';
	
	        if ( $post->post_type === 'quote_wc' ) {
	            $quote_id = $post->ID;
	            $order_id = $post->post_parent;
	             
	        } else if( $post->post_type === 'shop_order' ) {
	            $order_id = $post->ID;
	            $quote_id = 0;
	        }
	        wp_localize_script( 'qwc-admin', 'qwc_params', array(
	        'ajax_url' => $ajax_url,
	        'order_id' => $order_id,
	        'quote_id' => $quote_id,
	        'email_msg' => __( 'Quote emailed.', 'quote-wc' ),
	        )
	        );
	        wp_enqueue_script( 'qwc-admin' );
	    }
	}
	
	function qwc_css_edit_cpt() {
		
	    global $post;
	    
	    if ( get_post_type( $post->ID ) === 'quote_wc' ) {
	        $plugin_version = get_option( 'quotes_for_wc' );
	        
	        wp_enqueue_style( 'qwc-edit-quote', plugins_url() . '/quotes-for-woocommerce/assets/css/qwc-edit-cpt.css' , '', $plugin_version , false );
	    }
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
	
	    }
	}
	
	/**
	 * Save the quotes setting.
	 * @since 1.0
	 */
	function qwc_save_setting( $post_id ) {
	    $enable_quotes = ( isset( $_POST[ 'qwc_enable_quotes' ] ) ) ? 'on' : '';
	    update_post_meta( $post_id, 'qwc_enable_quotes', $enable_quotes );
	}
	
    function qwc_save_post( $post_data, $post ) {
        
        if ( 'quote_wc' !== $post[ 'post_type' ] ) {
            return $post_data;
        }
         
        $post_id = $post[ 'ID' ];
         
        // Check the post being saved == the $post_id to prevent triggering this call for other save_post events
        if ( empty( $post['post_ID'] ) || intval( $post['post_ID'] ) !== $post_id ) {
            return $post_data;
        }
        
        if ( ! isset( $post['quote_save_meta_box_nonce'] ) || ! wp_verify_nonce( $post['quote_save_meta_box_nonce'], 'quote_save_meta_box' ) ) {
            return $post_data;
        }
        
        $new_status = $post[ '_qwc_status' ];
        $new_qty = $post[ 'qwc_qty' ];
        $quote_amt = $post[ 'qwc_quote' ];
         
        $quote = new Quotes_WC( $post_id );
        // Update the Status if needed
        if ( $quote->get_status() !== $new_status ) {
            $quote->update_status( $new_status );
        }
        	
        // update the qty
        update_post_meta( $post_id, '_qwc_qty', $new_qty );
        
        // update the quote amount
        update_post_meta( $post_id, '_qwc_quote', $quote_amt );
    }	

    /**
     *  Send quote email to user.
     *  @since 1.0
     */
    function qwc_send_quote() {
         
        $order_id = ( isset( $_POST[ 'order_id' ] ) ) ? $_POST[ 'order_id' ] : 0;
         
        if ( $order_id > 0 ) {
             
            $qwc_admin = new QWC_Admin();
            $send_status = $qwc_admin->send_quote_email( $order_id );
    
            if ( $send_status === 'success' ) {
                // update the quote status
                update_post_meta( $order_id, '_quote_status', 'quote-sent' );
                echo 'quote-sent';
            }
        }
        die();
    }
    
    function qwc_send_quote_post() {
    
        $order_id = $_POST[ 'order_id' ];
        $post_id = $_POST[ 'post_id' ];
    
        // check if any items in the order have quote status pending
        $return_items = get_pending_quotes( $order_id );
    
        if ( is_array( $return_items ) && count( $return_items ) > 0 ) {
            // echo a message stating the quote cannot be sent
            update_post_meta( $post_id, '_qwc_send_status', 'Failed' );
            update_post_meta( $post_id, '_qwc_send_msg', array( 'Quote cannot be emailed as some quotes in the order are Pending. Please update the statuses and try again.' ) );
        } else {
            // send the quotes
            $qwc_admin = new QWC_Admin();
            $send_status = $qwc_admin->send_quote_email( $order_id );
            if ( $send_status == 'success' ) {
                // echo a message stating the quote was sent
                update_post_meta( $post_id, '_qwc_send_status', 'Success' );
                update_post_meta( $post_id, '_qwc_send_msg', array( 'Quote emailed successfully.' ) );
            } else {
                // echo a message stating the quote cannot be sent
                update_post_meta( $post_id, '_qwc_send_status', 'Failed' );
                update_post_meta( $post_id, '_qwc_send_msg', array( 'Quote could not be emailed. Please try again.' ) );
            }
        }
    
        die();
    }
    
    function send_quote_email( $order_id ) {
    
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
            return 'success';
        }
    
    }
}
return new QWC_Admin();

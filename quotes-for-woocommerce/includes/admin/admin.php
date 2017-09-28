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
	    
		add_action( 'admin_enqueue_scripts', array( $this, 'qwc_css_edit_cpt' ) );
		
		add_action( 'wp_insert_post_data', array( &$this, 'qwc_save_post' ), 10, 2 );
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

}
return new QWC_Admin();

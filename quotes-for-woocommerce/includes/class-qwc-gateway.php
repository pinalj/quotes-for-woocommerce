<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Quotes_Payment_Gateway class
 */
if ( class_exists( 'WC_Payment_Gateway' ) ) { 
    class Quotes_Payment_Gateway extends WC_Payment_Gateway {
    
    	/**
    	 * Constructor for the gateway.
    	 */
    	public function __construct() {
    		$this->id                = 'quotes-gateway';
    		$this->icon              = '';
    		$this->has_fields        = false;
    		$this->method_title      = __( 'Ask for Quote', 'quote-wc' );
    		$this->title             = $this->method_title;
    		$this->order_button_text = __( 'Request Quote', 'quote-wc' );
    
    		// Actions
    		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
    	}
    	
    	/**
    	 * 
    	 */
    	public function admin_options() {
    	    $title = ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'quote-wc' ) ;
    	
    	    echo '<h3>' . $title . '</h3>';
    	
    	    echo '<p>' . __( 'This is fictitious payment method used for quotes.', 'quote-wc' ) . '</p>';
    	    echo '<p>' . __( 'This gateway requires no configuration.', 'quote-wc' ) . '</p>';
    	
    	    // Hides the save button
    	    echo '<style>p.submit input[type="submit"] { display: none }</style>';
    	}
    	
    	/**
    	 * 
    	 * @param unknown $order_id
    	 * @return multitype:string NULL
    	 */
    	public function process_payment( $order_id ) {
    	    $order = new WC_Order( $order_id );
    	
    	    // Add meta
    	    update_post_meta( $order_id, '_qwc_quote', '1' );
    	
    	    // Add custom order note.
    	    $order->add_order_note( __( 'This order is awaiting quote.', 'quote-wc' ) );
    	
    	    // Remove cart
    	    WC()->cart->empty_cart();
    	
    	    // Return thankyou redirect
    	    return array(
    	        'result' 	=> 'success',
    	        'redirect'	=> $this->get_return_url( $order )
    	    );
    	}
    	
    	/**
    	 * 
    	 * @param unknown $order_id
    	 */
    	public function thankyou_page( $order_id ) {
    	    $order = new WC_Order( $order_id );
    	    
    	    if ( 'completed' == $order->get_status() ) {
    	        echo '<p>' . __( 'We have received your order. Thank you.', 'quote-wc' ) . '</p>';
    	    } else {
    	        echo '<p>' . __( 'We have received your request for a quote. You will be notified via email soon.', 'quote-wc' ) . '</p>';
    	    }
    	}
    	
    }// end of class
}
//$Quotes_Payment_Gateway = new Quotes_Payment_Gateway();
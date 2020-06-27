<?php
/**
 * Request New Quote email
 */
$order_obj = new WC_order( $order->order_id );
$display_price = false;
$opening_paragraph = __( 'You have made a request for a quote on %s. The details of the order are as follows:', 'quotes-for-woocommerce' );

do_action( 'woocommerce_email_header', $email_heading );

if( $order ) {
	echo sprintf( $opening_paragraph, $site_name );
}

if ( $order_obj ) {
    echo sprintf( __( 'Product', 'quotes-for-woocommerce' ) );
    echo sprintf( __( 'Quantity', 'quotes-for-woocommerce' ) );
    if( qwc_order_display_price( $order_obj ) ) {
        $display_price = true;
        echo sprintf( __( 'Product Price', 'quotes-for-woocommerce' ) );
    }
    
    echo "\n";
			
	foreach( $order_obj->get_items() as $items ) {
	
        echo $items->get_name();
        echo $items->get_quantity();
        if( $display_price ) {
            echo $order_obj->get_formatted_line_subtotal( $items );
        }
        echo "\n";
            
	} 
	
    echo sprintf( __( 'This order is awaiting a quote.', 'quotes-for-woocommerce' ) );
    
    echo sprintf( __( 'You shall receive a quote email from the site admin soon.', 'quotes-for-woocommerce' ) );
    
    do_action( 'woocommerce_email_footer' );
}
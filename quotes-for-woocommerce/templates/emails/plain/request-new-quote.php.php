<?php
/**
 * Request New Quote email
 */
$order_obj = new WC_order( $order->order_id );

$opening_paragraph = __( 'A request for quote has been made by %s and is awaiting your attention. The details of the order are as follows:', 'quote-wc' );

do_action( 'woocommerce_email_header', $email_heading );

$billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order_obj->billing_first_name : $order_obj->get_billing_first_name();
$billing_last_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order_obj->billing_last_name : $order_obj->get_billing_last_name(); 
if ( $order_obj && $billing_first_name && $billing_last_name ) :
	echo sprintf( $opening_paragraph, $billing_first_name . ' ' . $billing_last_name );
endif;

if ( $order_obj ) {
    echo sprintf( __( 'Product', 'quote-wc' ) );
    echo sprintf( __( 'Quantity', 'quote-wc' ) );
    echo sprintf( __( 'Product Price', 'quote-wc' ) );

    echo "\n";
			
	foreach( $order_obj->get_items() as $items ) {
	
        echo $items->get_name();
        echo $items->get_quantity();
        echo $order_obj->get_formatted_line_subtotal( $items );;
        echo "\n";
            
	} 
	
    echo sprintf( __( 'This order is awaiting a quote.', 'quote-wc' ) );
    
    echo make_clickable( sprintf( __( 'You can view and edit this order in the dashboard here: %s', 'quote-wc' ), admin_url( 'post.php?post=' . $order->order_id . '&action=edit' ) ) );
    
    do_action( 'woocommerce_email_footer' );
}
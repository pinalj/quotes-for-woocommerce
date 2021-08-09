<?php
/**
 * Request New Quote email
 */
$display_price = false;
$text_align    = is_rtl() ? 'right' : 'left';
$margin_side   = is_rtl() ? 'left' : 'right';

$opening_paragraph = __( 'You have made a request for a quote on %s. The details of the order are as follows:', 'quote-wc' );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
do_action( 'woocommerce_email_header', $email_heading, $email );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
if( $order_details ) {
	echo sprintf( $opening_paragraph, $site_name );
}

if ( $order ) {
    echo "\n----------------------------------------\n\n";	
    echo sprintf( __( 'Product', 'quote-wc' ) );
    echo sprintf( __( 'Quantity', 'quote-wc' ) );
    if( qwc_order_display_price( $order ) ) {
        $display_price = true;
        echo sprintf( __( 'Product Price', 'quote-wc' ) );
    }
    
    echo "\n";
			
	foreach( $order->get_items() as $items ) {
        $item_id = $items->get_id();
        echo $items->get_name();
        // allow other plugins to add additional product information here.
		do_action( 'woocommerce_order_item_meta_start', $item_id, $items, $order, $plain_text );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo strip_tags(
			wc_display_item_meta(
				$items,
				array(
					'before'    => "\n- ",
					'separator' => "\n- ",
					'after'     => '',
					'echo'      => false,
					'autop'     => false,
				)
			)
		);

		// allow other plugins to add additional product information here.
		do_action( 'woocommerce_order_item_meta_end', $item_id, $items, $order, $plain_text );
        echo $items->get_quantity();
        if( $display_price ) {
            echo $order->get_formatted_line_subtotal( $items );
        }
        echo "\n";
            
	} 
    echo "\n----------------------------------------\n\n";
    echo sprintf( __( 'This order is awaiting a quote.', 'quote-wc' ) );
    
    echo sprintf( __( 'You shall receive a quote email from the site admin soon.', 'quote-wc' ) );
    
    do_action( 'woocommerce_email_footer' );
}

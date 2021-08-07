<?php
/**
 * Request New Quote email
 */
$text_align  = is_rtl() ? 'right' : 'left';
$margin_side = is_rtl() ? 'left' : 'right';

$opening_paragraph = __( 'A request for quote has been made by %s and is awaiting your attention. The details of the order are as follows:', 'quote-wc' );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
do_action( 'woocommerce_email_header', $email_heading, $email );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
$billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_first_name : $order->get_billing_first_name();
$billing_last_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_last_name : $order->get_billing_last_name(); 
if ( $order_details && $billing_first_name && $billing_last_name ) :
	echo sprintf( $opening_paragraph, $billing_first_name . ' ' . $billing_last_name );
endif;

if ( $order ) {
    echo "\n----------------------------------------\n\n";
    echo sprintf( __( 'Product', 'quote-wc' ) );
    echo sprintf( __( 'Quantity', 'quote-wc' ) );
    echo sprintf( __( 'Product Price', 'quote-wc' ) );

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
        echo $order->get_formatted_line_subtotal( $items );
        echo "\n";
            
	} 
    do_action( 'qwc_new_quote_admin_row', $order_details->order_id, $order );
    echo "\n----------------------------------------\n\n";
    echo sprintf( __( 'This order is awaiting a quote.', 'quote-wc' ) );
    
    echo make_clickable( sprintf( __( 'You can view and edit this order in the dashboard here: %s', 'quote-wc' ), admin_url( 'post.php?post=' . $order_details->order_id . '&action=edit' ) ) );
    
    do_action( 'woocommerce_email_footer' );
}

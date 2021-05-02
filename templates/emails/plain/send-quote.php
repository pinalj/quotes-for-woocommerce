<?php
/**
 * Send Quote Email
 */
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
do_action( 'woocommerce_email_header', $email_heading, $email );
echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
if ( $order ) : 
    $billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_first_name : $order->get_billing_first_name();
    echo sprintf( __( 'Hello %s', 'quote-wc' ), $billing_first_name ) . "\n\n";
endif;

echo sprintf( __( 'You have received a quotation for your order on %s. The details of the same are shown below.', 'quote-wc' ), $order_details->blogname );

if ( $order ) :

    $order_status = $order->get_status(); 
	if ( $order_status == 'pending' ) :
        echo sprintf( __( 'To pay for this order please use the following link: %s', 'quote-wc' ), $order->get_checkout_payment_url() );
	endif;

	do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );

    
    if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
        $order_date = $order->order_date;
    } else {
        $order_post = get_post( $order_details->order_id );
        $post_date = strtotime ( $order_post->post_date );
        $order_date = date( 'Y-m-d H:i:s', $post_date );
    }
	echo "\n----------------------------------------\n\n";
    echo sprintf( __( 'Order number: %s', 'quote-wc'), $order->get_order_number() ) . "\n";
    echo sprintf( __( 'Order date: %s', 'quote-wc'), date_i18n( wc_date_format(), strtotime( $order_date ) ) ) . "\n";
	echo "\n----------------------------------------\n\n";
	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text );

	echo "\n";

    $downloadable = $order->is_download_permitted();
                
	switch ( $order_status ) {
		case "completed" :
    	    $args = array( 'show_download_links' => $downloadable,
					        'show_sku' => false,
					        'show_purchase_note' => true 
                   );
			if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
                echo $order->email_order_items_table( $args );
		    } else {
                echo wc_get_email_order_items( $order, $args );
		    }
            break;
		case "processing" :
		    $args = array( 'show_download_links' => $downloadable,
        			        'show_sku' => true,
					        'show_purchase_note' => true 
		          );
		    if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
                echo $order->email_order_items_table( $args );
		    } else {
                echo wc_get_email_order_items( $order, $args );
		    }
            break;
		default :
		    $args = array( 'show_download_links' => $downloadable,
    				        'show_sku' => true,
					        'show_purchase_note' => false 
		          );
		    if ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) {
                echo $order->email_order_items_table( $args );
		    } else {
                echo wc_get_email_order_items( $order, $args );
		    }
            break;
	}

	echo "\n----------------------------------------\n\n";

	if ( $order->get_order_item_totals() ) {
	    $i = 0;
		foreach ( $order->get_order_item_totals() as $total ) {
		    $i++;
		    if ( $i == 1 ) {
                echo $total['label'] . "\t " . $total['value'] . "\n";
		    }
		}
	}

	echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

	do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );
endif;

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );

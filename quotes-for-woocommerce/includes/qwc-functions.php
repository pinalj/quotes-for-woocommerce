<?php
function product_quote_enabled( $product_id ) {
    $quote_enabled = false;
    
    $quote_setting = get_post_meta( $product_id, 'qwc_enable_quotes', true );
    
    if ( $quote_setting === 'on' ) {
        $quote_enabled = true;
    }
    return $quote_enabled;
} 

function qwc_get_product_id_by_variation_id($var_id) {
    $post = get_post($var_id);
    if ($post) {
        return $post->post_parent;
    } else {
        return false;
    }
}

function cart_contains_quotable() {
    
    $quotable = false;
    
    if ( isset( WC()->cart ) ) {
        foreach ( WC()->cart->cart_contents as $item ) {
            
            if($item['product_id'] === 0)			{
                $item['product_id'] = qwc_get_product_id_by_variation_id($item['variation_id']);
            }
            $quote_enabled = product_quote_enabled( $item['product_id'] );
    
            if ( $quote_enabled ) {
                $quotable = true;
                break;
            }
        }
    }
    return $quotable;
    
}

function order_requires_quote( $order ) {

    $requires = false;

    if ( $order ) {
        foreach( $order->get_items() as $item ) {
            $product_quote = product_quote_enabled( $item[ 'product_id' ] );
            if ( $product_quote ) {
                $requires = true;
                break;
            }
        }
    }

    return $requires;
}

function product_price_display( $product_id ) {

    $display_price = false;

    // check if price should be displayed or no
    $display_enabled = get_post_meta( $product_id, 'qwc_display_prices', true );

    if( $display_enabled == 'on' ) {
        $display_price = true;
    }

    return $display_price;
}

function qwc_cart_display_price() {

    $display = false;

    if ( isset( WC()->cart ) ) {
        foreach ( WC()->cart->cart_contents as $item ) {
            $price_enabled = product_price_display( $item['product_id'] );

            if ( $price_enabled ) {
                $display = true;
                break;
            }
        }
    }
    return $display;
}

function qwc_order_display_price( $order ) {

    $display = false;

    if ( $order ) {
        foreach( $order->get_items() as $item ) {
            $product_display = product_price_display( $item[ 'product_id' ] );
            if ( $product_display ) {
                $display = true;
                break;
            }
        }
    }

    return $display;

}
?>
<?php
function product_quote_enabled( $product_id ) {
    $quote_enabled = false;
    
    $quote_setting = get_post_meta( $product_id, 'qwc_enable_quotes', true );
    
    if ( $quote_setting === 'on' ) {
        $quote_enabled = true;
    }
    return $quote_enabled;
} 

function cart_contains_quotable() {
    
    $quotable = false;
    
    if ( isset( WC()->cart ) ) {
        foreach ( WC()->cart->cart_contents as $item ) {
            $quote_enabled = product_quote_enabled( $item['product_id'] );
    
            if ( $quote_enabled ) {
                $quotable = true;
                break;
            }
        }
    }
    return $quotable;
    
}
?>
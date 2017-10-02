<?php
/**
 * Returns true if the product allows quotes
 */
function product_quote_enabled( $product_id ) {
    $quote_enabled = false;
    
    $quote_setting = get_post_meta( $product_id, 'qwc_enable_quotes', true );
    
    if ( $quote_setting === 'on' ) {
        $quote_enabled = true;
    }
    return $quote_enabled;
} 

/**
 * Returns true if cart contains products that require quotes
 */
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

/**
 * Returns true if the order contains products that require quotes
 * @param order object
 */
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

/**
 * Returns an array of quote statuses
 */
function get_quote_statuses() {

    return array(
        'quote-pending'     => __( 'Pending', 'quote-wc' ),
        'quote-ready'       => __( 'Ready to Send', 'quote-wc' ),
        'quote-complete'    => __( 'Complete', 'quote-wc' ),
        'quote-sent'        => __( 'Sent', 'quote-wc' ),
        'quote-cancelled'   => __( 'Cancelled', 'quote-wc' ),
    );
}

/**
 * Creates and returns an array of products that require quotes
 */
function get_wc_product_list( $variations = true ) {

    $full_product_list = array();

    $select_variation = '';
    if ( $variations ) {
        $select_variation = 'product_variation';
    }
     
    $args       = array( 'post_type' => array('product', $select_variation ), 'posts_per_page' => -1 );
    $product    = get_posts( $args );

    $parent_array = array();

    foreach ( $product as $k => $value ) {
        $theid = $value->ID;

        if ( 'product_variation' == $value->post_type ) {
            $parent_id = $value->post_parent;
            // ignore orphan variations
            if( 0 == $parent_id ) {
                continue;
            }
            if ( ! in_array( $parent_id, $parent_array ) ) {
                $parent_array[] = $parent_id;
            }

            $product_id = $parent_id;
        } else {
            $parent_id = 0;
            $product_id = $theid;
        }

        $quote_enabled = product_quote_enabled( $product_id );

        if ( $quote_enabled ) {

            $_product = wc_get_product( $product_id );
            $thetitle = $_product->get_formatted_name();

            $full_product_list[] = array($thetitle, $product_id);
        }

    }

    // remove the parent products for variations
    foreach( $full_product_list as $key => $products ) {
        if ( in_array( $products[ 1 ], $parent_array ) ) {
            unset( $full_product_list[ $key ] );
        }
    }
     
    // sort into alphabetical order, by title
    sort($full_product_list);
    return $full_product_list;

}

/**
 * Returns true if a quote request is present for the product
 */
function qwc_check_quote_request( $product_id ) {

    $quote_exists = false;
    global $wpdb;

    $query = "SELECT post_id FROM `" . $wpdb->prefix . "postmeta`
                WHERE meta_key = %s
                AND meta_value = %d
                ORDER BY post_id DESC LIMIT 1";

    $results_query = $wpdb->get_results( $wpdb->prepare( $query, '_qwc_product_id', $product_id ) );

    if ( isset( $results_query ) && count( $results_query ) > 0 ) {
        $quote_exists = true;
    }

    return $quote_exists;
}

/**
 * Returns an array of quote IDs belonging to
 * the order with quote status Pending
 */
function get_pending_quotes( $order_id ) {

    if ( $order_id > 0 ) {
        // get all the quotes for the order
        $args = array(
            'post_type' => 'quote_wc',
            'post_parent' => $order_id,
            'post_status' => array( 'all' ),
        );

        $posts_list = get_posts( $args );

        $pending_quotes = array();

        foreach( $posts_list as $k => $v ) {
            $quote_id = $v->ID;
            $quote_status = $v->post_status;

            if ( 'quote-pending' === $quote_status ) {
                $pending_quotes[] = $quote_id;
            }
        }
        wp_reset_postdata();

        return $pending_quotes;
    }

    return false;
}
?>
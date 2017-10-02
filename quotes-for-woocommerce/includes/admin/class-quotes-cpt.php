<?php 

if ( ! class_exists( 'Quotes_WC_CPT' ) ) :

    /**
     * Quotes_WC_CPT Class.
    */
    class Quotes_WC_CPT {
    
        /**
         * Constructor.
         */
        public function __construct() {
            $this->type = 'quote_wc';
    
            // Admin Columns
            add_filter( 'manage_edit-' . $this->type . '_columns', array( $this, 'qwc_edit_columns' ) );
            add_action( 'manage_' . $this->type . '_posts_custom_column', array( $this, 'qwc_custom_columns' ), 2 );
            add_filter( 'manage_edit-' . $this->type . '_sortable_columns', array( $this, 'qwc_custom_columns_sort' ), 1 );
            add_filter( 'request', array( $this, 'qwc_custom_columns_orderby' ), 1 ); 
            
            // Filtering
            add_action( 'restrict_manage_posts', array( $this, 'qwc_filters' ) );
            add_filter( 'parse_query', array( $this, 'qwc_filters_query' ) );
            add_filter( 'get_search_query', array( $this, 'qwc_search_label' ) );
            
            // Search
            add_filter( 'parse_query', array( $this, 'qwc_search_custom_fields' ) );
            
            // Actions
            add_filter( 'bulk_actions-edit-' . $this->type, array( $this, 'qwc_bulk_actions' ), 10, 1 );
            add_action( 'load-edit.php', array( $this, 'qwc_bulk_action' ) );
            add_action( 'admin_footer', array( $this, 'qwc_bulk_admin_footer' ), 10 );
            add_action( 'admin_notices', array( $this, 'qwc_bulk_admin_notices' ) ); 
            
        }
        
        function qwc_edit_columns( $existing_columns ) {
            
            if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
                $existing_columns = array();
            }
            
            unset( $existing_columns['comments'], $existing_columns['title'], $existing_columns['date'] );
            
            $columns                    = array();
            $columns[ "qwc_status" ]    = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'quote-wc' ) . '">' . esc_attr__( 'Status', 'quote-wc' ) . '</span>';
            $columns[ "qwc_id" ]        = __( 'ID', 'quote-wc' );
            $columns[ "qwc_product" ]   = __( 'Product', 'quote-wc' );
            $columns[ "qwc_customer" ]  = __( 'Requested By', 'quote-wc' );
            $columns[ "qwc_order" ]     = __( 'Order', 'quote-wc' );
            $columns[ "qwc_qty" ]       = __( 'Quantity', 'quote-wc' );
            $columns[ "qwc_price" ]     = __( 'Product Price', 'quote-wc' );
            $columns[ "qwc_quote" ]     = __( 'Quote', 'quote-wc' );
            $columns[ "qwc_actions" ]   = __( 'Actions', 'quote-wc' );
            
            return array_merge( $existing_columns, $columns );
            
        }
        
        function qwc_custom_columns( $column ) {
            
            global $post;

            if ( get_post_type( $post->ID ) === $this->type ) {
                
                $quote_id = $post->ID;
                
                $quote = new Quotes_WC( $quote_id );
                $status = $quote->get_status();
                
                switch( $column ) {
                    case 'qwc_status':
                        $quote_statuses = get_quote_statuses();
                        $status_label = ( array_key_exists( $status, $quote_statuses ) ) ? $quote_statuses[ $status ] : ucwords( $status );
                        echo '<span class="status-' . esc_attr( $status ) . ' tips" date-tip="' . esc_attr( $status_label ) . '">' . esc_html( $status_label ) . '</span>';
                        break;
                    case 'qwc_id':
                        printf( '<a href="%s">' . __( 'Quote #%d', 'quote-wc' ) . '</a>', admin_url( 'post.php?post=' . $quote_id . '&action=edit' ), $quote_id );
                        break; 
                    case 'qwc_product':
                        $product = $quote->get_product();
                        
                        if ( $product ) {
                            echo '<a href="' . admin_url( 'post.php?post=' . $product->get_id() . '&action=edit' ) . '">' . $product->get_title() . '</a>';
                        } else {
                            echo '-';
                        }
                        break;
                    case 'qwc_customer':
                        $customer = $quote->get_customer();
                         
                        if ( $customer->email && $customer->name ) {
                            echo esc_html( $customer->name );
                        } else {
                            echo '-';
                        }
                        break;
                    case 'qwc_order':
                        $order = $quote->get_order();
                        if ( $order ) {
                            echo '<a href="' . admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) . '">#' . $order->get_order_number() . '</a> - ' . esc_html( wc_get_order_status_name( $order->get_status() ) );
                        } else {
                            echo '-';
                        }
                        break;
                    case 'qwc_qty':
                        $quantity = $quote->get_quantity();
                        echo "$quantity";
                        break;
                    case 'qwc_price':
                        $order = $quote->get_order();
                        $currency = $order->get_currency();
                        $currency_symbol = get_woocommerce_currency_symbol( $currency );
                        
                        echo $currency_symbol . $quote->get_product_cost();
                        break;
                    case 'qwc_quote':
                        $order = $quote->get_order();
                        $currency = $order->get_currency();
                        $currency_symbol = get_woocommerce_currency_symbol( $currency );
                        
                        echo $currency_symbol . $quote->get_quote();
                        break;
                    case 'qwc_actions':
                        echo '<p>';
                        $actions = array(
                            'view' => array(
                                'url'    => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
                                'name'   => __( 'View', 'quote-wc' ),
                                'action' => 'view',
                            ),
                        );
                        
                        if ( in_array( $status, array( 'quote-ready', 'quote-commplete' ) ) ) {
                            $actions['send_quote'] = array(
                                'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=qwc_send_quote&quote_id=' . $post->ID ), 'qwc-send-quote' ),
                                'name'   => __( 'Send Quote', 'quote-wc' ),
                                'action' => 'send_quote',
                            );
                        }
                        
                        if ( in_array( $status, array( 'quote-pending', 'quote-ready' ) ) ) {
                            $actions['complete'] = array(
                                'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=qwc_complete&quote_id=' . $post->ID ), 'qwc-complete' ),
                                'name'   => __( 'Complete', 'quote-wc' ),
                                'action' => 'complete',
                            );
                        }
                        
                        $actions = apply_filters( 'qwc_view_quote_actions', $actions, $quote );
                         
                        foreach ( $actions as $action ) {
                            printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
                        }
                        echo '</p>';
                        break;
                }
            }
        }
        
        function qwc_custom_columns_sort( $columns ) {
            
            $custom = array(
                'qwc_id'          => 'qwc_id',
                'qwc_product'     => 'qwc_product',
                'qwc_status'      => 'qwc_status',
            );
            return wp_parse_args( $custom, $columns );
        }
        
        function qwc_custom_columns_orderby( $vars ) {

            if ( isset( $vars['orderby'] ) ) {
                if ( 'qwc_id' == $vars['orderby'] ) {
                    $vars = array_merge( $vars, array(
                        'orderby' => 'ID', // sort using the ID column in posts
                    ) );
                }
                 
                if ( 'qwc_product' == $vars['orderby'] ) {
                    $vars = array_merge( $vars, array(
                        'meta_key' => '_qwc_product_id',
                        'orderby'  => 'meta_value_num',
                    ) );
                } 
                 
                if ( 'qwc_status' == $vars['orderby'] ) {
                    $vars = array_merge( $vars, array(
                        'orderby' => 'post_status', // sort using the post status
                    ) );
                }
            }
            return $vars;
        }
        
        function qwc_filters() {
            global $typenow, $wp_query;
        
            if ( $typenow !== $this->type ) {
                return;
            }
        
            $filters = array();
        
            $products = get_wc_product_list( false );
        
            foreach ( $products as $product ) {
                // check if a quote request is present for that product
                $present = qwc_check_quote_request( $product[1] );
        
                if ( $present ) {
                    $filters[ $product[1] ] = $product[0];
                }
        
            }
        
            $output = '';
        
            if ( is_array( $filters ) && count( $filters ) > 0 ) {
                $output .= '<select name="filter_products">';
                $output .= '<option value="">' . __( 'All Quote Products', 'quote-wc' ) . '</option>';
        
                foreach ( $filters as $filter_id => $filter ) {
                    $output .= '<option value="' . absint( $filter_id ) . '" ';
        
                    if ( isset( $_REQUEST['filter_products'] ) ) {
                        $output .= selected( $filter_id, $_REQUEST['filter_products'], false );
                    }
        
                    $output .= '>' . esc_html( $filter ) . '</option>';
                }
        
                $output .= '</select>';
            }
        
            echo $output;
        }
         
        function qwc_filters_query( $query ) {
            global $typenow, $wp_query;
             
            if ( $typenow === $this->type ) {
                 
                if ( ! empty( $_REQUEST['filter_products'] ) && empty( $query->query_vars['suppress_filters'] ) ) {
                    $query->query_vars['meta_query'] = array(
                        array(
                            'key'   => '_qwc_product_id',
                            'value' => absint( $_REQUEST['filter_products'] ),
                        ),
                    );
                }
            }
        }
        
        function qwc_search_label( $query ) {
            
            global $pagenow, $typenow;
             
            if ( 'edit.php' !== $pagenow ) {
                return $query;
            }
             
            if ( $typenow != $this->type ) {
                return $query;
            }
             
            if ( ! get_query_var( 'quote_search' ) ) {
                return $query;
            }
             
            return wc_clean( $_GET['s'] );
        }
        
        function qwc_bulk_actions( $actions ) {
            if ( isset( $actions['edit'] ) ) {
                unset( $actions['edit'] );
            }
            return $actions;
        }

        function qwc_bulk_action() {
        
            global $post_type;
        
            if ( $this->type === $post_type ) {
                $wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
                $action = $wp_list_table->current_action();
        
                switch ( $action ) {
                    case 'setup_quote' :
                        $new_status = 'setup';
                        $report_action = 'quotes_setup';
                        break;
                    case 'complete_quote' :
                        $new_status = 'complete';
                        $report_action = 'quotes_complete';
                        break;
                    case 'cancel_quote':
                        $new_status = 'cancelled';
                        $report_action = 'quotes_cancelled';
                        break;
                    case 'send_quote':
                        $new_status = 'send_quote';
                        $report_action = 'send_quote';
                        break;
                    default:
                        return;
                }
        
                $changed = 0;
        
                $post_ids = array_map( 'absint', (array) $_REQUEST['post'] );
        
                foreach ( $post_ids as $post_id ) {
        
                    if ( $new_status === 'send_quote' ) {
                        // do something with the post iD
                    } else {
                        // update the quote status
                        $quote_obj = new Quote_WC( $post_id );
                        if ( $quote_obj->get_status !== $new_status ) {
                            $quote_obj->update_status( $new_status );
                        }
                    }
                    $changed++;
                }
        
                $sendback = add_query_arg( array( 'post_type' => $this->type, $report_action => true, 'changed' => $changed, 'ids' => join( ',', $post_ids ) ), '' );
                wp_redirect( $sendback );
                exit();
            }
        }
        
        function qwc_bulk_admin_footer() {
            
            global $post_type;
            
            if ( $this->type === $post_type ) {
                ?>
				<script type="text/javascript">
					jQuery( document ).ready( function ( $ ) {
						$( '<option value="setup_quote"><?php _e( 'Mark Setup', 'quote-wc' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );
						$( '<option value="complete_quote"><?php _e( 'Mark Complete', 'quote-wc' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );
						$( '<option value="cancel_quote"><?php _e( 'Mark Cancelled', 'quote-wc' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );
						$( '<option value="send_quote"><?php _e( 'Send Quote', 'quote-wc' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );
					});
				</script>
			<?php
            }
        }
        
        function qwc_bulk_admin_notices() {
            
            global $post_type, $pagenow;
            
            if ( isset( $_REQUEST['quotes_setup'] ) || isset( $_REQUEST['quotes_complete'] ) || isset( $_REQUEST['quotes_cancelled'] ) ) {
                $number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
            
                if ( 'edit.php' == $pagenow && $this->type == $post_type ) {
                    $message = sprintf( _n( 'Quote status changed.', '%s quote statuses changed.', $number, 'quote-wc' ), number_format_i18n( $number ) );
                    echo '<div class="updated"><p>' . $message . '</p></div>';
                }
            } else if( $_REQUEST[ 'send_quote' ] ) {
                
                $number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;
                
                if ( 'edit.php' == $pagenow && $this->type == $post_type ) {
                    $message = sprintf( _n( '%s Quotes emailed.', '%s quotes emailed.', $number, 'quote-wc' ), number_format_i18n( $number ) );
                    echo '<div class="updated"><p>' . $message . '</p></div>';
                }
            }
        }
        
        function qwc_search_custom_fields( $wp ) {
            global $pagenow, $wpdb;
             
            if ( 'edit.php' != $pagenow || empty( $wp->query_vars['s'] ) || $wp->query_vars['post_type'] !== $this->type ) {
                return $wp;
            }
             
            $term = wc_clean( $_GET['s'] );
             
            if ( is_numeric( $term ) ) {
                // check if a quote exists by this ID
                if ( false !== get_post_status( $term ) && 'quote_wc' === get_post_type( $term ) )
                    $quote_ids = array( $term );
                else { // else assume the numeric value is an order ID
                    if ( function_exists( 'wc_order_search' ) ) {
                        $order_ids = wc_order_search( wc_clean( $_GET['s'] ) );
                        $quote_ids = $order_ids ? get_quote_ids_from_order_id( $order_ids ) : array( 0 );
        
                        if ( is_array( $quote_ids ) && count( $quote_ids ) == 0 ) {
                            $quote_ids = array( 0 );
                        }
                    }
                }
        
            } else {
        
                $search_fields = array_map( 'wc_clean', array(
                    '_billing_first_name',
                    '_billing_last_name',
                    '_billing_company',
                    '_billing_address_1',
                    '_billing_address_2',
                    '_billing_city',
                    '_billing_postcode',
                    '_billing_country',
                    '_billing_state',
                    '_billing_email',
                    '_billing_phone',
                    '_shipping_first_name',
                    '_shipping_last_name',
                    '_shipping_address_1',
                    '_shipping_address_2',
                    '_shipping_city',
                    '_shipping_postcode',
                    '_shipping_country',
                    '_shipping_state',
                ) );
                 
                // Search orders
                $order_ids = $wpdb->get_col(
                    $wpdb->prepare( "
                        SELECT post_id
                        FROM {$wpdb->postmeta}
                        WHERE meta_key IN ('" . implode( "','", $search_fields ) . "')
                        AND meta_value LIKE '%%%s%%'",
                            esc_attr( $_GET['s'] )
                    )
                );
        
                // ensure db query doesn't throw an error due to empty post_parent value
                $order_ids = empty( $order_ids ) ? array( '-1' ) : $order_ids;
        
                // so we know we're doing this
                $quote_ids = array_merge(
                    $wpdb->get_col( "
                        SELECT ID FROM {$wpdb->posts}
                        WHERE post_parent IN (" . implode( ',', $order_ids ) . ");
                    "),
                    $wpdb->get_col(
                        $wpdb->prepare( "
                            SELECT ID
                            FROM {$wpdb->posts}
                            WHERE post_title LIKE '%%%s%%'
                            OR ID = %d
                            ;",
                            esc_attr( $_GET['s'] ),
                            absint( $_GET['s'] )
                            )
                        ),
                        array( 0 ) // to ensure all the results are not returned
				);
            }
        
			$wp->query_vars['s']              = false;
			$wp->query_vars['post__in']       = $quote_ids;
			$wp->query_vars['quote_search'] = true;
        
		}
        
    }// end of class
endif;
return new Quotes_WC_CPT();
?>
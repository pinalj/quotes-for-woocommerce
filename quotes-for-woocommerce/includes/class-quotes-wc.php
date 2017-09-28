<?php 
/**
* Main model class for all quotes, this handles all the data
*/
class Quotes_WC {

	/** @public int */
	public $id;

	/** public string */
	public $quote_date;
	
	/** @public string */
	public $modified_date;

	/** @public object */
	public $post;

	/** @public int */
	public $product_id;

	/** @public int */
	public $order_id;

	/** @public int */
	public $item_id;
	
	/** @public object */
	public $order;

	/** @public int */
	public $customer_id;

	/** @public string */
	public $status;

	/** @public array - contains all post meta values for this quote */
	public $custom_fields;

	/** @public bool */
	public $populated;

	/** @private array - used to temporarily hold order data for new quotes */
	private $order_data;

	/**
	 * Constructor, possibly sets up with post or id belonging to existing booking
	 * or supplied with an array to construct a new booking
	 * @param int/array/obj $booking_data
	 */
	public function __construct( $quote_data = false ) {
		$populated = false;

		if ( is_array( $quote_data ) ) {
			$this->order_data = $quote_data;
			$populated = false;
		} else if ( is_int( intval( $quote_data ) ) && 0 < $quote_data ) {
			$populated = $this->populate_data( $quote_data );
		} else if ( is_object( $quote_data ) && isset( $quote_data->ID ) ) {
			$this->post = $quote_data;
			$populated = $this->populate_data( $quote_data->ID );
		}

		$this->populated = $populated;
	}
	
	/**
	 * Populate the data with the id of the quote provided
	 * Will query for the post belonging to this Quote and store it
	 * @param int $quote_id
	 */
	public function populate_data( $quote_id ) {
	    if ( ! isset( $this->post ) ) {
	        $post = get_post( $quote_id );
	    }
	
	    if ( is_object( $post ) ) {
	        // We have the post object belonging to this quote, now let's populate
	        $this->id            = $post->ID;
	        $this->quote_date    = $post->post_date;
	        $this->modified_date = $post->post_modified;
	        $this->customer_id   = $post->post_author;
	        $this->custom_fields = get_post_meta( $this->id );
	        $this->status        = $post->post_status;
	        $this->order_id      = $post->post_parent;
	
	        // Define the data we're going to load: Key => Default value
	        $load_data = array(
	            'product_id'  => 0,
	            'qty'         => 1,
	            'cost'        => 0,
	            'customer_id' => '',
	            'parent_id'   => 0,
	            'variation_id'=> 0,
	            'item_id'    => 0,
	        );
	
	        // Load the data from the custom fields (with prefix for this plugin)
	        $meta_prefix = '_qwc_';
	
	        foreach ( $load_data as $key => $default ) {
	            if ( isset( $this->custom_fields[ $meta_prefix . $key ][0] ) && $this->custom_fields[ $meta_prefix . $key ][0] !== '' ) {
	                $this->$key = maybe_unserialize( $this->custom_fields[ $meta_prefix . $key ][0] );
	            } else {
	                $this->$key = $default;
	            }
	        }
	
	        // Save the post object itself for future reference
	        $this->post = $post;
	        return true;
    }
	
	    return false;
	}
	
	/**
    * Actual create for the new quote belonging to an order
    * @param string Status for new order
    */
	public function create( $status = 'pending' ) {
	    $this->new_quote( $status, $this->order_data );
	}
	
    /**
	 * Makes the new quote belonging to an order
	 * @param string $status The status for this new quote
	 * @param array $order_data Array with all the new order data
	 */
	private function new_quote( $status, $order_data ) {
        global $wpdb;
	
	    $order_data = wp_parse_args( $order_data, array(
	        'user_id'           => 0,
	        'product_id'        => 0,
	        'order_item_id'     => 0,
	        'cost'              => 0,
	        'parent_id'         => 0,
	        'qty'               => 1,
	        'variation_id'      => 0,
	    ) );
	
	    $order_id = $order_data[ 'parent_id' ];
	    
        $quote_data = array(
	        'post_type'   => 'quote_wc',
	        'post_title'  => sprintf( __( 'Quote &ndash; %s', 'quote-wc' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Request date parsed by strftime', 'quote-wc' ) ) ),
	        'post_status' => $status,
	        'ping_status' => 'closed',
	        'post_parent' => $order_id
	    );
	
	    $this->id = wp_insert_post( $quote_data );
	
	    // Setup the required data for the current user
	    if ( ! $order_data['user_id'] ) {
	        if ( is_user_logged_in() ) {
	            $order_data['user_id'] = get_current_user_id();
	        } else {
	            $order_data['user_id'] = 0;
	        }
	    }
	
       $meta_args = array(
	        '_qwc_order_item_id'  => $order_data[ 'order_item_id' ],
	        '_qwc_product_id'     => $order_data[ 'product_id' ],
	        '_qwc_cost'           => $order_data[ 'cost' ],
	        '_qwc_parent_id'      => $order_data[ 'parent_id' ],
	        '_qwc_customer_id'    => $order_data[ 'user_id' ],
            '_qwc_qty'            => $order_data[ 'qty' ],
            '_qwc_variation_id'   => $order_data[ 'variation_id' ],
            '_qwc_quote'          => $order_data[ 'cost' ],
	    );

	    foreach ( $meta_args as $key => $value ) {
	        update_post_meta( $this->id, $key, $value );
	    }
	
	    do_action( 'qwc_new-quote', $this->id );
	}
	
    /**
	 * Returns the id of this quote
	 * @return Id of the quote or false if quote is not populated
	 */
	public function get_id() {
	    if ( $this->populated ) {
	        return $this->id;
	    }
	
	    return false;
	}
	
	/**
	 * Returns the status of this quote
	 * @param Bool to ask for pretty status name (if false)
	 * @return String of the quote status
	 */
	public function get_status( $raw = true ) {
	    if ( $this->populated ) {
        if ( $raw ) {
	            return $this->status;
	        } else {
	            $status_object = get_post_status_object( $this->status );
	            return $status_object->label;
	        }
	    }
	
	    return false;
	}
	
	/**
	 * Set the new status for this quote
	 * @param string $status
	 * @return bool
	 */
	public function update_status( $status ) {
        $current_status   = $this->get_status( true );
	    $allowed_statuses = get_quote_statuses();
	    
	    if ( $this->populated ) {
	        
	        if ( array_key_exists( $status, $allowed_statuses ) ) {
	            
	            wp_update_post( array( 'ID' => $this->id, 'post_status' => $status ) );

                // Trigger actions
	            do_action( 'quote_wc_' . $current_status . '_to_' . $status, $this->id );
	            do_action( 'quote_wc_' . $status, $this->id );
	
	            // Note in the order
	            if ( $order = $this->get_order() ) {
	                $order->add_order_note( sprintf( __( 'Quote #%d status changed from "%s" to "%s"', 'quote-wc' ), $this->id, $current_status, $status ) );
	            }
	
	            return true;
	        }
	    }
	
	    return false;
	}
	
	/**
	 * Returns the object of the order corresponding to this quote
	 * @return Order object or false if quote is not populated
	 */
	public function get_order() {
	    if ( empty( $this->order ) ) {
	        if ( $this->populated && ! empty( $this->order_id ) && 'shop_order' === get_post_type( $this->order_id ) ) {
	            $this->order = wc_get_order( $this->order_id );
	        } else {
	            return false;
	        }
	    }
	
	    return $this->order;
	}
	

	/**
	 * Returns the Customer ID
	 * @return Customer ID
	 */
	public function get_customer_id() {
	
	    if ( ! empty( $this->customer_id ) ) {
	        return $this->customer_id;
	    } else {
	        return false;
	    }
	}
	
	/**
	 * Returns the Order ID
	 * @return Order ID
	 */
	public function get_order_id() {
	
	    if ( empty( $this->order_id ) ) {
	        if ( ! empty( $this->order ) ) {
	            $order_id = $this->order->get_id();
	            return $order_id;
	        }
	    } else {
	        return $this->order_id;
	    }
	}

	/**
	 * Returns the Product ID
	 * @return Product ID
	 */
	public function get_product_id() {
	     
	    if ( empty( $this->product_id ) ) {
	        if ( ! empty( $this->product ) ) {
	            return $this->product->id;
	        }
	    } else {
	        return $this->product_id;
	    }
	}
	
	/**
	 * Returns the Product Object
	 * @return Product Object
	 */
	public function get_product() {
	     
	    if ( empty( $this->product ) ) {
	        if ( ! empty( $this->product_id ) ) {
	            return wc_get_product( $this->product_id );
	        }
	    } else {
	        return $this->product;
	    }
	}
	
	/**
	 * Returns the Order Date
	 * @return Order Date
	 */
	public function get_date_created() {
	     
	    if ( ! empty( $this->order_id ) ) {
	        $order_post = get_post( $this->order_id );
	        $post_date = strtotime ( $order_post->post_date );
	        $order_date = date( 'Y-m-d H:i:s', $post_date );
	        return $order_date;
	    }
	}
	
	/**
	 * Returns the Customer Object
	 * @return Customer Object
	 */
	public function get_customer() {
	    $name    = '';
	    $email   = '';
	    $user_id = 0;
	
	    if ( $order = $this->get_order() ) {
	        $first_name = is_callable( array( $order, 'get_billing_first_name' ) ) ? $order->get_billing_first_name() : $order->billing_first_name;
	        $last_name  = is_callable( array( $order, 'get_billing_last_name' ) ) ? $order->get_billing_last_name()   : $order->billing_last_name;
	        $name       = trim( $first_name . ' ' . $last_name );
	        $email      = is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email()           : $order->billing_email;
	        $user_id    = is_callable( array( $order, 'get_customer_id' ) ) ? $order->get_customer_id()               : $order->customer_user;
	        $name 		= 0 !== absint( $user_id ) ? $name : sprintf( _x( '%s (Guest)', 'Guest string with name from order in brackets', 'quote-wc' ), $name );
	    } elseif ( $this->get_customer_id() ) {
	        $user    = get_user_by( 'id', $this->get_customer_id() );
	        $name    = $user->display_name;
	        $email   = $user->user_email;
	        $user_id = $this->get_customer_id();
	    }
	    return (object) array(
	        'name'    => $name,
	        'email'   => $email,
	        'user_id' => $user_id,
	    );
	}
	
	function get_quantity() {
	    return get_post_meta( $this->id, '_qwc_qty', true );
	}
	
	function get_product_cost() {
	    return get_post_meta( $this->id, '_qwc_cost', true );
	}
	
	function get_quote() {
	    return get_post_meta( $this->id, '_qwc_quote', true );
	}
	
	function get_notes() {
	    $order = $this->get_order();
	    
	    return $order->get_customer_note();
	}
}
?>
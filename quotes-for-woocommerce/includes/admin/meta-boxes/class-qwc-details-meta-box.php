<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * QWC_Details_Meta_Box.
 */
class QWC_Details_Meta_Box {

	/**
	 * Meta box ID.
	 */
	public $id;

	/**
	 * Meta box title.
	 */
	public $title;

	/**
	 * Meta box context.
	 */
	public $context;

	/**
	 * Meta box priority.
	 */
	public $priority;

	/**
	 * Meta box post types.
	 */
	public $post_types;

	/**
	 * Are meta boxes saved?
	 */
	private static $saved_meta_box = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'quote-data';
		$this->title      = __( 'Quote Details', 'quote-wc' );
		$this->context    = 'normal';
		$this->priority   = 'high';
		$this->post_types = array( 'quote_wc' );
	}

	function display_messages() {
	    global $post;
	    $quote_id = isset( $post->ID ) ? $post->ID : 0;
	     
	    if ( $quote_id > 0 ) {
	        // check if update errors exist
	        $qwc_status = get_post_meta( $quote_id, '_qwc_send_status', true );
	         
	        if ( isset( $qwc_status ) && 'Failed' === $qwc_status ) {
	            $class = 'error';
	        } else {
	            $class = 'notice';
	        }
	        $msg_list = get_post_meta( $quote_id, '_qwc_send_msg', true );
	        if ( is_array( $msg_list ) && count( $msg_list ) > 0 ) {
	            foreach( $msg_list as $message ) {
	                echo '<div class="' . $class . '"><p>' . __( $message, 'quote-wc' ) . '</p></div>';
	            }
	            delete_post_meta( $quote_id, '_qwc_send_msg' );
	        }
	        delete_post_meta( $quote_id, '_qwc_send_status' );
	    }
	}
	
	/**
	 * Meta box content.
	 */
	public function meta_box_inner( $post ) {
		global $booking;

		wp_nonce_field( 'quote_details_meta_box', 'quote_details_meta_box_nonce' );

		if ( get_post_type( $post->ID ) === 'quote_wc' ) {
			$quote = new Quotes_WC( $post->ID );
		}
		$order             = $quote->get_order();
		$order_id          = absint( $order->get_id() );
		$product_id        = $quote->get_product_id();
		
		$customer_id       = $quote->get_customer_id();
		$product           = $quote->get_product( $product_id );
		$customer          = $quote->get_customer();
		
		$statuses          = get_quote_statuses();
		
		$product_price     = $quote->get_product_cost();
		$quote_price       = $quote->get_quote();
        $quote_notes       = $quote->get_notes();
        
		$quantity = get_post_meta( $post->ID, '_qwc_qty', true );
		
		if ( ! is_numeric( $quantity ) || $quantity < 1 ) {
		    $quantity = 1;
		}

		$this->display_messages();
		?>
		<style type="text/css">
			#post-body-content, #titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
		</style>
		<div class="panel-wrap woocommerce">
			<div id="qwc_data" class="panel">
				<h2><?php printf( __( 'Quote #%s details', 'quote-wc' ), esc_html( $post->ID ) ) ?></h2>
				<p class="qwc_number"><?php
					if ( $order ) {
						printf( ' ' . __( 'Linked to order %s.', 'quote-wc' ), '<a href="' . admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' ) . '">#' . esc_html( $order_id ) . '</a>' );
					}

				?></p>

				<div class="qwc_data_column_container">
					<div class="qwc_data_column">
						<h4><?php _e( 'General details', 'quote-wc' ); ?></h4>

						<p class="form-field form-field-wide">
							<label for="_qwc_order_id"><?php _e( 'Order ID:', 'quote-wc' ); ?></label>
							<?php if ( $quote->get_order_id() && $order ) : ?>
								<input name="_qwc_order_id" id="_qwc_order_id" value="<?php echo esc_html( $order_id . ' &ndash; ' . date_i18n( wc_date_format(), strtotime( $order->get_date_created() ) ) ); ?>" readonly/>
							<?php endif; ?>
						</p>

						<p class="form-field form-field-wide"><label for="qwc_date"><?php _e( 'Date created:', 'quote-wc' ); ?></label>
							<input type="text" name="qwc_date" id="qwc_date" maxlength="10" value="<?php echo date_i18n( 'Y-m-d', strtotime( $quote->get_date_created() ) ); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" readonly /> @ <input type="number" class="hour" placeholder="<?php _e( 'h', 'quote-wc' ); ?>" name="qwc_date_hour" id="qwc_date_hour" maxlength="2" size="2" value="<?php echo date_i18n( 'H', strtotime( $quote->get_date_created() ) ); ?>" pattern="\-?\d+(\.\d{0,})?" readonly />:<input type="number" class="minute" placeholder="<?php _e( 'm', 'quote-wc' ); ?>" name="qwc_date_minute" id="qwc_date_minute" maxlength="2" size="2" value="<?php echo date_i18n( 'i', strtotime( $quote->get_date_created() ) ); ?>" pattern="\-?\d+(\.\d{0,})?" readonly />
						</p>

						<p class="form-field form-field-wide">
							<label for="_qwc_status"><?php _e( 'Quote Status:', 'quote-wc' ); ?></label>
							<select id="_qwc_status" name="_qwc_status" class="wc-enhanced-select"><?php
								foreach ( $statuses as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $quote->get_status(), false ) . '>' . esc_html__( $value, 'quote-wc' ) . '</option>';
								}
							?></select>
						</p>

						<p class="form-field form-field-wide">
							<label for="_qwc_customer_id"><?php _e( 'Customer:', 'quote-wc' ); ?></label>
							<?php
								$name = ! empty( $customer->name ) ? ' &ndash; ' . $customer->name : '';
								
								if ( $quote->get_customer_id() ) {
									$user            = get_user_by( 'id', $quote->get_customer_id() );
									$customer_string = sprintf(
										esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'quote-wc' ),
										trim( $user->first_name . ' ' . $user->last_name ),
										$customer->user_id,
										$customer->email
									);
								} else {
									$customer_string = $name;
								}
							?>
							<?php if ( $customer_string !== '' ) : ?>
								<input name="_qwc_customer_id" id="_qwc_customer_id" value="<?php echo esc_attr( $customer_string ); ?>" readonly />
							<?php endif; ?>
						</p>

						<?php do_action( 'qwc_edit_post_after_order_details', $post->ID ); ?>

					</div>
					<div class="qwc_data_column">
						<h4><?php _e( 'Product Details', 'quote-wc' ); ?></h4>

						<p class="form-field form-field-wide">
							<label for="qwc_product_name"><?php _e( 'Product:', 'quote-wc' ); ?></label>
							<?php if ( $product ) { ?>
                                <input name="qwc_product_name" id="qwc_product_name" value="<?php echo esc_html( $product->get_name() ); ?>" readonly/>
								<input type="hidden" name="qwc_product_id" id="qwc_product_id" value="<?php echo $product_id; ?>" readonly/>
							<?php } ?>
						</p>
						
						<p class="form-field form-field-wide">
							<label for="qwc_qty"><?php _e( 'Quantity:', 'quote-wc' ); ?></label>
							<input type='number' min=1 name="qwc_qty" id="qwc_qty" value="<?php echo $quantity; ?>" />
						</p>
						
					</div>
					<div class="qwc_data_column">
						<h4><?php _e( 'Quote Details', 'quote-wc' ); ?></h4>

						<?php
						$currency = $order->get_currency();
						$currency_symbol = get_woocommerce_currency_symbol( $currency ); 
						?>
						<p class="form-field form-field-wide">
							<label for="qwc_product_price"><?php _e( "Product price ($currency_symbol):", 'quote-wc' ); ?></label>
                            <input name="qwc_product_price" id="qwc_product_price" value="<?php echo $product_price; ?>" readonly/>
						</p>
						
						<p class="form-field form-field-wide">
							<label for="qwc_quote"><?php _e( "Price Quoted ($currency_symbol):", 'quote-wc' ); ?></label>
							<input name="qwc_quote" id="qwc_quote" value="<?php echo $quote_price; ?>" />
						</p>
						
						<p class="form-field form-field-wide">
							<label for="qwc_notes"><?php _e( 'Customer Notes (if any):', 'quote-wc' ); ?></label>
							<textarea row=4 col=20 name="qwc_notes" id="qwc_notes" readonly><?php echo $quote_notes; ?></textarea>
						</p>
						
					</div>
					
				</div>
			</div>
		</div>
<?php 
	}
}

return new QWC_Details_Meta_Box();
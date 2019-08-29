<?php
/**
 * Send Quote Email
 */
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php 
$order_obj = new WC_order( $order->order_id );
if ( $order_obj ) : 
    $billing_first_name = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order_obj->billing_first_name : $order_obj->get_billing_first_name(); ?>
	<p><?php printf( __( 'Hello %s', 'quote-wc' ), $billing_first_name ); ?></p>
<?php endif; ?>

<p><?php printf( __( 'You have received a quotation for your order on %s. The details of the same are shown below.', 'quote-wc' ), $order->blogname ); ?></p>

<?php if ( $order_obj ) : ?>

	<?php
        $order_status = $order_obj->get_status(); 
    	if ( $order_status == 'pending' ) : ?>
		<p><?php printf( __( 'To pay for this order please use the following link: %s', 'quote-wc' ), '<a href="' . esc_url( $order_obj->get_checkout_payment_url() ) . '">' . __( 'Pay for order', 'quote-wc' ) . '</a>' ); ?></p>
	<?php 
		endif;
	
	do_action( 'woocommerce_email_order_details', $order_obj, $sent_to_admin, $plain_text, $email );

	do_action( 'woocommerce_email_after_order_table', $order_obj, $sent_to_admin, $plain_text, $email );

	do_action( 'woocommerce_email_order_meta', $order_obj, $sent_to_admin, $plain_text );

	endif;

do_action( 'woocommerce_email_footer' );
?>

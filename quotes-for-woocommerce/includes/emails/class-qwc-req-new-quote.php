<?php 
/**
 * Request New Quote EMail
 *
 * An email sent to admin when a new quote request arrives
 * 
 * @class       QWC_Request_New_Quote
 * @extends     WC_Email
 *
 */
class QWC_Request_New_Quote extends WC_Email {
    
    function __construct() {
        
        $this->id                   = 'qwc_req_new_quote';
        $this->title                = __( 'Request for New Quote', 'quote-wc' );
        $this->description          = __( 'This email is sent to the site admin when a request for a quotation comes through.', 'quote-wc' );
        
        $this->heading              = __( 'Quotation Request for #{order_number}', 'quote-wc' );
        $this->subject              = __( '[{blogname}] Quotation Request (Order {order_number}) - {order_date}', 'quote-wc' );
        
        $this->template_html    = 'emails/request-new-quote.php';
        $this->template_plain   = 'emails/plain/request-new-quote.php';
        
        // Triggers for this email
        add_action( 'qwc_pending_quote_notification', array( $this, 'trigger' ) );
        
        // Call parent constructor
        parent::__construct();
        
        // Other settings
        $this->template_base = QUOTES_TEMPLATE_PATH;
        
    }
    
    function trigger( $order_id ) {
        
        $send_email = true;
        
        // add a filter using which an addon can modify the email send status
        // setting it to true will send the email
        // setting it to false will make sure that the email is not sent for the given item
        $send_email = apply_filters( 'qwc_request_new_quote_email', $send_email, $order_id );
        
        if ( $order_id > 0 && $send_email ) {
        
            $this->object = $this->get_order_details( $order_id );
            
            // allowed quote statuses
            $_status = array(
                'quote-pending',
            );
             
            if ( in_array( $this->object->quote_status , $_status ) && $this->is_enabled() ) {

                $this->recipient     = $this->get_option( 'recipient', get_option( 'admin_email' ) );
                if ( $this->object->order_id ) {
                    
                    $this->find[]    = '{order_date}';
                    $this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
        
                    $this->find[]    = '{order_number}';
                    $this->replace[] = $this->object->order_id;
                } else {
                    
                    $this->find[]    = '{order_date}';
                    $this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->item_hidden_date ) );
        
                    $this->find[]    = '{order_number}';
                    $this->replace[] = __( 'N/A', 'quote-wc' );
                }
    
                $this->find[]    = '{blogname}';
                $this->replace[] = $this->object->blogname;

                if ( ! $this->get_recipient() ) {
                    return;
                }
        
                $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            }
        } 
    }
    
    function get_order_details( $order_id ) {

        $order_obj = new stdClass();
        
        $order_obj->order_id = $order_id;
        
        $order = new WC_order( $order_id );
        
        // order date
        $post_data = get_post( $order_id );
        $order_obj->order_date = $post_data->post_date;
        
        // email adress
        $order_obj->billing_email = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->billing_email : $order->get_billing_email();
        
        // customer ID
        $order_obj->customer_id = ( version_compare( WOOCOMMERCE_VERSION, "3.0.0" ) < 0 ) ? $order->user_id : $order->get_user_id();
        
        // quote status
        $order_obj->quote_status = get_post_meta( $order_id, '_quote_status', true );
        
        $order_obj->blogname = get_option( 'blogname' ); 
        return $order_obj;
        
    }
    
    function get_content_html() {
        ob_start();
        wc_get_template( $this->template_html, array(
        'order'       => $this->object,
        'email_heading' => $this->get_heading(),
        'sent_to_admin' => true,
        'plain_text'    => false,
        'email'         => $this,
        ), 'quotes-for-wc/', $this->template_base );
        return ob_get_clean();
    }
    
    function get_content_plain() {
        ob_start();
        wc_get_template( $this->template_plain, array(
            'order'       => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => true,
            'plain_text'    => true,
            'email'         => $this,
            ), 'quotes-for-wc/', $this->template_base );
        return ob_get_clean();
    }
    
    function get_default_subject() {
        return  __( '[{blogname}] Quotation Request (Order {order_number}) - {order_date}', 'quote-wc' );
    
    }
    
    public function get_default_heading() {
        return __( 'Quotation Request for #{order_number}', 'quote-wc' );

    }
    
    function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' 		=> __( 'Enable/Disable', 'quote-wc' ),
                'type' 			=> 'checkbox',
                'label' 		=> __( 'Enable this email notification', 'quote-wc' ),
                'default' 		=> 'yes'
            ),
            'recipient' => array(
                'title'         => __( 'Recipient', 'quote-wc' ),
                'type'          => 'text',
                'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s', 'quote-wc' ), get_option( 'admin_email' ) ),
                'default'       => get_option( 'admin_email' )
            ),
            'subject' => array(
                'title' 		=> __( 'Subject', 'quote-wc' ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'quote-wc' ), $this->subject ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'heading' => array(
                'title' 		=> __( 'Email Heading', 'quote-wc' ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'quote-wc' ), $this->heading ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'email_type' => array(
                'title' 		=> __( 'Email type', 'quote-wc' ),
                'type' 			=> 'select',
                'description' 	=> __( 'Choose which format of email to send.', 'quote-wc' ),
                'default' 		=> 'html',
                'class'			=> 'email_type',
                'options'		=> array(
                    'plain'		 	=> __( 'Plain text', 'quote-wc' ),
                    'html' 			=> __( 'HTML', 'quote-wc' ),
                    'multipart' 	=> __( 'Multipart', 'quote-wc' ),
                )
            )
        );
    }
    
}
return new QWC_Request_New_Quote();
?>
<?php
/**
 * Handles Emails
 */ 
class QWC_Email_manager {
    
    // Constructor
    public function __construct() {
        
        add_filter( 'woocommerce_email_classes', array( &$this, 'qwc_init_emails' ) );
     
        // Email Actions
        $email_actions = array(
            // Send Quotes
            'qwc_send_quote',
        );
        
        foreach ( $email_actions as $action ) {
            add_action( $action, array( 'WC_Emails', 'send_transactional_email' ), 10, 10 );
        }
        
        add_filter( 'woocommerce_template_directory', array( $this, 'qwc_template_directory' ), 10, 2 );
    }
    
    function qwc_init_emails( $emails ) {
        
        if ( ! isset( $emails[ 'QWC_Send_Quote' ] ) ) {
            $emails[ 'QWC_Send_Quote' ] = include_once( plugin_dir_path( __FILE__ ) . 'emails/class-qwc-send-quote.php' );
        }
        return $emails;
    }
    
    public function qwc_template_directory( $directory, $template ) {
        if ( false !== strpos( $template, 'quote' ) ) {
            return 'quotes-for-wc';
        }
    
        return $directory;
    }
     
} // end of class
return new QWC_Email_manager();
?>
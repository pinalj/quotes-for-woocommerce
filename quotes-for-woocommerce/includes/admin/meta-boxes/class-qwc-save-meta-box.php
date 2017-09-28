<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quotes_WC_Save_Meta_Box.
 */
class QWC_Save_Meta_Box {

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

	public function __construct() {
		$this->id         = 'quote-wc-save';
		$this->title      = __( 'Quote actions', 'quote-wc' );
		$this->context    = 'side';
		$this->priority   = 'high';
		$this->post_types = array( 'quote_wc' );
	}

	public function meta_box_inner( $post ) {
		wp_nonce_field( 'quote_save_meta_box', 'quote_save_meta_box_nonce' );

		$statuses = array( 'quote-pending', 'quote-cancelled' );
		$margin = '40px;';
		?>	
        <div id="delete-action"><a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php _e( 'Move to trash', 'quote-wc' ); ?></a>
        
        <?php if ( ! in_array( get_post_status( $post->ID ), $statuses ) ) { ?>
            <a style='margin-left:10px;' class="sendquote email" href="<?php ?>"><?php _e( 'Send Quote to Customer', 'quote-wc' ); ?></a>
            </div><br><br>
            <?php $margin = '150px;'?>
        <?php } else { ?>
        </div>
        <?php }?>
		<input type="submit" style='margin-left:<?php echo $margin;?>' class="button save_quote button-primary tips" name="qwc_save" value="<?php _e( 'Save Quote', 'quote-wc' ); ?>" data-tip="<?php _e( 'Save/update the quote', 'quote-wc' ); ?>" />
		<?php
	}
}
return new QWC_Save_Meta_Box();

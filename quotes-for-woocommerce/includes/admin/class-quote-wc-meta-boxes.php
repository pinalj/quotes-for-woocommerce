<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quote_WC_Meta_Boxes.
 */
class Quote_WC_Meta_Boxes {

	/**
	 * Stores an array of meta boxes we include.
	 *
	 * @var array
	 */
	private $meta_boxes = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->meta_boxes[] = include( 'meta-boxes/class-qwc-details-meta-box.php' );
		$this->meta_boxes[] = include( 'meta-boxes/class-qwc-customer-meta-box.php' );
		$this->meta_boxes[] = include( 'meta-boxes/class-qwc-save-meta-box.php' );

		add_action( 'add_meta_boxes', array( $this, 'qwc_add_meta_boxes' ), 10, 1 );
		add_action( 'admin_menu', array( $this, 'qwc_remove_submitdiv' ) );
	}

	/**
	 * Add meta boxes to custom post edit page
	 */
	public function qwc_add_meta_boxes() {
		foreach ( $this->meta_boxes as $meta_box ) {
			foreach ( $meta_box->post_types as $post_type ) {
				add_meta_box(
					$meta_box->id,
					$meta_box->title,
					array( $meta_box, 'meta_box_inner' ),
					$post_type,
					$meta_box->context,
					$meta_box->priority
				);
			}
		}
	}

	public function qwc_remove_submitdiv() {
		remove_meta_box( 'submitdiv', 'quote_wc', 'side' );
	}
}
return new Quote_WC_Meta_Boxes();

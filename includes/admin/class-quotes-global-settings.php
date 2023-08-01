<?php
/**
 * Global Settings.
 *
 * @package Quotes for WooCommerce/Admin
 */

if ( ! class_exists( 'Quotes_Global_Settings' ) ) {

	/**
	 * Admin settings.
	 */
	class Quotes_Global_Settings {

		/**
		 * Construct.
		 */
		public function __construct() {
		}
		/**
		 * Adds the Section and fields to Quotes->Settings page.
		 *
		 * @since 1.5
		 */
		public static function qwc_general_settings_display() {
			?>
			<div>
				<form method="post" action="options.php">
					<?php settings_errors(); ?>
					<?php settings_fields( 'qwc_bulk_settings' ); ?>
					<?php do_settings_sections( 'qwc_bulk_page' ); ?>
					<?php submit_button(); ?>    
				</form>
				<form method="post" action="options.php">
					<?php settings_fields( 'quote_settings' ); ?>
					<?php do_settings_sections( 'qwc_page' ); ?>
					<?php submit_button(); ?>    
				</form>

			</div>
			<?php
		}

		/**
		 * Adds the content to Quotes->Settings page
		 *
		 * @since 1.5
		 */
		public static function qwc_settings() {

			if ( is_user_logged_in() ) {
				global $wpdb;
				// Check the user capabilities.
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'quote-wc' ) );
				}

				$action  = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
				$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

				$qwc_general_settings_class = '';
				switch ( $section ) {
					case '':
					case 'qwc_general_settings':
						$qwc_general_settings_class = 'current';
						break;
				}
				?>

				<h1><?php esc_html_e( 'Quote Settings' ); ?></h1>
				<ul class="subsubsub" id="qwc_general_settings_list">
					<li>
						<a href="admin.php?page=quote_settings&action=globalsettings&section=qwc_general_settings" class="<?php echo esc_attr( $qwc_general_settings_class ); ?>"><?php esc_html_e( 'General', 'quote-wc' );?> </a>
					</li>
					<?php do_action( 'qwc_add_views_general_settings', 'quote-wc' ); ?>
				</ul>
				<br><br>
				<?php
				switch ( $section ) {
					case '':
					case 'qwc_general_settings':
						self::qwc_general_settings_display();
						break;
				}
			}
		}

	}
}
$quotes_global_settings = new Quotes_Global_Settings();

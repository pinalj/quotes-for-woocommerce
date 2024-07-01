<?php
/**
 * TechnoVama
 *
 * Plugin Tracking Class.
 *
 * @author      TechnoVama
 * @package     TechnoVama
 * @category    Classes
 * @since       1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Vama_Plugin_Tracking' ) ) {

	/**
	 * Plugin Tracking.
	 *
	 * @since 1.0
	 */
	class Vama_Plugin_Tracking {

		/**
		 * Version.
		 *
		 * @var string $version
		 */
		private $version = '';

		/**
		 * API Url.
		 *
		 * @var string $api_url
		 */
		protected $api_url = 'https://tracking.technovama.com/v1/';

		/**
		 * Plugin Name.
		 *
		 * @var string $plugin_name
		 */
		private $plugin_name = '';

		/**
		 * Plugin Short Name.
		 *
		 * @var string $plugin_short_name
		 */
		private $plugin_short_name = '';

		/**
		 * Plugin Locale.
		 *
		 * @var string $plugin_locale
		 */
		private $plugin_locale = '';

		/**
		 * Blog Link.
		 *
		 * @var string $blog_link
		 */
		private $blog_link = '';

		/**
		 * Construct
		 *
		 * @param array $options Options.
		 */
		public function __construct( $options ) {

			if ( ! $this->init_vars( $options ) ) {
				return;
			}

			// Add admin notice.
			add_action( 'admin_notices', array( &$this, 'display_tracker_html_template' ) );
			// Initalize the tracker.
			add_action( 'admin_init', array( &$this, 'init_tracker' ) );
			// Initiate data sending.
			add_action( $this->plugin_short_name . '_tracker_send_event', array( &$this, 'send_tracking_data' ) );
			// Dismiss notice ajax call.
			add_action( 'wp_ajax_' . $this->plugin_short_name . '_tracker_dismiss_notice', array( &$this, 'dismiss_notice' ) );
			// Add Action Scheduler Library.
			add_action( 'plugins_loaded', array( &$this, 'vama_load_as_library' ) );
			$this->schedule_action();
		}

		/**
		 * Initialize variables from options array.
		 *
		 * @param array $options Options.
		 *
		 * @since 1.0
		 */
		public function init_vars( $options ) {

			if ( ! is_array( $options ) ) {
				return false;
			}

			if ( empty( array_diff( array( 'plugin_name', 'plugin_locale', 'plugin_short_name', 'version', 'blog_link' ), $options ) ) ) {
				return false;
			}

			$this->plugin_name       = $options['plugin_name'];
			$this->plugin_locale     = $options['plugin_locale'];
			$this->plugin_short_name = $options['plugin_short_name'];
			$this->version           = $options['version'];
			$this->blog_link         = $options['blog_link'];

			return true;
		}

		/**
		 * Load the Action Scheduler library.
		 */
		public static function vama_load_as_library() {
			if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php' ) ) {
				require_once WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php';
			}
		}
		/**
		 * Action Scheduler.
		 */
		public function schedule_action() {
			if ( ! as_next_scheduled_action( $this->plugin_short_name . '_tracker_send_event' ) ) {
				as_schedule_recurring_action( time() + 60, 604800, $this->plugin_short_name . '_tracker_send_event' );
			}
		}

		/**
		 * Display the tracking notice.
		 */
		public function display_tracker_html_template() {

			$current_screen = get_current_screen();

			if ( 'page' === get_post_type() || 'post' === get_post_type() || ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) || ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) || ! apply_filters( $this->plugin_short_name . '_tracker_display_notice', true ) ) {
				return;
			}

			echo '<input type="hidden" id="admin_url" value="' . esc_url( get_admin_url() ) . '"/>';

			if ( '' === get_option( $this->plugin_short_name . '_allow_tracking', '' ) ) { ?>
				<div class="<?php echo esc_attr( $this->plugin_short_name ); ?>-message <?php echo esc_attr( $this->plugin_short_name ); ?>-tracker notice notice-info is-dismissible" style="position: relative;">
					<div style="position: absolute;"><img class="site-logo" style="width: 80%; margin-left: -1em; margin-top: 1.4em;" src= "<?php echo esc_url( $this->api_url . 'assets/plugin-tracking/images/site-logo.png?v=' . $this->version ); ?> "></div>
					<p style="margin: 10px 0 10px 130px; font-size: medium;">
							<?php print( __( 'Want to help make ' . $this->plugin_name . ' even more awesome? Allow ' . $this->plugin_name . ' to collect non-sensitive diagnostic data and usage information. <a href="' . $this->blog_link . '">Find out more</a>.', $this->plugin_locale ) ); //phpcs:ignore ?>
					</p>
					<p class="submit">
						<a class="button-primary button button-large" href="<?php echo esc_url( wp_nonce_url( add_query_arg( $this->plugin_short_name . '_tracker_optin', 'true' ), $this->plugin_short_name . '_tracker_optin', $this->plugin_short_name . '_tracker_nonce' ) ); ?>"><?php esc_html_e( 'Allow', $this->plugin_locale ); //phpcs:ignore ?></a>
						<a class="button-secondary button button-large skip"  href="<?php echo esc_url( wp_nonce_url( add_query_arg( $this->plugin_short_name . '_tracker_optout', 'true' ), $this->plugin_short_name . '_tracker_optout', $this->plugin_short_name . '_tracker_nonce' ) ); ?>"><?php esc_html_e( 'No thanks', $this->plugin_locale ); //phpcs:ignore ?></a>
					</p>
				</div>
				<?php
			}
		}
		/**
		 * Initiates the tracker and sends plugin data to the tracking server.
		 */
		public function init_tracker() {

			if ( ! isset( $_GET[ $this->plugin_short_name . '_tracker_nonce' ] ) ) {
				return;
			}

			$tracker_option = isset( $_GET[ $this->plugin_short_name . '_tracker_optin' ] ) ? $this->plugin_short_name . '_tracker_optin' : ( isset( $_GET[ $this->plugin_short_name . '_tracker_optout' ] ) ? $this->plugin_short_name . '_tracker_optout' : '' ); // phpcs:ignore

			if ( '' === $tracker_option || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $this->plugin_short_name . '_tracker_nonce' ] ) ), $tracker_option ) ) {
				return;
			}

			$user_choice = isset( $_GET[ $this->plugin_short_name . '_tracker_optin' ] ) ? 'yes' : 'no';
			update_option( $this->plugin_short_name . '_allow_tracking', $user_choice );
			$this->send_tracking_data();
			do_action( $this->plugin_short_name . '_init_tracker_completed' );
		}

		/**
		 * Called when the dismiss icon is clicked on the notice.
		 */
		public function dismiss_notice() {
			if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['security'] ) || ( isset( $_POST['security'] ) && ! wp_verify_nonce( sanitize_key( $_POST['security'] ), 'tracking_notice' ) ) ) {
				die( 'Security check failed' );
			}
			update_option( $this->plugin_short_name . '_allow_tracking', 'dismissed' );
			$this->send_tracking_data();

			return 'success';
		}

		/**
		 * Send the Tracking Data.
		 *
		 * @since 1.0
		 */
		public function send_tracking_data() {

			$allow_tracking = get_option( $this->plugin_short_name . '_allow_tracking', '' );

			if ( '' === $allow_tracking ) {
				return;
			}

			$last_sent_time = apply_filters( $this->plugin_short_name . '_tracker_last_send_time', get_option( $this->plugin_short_name . '_tracker_last_send', false ) );

			// Send a maximum of once per week.
			if ( $last_sent_time && $last_sent_time > apply_filters( 'vama_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
				return;
			}

			// Update the last sent time.
			update_option( $this->plugin_short_name . '_tracker_last_send', time() );

			$params = array(
				'url'         => home_url(),
				'plugin_name' => $this->plugin_name,
				'plugin_slug' => $this->plugin_short_name,
				'action'      => 'tracking',
			);

			if ( 'yes' === $allow_tracking ) {

				// Add a 1 hour delay to ensure multiple requests are not sent at one go.
				if ( $last_sent_time && $last_sent_time > strtotime( '-1 hour' ) ) {
					return;
				}
				$params['tracking_usage'] = get_option( 'qwc_allow_tracking' );

				$params = array_merge(
					$params,
					$this->tracking_data()
				);

			} else {
				$params['tracking_usage'] = 1;
				$params['email']          = apply_filters( 'vama_tracker_admin_email', get_option( 'admin_email' ) );
				$params                   = apply_filters( 'vama_tracker_opt_out_data', $params );
			}

			wp_safe_remote_post(
				$this->api_url,
				array(
					'method'      => 'POST',
					'timeout'     => 60,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => false,
					'headers'     => array( 'user-agent' => 'WordPress/' . md5( esc_url( home_url( '/' ) ) ) . ';' ),
					'body'        => wp_json_encode( $params ),
					'cookies'     => array(),
				)
			);
		}

		/**
		 * Generates the Tracking Data.
		 *
		 * @since 1.0
		 */
		public function tracking_data() {

			global $wpdb;

			$data = array();

			// General site info.
			$data['url']   = home_url();
			$data['email'] = apply_filters( 'vama_tracker_admin_email', get_option( 'admin_email' ) );

			// WordPress Info.
			$data['wp'] = array(
				'debug_mode'      => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No',
				'locale'          => get_locale(),
				'wp_version'      => get_bloginfo( 'version' ),
				'multisite'       => is_multisite() ? 'Yes' : 'No',
				'blogdescription' => get_option( 'blogdescription' ),
				'blogname'        => get_option( 'blogname' ),
				'wc_city'         => get_option( 'woocommerce_store_city' ),
				'wc_country'      => get_option( 'woocommerce_default_country' ),
			);

			// Theme Info.
			$theme_data         = wp_get_theme();
			$data['theme_info'] = array(
				'theme_name'    => $theme_data->get( 'Name' ),
				'theme_version' => $theme_data->get( 'Version' ),
				'child_theme'   => is_child_theme() ? 'Yes' : 'No',
			);

			// Server Info.
			$data['server'] = array(
				'mysql_version'        => $wpdb->db_version(),
				'php_max_upload_size'  => size_format( wp_max_upload_size() ),
				'php_default_timezone' => date_default_timezone_get(),
			);

			if ( isset( $_SERVER['SERVER_SOFTWARE'] ) && ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
				$data['server']['software'] = sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) );
			}

			if ( function_exists( 'phpversion' ) ) {
				$data['server']['php_version'] = phpversion();
			}

			// Plugin info.
			if ( ! function_exists( 'get_plugins' ) ) {
				include ABSPATH . '/wp-admin/includes/plugin.php'; // Ensure get_plugins function is loaded.
			}

			$plugins             = get_plugins();
			$active_plugins_keys = get_option( 'active_plugins', array() );
			$active_plugins      = array();

			foreach ( $plugins as $k => $v ) {

				// Take care of formatting the data how we want it.
				$formatted         = array();
				$formatted['name'] = wp_strip_all_tags( $v['Name'] );

				if ( isset( $v['Version'] ) ) {
					$formatted['version'] = wp_strip_all_tags( $v['Version'] );
				}

				if ( isset( $v['Author'] ) ) {
					$formatted['author'] = wp_strip_all_tags( $v['Author'] );
				}

				if ( isset( $v['Network'] ) ) {
					$formatted['network'] = wp_strip_all_tags( $v['Network'] );
				}

				if ( isset( $v['PluginURI'] ) ) {
					$formatted['plugin_uri'] = wp_strip_all_tags( $v['PluginURI'] );
				}

				if ( in_array( $k, $active_plugins_keys, true ) ) {
					// Remove active plugins from list so we can show active and inactive separately.
					unset( $plugins[ $k ] );
					$active_plugins[ $k ] = $formatted;
				} else {
					$plugins[ $k ] = $formatted;
				}
			}

			$data['active_plugins']    = $active_plugins;
			$data['inactive_plugins']  = $plugins;
			$data['wc_plugin_version'] = WC()->version; // WooCommerce version.

			return apply_filters( $this->plugin_short_name . '_tracker_data', $data );
		}
	}
}

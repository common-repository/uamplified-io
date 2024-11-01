<?php
/**
 * Plugin Name: uamplified.io
 * Description: Connect your website with your uamplified.io account.
 * Version: 1.1
 * Author: uamplified.io
 * Author URI: https//uamplified.io
 * Requires at least: WP 4.9
 * Tested up to: WP 4.9.8
 * Text Domain: uamplified
 * Domain Path: /lang
 * License: GPL
 */
if ( ! class_exists( 'uamplified_io' ) ) :
	final class uamplified_io {

		// Plugin Version
		public $version             = '1.1';

		// Plugin Slug
		public $slug                = 'uamplified-io';

		// Plugin name
		public $plugin_name         = 'uamplified-io';

		// Plugin file
		public $plugin              = '';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		/**
		 * Setup Instance
		 * @since	1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since	1.0
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Not allowed
		 * @since	1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Define
		 * @since	1.0
		 * @version 1.0
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 * @since	1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 * @since	1.0
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();
			$this->includes();
			$this->wordpress();

			$this->plugin     = $this->slug . '/' . $this->slug . '.php';

		}

		/**
		 * Define Constants
		 * @since	1.0
		 * @version 1.0
		 */
		private function define_constants() {

			$this->define( 'UAMPLIFIED_IO_VERSION',       $this->version );
			$this->define( 'UAMPLIFIED_IO_SLUG',          $this->slug );

			// Maximum number of items to show in results. Controlled by uamplified.io
			$this->define( 'UAMPLIFIED_IO_MAX_ITEMS',     20 );

			$this->define( 'UAMPLIFIED_IO_MENU_VERSION',  '1.0' );

			// If set to true, the menu item post type will become visible in the admin menu.
			$this->define( 'UAMPLIFIED_IO_MENU_DEBUG',    false );

			// If set to true, the alternate cron will only trigger on page loads in the wp-admin area.
			$this->define( 'UAMPLIFIED_IO_CRON_IN_ADMIN', false );

			$this->define( 'UAMPLIFIED_IO',               __FILE__ );
			$this->define( 'UAMPLIFIED_IO_ROOT_DIR',      plugin_dir_path( UAMPLIFIED_IO ) );
			$this->define( 'UAMPLIFIED_IO_INCLUDES_DIR',  UAMPLIFIED_IO_ROOT_DIR . 'includes/' );
			$this->define( 'UAMPLIFIED_IO_ASSETS_DIR',    UAMPLIFIED_IO_ROOT_DIR . 'assets/' );

		}

		/**
		 * Include Plugin Files
		 * @since	1.0
		 * @version 1.0
		 */
		public function includes() {

			$this->file( UAMPLIFIED_IO_INCLUDES_DIR . 'uamp-functions.php' );
			$this->file( UAMPLIFIED_IO_INCLUDES_DIR . 'uamp-widgets.php' );
			$this->file( UAMPLIFIED_IO_INCLUDES_DIR . 'uamp-admin.php' );
			$this->file( UAMPLIFIED_IO_INCLUDES_DIR . 'uamp-menu-items.php' );
			$this->file( UAMPLIFIED_IO_INCLUDES_DIR . 'uamp-help.php' );

		}

		/**
		 * WordPress
		 * @since	1.0
		 * @version 1.0
		 */
		public function wordpress() {

			register_activation_hook(   UAMPLIFIED_IO, array( __CLASS__, 'activate_plugin' ) );
			register_deactivation_hook( UAMPLIFIED_IO, array( __CLASS__, 'deactivate_plugin' ) );
			register_uninstall_hook(    UAMPLIFIED_IO, array( __CLASS__, 'uninstall_plugin' ) );

			uamplified_io_admin::instance();
			uamplified_io_menu_items::instance();

			add_action( 'init',                     array( $this, 'load_textdomain' ) );
			add_action( 'init',                     array( $this, 'register_scripts' ) );
			add_action( 'widgets_init',             array( $this, 'load_widgets' ) );

			add_action( 'wp_footer',                array( $this, 'wp_footer' ) );

			add_action( 'wp_footer',                array( $this, 'maybe_load_cron' ) );
			add_action( 'uamplified_cron_hourly',   array( $this, 'cron_hourly' ), 1 );
			add_action( 'uamplified_cron_daily',    array( $this, 'cron_daily' ), 1 );

		}

		/**
		 * Load Textdomain
		 * @since	1.0
		 * @version 1.0
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), 'uamplified' );

			load_textdomain( 'uamplified', WP_LANG_DIR . '/' . $this->slug . '/uamplified-' . $locale . '.mo' );
			load_plugin_textdomain( 'uamplified', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		}

		/**
		 * Register Scripts
		 * @since	1.0
		 * @version 1.0
		 */
		public function register_scripts() {

			wp_register_style( 'uamp-widgets',         plugins_url( 'assets/css/uamp-widgets.css', UAMPLIFIED_IO ), array(), '1.0', 'all' );

			wp_register_script( 'uamplified-settings', plugins_url( 'assets/js/uamp-settings.js', UAMPLIFIED_IO ), array( 'jquery' ), '1.0', true );
			wp_register_script( 'uamplified-tools',    plugins_url( 'assets/js/uamp-tools.js', UAMPLIFIED_IO ), array( 'jquery' ), '1.0', true );

		}

		/**
		 * Load Widgets
		 * @since	1.0
		 * @version 1.0
		 */
		public function load_widgets() {

			if ( empty( get_connected_uamplified_products() ) ) return;

			register_widget( 'uamplified_io_product_listen' );
			register_widget( 'uamplified_io_product_talk' );
			register_widget( 'uamplified_io_product_launch' );

		}

		/**
		 * WordPress Footer
		 * @since	1.0
		 * @version 1.0
		 */
		public function wp_footer() {

			$settings = get_uamplified_settings();

			// Unless disabled, load the widgets stylesheet
			if ( $settings['disable_styling'] == 0 )
				wp_enqueue_style( 'uamp-widgets' );

		}

		/**
		 * Alternate CRON
		 * Triggered by visits to the website.
		 * @since	1.0
		 * @version 1.0
		 */
		public function maybe_load_cron() {

			$settings = get_uamplified_settings();

			if ( $settings['alternate_cron'] == 1 ) {

				if ( UAMPLIFIED_IO_CRON_IN_ADMIN && ! is_admin() ) return;

				$now           = time();
				$next_schedule = get_next_uamplified_cron_schedule( 'hourly' );
				if ( $now <= $next_schedule ) {

					$this->cron_hourly();

				}

				$next_schedule = get_next_uamplified_cron_schedule( 'daily' );
				if ( $now <= $next_schedule ) {

					$this->cron_daily();

				}

			}

		}

		/**
		 * CRON: Hourly Tasks
		 * @since	1.0
		 * @version 1.0
		 */
		public function cron_hourly() {

			wp_clear_scheduled_hook( 'uamplified_cron_hourly' );

			$next_timestamp = time() + ( 1 * HOUR_IN_SECONDS );
			wp_schedule_single_event( $next_timestamp, 'uamplified_cron_hourly' );
			update_option( 'uamp-cron-next-hourly', $next_timestamp );

			do_action( 'uamplified_io_hourly_cron' );

			delete_expired_uamplified_cache( 'hourly' );

		}

		/**
		 * CRON: Daily Tasks
		 * @since	1.0
		 * @version 1.0
		 */
		public function cron_daily() {

			wp_clear_scheduled_hook( 'uamplified_cron_daily' );

			$next_timestamp = time() + ( 1 * DAY_IN_SECONDS );
			wp_schedule_single_event( $next_timestamp, 'uamplified_cron_daily' );
			update_option( 'uamp-cron-next-daily', $next_timestamp );

			do_action( 'uamplified_io_daily_cron' );

			delete_expired_uamplified_cache( 'daily' );

		}

		/**
		 * Activate
		 * @since	1.0
		 * @version 1.0
		 */
		public static function activate_plugin() {

			maybe_schedule_uamplified_cron( 'hourly' );
			maybe_schedule_uamplified_cron( 'daily' );

		}

		/**
		 * Deactivate
		 * @since	1.0
		 * @version 1.0
		 */
		public static function deactivate_plugin() {

			maybe_schedule_uamplified_cron( 'hourly', false );
			maybe_schedule_uamplified_cron( 'daily', false );

			delete_all_uamplified_cache( 'hourly' );
			delete_all_uamplified_cache( 'daily' );

		}

		/**
		 * Uninstall
		 * @since	1.0
		 * @version 1.0
		 */
		public static function uninstall_plugin() {

			delete_all_uamplified_data();

			delete_all_uamplified_cache( 'hourly' );
			delete_all_uamplified_cache( 'daily' );

			maybe_schedule_uamplified_cron( 'hourly', false );
			maybe_schedule_uamplified_cron( 'daily', false );

		}

	}
endif;

function uamplified_io_plugin() {
	return uamplified_io::instance();
}
uamplified_io_plugin();

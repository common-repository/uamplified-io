<?php
if ( ! defined( 'UAMPLIFIED_IO_VERSION' ) ) exit;

/**
 * 
 * @since 1.0
 * @version 1.0
 */
if ( ! class_exists( 'uamplified_io_admin' ) ) :
	final class uamplified_io_admin {

		public $version             = '1.0';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		/**
		 * Setup Instance
		 * @since 1.0
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
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			add_action( 'admin_init',                           array( $this, 'register_settings' ) );
			add_action( 'admin_menu',                           array( $this, 'admin_menu' ) );

			add_action( 'uamplified_io_daily_cron',             array( $this, 'cron_daily_sync_products' ) );

			add_action( 'wp_ajax_uamplified-verify-api-key',    array( $this, 'ajax_validate_api_key' ) );
			add_action( 'wp_ajax_uamplified-remove-api-key',    array( $this, 'ajax_remove_api_key' ) );
			add_action( 'wp_ajax_uamplified-sync-product',      array( $this, 'ajax_sync_product' ) );

			add_action( 'wp_ajax_uamplified-run-admin-tool',    array( $this, 'ajax_run_tool' ) );

		}

		/**
		 * Register Settings
		 * @since 1.0
		 * @version 1.0
		 */
		public function register_settings() {

			register_setting( 'uamplified-io', 'uamplified_io', array( $this, 'sanitize_settings' ) );

		}

		/**
		 * Sanitize Settings
		 * @since	1.0
		 * @version 1.1
		 */
		public function sanitize_settings( $new = array() ) {

			$current                = get_uamplified_settings();
			$now                    = time();

			$new['disable_styling'] = ( array_key_exists( 'disable_styling', $new ) ) ? $new['disable_styling'] : 0;
			$new['alternate_cron']  = ( array_key_exists( 'alternate_cron', $new ) ) ? $new['alternate_cron'] : 0;

			$site_url               = sanitize_text_field( $new['site_url'] );
			$site_url               = str_replace( array( 'http://', 'https://' ), '', rtrim( $site_url, '/' ) );
			$new['site_url']        = $site_url;

			// Changing the site url triggers a new sync
			if ( $current['site_url'] != $new['site_url'] && $new['site_url'] != '' ) {

				$products        = get_connected_uamplified_products();
				$synced_products = array();
				if ( ! empty( $products ) ) {
					foreach ( $products as $product_id => $product ) {

						if ( ! isset( $product->api_key ) ) continue;

						$sync                           = get_fresh_uamplified_product( $product->api_key, $new );
						if ( is_wp_error( $sync ) || ! isset( $sync->product_id ) ) continue;

						$sync->last_sync                = $now;
						$synced_products[ $product_id ] = $sync;

					}
				}
				update_option( 'uamplified_io_products', $synced_products );

			}

			return $new;

		}

		/**
		 * Admin Menu
		 * @since 1.0
		 * @version 1.0
		 */
		public function admin_menu() {

			$page = add_options_page(
				__( 'uamplified.io', 'uamplified' ),
				__( 'uamplified.io', 'uamplified' ),
				'manage_options',
				'uamplified',
				array( $this, 'screen_settings' )
			);

			add_action( 'load-'. $page, array( $this, 'load_settings_screen' ) );

			$page = add_management_page(
				__( 'uamplified.io', 'uamplified' ),
				__( 'uamplified.io', 'uamplified' ),
				'manage_options',
				'uamplified-tools',
				array( $this, 'screen_tools' )
			);

			add_action( 'load-'. $page, array( $this, 'load_tools_screen' ) );

		}

		/**
		 * Load Screen Settings
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_settings_screen() {

			wp_localize_script(
				'uamplified-settings',
				'Uamp',
				array(
					'ajaxurl'    => admin_url( 'admin-ajax.php' ),
					'verify'     => wp_create_nonce( 'uamplified-verify-api-key' ),
					'remove'     => wp_create_nonce( 'uamplified-remove-api-key' ),
					'sync'       => wp_create_nonce( 'uamplified-sync-product' ),
					'validating' => esc_js( __( 'Validating ...', 'uamplified' ) ),
					'removing'   => esc_js( __( 'Removing ...', 'uamplified' ) ),
					'syncing'    => esc_js( __( 'Updating ...', 'uamplified' ) ),
					'ajaxerror'  => esc_js( __( 'Communications error. Please refresh this page and try again.', 'uamplified' ) )
				)
			);
			wp_enqueue_script( 'uamplified-settings' );

		}

		/**
		 * Screen Settings
		 * @since 1.0
		 * @version 1.0
		 */
		public function screen_settings() {

			if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access Denied' );

			$settings          = get_uamplified_settings();
			$products          = get_connected_uamplified_products();

			$saved_hourly_keys = get_option( 'uamp-hourly-keys', array() );
			$saved_daily_keys  = get_option( 'uamp-daily-keys', array() );

?>
<div class="wrap">
	<h1><?php _e( 'Uamplified Settings', 'uamplified' ); ?></h1>
	<form method="post" action="options.php">

		<?php settings_fields( 'uamplified-io' ); ?>

		<p><?php _e( 'Please fill out the required fields below and save, before connecting your products further down.', 'uamplified' ); ?></p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="uamp-site-url"><?php _e( 'Company Domain', 'uamplified' ); ?></label>
					</th>
					<td>
						<input type="text" name="uamplified_io[site_url]" id="uamp-site-url" size="50" placeholder="<?php _e( 'required', 'uamplified' ); ?>" class="short-text" value="<?php echo esc_attr( $settings['site_url'] ); ?>" />
						<p class="description" id="tagline-description"><?php _e( 'Please enter the company domain for your uamplified.io hosted website.', 'uamplified' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="uamp-disable-css"><?php _e( 'Widget Styling', 'uamplified' ); ?></label>
					</th>
					<td>
						<label for="uamp-disable-css"><input name="uamplified_io[disable_styling]"<?php checked( $settings['disable_styling'], 1 ); ?> type="checkbox" id="uamp-disable-css" value="1"> <?php _e( 'Disable the built-in widget styling.', 'uamplified' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="uamp-alt-cron"><?php _e( 'Alternate CRON', 'uamplified' ); ?></label>
					</th>
					<td>
						<label for="uamp-alt-cron"><input name="uamplified_io[alternate_cron]"<?php checked( $settings['alternate_cron'], 1 ); ?> type="checkbox" id="uamp-alt-cron" value="1"> <?php _e( 'Use the alternate CRON to clear cached data.', 'uamplified' ); ?></label>
						<p class="description" id="tagline-description"><?php _e( 'Make sure you select this if you have the built-in WordPress CRON disabled.', 'uamplified' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Save Changes', 'uamplified' ), 'primary' ); ?>

		<hr />
		<h3><?php _e( 'Connected Products', 'uamplified' ); ?></h3>

		<p><?php printf( __( 'To start using this plugin, first we need to connect your website with your uamplified.io products. Please enter the API Key for the product you wish to connect below and click Validate. You can find this key in your %s. Your product details, such as name, url or active modules will be synced once a day to keep it up to date. You can however sync a product if you made changes on the uamplified website by using the Sync Product button below.', 'uamplified' ), sprintf( '<a href="https://app.uamplified.io" target="_blank">%s</a>', __( 'uamplified.io account', 'uamplified' ) ) ); ?></p>
		<table class="form-table" id="product-api-table">
			<tbody>
<?php

			if ( ! empty( $products ) ) {
				foreach ( $products as $product_id => $product ) {

					echo generate_api_key_table_row( $product );

				}
			}

?>
				<tr id="uamplified-product-new">
					<th scope="row">
						<label for="uamp-api-key0"><?php _e( 'Product API Key', 'uamplified' ); ?></label>
					</th>
					<td>
						<input type="text" id="uamp-api-key-new" placeholder="<?php _e( 'required', 'uamplified' ); ?>" class="regular-text" value="" /> <button type="button" class="button button-secondary validate-product-api-key"><?php _e( 'Validate', 'uamplified' ); ?></button>
					</td>
				</tr>
			</tbody>
		</table>

	</form>
</div>
<?php

		}

		/**
		 * Load Tools Settings
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_tools_screen() {

			wp_localize_script(
				'uamplified-tools',
				'Uamp',
				array(
					'ajaxurl'    => admin_url( 'admin-ajax.php' ),
					'token'      => wp_create_nonce( 'uamplified-run-admin-tool' ),
					'running'    => esc_js( __( 'Running ...', 'uamplified' ) ),
					'ajaxerror'  => esc_js( __( 'Communications error. Please refresh this page and try again.', 'uamplified' ) ),
					'confirm'    => array(
						'clear-widget-cache'     => esc_js( __( 'Are you sure you want to clear all cached results?', 'uamplified' ) ),
						'resedule-cron-jobs'     => esc_js( __( 'Are you sure you want to re-schedule all cron jobs?', 'uamplified' ) ),
						'delete-uamplified-data' => esc_js( __( 'Are you sure you want to delete all data?', 'uamplified' ) )
					)
				)
			);
			wp_enqueue_script( 'uamplified-tools' );

		}

		/**
		 * Tools Settings
		 * @since 1.0
		 * @version 1.0
		 */
		public function screen_tools() {

			if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Access Denied' );

			$settings    = get_uamplified_settings();
			$total_cache = get_total_cached_uamplified_data();
			$hourly_job  = get_next_uamplified_cron_schedule( 'hourly' );
			$daily_job   = get_next_uamplified_cron_schedule( 'daily' );

?>
<div class="wrap">
	<h1><?php _e( 'Uamplified Tools', 'uamplified' ); ?></h1>
<?php

			if ( $settings['alternate_cron'] == 1 )
				echo '<div id="message" class="warning notice notice-warning is-dismissible"><p>You have selected to use the alternate CRON system. The reschedule tool has been disabled.</p></div>';

?>
	<p><?php _e( 'We have put together some tools to help you resolve scheduling and caching issues for the uamplified.io plugin.', 'uamplified' ); ?></p>
	<table class="widefat importers striped">
		<tbody>
			<tr class="importer-item">
				<td class="import-system">
					<span class="importer-title"><?php _e( 'Tool: Clear Widget Cache', 'uamplified' ); ?></span>
					<span class="importer-action"><a href="javascript:void(0);" class="trigger-uamp-tool" id="clear-widget-cache"><?php _e( 'Delete Cache', 'uamplified' ); ?></a>
				</td>
				<td class="desc">
					<span class="importer-desc"><?php _e( 'Clearing your widget cache will force all widgets to load a fresh set of data from your uamplified.io account.', 'uamplified' ); ?></span>
					<hr />
					<span class="importer-desc"><?php _e( 'Total cached results:', 'uamplified' ); ?> <strong id="total-cached-results"><?php echo $total_cache; ?></strong></span>
				</td>
			</tr>
			<tr class="importer-item">
				<td class="import-system">
					<span class="importer-title"><?php _e( 'Tool: Reschedule CRON Jobs', 'uamplified' ); ?></span>
					<?php if ( ! $settings['alternate_cron'] ) : ?><span class="importer-action"><a href="javascript:void(0);" class="trigger-uamp-tool" id="resedule-cron-jobs"><?php _e( 'Reschedule', 'uamplified' ); ?></a><?php endif; ?>
				</td>
				<td class="desc">
					<span class="importer-desc"><?php _e( 'Use this option if you experience issues with the uamplified hourly and daily cron schedules.', 'uamplified' ); ?></span>
					<hr />
					<span class="importer-desc"><?php _e( 'Next Hourly Cron Job will run in', 'uamplified' ); ?> <strong id="current-hourly-schedule"><?php if ( $hourly_job !== false ) echo human_time_diff( $hourly_job ); else _e( 'Unknown', 'uamplified' ); ?></strong></span>
					<span class="importer-desc"><?php _e( 'Next Daily Cron Job will run in', 'uamplified' ); ?> <strong id="current-hourly-schedule"><?php if ( $daily_job !== false ) echo human_time_diff( $daily_job ); else _e( 'Unknown', 'uamplified' ); ?></strong></span>
				</td>
			</tr>
			<tr class="importer-item">
				<td class="import-system">
					<span class="importer-title"><?php _e( 'Tool: Delete uamplified.io Data', 'uamplified' ); ?></span>
					<span class="importer-action"><a href="javascript:void(0);" class="trigger-uamp-tool" id="delete-uamplified-data"><?php _e( 'Delete Data', 'uamplified' ); ?></a>
				</td>
				<td class="desc">
					<span class="importer-desc"><?php _e( 'Warning. Using this option will delete all plugin related data from your database. This includes your plugin settings, product data, authentication keys and module data. This action can not be undone!', 'uamplified' ); ?></span>
				</td>
			</tr>
		</tbody>
	</table>
</div>
<?php

		}

		/* Cron Jobs */

		/**
		 * CRON: Sync Products
		 * Once a day, we sync our product data to ensure we show any potential changes we might have made.
		 * @since 1.0
		 * @version 1.0
		 */
		public function cron_daily_sync_products() {

			// Allow override
			if ( defined( 'UAMPLIFIED_IO_DISABLE_PRODUCT_SYNC' ) && UAMPLIFIED_IO_DISABLE_PRODUCT_SYNC ) return;

			$settings    = get_uamplified_settings();
			if ( $settings['site_url'] != '' ) {

				$products = get_connected_uamplified_products();
				if ( ! empty( $products ) ) {
					foreach ( $products as $product_id => $product ) {

						$uamplified = get_fresh_uamplified_product( $product->api_key );

						if ( ! is_wp_error( $uamplified ) && isset( $uamplified->product_id ) ) {

							$uamplified->last_sync = time();
							add_connected_uamplified_product( $uamplified );

							update_uamplified_menu_items( $uamplified );

						}

					}
				}

			}

		}

		/* AJAX Call Handlers */

		/**
		 * AJAX: Validate API Key
		 * @since	1.0
		 * @version 1.0.1
		 */
		public function ajax_validate_api_key() {

			global $wp_version;

			check_ajax_referer( 'uamplified-verify-api-key', 'token' );

			if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Access Denied' );

			$api_key               = sanitize_text_field( $_POST['api'] );
			if ( $api_key == '' )
				wp_send_json_error( esc_js( __( 'Please enter an API Key.', 'uamplified' ) ) );

			$settings              = get_uamplified_settings();
			if ( $settings['site_url'] == '' )
				wp_send_json_error( esc_js( __( 'Please enter your company domain before doing this action.', 'uamplified' ) ) );

			$exists                = false;
			$products              = get_connected_uamplified_products();
			if ( ! empty( $products ) ) {
				foreach ( $products as $product_id => $product ) {
					if ( $product->api_key == $api_key ) {
						$exists = true;
						break;
					}
				}
			}

			if ( $exists )
				wp_send_json_error( esc_js( __( 'This API Key has already been validated.', 'uamplified' ) ) );

			$uamplified            = get_fresh_uamplified_product( $api_key );

			if ( is_wp_error( $uamplified ) )
				wp_send_json_error( esc_js( $uamplified->get_error_message() ) );

			if ( ! isset( $uamplified->product_id ) )
				wp_send_json_error( esc_js( __( 'Invalid API Key. Validation failed.', 'uamplified' ) ) );

			$uamplified->api_key   = $api_key;
			$uamplified->last_sync = current_time( 'timestamp' );
			if ( ! add_connected_uamplified_product( $uamplified ) )
				wp_send_json_error( esc_js( __( 'Could not save the new key. Please refresh this page and try again.', 'uamplified' ) ) );

			update_uamplified_menu_items( $uamplified );

			$uamplified->last_sync = 0;
			wp_send_json_success( generate_api_key_table_row( $uamplified ) );

		}

		/**
		 * AJAX: Remove API Key
		 * @since 1.0
		 * @version 1.0
		 */
		public function ajax_remove_api_key() {

			global $wp_version;

			check_ajax_referer( 'uamplified-remove-api-key', 'token' );

			if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Access Denied' );

			$settings    = get_uamplified_settings();
			if ( $settings['site_url'] == '' )
				wp_send_json_error( esc_js( __( 'Please enter your company domain before doing this action.', 'uamplified' ) ) );

			$product_id  = absint( $_POST['productid'] );
			if ( $product_id === 0 )
				wp_send_json_error( esc_js( __( 'API Key not found.', 'uamplified' ) ) );

			delete_connected_uamplified_product( $product_id );

			delete_uamplified_menu_items( $product_id );

			wp_send_json_success();

		}

		/**
		 * AJAX: Sync Product
		 * @since	1.0
		 * @version 1.0.1
		 */
		public function ajax_sync_product() {

			global $wp_version;

			check_ajax_referer( 'uamplified-sync-product', 'token' );

			if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Access Denied' );

			$settings              = get_uamplified_settings();
			if ( $settings['site_url'] == '' )
				wp_send_json_error( esc_js( __( 'Please enter your company domain before doing this action.', 'uamplified' ) ) );

			$product_id            = absint( $_POST['productid'] );
			if ( $product_id === 0 )
				wp_send_json_error( esc_js( __( 'Invalid product ID. Please refresh this page and try again.', 'uamplified' ) ) );

			$product               = get_uamplified_product( $product_id );
			if ( $product === false )
				wp_send_json_error( esc_js( __( 'Product not found. Please refresh this page and try again.', 'uamplified' ) ) );

			$uamplified            = get_fresh_uamplified_product( $product->api_key );

			if ( is_wp_error( $uamplified ) )
				wp_send_json_error( esc_js( $uamplified->get_error_message() ) );

			if ( ! isset( $uamplified->product_id ) )
				wp_send_json_error( esc_js( __( 'Invalid API Key. Sync failed. Please refresh this page and try again.', 'uamplified' ) ) );

			$uamplified->api_key   = $product->api_key;
			$uamplified->last_sync = time();
			if ( ! add_connected_uamplified_product( $uamplified ) )
				wp_send_json_error( esc_js( __( 'Could not save the new key. Please refresh this page and try again.', 'uamplified' ) ) );

			update_uamplified_menu_items( $uamplified );

			$uamplified->last_sync = 0;
			wp_send_json_success( generate_api_key_table_row( $uamplified ) );

		}

		/**
		 * AJAX: Run Tool
		 * @since 1.0
		 * @version 1.0
		 */
		public function ajax_run_tool() {

			check_ajax_referer( 'uamplified-run-admin-tool', 'token' );

			if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Access Denied' );

			$tool_id = sanitize_text_field( $_POST['toolid'] );
			if ( $tool_id == '' )
				wp_send_json_error( esc_js( __( 'Unknown tool id. Please refresh this page and try again.', 'uamplified' ) ) );

			if ( $tool_id == 'clear-widget-cache' ) {

				delete_all_uamplified_cache( 'hourly' );
				delete_all_uamplified_cache( 'daily' );

				wp_send_json_success( array( 'message' => esc_js( __( 'Widget caches successfully deleted.', 'uamplified' ) ), 'fields' => array( 'total-cached-results' => 0 ) ) );

			}

			elseif ( $tool_id == 'resedule-cron-jobs' ) {

				$hourly_job  = maybe_schedule_uamplified_cron( 'hourly' );
				$hourly_html = ( (int) $hourly_job > 0 ) ? human_time_diff( $hourly_job ) : esc_js( __( 'Not Scheduled', 'uamplified' ) );

				$daily_job   = maybe_schedule_uamplified_cron( 'daily' );
				$daily_html  = ( (int) $daily_job > 0 ) ? human_time_diff( $daily_job ) : esc_js( __( 'Not Scheduled', 'uamplified' ) );

				wp_send_json_success( array( 'message' => esc_js( __( 'Cron jobs successfully re-scheduled.', 'uamplified' ) ), 'fields' => array( 'current-hourly-schedule' => $hourly_html, 'current-hourly-schedule' => $daily_html ) ) );

			}

			elseif ( $tool_id == 'delete-uamplified-data' ) {

				delete_all_uamplified_data();

				delete_all_uamplified_cache( 'hourly' );
				delete_all_uamplified_cache( 'daily' );

				wp_send_json_success( array( 'message' => esc_js( __( 'All data was successfully deleted. If you have any uamplified widgets active, please remove them before disabling this plugin.', 'uamplified' ) ), 'fields' => false ) );

			}

			wp_send_json_error( esc_js( __( 'Unknown tool.', 'uamplified' ) ) );

		}

	}
endif;

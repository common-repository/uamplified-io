<?php
if ( ! defined( 'UAMPLIFIED_IO_VERSION' ) ) exit;

/**
 * Get uamplified Settings
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'uamp_add_contextual_help' ) ) :
	function uamp_add_contextual_help( $contextual_help, $screen_id ) {

		switch ( $screen_id ) {

			case 'tools_page_uamplified-tools' :

				get_current_screen()->set_help_sidebar( '<p><strong>For more information:</strong></p><p><a href="https://uamplified.io" target="_blank">uamplified.io</a></p><p><a href="https://doc.uamplified.io" target="_blank">Documentation</a></p><p><a href="https://app.uamplified.io" target="_blank">My uamplified.io Account</a></p>' );

				get_current_screen()->add_help_tab( array(
					'id'        => 'uamp-tools-intro',
					'title'     => __( 'Plugin Tools', 'uamplified' ),
					'content'   => '<p>' . __( 'We have put together a list of tools to help you with your uamplified.io integration. You should only use these tools when needed.', 'uamplified' ) . '</p>'
				) );

				get_current_screen()->add_help_tab( array(
					'id'        => 'uamp-tools-widget-cache',
					'title'     => __( 'Clear Widget Cache', 'uamplified' ),
					'content'   => '
<p>' . __( 'It would be impractical to request data from the uamplified.io servers for your products on every single page load. These calls can take time which in turn would affect your websites load time significantly. To avoid this, the plugin caches your requests and saves them for either one hour or one day. This includes data you see in your widgets, in your menu items or on the settings page.', 'uamplified' ) . '</p>
<p>' . __( 'Deleting these cache files will force your widgets and menu items to request a fresh set of data from the uamplified.io servers. If the servers are down at the time of the sync, your product data will remain the same but your widgets will show an error message until a successfully sync is achieved.', 'uamplified' ) . '</p>'
				) );

				get_current_screen()->add_help_tab( array(
					'id'        => 'uamp-tools-reschedule-cron',
					'title'     => __( 'Reschedule CRON Jobs', 'uamplified' ),
					'content'   => '
<p>' . __( 'To ensure that our cached data is kept up to date, they are scheduled to be deleted once an hour or one a day. This task is handled by WordPress Scheduled Events which is managed by the WP CRON.', 'uamplified' ) . '</p>
<p>' . __( 'Under normal conditions, the plugin will schedule in two CRON jobs. One that runs every hour and one that runs one day. If this is working correctly, you should see when they will run next below. If you see "Unknown", the jobs might failed to schedule or re-schedule properly or the WP CRON is disabled on your site. If you have selected to use the Alternate CRON in your settings, you might see "Unknown" until the first job triggers.', 'uamplified' ) . '</p>
<p>' . __( 'Click on the "Reschedule" link to re-schedule the two CRON jobs.', 'uamplified' ) . '</p>'
				) );

				get_current_screen()->add_help_tab( array(
					'id'        => 'uamp-tools-delete-data',
					'title'     => __( 'Delete uamplified.io Data', 'uamplified' ),
					'content'   => '<p>' . __( 'When you disable and select to delete this plugin here in the wp-admin area, the plugin will clean up after it\'s self by removing all your data automatically. You can however use this tool to reset your installation if you experience problems and want to start fresh. It is highly recommended that you remove menu-items and widgets you might be using before selecting to use this tool.', 'uamplified' ) . '</p>'
				) );

			break;

			case 'settings_page_uamplified' :


			break;

			case 'edit-uamp_menu' :
			case 'uamp_menu' :


			break;

		}

		return $contextual_help;

	}
endif;
add_filter( 'contextual_help', 'uamp_add_contextual_help', 10, 2 );

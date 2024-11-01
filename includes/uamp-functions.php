<?php
if ( ! defined( 'UAMPLIFIED_IO_VERSION' ) ) exit;

/**
 * Get uamplified Settings
 * @since	1.0
 * @version 1.1
 */
if ( ! function_exists( 'get_uamplified_settings' ) ) :
	function get_uamplified_settings() {

		$default = array(
			'site_url'        => '',
			'disable_styling' => 0,
			'alternate_cron'  => 0
		);

		$saved   = get_option( 'uamplified_io', $default );

		return shortcode_atts( $default, $saved );

	}
endif;

/**
 * Get Uamplified Data
 * Calls home to the companies uamplified.io hosted website to request data.
 * @since	1.0
 * @version 1.1
 */
if ( ! function_exists( 'get_uamplified_data' ) ) :
	function get_uamplified_data( $endpoint = '', $body = array(), $api_key = '' ) {

		// Can't do much without these items
		if ( $endpoint == '' || $api_key == '' )
			return new WP_Error( 'uamp', __( 'Invalid uamplified.io request.', 'uamplified' ) );

		// Need the API base url
		$settings     = get_uamplified_settings();
		if ( $settings['site_url'] == '' )
			return new WP_Error( 'uamp', __( 'Your site url is missing. No API calls can be made.', 'uamplified' ) );

		// Need to know what method we are looking to use
		$method       = ( count( explode( '/', $endpoint ) ) == 2 ) ? explode( '/', $endpoint )[0] : 'get';

		global $wp_version;

		// The base request required to comply with the API
		$request_args = array(
			'headers'    => array(
				'Content-Type'  => 'application/json',
				'User-Agent'    => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
				'Authorization' => 'Bearer ' . $api_key
			),
			'body'       => ''
		);

		// Posting
		if ( $method == 'post' ) {

			// When posting, we append a json encoded body
			$request_args['body'] = json_encode( $body );

			$request_url = 'https://' . $settings['site_url'] . '/api/' . $endpoint . '/';
			$response    = wp_remote_get( $request_url, $request_args );

		}

		// Getting
		else {

			// For GET requests we need to append to the url
			$request_url = add_query_arg( $body, 'https://' . $settings['site_url'] . '/api/' . $endpoint . '/' );
			$response    = wp_remote_get( $request_url, $request_args );

		}

		// Ups, something did not go well
		if ( is_wp_error( $response ) )
			return $response;

		$body         = json_decode( $response['body'] );

		// Things did not go well
		if ( isset( $body->error ) && $body->error )
			return new WP_Error( 'uamp', sprintf( __( 'Error %s. Message given: %s', 'uamplified' ), $body->code, $body->message ) );

		return $body;

	}
endif;

/**
 * Get uamplified Connected Products
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'get_connected_uamplified_products' ) ) :
	function get_connected_uamplified_products() {

		return get_option( 'uamplified_io_products', array() );

	}
endif;

/**
 * Add Connected Product
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'add_connected_uamplified_product' ) ) :
	function add_connected_uamplified_product( $product = '' ) {

		if ( ! is_object( $product ) ) return false;

		$products = get_connected_uamplified_products();

		$products[ $product->product_id ] = $product;

		update_option( 'uamplified_io_products', $products );

		return true;

	}
endif;

/**
 * Delete Connected Product
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'delete_connected_uamplified_product' ) ) :
	function delete_connected_uamplified_product( $product_id = 0 ) {

		$product_id   = absint( $product_id );
		if ( $product_id === 0 ) return false;

		$products     = get_connected_uamplified_products();
		if ( empty( $products ) ) return true;

		$new_products = array();
		foreach ( $products as $pid => $product ) {
			if ( $product_id == $pid ) continue;
			$new_products[ $pid ] = $product;
		}

		update_option( 'uamplified_io_products', $new_products );

		return true;

	}
endif;

/**
 * Get Uamplified.io Product
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'get_uamplified_product' ) ) :
	function get_uamplified_product( $product_id = 0 ) {

		$product_id = absint( $product_id );
		if ( $product_id === 0 ) return false;

		$products   = get_connected_uamplified_products();
		if ( ! array_key_exists( $product_id, $products ) ) return false;

		return $products[ $product_id ];

	}
endif;

/**
 * Get Fresh Uamplified.io Product
 * While the get_uamplified_product() function returns the cached data for a product, this
 * function requests a new object from the uamplified.io servers.
 * @since	1.0
 * @version 1.1
 */
if ( ! function_exists( 'get_fresh_uamplified_product' ) ) :
	function get_fresh_uamplified_product( $api_key = '', $settings = array() ) {

		if ( $api_key == '' ) return false;

		$uamplified = get_uamplified_data( 'get/product', array(), $api_key, $settings );

		return $uamplified;

	}
endif;

/**
 * Get Product Requests
 * Queries requests for a given product. Returns either a cached result or a fresh 
 * set of data from the uamplified.io servers.
 * @since	1.0
 * @version 1.1
 */
if ( ! function_exists( 'get_uamplified_product_requests' ) ) :
	function get_uamplified_product_requests( $args = array(), $api_key = '', $settings = array() ) {

		$args       = shortcode_atts( array(
			'page'       => 1,
			'number'     => 10,
			'orderby'    => 'date',
			'order'      => 'desc',
			'cache'      => false
		), $args );

		if ( $args['number'] > UAMPLIFIED_IO_MAX_ITEMS ) $args['number'] = UAMPLIFIED_IO_MAX_ITEMS;

		$results         = array();
		$settings        = ( empty( $settings ) ) ? get_uamplified_settings() : $settings;
		$cache_key       = 'uamp-cache-' . md5( 'listen' . implode( ':', $args ) );
		if ( $args['cache'] !== false ) {

			$cached_results = get_option( $cache_key, false );
			if ( $cached_results !== false )
				return $cached_results;

		}

		$uamplified      = get_uamplified_data( 'get/requests', $args, $api_key );
		if ( ! is_wp_error( $uamplified ) && isset( $uamplified->item_count ) ) {

			$results = $uamplified->items;

			if ( $args['cache'] !== false ) {

				update_option( $cache_key, $results );

				$caches = get_option( 'uamp-' . $args['cache'] . '-keys', array() );
				$caches[ $cache_key ] = time();
				update_option( 'uamp-' . $args['cache'] . '-keys', $caches );

			}

		}

		elseif ( is_wp_error( $uamplified ) )
			$results = $uamplified;

		return $results;

	}
endif;

/**
 * Get Product Updates
 * Queries announcements / releases for a given product. Returns either a cached result or a fresh 
 * set of data from the uamplified.io servers.
 * @since	1.0
 * @version 1.1
 */
if ( ! function_exists( 'get_uamplified_product_updates' ) ) :
	function get_uamplified_product_updates( $args = array(), $api_key = '' ) {

		$args       = shortcode_atts( array(
			'page'       => 1,
			'number'     => 10,
			'type'       => 'all',
			'orderby'    => 'date',
			'order'      => 'desc',
			'cache'      => false
		), $args );

		if ( $args['number'] > UAMPLIFIED_IO_MAX_ITEMS ) $args['number'] = UAMPLIFIED_IO_MAX_ITEMS;

		$results         = array();
		$settings        = get_uamplified_settings();
		$cache_key       = 'uamp-cache-' . md5( 'talk' . implode( ':', $args ) );
		if ( $args['cache'] !== false ) {

			$cached_results = get_option( $cache_key, false );
			if ( $cached_results !== false )
				return $cached_results;

		}

		$uamplified      = get_uamplified_data( 'get/updates', $args, $api_key );
		if ( ! is_wp_error( $uamplified ) && isset( $uamplified->item_count ) ) {

			$results = $uamplified->items;

			if ( $args['cache'] !== false ) {

				update_option( $cache_key, $results );

				$caches = get_option( 'uamp-' . $args['cache'] . '-keys', array() );
				$caches[ $cache_key ] = time();
				update_option( 'uamp-' . $args['cache'] . '-keys', $caches );

			}
		}

		elseif ( is_wp_error( $uamplified ) )
			$results = $uamplified;

		return $results;

	}
endif;

/**
 * Get Product Campaigns
 * Queries campaigns for a given product. Returns either a cached result or a fresh 
 * set of data from the uamplified.io servers.
 * @since	1.0
 * @version 1.1
 */
if ( ! function_exists( 'get_uamplified_product_campaigns' ) ) :
	function get_uamplified_product_campaigns( $args = array(), $api_key = '' ) {

		$args       = shortcode_atts( array(
			'page'       => 1,
			'number'     => 10,
			'search'     => '',
			'orderby'    => 'date',
			'order'      => 'desc',
			'cache'      => false
		), $args );

		if ( $args['number'] > UAMPLIFIED_IO_MAX_ITEMS ) $args['number'] = UAMPLIFIED_IO_MAX_ITEMS;

		$results         = array();
		$settings        = get_uamplified_settings();
		$cache_key       = 'uamp-cache-' . md5( 'launch' . implode( ':', $args ) );
		if ( $args['cache'] !== false ) {

			$cached_results = get_option( $cache_key, false );
			if ( $cached_results !== false )
				return $cached_results;

		}

		$uamplified      = get_uamplified_data( 'get/campaigns', $args, $api_key );
		if ( ! is_wp_error( $uamplified ) && isset( $uamplified->item_count ) ) {

			$results = $uamplified->items;

			if ( $args['cache'] !== false ) {

				update_option( $cache_key, $results );

				$caches = get_option( 'uamp-' . $args['cache'] . '-keys', array() );
				$caches[ $cache_key ] = time();
				update_option( 'uamp-' . $args['cache'] . '-keys', $caches );

			}
		}

		elseif ( is_wp_error( $uamplified ) )
			$results = $uamplified;

		return $results;

	}
endif;

/**
 * Generate API Key Table Row
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'generate_api_key_table_row' ) ) :
	function generate_api_key_table_row( $product = NULL ) {

		$last_sync = ( $product->last_sync == 0 ) ? __( 'Just now', 'uamplified' ) : sprintf( __( 'Last synced %s ago.', 'uamplified' ), human_time_diff( $product->last_sync ) );

		$buttons   = array();
		$buttons[] = '<button type="button" class="button button-secondary remove-product-api-key" data-id="' . esc_attr( $product->product_id ) . '">' . __( 'Remove', 'uamplified' ) . '</button>';

		if ( $product->last_sync > 0 )
			$buttons[] = '<button type="button" class="button button-secondary sync-product" data-id="' . esc_attr( $product->product_id ) . '">' . __( 'Sync Product', 'uamplified' ) . '</button>';

		$html      = '
<tr id="uamplified-product' . esc_attr( $product->product_id ) . '">
	<th scope="row">
		<label for="uamp-api-key' . esc_attr( $product->product_id ) . '">' . esc_html( $product->title ) . '</label>
	</th>
	<td>
		<input type="password" id="uamp-api-key' . esc_attr( $product->product_id ) . '" readonly="readonly" class="regular-text" value="' . esc_attr( $product->api_key ) . '" /> ' . implode( ' ' , $buttons ) . '<br />
		<p class="description">' . $last_sync . '</p>
	</td>
</tr>';

		return $html;

	}
endif;

/**
 * Generate Product Category Label
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'generate_product_category_label' ) ) :
	function generate_product_category_label( $categories = NULL, $category = NULL, $class = '' ) {

		if ( ! is_object( $categories ) || ! isset( $category->term_id ) ) return '';

		$term_id      = $category->term_id;
		$the_category = $categories->$term_id;

		return '<span class="item-category-label' . $class . '" style="background-color: ' . $the_category->bg_color . '; color: ' . $the_category->text_color . ';">' . $the_category->name . '</span>';

	}
endif;

/**
 * Generate Request Vote Box
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'generate_uamplified_request_vote_box' ) ) :
	function generate_uamplified_request_vote_box( $request = NULL ) {

		if ( ! is_object( $request ) || ! isset( $request->request_id ) ) return '';

		$status = '<span class="vote-up">' . __( 'Votes', 'uamplified' ) . '</span><h4 class="vite-result">' . $request->vote_result . '</h4>';
		// if ( $request->vote_status != 'closed' )
		//	$status = '<span class="vote-up">Up</span><h4 class="vite-result">' . $request->vote_result . '</h4><span class="vite-down">Down</span>';

		$html = '<div class="vote-wrapper" data-id="' . $request->request_id . '">' . $status . '</div>';

		return $html;

	}
endif;

/**
 * Generate Menu Item Popup
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'generate_uamplified_menu_item_popup' ) ) :
	function generate_uamplified_menu_item_popup( $args = array(), $product = NULL ) {

		$args = shortcode_atts( array(
			'module'     => '',
			'api_key'    => '',
			'number'     => 5,
			'type'       => 'all',
			'ordeby'     => 'date',
			'order'      => 'desc',
			'cache'      => 'hourly'
		), $args );

		extract( $args );

		$popup   = '';
		$results = array( 'popup' => '<p>No items found.</p>', 'ids' => '0', 'indicator' => 'ready' );
		if ( $product == '' || $module == '' ) return $results;

		$popup .= '<div class="uamp-overflow-wrapper" style="width: #width#; height: #height#;"><ul class="uamp-item-list uamp-' . $module . '-item-list">';

		if ( $module == 'listen' ) {

			$data   = get_uamplified_product_requests( $args, $api_key );

			if ( is_wp_error( $data ) ) {

				$popup .= '<li>' . $data->get_error_message() . '</li>';
				$results['indicator'] = 'error';

			}

			elseif ( empty( $data ) ) {

				$popup .= '<li>' . __( 'No items found.', 'uamplified' ) . '</li>';
				$results['indicator'] = 'empty';

			}

			else {

				foreach ( $data as $request_id => $request ) {
					$popup .= '<li class="uamp-list-item"><a href="' . esc_url( $request->url ) . '">' . generate_product_category_label( $product->categories, $request->category, ( ( is_rtl() ) ? ' pull-left' : ' pull-right' ) ) . '' . esc_html( $request->title ) . '</a></li>' . "\n";
				}

				$results['indicator'] = 'ready';
				$results['ids']       = implode( ',', array_keys( (array) $data ) );

			}

		}

		elseif ( $module == 'talk' ) {

			$data   = get_uamplified_product_updates( $args, $api_key );

			if ( is_wp_error( $data ) ) {

				$popup .= '<li>' . $data->get_error_message() . '</li>';
				$results['indicator'] = 'error';

			}

			elseif ( empty( $data ) ) {

				$popup .= '<li>' . __( 'No items found.', 'uamplified' ) . '</li>';
				$results['indicator'] = 'empty';

			}

			else {

				foreach ( $data as $release_id => $release ) {
					$just_text       = strip_tags( $release->description );
					$release_excerpt = ( strlen( $just_text ) > 150 ) ? substr( $just_text, 0, 149 ) . ' ...' : $just_text;
					$popup          .= '<li class="uamp-list-item"><a href="' . esc_url( $release->url ) . '"><strong>' . esc_html( $release->title ) . '</strong>' . $release_excerpt . '</a></li>' . "\n";
				}

				$results['indicator'] = 'ready';
				$results['ids']       = implode( ',', array_keys( (array) $data ) );

			}

		}

		elseif ( $module == 'launch' ) {

			$data   = get_uamplified_product_campaigns( $args, $api_key );

			if ( is_wp_error( $data ) ) {

				$popup .= '<li>' . $data->get_error_message() . '</li>';
				$results['indicator'] = 'error';

			}

			elseif ( empty( $data ) ) {

				$popup .= '<li>' . __( 'No items found.', 'uamplified' ) . '</li>';
				$results['indicator'] = 'empty';

			}

			else {

				foreach ( $data as $campaign_id => $campaign ) {
					$just_text        = strip_tags( $campaign->description );
					$campaign_excerpt = ( strlen( $just_text ) > 150 ) ? substr( $just_text, 0, 149 ) . ' ...' : $just_text;
					$popup           .= '<li class="uamp-list-item"><a href="' . esc_url( $campaign->url ) . '"><strong>' . esc_html( $campaign->title ) . '</strong>' . $campaign_excerpt . '</a></li>' . "\n";
				}

				$results['indicator'] = 'ready';
				$results['ids']       = implode( ',', array_keys( (array) $data ) );

			}

		}

		$popup .= '</ul></div><a href="#module-url#" class="view-all" target="_blank">' . esc_attr( __( 'View All', 'uamplified' ) ) . '</a>';

		$results['popup'] = $popup;

		return $results;

	}
endif;

/**
 * Update Widget Cache
 * Will attempt to find the previous arguments used by a given widget. If one is found, it is deleted
 * to ensure we keep a clean database.
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'uamplified_update_widget_cache' ) ) :
	function uamplified_update_widget_cache( $widget_id = '', $instance, $old_instance ) {

		$now           = time();
		$args          = array();
		$old_cache_key = false;

		if ( $widget_id == 'uamplified_io_product_listen' ) {

			$old_args = array(
				'page'       => 1,
				'number'     => ( isset( $old_instance['number'] ) ) ? $old_instance['number'] : $instance['number'],
				'author'     => 0,
				'search'     => '',
				'orderby'    => ( isset( $old_instance['sort'] ) ) ? $old_instance['sort'] : $instance['sort'],
				'order'      => ( isset( $old_instance['order'] ) ) ? $old_instance['order'] : $instance['order'],
				'update'     => ( isset( $old_instance['update'] ) ) ? $old_instance['update'] : $instance['update']
			);
			$old_cache_key = 'uamp-cache-' . md5( 'listen' . implode( ':', $old_args ) );

		}

		elseif ( $widget_id == 'uamplified_io_product_talk' ) {

			$old_args = array(
				'page'       => 1,
				'number'     => ( isset( $old_instance['number'] ) ) ? $old_instance['number'] : $instance['number'],
				'type'       => ( isset( $old_instance['show'] ) ) ? $old_instance['show'] : $instance['show'],
				'author'     => 0,
				'search'     => '',
				'orderby'    => 'date',
				'order'      => 'desc',
				'update'     => ( isset( $old_instance['update'] ) ) ? $old_instance['update'] : $instance['update']
			);
			$old_cache_key = 'uamp-cache-' . md5( 'talk' . implode( ':', $old_args ) );

		}

		elseif ( $widget_id == 'uamplified_io_product_launch' ) {

			$old_args = array(
				'page'       => 1,
				'product'    => ( isset( $old_instance['product'] ) ) ? $old_instance['product'] : $instance['product'],
				'number'     => ( isset( $old_instance['number'] ) ) ? $old_instance['number'] : $instance['number'],
				'author'     => 0,
				'search'     => '',
				'orderby'    => ( isset( $old_instance['sort'] ) ) ? $old_instance['sort'] : $instance['sort'],
				'order'      => ( isset( $old_instance['order'] ) ) ? $old_instance['order'] : $instance['order'],
				'update'     => ( isset( $old_instance['update'] ) ) ? $old_instance['update'] : $instance['update']
			);
			$old_cache_key = 'uamp-cache-' . md5( 'launch' . implode( ':', $old_args ) );

		}

		if ( $old_cache_key === false ) return false;

		delete_expired_uamplified_cache( 'hourly', $old_cache_key );
		delete_expired_uamplified_cache( 'daily', $old_cache_key );

		return true;

	}
endif;

/**
 * Delete Expired Cache
 * Runs through all saved cache keys and it's expiry date to see if they need
 * to be deleted from the database.
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'delete_expired_uamplified_cache' ) ) :
	function delete_expired_uamplified_cache( $type = 'hourly', $delete_this_key = false ) {

		$saved_keys  = get_option( 'uamp-' . $type . '-keys', array() );
		$new_keys    = array();
		$now         = time();

		if ( ! empty( $saved_keys ) ) {
			foreach ( $saved_keys as $cache_id => $timestamp ) {

				if ( $timestamp <= ( $now - HOUR_IN_SECONDS ) ) {
					delete_option( $cache_id );
					continue;
				}

				if ( $delete_this_key !== false && $cache_id == $delete_this_key ) {
					delete_option( $delete_this_key );
					continue;
				}

				$new_keys[ $cache_id ] = $timestamp;

			}
		}

		update_option( 'uamp-' . $type . '-keys', $new_keys );

	}
endif;

/**
 * Delete All Cache
 * Forcably deletes all saved caches.
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'delete_all_uamplified_cache' ) ) :
	function delete_all_uamplified_cache( $type = 'hourly' ) {

		$saved_keys  = get_option( 'uamp-' . $type . '-keys', array() );

		if ( ! empty( $saved_keys ) ) {
			foreach ( $saved_keys as $cache_id => $timestamp ) {

				delete_option( $cache_id );

			}
		}

		update_option( 'uamp-' . $type . '-keys', array() );

	}
endif;

/**
 * Get Total Cached Data
 * Returns the total number of saved cache keys.
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'get_total_cached_uamplified_data' ) ) :
	function get_total_cached_uamplified_data() {

		$total = 0;

		$total += count( get_option( 'uamp-hourly-keys', array() ) );
		$total += count( get_option( 'uamp-daily-keys', array() ) );

		return $total;

	}
endif;

/**
 * Delete All Data
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'delete_all_uamplified_data' ) ) :
	function delete_all_uamplified_data() {

		delete_option( 'uamplified_io' );
		delete_option( 'uamplified_io_products' );

		$menu_item_ids = get_option( 'uamplified-menu-ids', array() );
		if ( ! empty( $menu_item_ids ) ) {
			foreach ( $menu_item_ids as $product_id => $pages ) {
				foreach ( $pages as $module_id => $page_id ) {
					if ( $page_id == 0 ) continue;
					wp_delete_post( $page_id, true );
				}
			}
		}

		delete_option( 'uamplified-menu-ids' );
		delete_option( 'uamplified-menu-version' );

	}
endif;

/**
 * Get Next CRON Schedule
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'get_next_uamplified_cron_schedule' ) ) :
	function get_next_uamplified_cron_schedule( $type = 'hourly' ) {

		$settings      = get_uamplified_settings();
		$next_schedule = wp_next_scheduled( 'uamplified_cron_' . $type );
		if ( $settings['alternate_cron'] == 1 )
			$next_schedule = get_option( 'uamp-cron-next-' . $type, false );

		return $next_schedule;

	}
endif;

/**
 * Maybe Schedule Cron
 * Checks to ensire a cron job is scheduled in WordPress. If not, a new one is added.
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'maybe_schedule_uamplified_cron' ) ) :
	function maybe_schedule_uamplified_cron( $type = 'hourly', $reschedule = true ) {

		if ( empty( $type ) ) return false;

		// Setup cron schedules
		$next_schedule = get_next_uamplified_cron_schedule( $type );
		if ( $next_schedule !== false ) {
			wp_unschedule_event( $next_schedule, 'uamplified_cron_' . $type );
			delete_option( 'uamp-cron-next-' . $type );
		}

		if ( ! $reschedule ) return true;

		$timestamp     = time() + ( 1 * HOUR_IN_SECONDS );
		if ( $type == 'daily' )
			$timestamp = time() + ( 1 * DAY_IN_SECONDS );

		wp_schedule_single_event( $timestamp, 'uamplified_cron_' . $type );
		update_option( 'uamp-cron-next-' . $type, $timestamp );

		return get_next_uamplified_cron_schedule( $type );

	}
endif;

/**
 * Update Menu Items
 * Updates the saved menu items with a given product.
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'update_uamplified_menu_items' ) ) :
	function update_uamplified_menu_items( $product ) {

		$menu_item_ids         = get_option( 'uamplified-menu-ids', array() );
		$menu_updated          = false;
		if ( ! empty( $menu_item_ids ) && array_key_exists( $product->product_id, $menu_item_ids ) ) {
			foreach ( $menu_item_ids[ $product->product_id ] as $module_id => $page_id ) {

				if ( $page_id > 0 && $product->$module_id !== false ) {

					wp_update_post( array(
						'ID'         => $page_id,
						'post_title' => sprintf( '%s: %s', esc_html( $product->title ), esc_html( $product->$module_id->title ) )
					) );

					update_post_meta( $page_id, 'product_id', $product->product_id );
					update_post_meta( $page_id, 'product_module', $module_id );

				}

				elseif ( $page_id > 0 && $product->$module_id === false ) {

					$menu_item_ids[ $product->product_id ][ $module_id ] = 0;
					$menu_updated = true;

					wp_delete_post( $page_id, true );

				}

				elseif ( $product->$module_id !== false ) {

					$menu_item_id = wp_insert_post( array(
						'post_type'      => 'uamp_menu',
						'post_title'     => sprintf( '%s: %s', esc_html( $product->title ), esc_html( $product->$module_id->title ) ),
						'post_status'    => 'publish',
						'comment_status' => 'closed',
						'ping_status'    => 'closed'
					), true );

					if ( ! is_wp_error( $menu_item_id ) ) {
						$menu_updated = true;
						$menu_item_ids[ $product->product_id ][ $module_id ] = $menu_item_id;
						add_post_meta( $menu_item_id, 'product_id', $product->product_id, true );
						add_post_meta( $menu_item_id, 'product_module', $module_id, true );
					}

				}

			}
		}

		if ( $menu_updated ) update_option( 'uamplified-menu-ids', $menu_item_ids );

	}
endif;

/**
 * Delete Menu Item
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'delete_uamplified_menu_items' ) ) :
	function delete_uamplified_menu_items( $product_id = 0 ) {

		$menu_item_ids = get_option( 'uamplified-menu-ids', array() );
		if ( ! empty( $menu_item_ids ) && array_key_exists( $product_id, $menu_item_ids ) ) {

			foreach ( $menu_item_ids[ $product_id ] as $module_id => $page_id ) {

				if ( $page_id > 0 )
					wp_delete_post( $page_id, true );

			}

			unset( $menu_item_ids[ $product_id ] );

		}

		update_option( 'uamplified-menu-ids', $menu_item_ids );

	}
endif;

/**
 * Maybe Adjust Args
 * Used by the menu item description field where we can supply arguments to override the
 * built-in setup for the popup window.
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'maybe_adjust_uamplified_args' ) ) :
	function maybe_adjust_uamplified_args( $args = array(), $field = '' ) {

		$result     = $args;
		$maybe_args = explode( ",", $field );
		if ( ! empty( $maybe_args ) ) {

			$valid_args = array();
			foreach ( $maybe_args as $row ) {

				$row = explode( '=', $row );
				if ( count( $row ) == 1 && array_key_exists( $row[0], $args ) )
					$valid_args[ $row[0] ] = '';
				elseif ( count( $row ) == 2 && array_key_exists( $row[0], $args ) )
					$valid_args[ $row[0] ] = sanitize_text_field( $row[1] );

			}

			if ( ! empty( $valid_args ) )
				$result = shortcode_atts( $args, $valid_args );

		}



		return $result;

	}
endif;

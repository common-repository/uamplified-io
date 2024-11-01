<?php
if ( ! defined( 'UAMPLIFIED_IO_VERSION' ) ) exit;

/**
 * Widget: Product Requests
 * Allows you to show submitted requests for a specific product you are hosting with uamplified.io
 * @since	1.0
 * @version 1.0
 */
if ( ! class_exists( 'uamplified_io_product_listen' ) ) :
	class uamplified_io_product_listen extends WP_Widget {

		/**
		 * Construct
		 * @since	1.0
		 * @version 1.0
		 */
		public function __construct() {

			parent::__construct(
				'uamplified_io_product_listen',
				__( 'uamplified.io Product Listen', 'uamplified' ),
				array(
					'classname'   => 'widget-uamp',
					'description' => __( 'Show product related requests.', 'uamplified' )
				)
			);

		}

		/**
		 * Widget Output
		 * @since	1.0
		 * @version 1.0
		 */
		public function widget( $args, $instance ) {

			extract( $args, EXTR_SKIP );

			$products = get_connected_uamplified_products();
			if ( ! array_key_exists( $instance['product'], $products ) || $products[ $instance['product'] ]->listen === false ) {

				if ( current_user_can( 'manage_options' ) ) {

					if ( array_key_exists( $instance['product'], $products ) && $products[ $instance['product'] ]->listen === false )
						_e( 'The Listen module is not enabled for the selected product.', 'uamplified' );
					else
						_e( 'The selected product could not be found. Please check your API keys.', 'uamplified' );

				}

			}

			elseif ( $instance['visibility'] == 'members' && ! is_user_logged_in() ) { }
			elseif ( $instance['visibility'] == 'visitors' && is_user_logged_in() ) { }

			else {

				$selected_product = $products[ $instance['product'] ];

				echo $before_widget;

				if ( ! empty( $instance['title'] ) )
					echo $before_title . $instance['title'] . $after_title;

				$date_format = get_option( 'date_format' );
				$module_url  = $selected_product->listen->url;
				$requests    = get_uamplified_product_requests( array(
					'number'     => $instance['number'],
					'orderby'    => $instance['sort'],
					'order'      => $instance['order'],
					'cache'      => ( $instance['update'] != 'live' ) ? $instance['update'] : false
				), $selected_product->api_key );

				echo '<div class="uamp-widget-content widget_recent_entries uamp-listen">';

				if ( is_wp_error( $requests ) )
					echo '<p class="error">' . $requests->get_error_message() . '</p>';

				elseif ( ! empty( $requests ) ) {

					echo '<ul class="uamp-item-list">';

					foreach ( $requests as $request_id => $request ) {

						echo '<li class="uamp-item-list-row">';

						echo '<div class="uamp-vote-box">' . generate_uamplified_request_vote_box( $request ) . '</div><div class="uamp-vote-info">';

						$meta     = array();
						$meta[]   = date_i18n( $date_format, $request->date );
						$comments = ( $request->comment_count > 0 ) ? sprintf( _n( '%d Comment', '%d Comments', $request->comment_count, 'uamplified' ), $request->comment_count ) : __( 'No comments', 'uamplified' );
						$meta[]   = sprintf( '<a href="%s">%s</a>', $request->url . '#comments', $comments );
						if ( isset( $request->author ) ) $meta[] = sprintf( '<strong>%s:</strong> %s', __( 'Author', 'uamplified' ), $request->author );

						$request_title = sprintf( '<h4>%s</h4> %s', sprintf( '%s<a href="%s">%s</a>', generate_product_category_label( $selected_product->categories, $request->category, ( ( is_rtl() ) ? ' pull-left' : ' pull-right' ) ), $request->url, $request->title ), '<span class="post-date">' . implode( ' <span class="uamp-meta-sep">|</span> ', $meta ) . '</span>' );

						echo apply_filters( 'uamplified_io_request_widget_title', $request_title, $request );

						$description = $request->description;

						if ( $instance['length'] > 0 ) {
							$description = strip_tags( $description );
							$description = substr( $description, 0, $instance['length'] );
							echo '<hr />' . wpautop( wptexturize( $description ) );
						}

						elseif ( $instance['length'] == 0 ) {
							echo '<hr />' . wpautop( wptexturize( $request->description ) );
						}

						echo '</div>';

						echo '</li>';

					}

					echo '</ul>';

				}
				else {
					echo '<p>' . __( 'No items found.', 'uamplified' ) . '</p>';
				}

				echo '<div class="uamp-widget-footer">' . sprintf( '<a href="%s">%s</a>', $module_url, sprintf( __( 'View %s', 'uamplified' ), $selected_product->listen->title ) ) . '</div>';

				echo '</div>';

				echo $after_widget;

			}

		}

		/**
		 * Settings
		 * @since	1.0
		 * @version 1.0
		 */
		public function form( $instance ) {

			// Defaults
			$title      = isset( $instance['title'] )      ? $instance['title']      : 'Product Requests';
			$product    = isset( $instance['product'] )    ? $instance['product']    : 0;
			$length     = isset( $instance['length'] )     ? $instance['length']     : -1;
			$sort       = isset( $instance['sort'] )       ? $instance['sort']       : 'date';
			$order      = isset( $instance['order'] )      ? $instance['order']      : 'asc';
			$number     = isset( $instance['number'] )     ? $instance['number']     : 5;
			$update     = isset( $instance['update'] )     ? $instance['update']     : 'live';
			$visibility = isset( $instance['visibility'] ) ? $instance['visibility'] : 'all';

			$products   = get_connected_uamplified_products();

?>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title', 'uamplified' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" class="widefat" />
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'product' ) ); ?>"><?php _e( 'Product', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'product' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'product' ) ); ?>" class="widefat">
<?php

			if ( ! empty( $products ) ) {
				foreach ( $products as $product_id => $p ) {

					echo '<option value="' . $product_id . '"';
					if ( $product == $product_id ) echo ' selected="selected"';
					echo '>' . esc_html( $p->title ) . '</option>';

				}
			}

?>
	</select>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php _e( 'Number', 'uamplified' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" min="1" max="<?php echo UAMPLIFIED_IO_MAX_ITEMS; ?>" value="<?php echo esc_attr( $number ); ?>" class="widefat" />
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'length' ) ); ?>"><?php _e( 'Content Length', 'uamplified' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'length' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'length' ) ); ?>" type="number" min="-1" value="<?php echo esc_attr( $length ); ?>" class="widefat" />
	<div class="description"><?php _e( "Option to set the maximum length of the request description. Use -1 to hide the content, 0, to show the entire content or the maximum length to show.", 'uamplified' ); ?></div>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'sort' ) ); ?>"><?php _e( 'Order By', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'sort' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'sort' ) ); ?>" class="widefat">
		<option value="date" <?php selected( $sort, 'date' ); ?>><?php _e( 'Publish Date', 'uamplified' ); ?></option>
		<option value="votes" <?php selected( $sort, 'votes' ); ?>><?php _e( 'Votes', 'uamplified' ); ?></option>
		<option value="comment_count" <?php selected( $sort, 'comments' ); ?>><?php _e( 'Comment Count', 'uamplified' ); ?></option>
	</select>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php _e( 'Order', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" class="widefat">
		<option value="asc" <?php selected( $order, 'asc' ) ?>><?php _e( 'Ascending', 'uamplified' ); ?></option>
		<option value="desc" <?php selected( $order, 'desc' ) ?>><?php _e( 'Descending', 'uamplified' ); ?></option>
	</select>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'update' ) ); ?>"><?php _e( 'Caching', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'update' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'update' ) ); ?>" class="widefat">
		<option value="hourly" <?php selected( $update, 'hourly' ); ?>><?php _e( 'Update Hourly', 'uamplified' ); ?></option>
		<option value="daily" <?php selected( $update, 'daily' ); ?>><?php _e( 'Update Daily', 'uamplified' ); ?></option>
	</select>
	<div class="description"><?php _e( "You can clear the cached results for this widget by re-saving it's settings.", 'uamplified' ); ?></div>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'visibility' ) ); ?>"><?php _e( 'Visibility', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'visibility' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'visibility' ) ); ?>" class="widefat">
		<option value="all" <?php selected( $visibility, 'all' ); ?>><?php _e( 'Show to anyone', 'uamplified' ); ?></option>
		<option value="members" <?php selected( $visibility, 'members' ); ?>><?php _e( 'Only logged in users', 'uamplified' ); ?></option>
		<option value="visitors" <?php selected( $visibility, 'visitors' ); ?>><?php _e( 'Visitors only', 'uamplified' ); ?></option>
	</select>
</p>
<?php

		}

		/**
		 * Save Settings
		 * @since	1.0
		 * @version 1.0
		 */
		public function update( $new_instance, $old_instance ) {

			$instance               = $old_instance;

			$instance['title']      = wp_kses_post( $new_instance['title'] );
			$instance['product']    = sanitize_text_field( $new_instance['product'] );
			$instance['length']     = sanitize_key( $new_instance['length'] );
			$instance['sort']       = sanitize_key( $new_instance['sort'] );
			$instance['order']      = sanitize_key( $new_instance['order'] );
			$instance['number']     = absint( $new_instance['number'] );
			$instance['update']     = sanitize_key( $new_instance['update'] );
			$instance['visibility'] = sanitize_key( $new_instance['visibility'] );

			if ( $instance['number'] > 20 ) $instance['number'] = UAMPLIFIED_IO_MAX_ITEMS;
			if ( $instance['length'] < -1 ) $instance['length'] = -1;
			elseif ( $instance['length'] > 0 ) $instance['length'] = absint( $instance['length'] );

			uamplified_update_widget_cache( $this->id_base, $instance, $old_instance );

			return $instance;

		}

	}
endif;

/**
 * Widget: Product Releases
 * Allows you to show published announcements or releases for a specific product you are hosting with uamplified.io
 * @since	1.0
 * @version 1.0
 */
if ( ! class_exists( 'uamplified_io_product_talk' ) ) :
	class uamplified_io_product_talk extends WP_Widget {

		/**
		 * Construct
		 * @since	1.0
		 * @version 1.0
		 */
		public function __construct() {

			parent::__construct(
				'uamplified_io_product_talk',
				__( 'uamplified.io Product Talk', 'uamplified' ),
				array(
					'classname'   => 'widget-uamp',
					'description' => __( 'Show latest product announcements / releases.', 'uamplified' )
				)
			);

		}

		/**
		 * Widget Output
		 * @since	1.0
		 * @version 1.0
		 */
		public function widget( $args, $instance ) {

			extract( $args, EXTR_SKIP );

			$products = get_connected_uamplified_products();
			if ( ! array_key_exists( $instance['product'], $products ) || $products[ $instance['product'] ]->talk === false ) {

				if ( current_user_can( 'manage_options' ) ) {

					if ( array_key_exists( $instance['product'], $products ) && $products[ $instance['product'] ]->talk === false )
						_e( 'The Talk module is not enabled for the selected product.', 'uamplified' );
					else
						_e( 'The selected product could not be found. Please check your API keys.', 'uamplified' );

				}

			}

			elseif ( $instance['visibility'] == 'members' && ! is_user_logged_in() ) { }
			elseif ( $instance['visibility'] == 'visitors' && is_user_logged_in() ) { }

			else {

				$selected_product = $products[ $instance['product'] ];

				echo $before_widget;

				if ( ! empty( $instance['title'] ) )
					echo $before_title . $instance['title'] . $after_title;

				$date_format = get_option( 'date_format' );
				$module_url  = $selected_product->talk->url;
				$updates     = get_uamplified_product_updates( array(
					'number'     => $instance['number'],
					'type'       => $instance['show'],
					'cache'      => ( $instance['update'] != 'live' ) ? $instance['update'] : false
				), $selected_product->api_key );

				echo '<div class="uamp-widget-content widget_recent_entries uamp-talk">';

				if ( is_wp_error( $updates ) )
					echo '<p class="error">' . $updates->get_error_message() . '</p>';

				elseif ( ! empty( $updates ) ) {

					echo '<ul class="uamp-item-list">';

					foreach ( $updates as $release_id => $release ) {

						echo '<li class="uamp-item-list-row">';

						$release_title = sprintf( '<div><small>%s</small></div><h4>%s</h4> %s', ( ( $release->type == 'release' ) ? __( 'RELEASE', 'uamplified' ) : __( 'ANNOUNCEMENT', 'uamplified' ) ), $release->title, '<span class="post-date">' . date_i18n( $date_format, $release->date ). '</span>' );

						echo apply_filters( 'uamplified_io_release_widget_title', $release_title, $release );

						if ( empty( $release->requests ) )
							echo wpautop( wptexturize( $release->description ) );

						else {
							echo '<div class="item-sub-list">';
							foreach ( $release->requests as $request_id => $request ) {

								$request_row = sprintf( '%s %s', generate_product_category_label( $selected_product->categories, $request->category ), sprintf( '<a href="%s">%s</a>', $request->url, $request->title ) );
								$request_row = apply_filters( 'uamplified_io_request_widget_row', $request_row, $request );
								echo '<div class="sub-item-list-row">' . $request_row . '</div>';

							}
							echo '</div>';
						}

						echo '</li>';

					}

					echo '</ul>';

				}
				else {
					echo '<p>' . __( 'No items found.', 'uamplified' ) . '</p>';
				}

				echo '<div class="uamp-widget-footer">' . sprintf( '<a href="%s">%s</a>', $module_url, sprintf( __( 'View %s', 'uamplified' ), $selected_product->talk->title ) ) . '</div>';

				echo '</div>';

				echo $after_widget;

			}

		}

		/**
		 * Settings
		 * @since	1.0
		 * @version 1.0
		 */
		public function form( $instance ) {

			// Defaults
			$title      = isset( $instance['title'] )      ? $instance['title']      : 'Product Updates';
			$product    = isset( $instance['product'] )    ? $instance['product']    : 0;
			$show       = isset( $instance['show'] )       ? $instance['show']       : 'all';
			$number     = isset( $instance['number'] )     ? $instance['number']     : 5;
			$update     = isset( $instance['update'] )     ? $instance['update']     : 'live';
			$visibility = isset( $instance['visibility'] ) ? $instance['visibility'] : 'all';

			$products   = get_connected_uamplified_products();

?>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title', 'uamplified' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" class="widefat" />
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'product' ) ); ?>"><?php _e( 'Product', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'product' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'product' ) ); ?>" class="widefat">
<?php

			if ( ! empty( $products ) ) {
				foreach ( $products as $product_id => $p ) {

					echo '<option value="' . $product_id . '"';
					if ( $product == $product_id ) echo ' selected="selected"';
					echo '>' . esc_html( $p->title ) . '</option>';

				}
			}

?>
	</select>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'show' ) ); ?>"><?php _e( 'Show', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'show' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show' ) ); ?>" class="widefat">
		<option value="all" <?php selected( $show, 'all' ); ?>><?php _e( 'Announcements & Releases', 'uamplified' ); ?></option>
		<option value="update" <?php selected( $show, 'update' ); ?>><?php _e( 'Announcements', 'uamplified' ); ?></option>
		<option value="release" <?php selected( $show, 'release' ); ?>><?php _e( 'Releases', 'uamplified' ); ?></option>
	</select>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php _e( 'Number', 'uamplified' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" min="1" max="<?php echo UAMPLIFIED_IO_MAX_ITEMS; ?>" value="<?php echo esc_attr( $number ); ?>" class="widefat" />
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'update' ) ); ?>"><?php _e( 'Caching', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'update' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'update' ) ); ?>" class="widefat">
		<option value="hourly" <?php selected( $update, 'hourly' ); ?>><?php _e( 'Update Hourly', 'uamplified' ); ?></option>
		<option value="daily" <?php selected( $update, 'daily' ); ?>><?php _e( 'Update Daily', 'uamplified' ); ?></option>
	</select>
	<div class="description"><?php _e( "You can clear the cached results for this widget by re-saving it's settings.", 'uamplified' ); ?></div>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'visibility' ) ); ?>"><?php _e( 'Visibility', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'visibility' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'visibility' ) ); ?>" class="widefat">
		<option value="all" <?php selected( $visibility, 'all' ); ?>><?php _e( 'Show to anyone', 'uamplified' ); ?></option>
		<option value="members" <?php selected( $visibility, 'members' ); ?>><?php _e( 'Only logged in users', 'uamplified' ); ?></option>
		<option value="visitors" <?php selected( $visibility, 'visitors' ); ?>><?php _e( 'Visitors only', 'uamplified' ); ?></option>
	</select>
</p>
<?php

		}

		/**
		 * Save Settings
		 * @since	1.0
		 * @version 1.0
		 */
		public function update( $new_instance, $old_instance ) {

			$instance               = $old_instance;

			$instance['title']      = wp_kses_post( $new_instance['title'] );
			$instance['product']    = sanitize_text_field( $new_instance['product'] );
			$instance['show']       = sanitize_key( $new_instance['show'] );
			$instance['number']     = absint( $new_instance['number'] );
			$instance['update']     = sanitize_key( $new_instance['update'] );
			$instance['visibility'] = sanitize_key( $new_instance['visibility'] );

			if ( $instance['number'] > 20 ) $instance['number'] = UAMPLIFIED_IO_MAX_ITEMS;

			uamplified_update_widget_cache( $this->id_base, $instance, $old_instance );

			return $instance;

		}

	}
endif;

/**
 * Widget: Product Campaigns
 * Allows you to show campaigns for a specific product you are hosting with uamplified.io
 * @since	1.0
 * @version 1.0
 */
if ( ! class_exists( 'uamplified_io_product_launch' ) ) :
	class uamplified_io_product_launch extends WP_Widget {

		/**
		 * Construct
		 * @since	1.0
		 * @version 1.0
		 */
		public function __construct() {

			parent::__construct(
				'uamplified_io_product_launch',
				__( 'uamplified.io Product Launch', 'uamplified' ),
				array(
					'classname'   => 'widget-uamp',
					'description' => __( 'Show product related campaigns.', 'uamplified' )
				)
			);

		}

		/**
		 * Widget Output
		 * @since	1.0
		 * @version 1.0
		 */
		public function widget( $args, $instance ) {

			extract( $args, EXTR_SKIP );

			$products = get_connected_uamplified_products();
			if ( ! array_key_exists( $instance['product'], $products ) || $products[ $instance['product'] ]->launch === false ) {

				if ( current_user_can( 'manage_options' ) ) {

					if ( array_key_exists( $instance['product'], $products ) && $products[ $instance['product'] ]->launch === false )
						_e( 'The Launch module is not enabled for the selected product.', 'uamplified' );
					else
						_e( 'The selected product could not be found. Please check your API keys.', 'uamplified' );

				}

			}

			elseif ( $instance['visibility'] == 'members' && ! is_user_logged_in() ) { }
			elseif ( $instance['visibility'] == 'visitors' && is_user_logged_in() ) { }

			else {

				$selected_product = $products[ $instance['product'] ];

				echo $before_widget;

				if ( ! empty( $instance['title'] ) )
					echo $before_title . $instance['title'] . $after_title;

				$date_format = get_option( 'date_format' );
				$module_url  = $selected_product->launch->url;
				$campaigns   = get_uamplified_product_campaigns( array(
					'number'     => $instance['number'],
					'orderby'    => $instance['sort'],
					'order'      => $instance['order'],
					'cache'      => ( $instance['update'] != 'live' ) ? $instance['update'] : false
				), $selected_product->api_key );

				echo '<div class="uamp-widget-content widget_recent_entries uamp-launch">';

				if ( is_wp_error( $campaigns ) )
					echo '<p class="error">' . $campaigns->get_error_message() . '</p>';

				elseif ( ! empty( $campaigns ) ) {

					echo '<ul class="uamp-item-list">';

					foreach ( $campaigns as $campaign_id => $campaign ) {

						echo '<li class="uamp-item-list-row">';

						$campaign_title = sprintf( '<div><small><strong>%s</strong> %s</small></div><h4>%s</h4>', __( 'Status:', 'uamplified' ), ( ( $campaign->status == 'publish' ) ? __( 'RUNNING', 'uamplified' ) : __( 'COMING SOON', 'uamplified' ) ), sprintf( '<a href="%s">%s</a>', $campaign->url, $campaign->title ) );

						echo apply_filters( 'uamplified_io_campaign_widget_title', $campaign_title, $campaign );

						if ( $campaign->end_time != '' ) {
							printf( '<div class="uamp-campaign-timer"><div><small>%s</small></div>%s<hr /></div>', __( 'Ends in', 'uamplified' ), human_time_diff( strtotime( $campaign->end_time ), $campaign->time ) );
						}

						if ( ! empty( $campaign->description ) )
							echo wpautop( wptexturize( $campaign->description ) );

						echo '</li>';

					}

					echo '</ul>';

				}
				else {
					echo '<p>' . __( 'No items found.', 'uamplified' ) . '</p>';
				}

				echo '<div class="uamp-widget-footer">' . sprintf( '<a href="%s">%s</a>', $module_url, sprintf( __( 'View %s', 'uamplified' ), $selected_product->launch->title ) ) . '</div>';

				echo '</div>';

				echo $after_widget;

			}

		}

		/**
		 * Settings
		 * @since	1.0
		 * @version 1.0
		 */
		public function form( $instance ) {

			// Defaults
			$title      = isset( $instance['title'] )      ? $instance['title']      : 'Product Campaigns';
			$product    = isset( $instance['product'] )    ? $instance['product']    : 0;
			$sort       = isset( $instance['sort'] )       ? $instance['sort']       : 'date';
			$order      = isset( $instance['order'] )      ? $instance['order']      : 'asc';
			$number     = isset( $instance['number'] )     ? $instance['number']     : 5;
			$update     = isset( $instance['update'] )     ? $instance['update']     : 'live';
			$visibility = isset( $instance['visibility'] ) ? $instance['visibility'] : 'all';

			$products   = get_connected_uamplified_products();

?>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title', 'uamplified' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" class="widefat" />
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'product' ) ); ?>"><?php _e( 'Product', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'product' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'product' ) ); ?>" class="widefat">
<?php

			if ( ! empty( $products ) ) {
				foreach ( $products as $product_id => $p ) {

					echo '<option value="' . $product_id . '"';
					if ( $product == $product_id ) echo ' selected="selected"';
					echo '>' . esc_html( $p->title ) . '</option>';

				}
			}

?>
	</select>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>"><?php _e( 'Number', 'uamplified' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" min="1" max="<?php echo UAMPLIFIED_IO_MAX_ITEMS; ?>" value="<?php echo esc_attr( $number ); ?>" class="widefat" />
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'sort' ) ); ?>"><?php _e( 'Order By', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'sort' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'sort' ) ); ?>" class="widefat">
		<option value="ending" <?php selected( $sort, 'ending' ); ?>><?php _e( 'Date Ending', 'uamplified' ); ?></option>
		<option value="date" <?php selected( $sort, 'date' ); ?>><?php _e( 'Date Published', 'uamplified' ); ?></option>
		<option value="popular" <?php selected( $sort, 'popular' ); ?>><?php _e( 'Popularity', 'uamplified' ); ?></option>
	</select>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php _e( 'Order', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" class="widefat">
		<option value="asc" <?php selected( $order, 'asc' ) ?>><?php _e( 'Ascending', 'uamplified' ); ?></option>
		<option value="desc" <?php selected( $order, 'desc' ) ?>><?php _e( 'Descending', 'uamplified' ); ?></option>
	</select>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'update' ) ); ?>"><?php _e( 'Caching', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'update' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'update' ) ); ?>" class="widefat">
		<option value="hourly" <?php selected( $update, 'hourly' ); ?>><?php _e( 'Update Hourly', 'uamplified' ); ?></option>
		<option value="daily" <?php selected( $update, 'daily' ); ?>><?php _e( 'Update Daily', 'uamplified' ); ?></option>
	</select>
	<div class="description"><?php _e( "You can clear the cached results for this widget by re-saving it's settings.", 'uamplified' ); ?></div>
</p>
<p class="uamp-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'visibility' ) ); ?>"><?php _e( 'Visibility', 'uamplified' ); ?>:</label>
	<select id="<?php echo esc_attr( $this->get_field_id( 'visibility' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'visibility' ) ); ?>" class="widefat">
		<option value="all" <?php selected( $visibility, 'all' ); ?>><?php _e( 'Show to anyone', 'uamplified' ); ?></option>
		<option value="members" <?php selected( $visibility, 'members' ); ?>><?php _e( 'Only logged in users', 'uamplified' ); ?></option>
		<option value="visitors" <?php selected( $visibility, 'visitors' ); ?>><?php _e( 'Visitors only', 'uamplified' ); ?></option>
	</select>
</p>
<?php

		}

		/**
		 * Save Settings
		 * @since	1.0
		 * @version 1.0
		 */
		public function update( $new_instance, $old_instance ) {

			$instance               = $old_instance;

			$instance['title']      = wp_kses_post( $new_instance['title'] );
			$instance['product']    = sanitize_text_field( $new_instance['product'] );
			$instance['sort']       = sanitize_key( $new_instance['sort'] );
			$instance['order']      = sanitize_key( $new_instance['order'] );
			$instance['number']     = absint( $new_instance['number'] );
			$instance['update']     = sanitize_key( $new_instance['update'] );
			$instance['visibility'] = sanitize_key( $new_instance['visibility'] );

			if ( $instance['number'] > 20 ) $instance['number'] = UAMPLIFIED_IO_MAX_ITEMS;

			uamplified_update_widget_cache( $this->id_base, $instance, $old_instance );

			return $instance;

		}

	}
endif;

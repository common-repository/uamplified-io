<?php
if ( ! defined( 'UAMPLIFIED_IO_VERSION' ) ) exit;

/**
 * Menu Items
 * @since 1.0
 * @version 1.0
 */
if ( ! class_exists( 'uamplified_io_menu_items' ) ) :
	final class uamplified_io_menu_items {

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
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', UAMPLIFIED_IO_MENU_VERSION ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', UAMPLIFIED_IO_MENU_VERSION ); }

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			add_action( 'init',                     array( $this, 'register_menu_items' ) );
			add_filter( 'wp_nav_menu_objects',      array( $this, 'filter_menu_items' ) );
			add_filter( 'nav_menu_link_attributes', array( $this, 'filter_menu_attributes' ), 10, 4 );
			add_filter( 'walker_nav_menu_start_el', array( $this, 'filter_final_menu' ), 10, 4 );
			add_action( 'wp_footer',                array( $this, 'maybe_load_popup_script' ) );
			add_filter( 'contextual_help',          array( $this, 'contextual_help' ), 10, 2 );

		}

		/**
		 * Register Menu Item
		 * First we register a custom post type that is not public, which will represent each
		 * menu item option a user can have for each product.
		 * @since 1.0
		 * @version 1.0
		 */
		public function register_menu_items() {

			$labels = array(
				'name'                  => 'uamplified.io',
				'singular_name'         => 'uamplified.io',
				'menu_name'             => 'uamplified.io',
				'name_admin_bar'        => 'uamplified.io',
				'archives'              => 'Item Archives',
				'attributes'            => 'Item Attributes',
				'parent_item_colon'     => 'Parent Item:',
				'all_items'             => 'All Items',
				'add_new_item'          => 'Add New Item',
				'add_new'               => 'Add New',
				'new_item'              => 'New Item',
				'edit_item'             => 'Edit Item',
				'update_item'           => 'Update Item',
				'view_item'             => 'View Item',
				'view_items'            => 'View Items',
				'search_items'          => 'Search Item',
				'not_found'             => 'Not found',
				'not_found_in_trash'    => 'Not found in Trash',
				'featured_image'        => 'Featured Image',
				'set_featured_image'    => 'Set featured image',
				'remove_featured_image' => 'Remove featured image',
				'use_featured_image'    => 'Use as featured image',
				'insert_into_item'      => 'Insert into item',
				'uploaded_to_this_item' => 'Uploaded to this item',
				'items_list'            => 'Items list',
				'items_list_navigation' => 'Items list navigation',
				'filter_items_list'     => 'Filter items list',
			);
			$args = array(
				'label'                 => 'uamplified.io',
				'description'           => '',
				'labels'                => $labels,
				'supports'              => array( 'title', 'editor', 'custom-fields' ),
				'hierarchical'          => true,
				'public'                => false,
				'show_ui'               => UAMPLIFIED_IO_MENU_DEBUG,
				'show_in_menu'          => true,
				'menu_position'         => 5,
				'show_in_admin_bar'     => false,
				'show_in_nav_menus'     => true,
				'can_export'            => false,
				'has_archive'           => false,
				'exclude_from_search'   => true,
				'publicly_queryable'    => false,
				'rewrite'               => false,
				'capability_type'       => 'page',
				'show_in_rest'          => false,
			);
			register_post_type( 'uamp_menu', $args );

			global $uamp_load_menu_script;

			$uamp_load_menu_script = false;

			if ( get_option( 'uamplified-menu-version', false ) != UAMPLIFIED_IO_MENU_VERSION ) {

				$products = get_connected_uamplified_products();
				if ( ! empty( $products ) ) {

					$menu_item_ids = get_option( 'uamplified-menu-ids', array() );
					foreach ( $products as $product_id => $product ) {

						$menu_item_ids[ $product_id ] = array( 'listen' => 0, 'talk' => 0, 'launch' => 0 );

						if ( $product->listen !== false ) {
							$menu_item_id = wp_insert_post( array(
								'post_type'      => 'uamp_menu',
								'post_title'     => sprintf( '%s: %s', esc_html( $product->title ), esc_html( $product->listen->title ) ),
								'post_status'    => 'publish',
								'comment_status' => 'closed',
								'ping_status'    => 'closed'
							), true );

							if ( ! is_wp_error( $menu_item_id ) ) {
								$menu_item_ids[ $product_id ]['listen'] = $menu_item_id;
								add_post_meta( $menu_item_id, 'product_id', $product_id, true );
								add_post_meta( $menu_item_id, 'product_module', 'listen', true );
							}
						}

						if ( $product->talk !== false ) {
							$menu_item_id = wp_insert_post( array(
								'post_type'      => 'uamp_menu',
								'post_title'     => sprintf( '%s: %s', esc_html( $product->title ), esc_html( $product->talk->title ) ),
								'post_status'    => 'publish',
								'comment_status' => 'closed',
								'ping_status'    => 'closed'
							), true );

							if ( ! is_wp_error( $menu_item_id ) ) {
								$menu_item_ids[ $product_id ]['talk'] = $menu_item_id;
								add_post_meta( $menu_item_id, 'product_id', $product_id, true );
								add_post_meta( $menu_item_id, 'product_module', 'talk', true );
							}
						}

						if ( $product->launch !== false ) {
							$menu_item_id = wp_insert_post( array(
								'post_type'      => 'uamp_menu',
								'post_title'     => sprintf( '%s: %s', esc_html( $product->title ), esc_html( $product->launch->title ) ),
								'post_status'    => 'publish',
								'comment_status' => 'closed',
								'ping_status'    => 'closed'
							), true );

							if ( ! is_wp_error( $menu_item_id ) ) {
								$menu_item_ids[ $product_id ]['launch'] = $menu_item_id;
								add_post_meta( $menu_item_id, 'product_id', $product_id, true );
								add_post_meta( $menu_item_id, 'product_module', 'launch', true );
							}
						}

					}

					update_option( 'uamplified-menu-ids', $menu_item_ids );
					update_option( 'uamplified-menu-version', UAMPLIFIED_IO_MENU_VERSION );

				}

			}

		}

		/**
		 * Filter Menu Items
		 * While we respect a title that might have been set for our menu items, the rest is replaced
		 * by inserting an "indicator" before the title and to prep the results, which we will show later.
		 * @since 1.0
		 * @version 1.0
		 */
		public function filter_menu_items( $items ) {

			global $uamp_load_menu_script;

			$items_to_remove = array();
			$products        = get_connected_uamplified_products();
			if ( empty( $products ) ) return $items;

			if ( ! empty( $items ) ) {
				foreach ( $items as $menu_item_id => $menu_item ) {

					if ( $menu_item->object == 'uamp_menu' ) {

						$product_id                              = get_post_meta( $menu_item->object_id, 'product_id', true );
						$module_id                               = get_post_meta( $menu_item->object_id, 'product_module', true );
						$selected_product                        = ( array_key_exists( $product_id, $products ) ) ? $products[ $product_id ] : false;

						if ( $selected_product === false || $selected_product->$module_id === false ) {

							$items_to_remove[] = $menu_item_id;
							continue;

						}

						$args                                    = maybe_adjust_uamplified_args( array(
							'theme'      => 'light',
							'width'      => '300px',
							'height'     => '150px',
							'module'     => $module_id,
							'api_key'    => $selected_product->api_key,
							'number'     => 5,
							'type'       => 'all',
							'ordeby'     => 'date',
							'order'      => 'desc',
							'cache'      => 'hourly'
						), $menu_item->description );

						$results                                   = generate_uamplified_menu_item_popup( $args, $selected_product );

						$item_title                                = ( $menu_item->title != '' ) ? '<span class="uamp-widget-title">' . $menu_item->title . '</span>' : '';

						$item_template                             = '<span class="uamp-indicator"><span class="#indicator#"></span></span>#title#';

						$item_template                             = str_replace( '#indicator#',     $results['indicator'], $item_template );
						$item_template                             = str_replace( '#title#',         $item_title, $item_template );

						$items[ $menu_item_id ]->title             = $item_template;
						$items[ $menu_item_id ]->url               = '#';
						$items[ $menu_item_id ]->classes[]         = 'has-uamp-widget';
						$items[ $menu_item_id ]->classes[]         = $args['theme'];
						$items[ $menu_item_id ]->description       = '';

						$items[ $menu_item_id ]->uamp_product_id   = $product_id;
						$items[ $menu_item_id ]->uamp_product      = esc_html( $selected_product->title );
						$items[ $menu_item_id ]->uamp_module       = $module_id;
						$items[ $menu_item_id ]->uamp_itemids      = $results['ids'];
						$items[ $menu_item_id ]->uamp_item_count   = count( explode( ',', $results['ids'] ) );
						$items[ $menu_item_id ]->uamp_popup        = $results['popup'];
						$items[ $menu_item_id ]->uamp_popup_width  = $args['width'];
						$items[ $menu_item_id ]->uamp_popup_height = $args['height'];
						$items[ $menu_item_id ]->uamp_module_url   = $selected_product->$module_id->url;

						$uamp_load_menu_script = true;

					}

				}
			}

			if ( ! empty( $items_to_remove ) ) {
				foreach ( $items_to_remove as $item_id ) {
					unset( $items[ $item_id ] );
				}
			}

			return $items;

		}

		/**
		 * Filter Menu Attributes
		 * Sets the required attributes for the anchor link. Mainly used by our javascript code.
		 * @since 1.0
		 * @version 1.0
		 */
		public function filter_menu_attributes( $atts, $item, $args, $depth ) {

			if ( $item->object == 'uamp_menu' ) {

				if ( array_key_exists( 'class', $atts ) )
					$atts['class'] .= ' uamp-widget';

				else
					$atts['class'] = 'uamp-widget';

				$atts['data-pid']    = $item->uamp_product_id;
				$atts['data-module'] = $item->uamp_module;
				$atts['data-ids']    = $item->uamp_itemids;

			}

			return $atts;

		}

		/**
		 * Filter Final Menu
		 * Triggered by the menu walker, here is where we insert the popup modal after our anchor link.
		 * Will not work if the walker_nav_menu_start_el filter is not available in a custom Walker!
		 * @since 1.0
		 * @version 1.0
		 */
		public function filter_final_menu( $item_output, $item, $depth, $args ) {

			if ( isset( $item->uamp_popup ) ) {

				$template = '<div class="uamp-widget-popup" style="display: none; width: #width#; height: #height#;">
		<div class="arrow-up"></div>
		<div class="uamp-widget-popup-inner">
			<div class="uamp-widget-header">#product-title#</div>
			#popup-content#
		</div>
	</div>';

				$popup    = str_replace( '#product-title#', $item->uamp_product, $template );
				$popup    = str_replace( '#popup-content#', $item->uamp_popup, $popup );
				$popup    = str_replace( '#module-url#',    $item->uamp_module_url, $popup );
				$popup    = str_replace( '#width#',         $item->uamp_popup_width, $popup );
				$popup    = str_replace( '#height#',        ( ( $item->uamp_item_count < 2 ) ? 'auto' : $item->uamp_popup_height ), $popup );

				if ( $args->after == '' )
					$item_output .= $popup;

				else {
					$item_output = str_replace( $args->after, $popup . $args->after, $item_output );
				}

			}

			return $item_output;

		}

		/**
		 * Load Popup Script
		 * @since 1.0
		 * @version 1.0
		 */
		public function maybe_load_popup_script() {

			global $uamp_load_menu_script;

			if ( $uamp_load_menu_script ) {

				wp_enqueue_script( 'jquery' );

?>
<script type="text/javascript">
function uamp_getCookie(name) { var dc = document.cookie; var prefix = name + "="; var begin = dc.indexOf("; " + prefix); if (begin == -1) { begin = dc.indexOf(prefix); if (begin != 0) return null; } else { begin += 2; var end = document.cookie.indexOf(";", begin); if (end == -1) { end = dc.length; } } return decodeURI(dc.substring(begin + prefix.length, end)); } function uamp_setCookie(name,value,days) { uamp_eraseCookie(name); var expires = ""; if (days) { var date = new Date(); date.setTime(date.getTime() + (days*24*60*60*1000)); expires = "; expires=" + date.toUTCString(); } document.cookie = name + "=" + (value || "")  + expires + "; path=/"; } function uamp_eraseCookie(name) { document.cookie = name+'=; Max-Age=-99999999;'; }
jQuery(function($){

	var close_all_uamp_popups = function() {

		$( 'a.uamp-widget' ).each(function(){

			var uampwidget     = $(this);
			if ( uampwidget.hasClass( 'open') ) {
				var uampwidgetpop  = uampwidget.next();
				uampwidgetpop.hide();
				uampwidget.removeClass( 'open' );
			}

		});

		return true;

	};

	$( 'a.uamp-widget' ).each(function(){

		var uampwidget     = $(this);
		var uampwidgetind  = uampwidget.find( '.uamp-indicator span' );
		var uampwidgetpop  = uampwidget.next();

		uampwidgetpop.hide();

		var uampwidgetids  = uampwidget.data( 'ids' );
		if ( uampwidgetids == '0' ) return false;

		var uampwidgetkey  = uampwidget.data( 'pid' );
		var uampwidgmodule = uampwidget.data( 'module' );

		var uampwidcookey  = 'uamp' + uampwidgmodule + uampwidgetkey;
		var uampwidgetcoo  = uamp_getCookie( uampwidcookey );
		if ( uampwidgetcoo == null || uampwidgetcoo != uampwidgetids ) {
			uampwidgetind.addClass( 'pulse' );
		}

	});

	$(document).ready(function(){

		$( 'a.uamp-widget' ).click(function(e){

			e.preventDefault();

			var widgetanchor    = $(this);
			var widgetindicator = widgetanchor.find( '.uamp-indicator span' );
			var widgetpopup     = widgetanchor.next();

			var widgetitemids   = widgetanchor.data( 'ids' );
			var widgetkey       = widgetanchor.data( 'pid' );
			var widgetmodule    = widgetanchor.data( 'module' );

			widgetindicator.removeClass( 'pulse' );

			var widgetcookiekey = 'uamp' + widgetmodule + widgetkey;
			var widgetcookie    = uamp_getCookie( widgetcookiekey );
			if ( widgetcookie == null || widgetcookie != widgetitemids ) {
				uamp_setCookie( widgetcookiekey, widgetitemids );
			}

			if ( widgetanchor.hasClass( 'open' ) ) {
				widgetanchor.removeClass( 'open' );
				widgetpopup.hide();
			}
			else {

				if ( close_all_uamp_popups() ) {
					widgetpopup.show();
					widgetanchor.addClass( 'open' );
				}

			}

		});

	});

});
</script>
<?php

			}

		}

		/**
		 * Contextual Help
		 * @since 1.0
		 * @version 1.0
		 */
		public function contextual_help( $contextual_help, $screen_id ) {

			if ( $screen_id == 'nav-menus' ) {

				get_current_screen()->add_help_tab( array(
					'id'        => 'uamp-menu-items',
					'title'     => __( 'uamplified.io', 'uamplified' ),
					'content'   => '
<p>' . __( 'You can select to insert product module popups as a menu item using the uamplified.io menu item.', 'uamplified' ) . '</p>
<p><strong>' . __( 'Customizations', 'uamplified' ) . '</strong></p>
<p>' . __( 'You can set the "Navigation Label" to anything you like, or leave it empty to only show the indicator. You can also add any "CSS Classes" or "Link Relationship". To customize the popup window, you will however need to use the "Description" field for the selected menu item. If you do not see this field, click on "Screen Options" and select to show the "Description" field.', 'uamplified' ) . '</p>
<p>' . __( 'Each variable you want to override and it\'s corresponding value needs to be set in the following format:', 'uamplified' ) . ' <code>{var}={value}</code> ' . __( 'Multiple variables are separated by commas. ex:', 'uamplified' ) . ' <code>{var}={value},{var}={value}</code>.</p>
<p><strong>' . __( 'Popup Options', 'uamplified' ) . '</strong></p>
<table class="widefat">
	<thead>
		<tr>
			<th style="width: 20%;">Variable</th>
			<th style="width: 20%;">Default Value</th>
			<th style="width: 20%;">Possible Values</th>
			<th style="width: auto;">Description</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><code>theme</code></td>
			<td><code>theme=light</code></td>
			<td><code>light</code> or <code>dark</code></td>
			<td>Sets the color theme for the popup.</td>
		</tr>
		<tr>
			<td><code>width</code></td>
			<td><code>width=300px</code></td>
			<td>Any valid CSS width</td>
			<td>Sets the width of the popup element.</td>
		</tr>
		<tr>
			<td><code>height</code></td>
			<td><code>height=150px</code></td>
			<td>Any valid CSS height</td>
			<td>Sets the height of the popup element. If the content of the popup exceeds this set height, the content will become scrollable.</td>
		</tr>
		<tr>
			<td><code>number</code></td>
			<td><code>number=5</code></td>
			<td>Any positive integer</td>
			<td>Sets the number of items to show in the popup. Note that the maximum requests you can show in a widget is 20.</td>
		</tr>
		<tr>
			<td><code>cache</code></td>
			<td><code>cache=hourly</code></td>
			<td><code>hourly</code> or <code>daily</code></td>
			<td>Sets how long the results should be cached.</td>
		</tr>
	</tbody>
</table>'
				) );

			}

			return $contextual_help;

		}

	}
endif;

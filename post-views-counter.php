<?php
/*
Plugin Name: Post Views Counter
Description: Post Views Counter allows you to display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.
Version: 1.2.8
Author: dFactory
Author URI: http://www.dfactory.eu/
Plugin URI: http://www.dfactory.eu/plugins/post-views-counter/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: post-views-counter
Domain Path: /languages

Post Views Counter
Copyright (C) 2014-2017, Digital Factory - info@digitalfactory.pl

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
    exit;

if ( ! class_exists( 'Post_Views_Counter' ) ) :

	/**
	 * Post Views Counter final class.
	 *
	 * @class Post_Views_Counter
	 * @version	1.2.8
	 */
	final class Post_Views_Counter {

		private static $instance;
		public $options;
		public $defaults = array(
			'general'	 => array(
				'post_types_count'		 => array( 'post' ),
				'counter_mode'			 => 'php',
				'post_views_column'		 => true,
				'time_between_counts'	 => array(
					'number' => 24,
					'type'	 => 'hours'
				),
				'reset_counts'			 => array(
					'number' => 30,
					'type'	 => 'days'
				),
				'flush_interval'		 => array(
					'number' => 0,
					'type'	 => 'minutes'
				),
				'exclude'				 => array(
					'groups' => array(),
					'roles'	 => array()
				),
				'exclude_ips'			 => array(),
				'restrict_edit_views'	 => false,
				'deactivation_delete'	 => false,
				'cron_run'				 => true,
				'cron_update'			 => true
			),
			'display'	 => array(
				'label'				 => 'Post Views:',
				'post_types_display' => array( 'post' ),
				'page_types_display' => array( 'singular' ),
				'restrict_display'	 => array(
					'groups' => array(),
					'roles'	 => array()
				),
				'position'			 => 'after',
				'display_style'		 => array(
					'icon'	 => true,
					'text'	 => true
				),
				'link_to_post'		 => true,
				'icon_class'		 => 'dashicons-chart-bar'
			),
			'version'	 => '1.2.8'
		);

		/**
		 * Disable object clone.
		 */
		private function __clone() {
			
		}

		/**
		 * Disable unserializing of the class.
		 */
		private function __wakeup() {
			
		}

		/**
		 * Main plugin instance,
		 * Insures that only one instance of the plugin exists in memory at one time.
		 * 
		 * @return object
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Post_Views_Counter ) ) {

				self::$instance = new Post_Views_Counter;
				self::$instance->define_constants();

				add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

				self::$instance->includes();

				self::$instance->update = new Post_Views_Counter_Update();
				self::$instance->settings = new Post_Views_Counter_Settings();
				self::$instance->query = new Post_Views_Counter_Query();
				self::$instance->cron = new Post_Views_Counter_Cron();
				self::$instance->counter = new Post_Views_Counter_Counter();
				self::$instance->columns = new Post_Views_Counter_Columns();
				self::$instance->crawler_detect = new Post_Views_Counter_Crawler_Detect();
				self::$instance->frontend = new Post_Views_Counter_Frontend();
				self::$instance->dashboard = new Post_Views_Counter_Dashboard();
				self::$instance->widgets = new Post_Views_Counter_Widgets();
			}
			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 *
		 * @return void
		 */
		private function define_constants() {
			define( 'POST_VIEWS_COUNTER_URL', plugins_url( '', __FILE__ ) );
			define( 'POST_VIEWS_COUNTER_PATH', plugin_dir_path( __FILE__ ) );
			define( 'POST_VIEWS_COUNTER_REL_PATH', dirname( plugin_basename( __FILE__ ) ) . '/' );
		}

		/**
		 * Include required files.
		 *
		 * @return void
		 */
		private function includes() {
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/update.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/settings.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/columns.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/query.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/cron.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/counter.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/crawler-detect.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/frontend.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/dashboard.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/widgets.php' );
		}

		/**
		 * Class constructor.
		 * 
		 * @return void
		 */
		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'multisite_activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'multisite_deactivation' ) );

			// settings
			$this->options = array(
				'general'	 => array_merge( $this->defaults['general'], get_option( 'post_views_counter_settings_general', $this->defaults['general'] ) ),
				'display'	 => array_merge( $this->defaults['display'], get_option( 'post_views_counter_settings_display', $this->defaults['display'] ) )
			);

			// actions
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'wp_loaded', array( $this, 'load_pluggable_functions' ), 10 );

			// filters
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		}

		/**
		 * Multisite activation.
		 * 
		 * @global object $wpdb
		 * @param bool $networkwide
		 */
		public function multisite_activation( $networkwide ) {
			if ( is_multisite() && $networkwide ) {
				global $wpdb;

				$activated_blogs = array();
				$current_blog_id = $wpdb->blogid;
				$blogs_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT blog_id FROM ' . $wpdb->blogs, '' ) );

				foreach ( $blogs_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->activate_single();
					$activated_blogs[] = (int) $blog_id;
				}

				switch_to_blog( $current_blog_id );
				update_site_option( 'post_views_counter_activated_blogs', $activated_blogs, array() );
			} else
				$this->activate_single();
		}

		/**
		 * Single site activation.
		 * 
		 * @global array $wp_roles
		 */
		public function activate_single() {
			global $wpdb, $charset_collate;

			// required for dbdelta
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			// create post views table
			dbDelta( '
				CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'post_views (
					id bigint unsigned NOT NULL,
					type tinyint(1) unsigned NOT NULL,
					period varchar(8) NOT NULL,
					count bigint unsigned NOT NULL,
					PRIMARY KEY  (type, period, id),
					UNIQUE INDEX id_type_period_count (id, type, period, count) USING BTREE,
					INDEX type_period_count (type, period, count) USING BTREE
				) ' . $charset_collate . ';'
			);

			// add default options
			add_option( 'post_views_counter_settings_general', $this->defaults['general'], '', 'no' );
			add_option( 'post_views_counter_settings_display', $this->defaults['display'], '', 'no' );
			add_option( 'post_views_counter_version', $this->defaults['version'], '', 'no' );

			// schedule cache flush
			$this->schedule_cache_flush();
		}

		/**
		 * Multisite deactivation.
		 * 
		 * @global array $wpdb
		 * @param bool $networkwide
		 */
		public function multisite_deactivation( $networkwide ) {
			if ( is_multisite() && $networkwide ) {
				global $wpdb;

				$current_blog_id = $wpdb->blogid;
				$blogs_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT blog_id FROM ' . $wpdb->blogs, '' ) );

				if ( ! ($activated_blogs = get_site_option( 'post_views_counter_activated_blogs', false, false )) )
					$activated_blogs = array();

				foreach ( $blogs_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->deactivate_single( true );

					if ( in_array( (int) $blog_id, $activated_blogs, true ) )
						unset( $activated_blogs[array_search( $blog_id, $activated_blogs )] );
				}

				switch_to_blog( $current_blog_id );
				update_site_option( 'post_views_counter_activated_blogs', $activated_blogs );
			} else
				$this->deactivate_single();
		}

		/**
		 * Single site deactivation.
		 * 
		 * @global array $wp_roles
		 * @param bool $multi
		 */
		public function deactivate_single( $multi = false ) {
			if ( $multi ) {
				$options = get_option( 'post_views_counter_settings_general' );
				$check = $options['deactivation_delete'];
			} else
				$check = $this->options['general']['deactivation_delete'];

			// delete default options
			if ( $check ) {
				delete_option( 'post_views_counter_settings_general' );
				delete_option( 'post_views_counter_settings_display' );

				global $wpdb;

				// delete table from database
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'post_views' );
			}

			// remove schedule
			wp_clear_scheduled_hook( 'pvc_reset_counts' );
			remove_action( 'pvc_reset_counts', array( Post_Views_Counter()->cron, 'reset_counts' ) );

			$this->remove_cache_flush();
		}

		/**
		 * Schedule cache flushing if it's not already scheduled.
		 * 
		 * @param bool $forced
		 */
		public function schedule_cache_flush( $forced = true ) {
			if ( $forced || ! wp_next_scheduled( 'pvc_flush_cached_counts' ) ) {
				wp_schedule_event( time(), 'post_views_counter_flush_interval', 'pvc_flush_cached_counts' );
			}
		}

		/**
		 * Remove scheduled cache flush and the corresponding action.
		 */
		public function remove_cache_flush() {
			wp_clear_scheduled_hook( 'pvc_flush_cached_counts' );
			remove_action( 'pvc_flush_cached_counts', array( Post_Views_Counter()->cron, 'flush_cached_counts' ) );
		}

		/**
		 * Load text domain.
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'post-views-counter', false, POST_VIEWS_COUNTER_REL_PATH . 'languages/' );
		}

		/**
		 * Load pluggable template functions.
		 */
		public function load_pluggable_functions() {
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/functions.php' );
		}

		/**
		 * Enqueue admin scripts and styles.
		 * 
		 * @global string $post_type
		 * @param string $page
		 */
		public function admin_enqueue_scripts( $page ) {
			wp_register_style(
				'pvc-admin', POST_VIEWS_COUNTER_URL . '/css/admin.css'
			);

			wp_register_script(
				'pvc-admin-settings', POST_VIEWS_COUNTER_URL . '/js/admin-settings.js', array( 'jquery' ), $this->defaults['version']
			);

			wp_register_script(
				'pvc-admin-post', POST_VIEWS_COUNTER_URL . '/js/admin-post.js', array( 'jquery' ), $this->defaults['version']
			);

			wp_register_script(
				'pvc-admin-quick-edit', POST_VIEWS_COUNTER_URL . '/js/admin-quick-edit.js', array( 'jquery', 'inline-edit-post' ), $this->defaults['version']
			);

			// load on PVC settings page
			if ( $page === 'settings_page_post-views-counter' ) {

				wp_enqueue_script( 'pvc-admin-settings' );

				wp_localize_script(
					'pvc-admin-settings', 'pvcArgsSettings', array(
						'resetToDefaults' => __( 'Are you sure you want to reset these settings to defaults?', 'post-views-counter' ),
						'resetViews' => __( 'Are you sure you want to delete all existing data?', 'post-views-counter' )
					)
				);

				wp_enqueue_style( 'pvc-admin' );

				// load on single post page
			} elseif ( $page === 'post.php' || $page === 'post-new.php' ) {

				$post_types = Post_Views_Counter()->options['general']['post_types_count'];

				global $post_type;

				if ( ! in_array( $post_type, (array) $post_types ) )
					return;

				wp_enqueue_style( 'pvc-admin' );
				wp_enqueue_script( 'pvc-admin-post' );
			} elseif ( $page === 'edit.php' ) {
				$post_types = Post_Views_Counter()->options['general']['post_types_count'];

				global $post_type;

				if ( ! in_array( $post_type, (array) $post_types ) )
					return;

				wp_enqueue_style( 'pvc-admin' );
				wp_enqueue_script( 'pvc-admin-quick-edit' );
			}
		}
		
		/**
		 * Add links to plugin support forum.
		 * 
		 * @param array $links
		 * @param string $file
		 * @return array
		 */
		public function plugin_row_meta( $links, $file ) {

			if ( ! current_user_can( 'install_plugins' ) )
				return $links;

			$plugin = plugin_basename( __FILE__ );

			if ( $file == $plugin ) {
				return array_merge(
					$links, array( sprintf( '<a href="http://www.dfactory.eu/support/forum/post-views-counter/" target="_blank">%s</a>', __( 'Support', 'post-views-counter' ) ) )
				);
			}

			return $links;
		}

		/**
		 * Add link to settings page.
		 * 
		 * @staticvar string $plugin
		 * @param array $links
		 * @param string $file
		 * @return array
		 */
		public function plugin_action_links( $links, $file ) {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) )
				return $links;

			static $plugin;

			$plugin = plugin_basename( __FILE__ );

			if ( $file == $plugin ) {
				$settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php' ) . '?page=post-views-counter', __( 'Settings', 'post-views-counter' ) );

				array_unshift( $links, $settings_link );
			}

			return $links;
		}

		/**
		 * Add admin notices.
		 */
		public function add_notice( $html = '', $status = '', $paragraph = false ) {
			$this->notices[] = array(
				'html'		 => $html,
				'status'	 => $status,
				'paragraph'	 => $paragraph
			);

			add_action( 'admin_notices', array( $this, 'display_notice' ) );
		}

		/**
		 * Print admin notices.
		 */
		public function display_notice() {
			foreach ( $this->notices as $notice ) {
				echo '
				<div class="post-views-counter ' . $notice['status'] . '">
					' . ( $notice['paragraph'] ? '<p>' : '' ) . '
					' . $notice['html'] . '
					' . ( $notice['paragraph'] ? '</p>' : '' ) . '
				</div>';
			}
		}
	}

endif; // end if class_exists check

/**
 * Initialise Post Views Counter.
 * 
 * @return object
 */
function Post_Views_Counter() {
	static $instance;

	// first call to instance() initializes the plugin
	if ( $instance === null || ! ( $instance instanceof Post_Views_Counter ) )
		$instance = Post_Views_Counter::instance();

	return $instance;
}

Post_Views_Counter();

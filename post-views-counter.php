<?php
/*
Plugin Name: Post Views Counter
Description: Post Views Counter allows you to display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.
Version: 1.3.2
Author: Digital Factory
Author URI: http://www.dfactory.eu/
Plugin URI: http://www.dfactory.eu/plugins/post-views-counter/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: post-views-counter
Domain Path: /languages

Post Views Counter
Copyright (C) 2014-2020, Digital Factory - info@digitalfactory.pl

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
	 * @version	1.3.2
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
				'strict_counts'			 => false,
				'restrict_edit_views'	 => false,
				'deactivation_delete'	 => false,
				'cron_run'				 => true,
				'cron_update'			 => true,
				'update_version'		=> 1,
				'update_notice'			=> true,
				'update_delay_date'		=> 0
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
			'version'	 => '1.3.2'
		);

		/**
		 * Disable object clone.
		 */
		private function __clone() {}

		/**
		 * Disable unserializing of the class.
		 */
		private function __wakeup() {}

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

				// minimal setup for Fast AJAX
				if ( defined( 'SHORTINIT' ) && SHORTINIT ) {
					include_once( POST_VIEWS_COUNTER_PATH . 'includes/counter.php' );
					include_once( POST_VIEWS_COUNTER_PATH . 'includes/crawler-detect.php' );
					include_once( POST_VIEWS_COUNTER_PATH . 'includes/functions.php' );
					
					self::$instance->counter = new Post_Views_Counter_Counter();
					self::$instance->crawler_detect = new Post_Views_Counter_Crawler_Detect();
				// regular setup
				} else {
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
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 *
		 * @return void
		 */
		private function define_constants() {
			// fix plugin_basename empty $wp_plugin_paths var
			if ( ! ( defined( 'SHORTINIT' ) && SHORTINIT ) ) {
				define( 'POST_VIEWS_COUNTER_URL', plugins_url( '', __FILE__ ) );
				define( 'POST_VIEWS_COUNTER_REL_PATH', dirname( plugin_basename( __FILE__ ) ) . '/' );
			}
			
			define( 'POST_VIEWS_COUNTER_PATH', plugin_dir_path( __FILE__ ) );
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
			if ( defined( 'SHORTINIT' ) && SHORTINIT ) {
				$this->options = array(
					'general'	 => array_merge( $this->defaults['general'], get_option( 'post_views_counter_settings_general', $this->defaults['general'] ) ),
					'display'	 => array_merge( $this->defaults['display'], get_option( 'post_views_counter_settings_display', $this->defaults['display'] ) )
				);
			} else {
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
				// add_action( 'init', array( $this, 'gutenberg_blocks' ) );
				add_action( 'admin_init', array( $this, 'update_notice' ) );
				add_action( 'wp_ajax_pvc_dismiss_notice', array( $this, 'dismiss_notice' ) );

				// filters
				add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
				add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
			}
		}

		/**
		 * Update notice.
		 *
		 * @return void
		 */
		public function update_notice() {
			if ( ! current_user_can( 'install_plugins' ) )
				return;

			$current_update = 2;

			// get current time
			$current_time = time();

			if ( $this->options['general']['update_version'] < $current_update ) {
				// check version, if update version is lower than plugin version, set update notice to true
				$this->options['general'] = array_merge( $this->options['general'], array( 'update_version' => $current_update, 'update_notice' => true ) );

				update_option( 'post_views_counter_settings_general', $this->options['general'] );

				// set activation date
				$activation_date = get_option( 'post_views_counter_activation_date' );

				if ( $activation_date === false )
					update_option( 'post_views_counter_activation_date', $current_time );
			}

			// display current version notice
			if ( $this->options['general']['update_notice'] === true ) {
				// include notice js, only if needed
				add_action( 'admin_print_scripts', array( $this, 'admin_inline_js' ), 999 );

				// get activation date
				$activation_date = get_option( 'post_views_counter_activation_date' );

				if ( (int) $this->options['general']['update_delay_date'] === 0 ) {
					if ( $activation_date + 1209600 > $current_time )
						$this->options['general']['update_delay_date'] = $activation_date + 1209600;
					else
						$this->options['general']['update_delay_date'] = $current_time;

					update_option( 'post_views_counter_settings_general', $this->options['general'] );
				}

				if ( ( ! empty( $this->options['general']['update_delay_date'] ) ? (int) $this->options['general']['update_delay_date'] : $current_time ) <= $current_time )
					$this->add_notice( sprintf( __( "Hey, you've been using <strong>Post Views Counter</strong> for more than %s.", 'post-views-counter' ), human_time_diff( $activation_date, $current_time ) ) . '<br />' . __( 'Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation.', 'post-views-counter' ) . '<br /><br />' . __( 'Your help is much appreciated. Thank you very much', 'post-views-counter' ) . ' ~ <strong>Bartosz Arendt</strong>, ' . sprintf( __( 'founder of <a href="%s" target="_blank">dFactory</a> plugins.', 'post-views-counter' ), 'https://dfactory.eu/' ) . '<br /><br />' . sprintf( __( '<a href="%s" class="pvc-dismissible-notice" target="_blank" rel="noopener">Ok, you deserve it</a><br /><a href="javascript:void(0);" class="pvc-dismissible-notice pvc-delay-notice" rel="noopener">Nope, maybe later</a><br /><a href="javascript:void(0);" class="pvc-dismissible-notice" rel="noopener">I already did</a>', 'post-views-counter' ), 'https://wordpress.org/support/plugin/post-views-counter/reviews/?filter=5#new-post' ), 'notice notice-info is-dismissible pvc-notice' );
			}
		}

		/**
		 * Add admin notices.
		 * 
		 * @param string $html
		 * @param string $status
		 * @param bool $paragraph
		 */
		public function add_notice( $html = '', $status = 'error', $paragraph = true ) {
			$this->notices[] = array(
				'html' 		=> $html,
				'status' 	=> $status,
				'paragraph' => $paragraph
			);

			add_action( 'admin_notices', array( $this, 'display_notice' ) );
		}

		/**
		 * Print admin notices.
		 * 
		 * @return mixed
		 */
		public function display_notice() {
			foreach( $this->notices as $notice ) {
				echo '
				<div class="' . $notice['status'] . '">
					' . ( $notice['paragraph'] ? '<p>' : '' ) . '
					' . $notice['html'] . '
					' . ( $notice['paragraph'] ? '</p>' : '' ) . '
				</div>';
			}
		}

		/**
		 * Print admin scripts.
		 * 
		 * @return mixed
		 */
		public function admin_inline_js() {
			if ( ! current_user_can( 'install_plugins' ) )
				return;
			?>
			<script type="text/javascript">
				( function ( $ ) {
					$( document ).ready( function () {
						// save dismiss state
						$( '.pvc-notice.is-dismissible' ).on( 'click', '.notice-dismiss, .pvc-dismissible-notice', function ( e ) {
							var notice_action = 'hide';
							
							if ( $( e.currentTarget ).hasClass( 'pvc-delay-notice' ) ) {
								notice_action = 'delay'
							}

							$.post( ajaxurl, {
								action: 'pvc_dismiss_notice',
								notice_action: notice_action,
								url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
								nonce: '<?php echo wp_create_nonce( 'pvc_dismiss_notice' ); ?>'
							} );

							$( e.delegateTarget ).slideUp( 'fast' );
						} );
					} );
				} )( jQuery );
			</script>
			<?php
		}

		/**
		 * Dismiss notice.
		 */
		public function dismiss_notice() {
			if ( ! current_user_can( 'install_plugins' ) )
				return;

			if ( wp_verify_nonce( esc_attr( $_REQUEST['nonce'] ), 'pvc_dismiss_notice' ) ) {
				$notice_action = empty( $_REQUEST['notice_action'] ) || $_REQUEST['notice_action'] === 'hide' ? 'hide' : esc_attr( $_REQUEST['notice_action'] );

				switch ( $notice_action ) {
					// delay notice
					case 'delay':
						// set delay period to 1 week from now
						$this->options['general'] = array_merge( $this->options['general'], array( 'update_delay_date' => time() + 1209600 ) );
						update_option( 'post_views_counter_settings_general', $this->options['general'] );
						break;

					// hide notice
					default:
						$this->options['general'] = array_merge( $this->options['general'], array( 'update_notice' => false ) );
						$this->options['general'] = array_merge( $this->options['general'], array( 'update_delay_date' => 0 ) );

						update_option( 'post_views_counter_settings_general', $this->options['general'] );
				}
			}

			exit;
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

				if ( ! ( $activated_blogs = get_site_option( 'post_views_counter_activated_blogs', false, false ) ) )
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
			if ( $forced || ! wp_next_scheduled( 'pvc_flush_cached_counts' ) )
				wp_schedule_event( time(), 'post_views_counter_flush_interval', 'pvc_flush_cached_counts' );
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
		 * Add Gutenberg blocks.
		 */
		public function gutenberg_blocks() {
			wp_register_script( 'pvc-admin-block-views', POST_VIEWS_COUNTER_URL . '/js/admin-block.js', array( 'wp-blocks', 'wp-element', 'wp-i18n' ) );

			register_block_type( 'post-views-counter/views', array( 'editor_script' => 'pvc-admin-block-views' ) );
		}

		/**
		 * Enqueue admin scripts and styles.
		 * 
		 * @global string $post_type
		 * @param string $page
		 */
		public function admin_enqueue_scripts( $page ) {
			wp_register_style( 'pvc-admin', POST_VIEWS_COUNTER_URL . '/css/admin.css' );

			wp_register_script( 'pvc-admin-settings', POST_VIEWS_COUNTER_URL . '/js/admin-settings.js', array( 'jquery' ), $this->defaults['version'] );
			wp_register_script( 'pvc-admin-post', POST_VIEWS_COUNTER_URL . '/js/admin-post.js', array( 'jquery' ), $this->defaults['version'] );
			wp_register_script( 'pvc-admin-quick-edit', POST_VIEWS_COUNTER_URL . '/js/admin-quick-edit.js', array( 'jquery', 'inline-edit-post' ), $this->defaults['version'] );

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
			// edit post
			} elseif ( $page === 'edit.php' ) {
				$post_types = Post_Views_Counter()->options['general']['post_types_count'];

				global $post_type;

				if ( ! in_array( $post_type, (array) $post_types ) )
					return;

				wp_enqueue_style( 'pvc-admin' );

				// woocommerce
				if ( get_post_type() !== 'product' )
					wp_enqueue_script( 'pvc-admin-quick-edit' );
			// widgets
			} elseif ( $page === 'widgets.php' )
				wp_enqueue_script( 'pvc-admin-widgets', POST_VIEWS_COUNTER_URL . '/js/admin-widgets.js', array( 'jquery' ), $this->defaults['version'] );
			// media
			elseif ( $page === 'upload.php' )
				wp_enqueue_style( 'pvc-admin' );
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

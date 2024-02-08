<?php
/*
Plugin Name: Post Views Counter
Description: Post Views Counter allows you to display how many times a post, page or custom post type had been viewed in a simple, fast and reliable way.
Version: 1.4.4
Author: dFactory
Author URI: https://dfactory.co/
Plugin URI: https://postviewscounter.com/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: post-views-counter
Domain Path: /languages

Post Views Counter
Copyright (C) 2014-2024, Digital Factory - info@digitalfactory.pl

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Post_Views_Counter' ) ) {
	/**
	 * Post Views Counter final class.
	 *
	 * @class Post_Views_Counter
	 * @version	1.4.4
	 */
	final class Post_Views_Counter {

		private static $instance;
		private $notices;
		public $options;
		public $defaults = [
			'general'	=> [
				'post_types_count'		=> [ 'post' ],
				'taxonomies_count'		=> false,
				'users_count'			=> false,
				'other_count'			=> false,
				'data_storage'			=> 'cookies',
				'amp_support'			=> false,
				'counter_mode'			=> 'php',
				'post_views_column'		=> true,
				'restrict_edit_views'	=> false,
				'time_between_counts'	=> [
					'number'	=> 24,
					'type'		=> 'hours'
				],
				'reset_counts'			=> [
					'number'	=> 0,
					'type'		=> 'days'
				],
				'object_cache'			=> false,
				'flush_interval'		=> [
					'number'	=> 0,
					'type'		=> 'minutes'
				],
				'exclude'				=> [
					'groups' => [],
					'roles'	 => []
				],
				'exclude_ips'			=> [],
				'strict_counts'			=> false,
				'cron_run'				=> true,
				'cron_update'			=> true,
				'update_version'		=> 1,
				'update_notice'			=> true,
				'update_delay_date'		=> 0
			],
			'display'	=> [
				'label'					=> 'Post Views:',
				'display_period'		=> 'total',
				'taxonomies_display'	=> [],
				'user_display'			=> false,
				'post_types_display'	=> [ 'post' ],
				'page_types_display'	=> [ 'singular' ],
				'restrict_display'		=> [
					'groups' => [],
					'roles'	 => []
				],
				'position'				=> 'after',
				'use_format'			=> true,
				'display_style'			=> [
					'icon'	 => true,
					'text'	 => true
				],
				'icon_class'			=> 'dashicons-chart-bar',
				'toolbar_statistics'	=> true
			],
			'other'		=> [
				'menu_position'			=> 'top',
				'import_meta_key'		=> 'views',
				'deactivation_delete'	=> false,
				'license'				=> ''
			],
			'version'	=> '1.4.4'
		];

		// instances
		public $counter;
		public $crawler;
		public $cron;
		public $dashboard;
		public $frontend;
		public $functions;
		public $settings_api;

		/**
		 * Disable object cloning.
		 *
		 * @return void
		 */
		public function __clone() {}

		/**
		 * Disable unserializing of the class.
		 *
		 * @return void
		 */
		public function __wakeup() {}

		/**
		 * Main plugin instance, insures that only one instance of the class exists in memory at one time.
		 *
		 * @return object
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Post_Views_Counter ) ) {
				self::$instance = new Post_Views_Counter();

				// short init?
				if ( defined( 'SHORTINIT' ) && SHORTINIT ) {
					include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-counter.php' );
					include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-crawler-detect.php' );
					include_once( POST_VIEWS_COUNTER_PATH . 'includes/functions.php' );

					self::$instance->counter = new Post_Views_Counter_Counter();
					self::$instance->crawler = new Post_Views_Counter_Crawler_Detect();
				// regular setup
				} else {
					add_action( 'init', [ self::$instance, 'load_textdomain' ] );

					self::$instance->includes();

					// create settings API
					self::$instance->settings_api = new Post_Views_Counter_Settings_API(
						[
							'object'		=> self::$instance,
							'prefix'		=> 'post_views_counter',
							'slug'			=> 'post-views-counter',
							'domain'		=> 'post-views-counter',
							'plugin'		=> 'Post Views Counter',
							'plugin_url'	=> POST_VIEWS_COUNTER_URL
						]
					);

					// initialize other classes
					self::$instance->functions = new Post_Views_Counter_Functions();

					new Post_Views_Counter_Update();
					new Post_Views_Counter_Settings();
					new Post_Views_Counter_Admin();
					new Post_Views_Counter_Query();

					self::$instance->cron = new Post_Views_Counter_Cron();
					self::$instance->counter = new Post_Views_Counter_Counter();

					new Post_Views_Counter_Columns();

					self::$instance->crawler = new Post_Views_Counter_Crawler_Detect();
					self::$instance->frontend = new Post_Views_Counter_Frontend();
					self::$instance->dashboard = new Post_Views_Counter_Dashboard();

					new Post_Views_Counter_Widgets();
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
				define( 'POST_VIEWS_COUNTER_BASENAME', plugin_basename( __FILE__ ) );
				define( 'POST_VIEWS_COUNTER_REL_PATH', dirname( POST_VIEWS_COUNTER_BASENAME ) );
			}

			define( 'POST_VIEWS_COUNTER_PATH', plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Include required files.
		 *
		 * @return void
		 */
		private function includes() {
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-functions.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-update.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-settings-api.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-settings.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-admin.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-columns.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-query.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-cron.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-counter.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-crawler-detect.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-frontend.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-dashboard.php' );
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/class-widgets.php' );
		}

		/**
		 * Class constructor.
		 *
		 * @return void
		 */
		private function __construct() {
			// define plugin constants
			$this->define_constants();

			// short init?
			if ( defined( 'SHORTINIT' ) && SHORTINIT ) {
				$this->options = [
					'general'	 => array_merge( $this->defaults['general'], get_option( 'post_views_counter_settings_general', $this->defaults['general'] ) ),
					'display'	 => array_merge( $this->defaults['display'], get_option( 'post_views_counter_settings_display', $this->defaults['display'] ) ),
					'other'		=> array_merge( $this->defaults['other'], get_option( 'post_views_counter_settings_other', $this->defaults['other'] ) )
				];

				return;
			}

			// activation hooks
			register_activation_hook( __FILE__, [ $this, 'activation' ] );
			register_deactivation_hook( __FILE__, [ $this, 'deactivation' ] );

			// settings
			$this->options = [
				'general'	=> array_merge( $this->defaults['general'], get_option( 'post_views_counter_settings_general', $this->defaults['general'] ) ),
				'display'	=> array_merge( $this->defaults['display'], get_option( 'post_views_counter_settings_display', $this->defaults['display'] ) ),
				'other'		=> array_merge( $this->defaults['other'], get_option( 'post_views_counter_settings_other', $this->defaults['other'] ) )
			];

			// actions
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
			add_action( 'wp_loaded', [ $this, 'load_pluggable_functions' ] );
			add_action( 'admin_init', [ $this, 'update_notice' ] );
			add_action( 'wp_initialize_site', [ $this, 'initialize_new_network_site' ] );
			add_action( 'wp_ajax_pvc_dismiss_notice', [ $this, 'dismiss_notice' ] );

			// filters
			add_filter( 'plugin_action_links_' . POST_VIEWS_COUNTER_BASENAME, [ $this, 'plugin_settings_link' ] );
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
				$this->options['general'] = array_merge(
					$this->options['general'],
					[
						'update_version'	=> $current_update,
						'update_notice'		=> true
					]
				);

				update_option( 'post_views_counter_settings_general', $this->options['general'] );

				// set activation date
				$activation_date = get_option( 'post_views_counter_activation_date' );

				if ( $activation_date === false )
					update_option( 'post_views_counter_activation_date', $current_time );
			}

			// display current version notice
			if ( $this->options['general']['update_notice'] === true ) {
				// include notice js, only if needed
				add_action( 'admin_print_scripts', [ $this, 'admin_inline_js' ], 999 );

				// get activation date
				$activation_date = get_option( 'post_views_counter_activation_date' );

				if ( (int) $this->options['general']['update_delay_date'] === 0 ) {
					if ( $activation_date + 2 * WEEK_IN_SECONDS > $current_time )
						$this->options['general']['update_delay_date'] = $activation_date + 2 * WEEK_IN_SECONDS;
					else
						$this->options['general']['update_delay_date'] = $current_time;

					update_option( 'post_views_counter_settings_general', $this->options['general'] );
				}

				if ( ( ! empty( $this->options['general']['update_delay_date'] ) ? (int) $this->options['general']['update_delay_date'] : $current_time ) <= $current_time )
					$this->add_notice( sprintf( __( "Hey, you've been using <strong>Post Views Counter</strong> for more than %s.", 'post-views-counter' ), human_time_diff( $activation_date, $current_time ) ) . '<br />' . __( 'Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation.', 'post-views-counter' ) . '<br /><br />' . __( 'Your help is much appreciated. Thank you very much', 'post-views-counter' ) . ' ~ <strong>Bartosz Arendt</strong>, ' . __( 'founder of', 'post-views-counter' ) . ' <a href="https://postviewscounter.com/" target="_blank">Post Views Counter</a>.' . '<br /><br />' . '<a href="https://wordpress.org/support/plugin/post-views-counter/reviews/?filter=5#new-post" class="pvc-dismissible-notice" target="_blank" rel="noopener">' . __( 'Ok, you deserve it', 'post-views-counter' ) . '</a><br /><a href="#" class="pvc-dismissible-notice pvc-delay-notice" rel="noopener">' . __( 'Nope, maybe later', 'post-views-counter' ) . '</a><br /><a href="#" class="pvc-dismissible-notice" rel="noopener">' . __( 'I already did', 'post-views-counter' ) . '</a>', 'notice notice-info is-dismissible pvc-notice' );
			}
		}

		/**
		 * Add admin notices.
		 *
		 * @param string $html Notice HTML
		 * @param string $status Notice status
		 * @param bool $paragraph Whether to use paragraph
		 * @param bool $network
		 * @return void
		 */
		public function add_notice( $html = '', $status = 'error', $paragraph = true, $network = false ) {
			$this->notices[] = [
				'html' 		=> $html,
				'status'	=> $status,
				'paragraph'	=> $paragraph
			];

			add_action( 'admin_notices', [ $this, 'display_notice' ] );

			if ( $network )
				add_action( 'network_admin_notices', [ $this, 'display_notice' ] );
		}

		/**
		 * Print admin notices.
		 *
		 * @return void
		 */
		public function display_notice() {
			$allowed_html = array_merge(
				wp_kses_allowed_html( 'post' ),
				[
					'input'	=> [
						'type'	=> true,
						'name'	=> true,
						'class'	=> true,
						'value'	=> true
					],
					'form'	=> [
						'action'	=> true,
						'method'	=> true
					]
				]
			);

			foreach ( $this->notices as $notice ) {
				echo '
				<div class="' . esc_attr( $notice['status'] ) . '">
					' . ( $notice['paragraph'] ? '<p>' : '' ) . '
					' . wp_kses( $notice['html'], $allowed_html ) . '
					' . ( $notice['paragraph'] ? '</p>' : '' ) . '
				</div>';
			}
		}

		/**
		 * Print admin scripts.
		 *
		 * @return void
		 */
		public function admin_inline_js() {
			if ( ! current_user_can( 'install_plugins' ) )
				return;

			// register and enqueue styles
			wp_register_script( 'pvc-notices', false );
			wp_enqueue_script( 'pvc-notices' );

			// add styles
			wp_add_inline_script( 'pvc-notices', "
				( function( $ ) {
					// ready event
					$( function() {
						// save dismiss state
						$( '.pvc-notice.is-dismissible' ).on( 'click', '.notice-dismiss, .pvc-dismissible-notice', function( e ) {
							if ( e.currentTarget.target !== '_blank' )
								e.preventDefault();

							var notice_action = 'hide';

							if ( e.currentTarget.classList.contains( 'pvc-delay-notice' ) )
								notice_action = 'delay';

							$.post( ajaxurl, {
								action: 'pvc_dismiss_notice',
								notice_action: notice_action,
								url: '" . esc_url_raw( admin_url( 'admin-ajax.php' ) ) . "',
								nonce: '" . esc_attr( wp_create_nonce( 'pvc_dismiss_notice' ) ) . "'
							} );

							$( e.delegateTarget ).slideUp( 'fast' );
						} );
					} );
				} )( jQuery );
			", 'after' );
		}

		/**
		 * Dismiss notice.
		 *
		 * @return void
		 */
		public function dismiss_notice() {
			if ( ! current_user_can( 'install_plugins' ) )
				return;

			if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'pvc_dismiss_notice' ) ) {
				$notice_action = empty( $_REQUEST['notice_action'] ) || $_REQUEST['notice_action'] === 'hide' ? 'hide' : sanitize_text_field( $_REQUEST['notice_action'] );

				switch ( $notice_action ) {
					// delay notice
					case 'delay':
						// set delay period to 1 week from now
						$this->options['general'] = array_merge(
							$this->options['general'],
							[
								'update_delay_date'	=> time() + 2 * WEEK_IN_SECONDS
							]
						);
						update_option( 'post_views_counter_settings_general', $this->options['general'] );
						break;

					// hide notice
					default:
						$this->options['general'] = array_merge(
							$this->options['general'],
							[
								'update_notice' => false
							]
						);
						$this->options['general'] = array_merge(
							$this->options['general'],
							[
								'update_delay_date' => 0
							]
						);

						update_option( 'post_views_counter_settings_general', $this->options['general'] );
				}
			}

			exit;
		}

		/**
		 * Plugin activation.
		 *
		 * @global object $wpdb
		 *
		 * @param bool $network
		 * @return void
		 */
		public function activation( $network ) {
			// network activation?
			if ( is_multisite() && $network ) {
				global $wpdb;

				// get all available sites
				$blogs_ids = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );

				foreach ( $blogs_ids as $blog_id ) {
					// change to another site
					switch_to_blog( (int) $blog_id );

					// run current site activation process
					$this->activate_site();

					restore_current_blog();
				}
			} else
				$this->activate_site();
		}

		/**
		 * Single site activation.
		 *
		 * @global object $wpdb
		 * @global string $charset_collate
		 *
		 * @return void
		 */
		public function activate_site() {
			global $wpdb, $charset_collate;

			// required for dbdelta
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			// create post views table
			dbDelta( '
				CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'post_views (
					`id` bigint unsigned NOT NULL,
					`type` tinyint(1) unsigned NOT NULL,
					`period` varchar(8) NOT NULL,
					`count` bigint unsigned NOT NULL,
					PRIMARY KEY  (type, period, id),
					UNIQUE INDEX id_type_period_count (id, type, period, count) USING BTREE,
					INDEX type_period_count (type, period, count) USING BTREE
				) ' . $charset_collate . ';'
			);

			// add default options
			add_option( 'post_views_counter_settings_general', $this->defaults['general'], null, false );
			add_option( 'post_views_counter_settings_display', $this->defaults['display'], null, false );
			add_option( 'post_views_counter_settings_other', $this->defaults['other'], null, false );
			add_option( 'post_views_counter_version', $this->defaults['version'], null, false );
		}

		/**
		 * Plugin deactivation.
		 *
		 * @global object $wpdb
		 *
		 * @param bool $network
		 * @return void
		 */
		public function deactivation( $network ) {
			// network deactivation?
			if ( is_multisite() && $network ) {
				global $wpdb;

				// get all available sites
				$blogs_ids = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );

				foreach ( $blogs_ids as $blog_id ) {
					// change to another site
					switch_to_blog( (int) $blog_id );

					// run current site deactivation process
					$this->deactivate_site( true );

					restore_current_blog();
				}
			} else
				$this->deactivate_site();
		}

		/**
		 * Single site deactivation.
		 *
		 * @global object $wpdb
		 *
		 * @param bool $multi
		 * @return void
		 */
		public function deactivate_site( $multi = false ) {
			if ( $multi === true ) {
				$options = get_option( 'post_views_counter_settings_other' );
				$check = $options['deactivation_delete'];
			} else
				$check = $this->options['other']['deactivation_delete'];

			// delete options if needed
			if ( $check ) {
				// delete options
				delete_option( 'post_views_counter_settings_general' );
				delete_option( 'post_views_counter_settings_display' );
				delete_option( 'post_views_counter_settings_other' );
				delete_option( 'post_views_counter_activation_date' );
				delete_option( 'post_views_counter_version' );

				// delete transients
				delete_transient( 'post_views_counter_ip_cache' );

				global $wpdb;

				// delete table from database
				$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'post_views' );
			}

			// remove schedule
			wp_clear_scheduled_hook( 'pvc_reset_counts' );

			remove_action( 'pvc_reset_counts', [ $this->cron, 'reset_counts' ] );
		}

		/**
		 * Initialize new network site.
		 *
		 * @param object $site
		 * @return void
		 */
		public function initialize_new_network_site( $site ) {
			if ( is_multisite() ) {
				// change to another site
				switch_to_blog( $site->blog_id );

				// run current site activation process
				$this->activate_site();

				restore_current_blog();
			}
		}

		/**
		 * Load text domain.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'post-views-counter', false, POST_VIEWS_COUNTER_REL_PATH . '/languages/' );
		}

		/**
		 * Load pluggable template functions.
		 *
		 * @return void
		 */
		public function load_pluggable_functions() {
			include_once( POST_VIEWS_COUNTER_PATH . 'includes/functions.php' );
		}

		/**
		 * Enqueue admin scripts and styles.
		 *
		 * @global string $post_type
		 *
		 * @param string $page
		 * @return void
		 */
		public function admin_enqueue_scripts( $page ) {
			// register styles
			wp_register_style( 'pvc-admin', POST_VIEWS_COUNTER_URL . '/css/admin.min.css', [], $this->defaults['version'] );

			// register scripts
			wp_register_script( 'pvc-admin-settings', POST_VIEWS_COUNTER_URL . '/js/admin-settings.js', [ 'jquery' ], $this->defaults['version'] );
			wp_register_script( 'pvc-admin-post', POST_VIEWS_COUNTER_URL . '/js/admin-post.js', [ 'jquery' ], $this->defaults['version'] );
			wp_register_script( 'pvc-admin-quick-edit', POST_VIEWS_COUNTER_URL . '/js/admin-quick-edit.js', [ 'jquery', 'inline-edit-post' ], $this->defaults['version'] );

			// load on pvc settings page
			if ( in_array( $page, [ 'toplevel_page_post-views-counter', 'settings_page_post-views-counter' ], true ) ) {
				wp_enqueue_script( 'pvc-admin-settings' );

				// prepare script data
				$script_data = [
					'resetToDefaults'	=> esc_html__( 'Are you sure you want to reset these settings to defaults?', 'post-views-counter' ),
					'resetViews'		=> esc_html__( 'Are you sure you want to delete all existing data?', 'post-views-counter' )
				];

				wp_add_inline_script( 'pvc-admin-settings', 'var pvcArgsSettings = ' . wp_json_encode( $script_data ) . ";\n", 'before' );

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
				wp_enqueue_script( 'pvc-admin-widgets', POST_VIEWS_COUNTER_URL . '/js/admin-widgets.js', [ 'jquery' ], $this->defaults['version'] );
			// media
			elseif ( $page === 'upload.php' )
				wp_enqueue_style( 'pvc-admin' );

			// register and enqueue styles
			wp_register_style( 'pvc-pro-style', false );
			wp_enqueue_style( 'pvc-pro-style' );

			// add styles
			wp_add_inline_style( 'pvc-pro-style', '
			.post-views-counter-settings tr.pvc-pro th:after, .nav-tab-wrapper a.nav-tab.nav-tab-disabled.pvc-pro:after, .post-views-counter-settings tr.pvc-pro-extended label[for="post_views_counter_general_counter_mode_ajax"]:after {
				content: \'PRO\';
				display: inline;
				background-color: #ffc107;
				color: white;
				padding: 2px 4px;
				text-align: center;
				border-radius: 4px;
				margin-left: 4px;
				font-weight: bold;
				font-size: 11px;
			}' );
		}

		/**
		 * Add link to Settings page.
		 *
		 * @param array $links
		 * @return array
		 */
		public function plugin_settings_link( $links ) {
			if ( ! current_user_can( 'manage_options' ) )
				return $links;

			// submenu?
			if ( $this->options['other']['menu_position'] === 'sub' )
				$url = admin_url( 'options-general.php?page=post-views-counter' );
			// topmenu?
			else
				$url = admin_url( 'admin.php?page=post-views-counter' );

			array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url_raw( $url ), esc_html__( 'Settings', 'post-views-counter' ) ) );

			return $links;
		}
	}
}

/**
 * Initialize Post Views Counter.
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
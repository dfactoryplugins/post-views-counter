<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Settings class.
 *
 * @class Post_Views_Counter_Settings
 */
class Post_Views_Counter_Settings {

	/**
	 * @var Post_Views_Counter_Settings_General
	 */
	public $general;

	/**
	 * @var Post_Views_Counter_Settings_Display
	 */
	public $display;

	/**
	 * @var Post_Views_Counter_Settings_Reports
	 */
	public $reports;

	/**
	 * @var Post_Views_Counter_Settings_Other
	 */
	public $other;

	/**
	 * @var Post_Views_Counter_Settings_Integrations
	 */
	public $integrations;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'pvc_settings_sidebar', [ $this, 'settings_sidebar' ], 12 );
		add_action( 'pvc_settings_form', [ $this, 'settings_form' ], 10, 4 );

		// filters
		add_filter( 'post_views_counter_settings_data', [ $this, 'settings_data' ] );
		add_filter( 'post_views_counter_settings_data', [ $this, 'settings_sections_compat' ], 99 );
		add_filter( 'post_views_counter_settings_pages', [ $this, 'settings_page' ] );
		add_filter( 'post_views_counter_settings_page_class', [ $this, 'settings_page_class' ] );
		add_filter( 'pvc_plugin_status_tables', [ $this, 'register_core_tables' ] );

		// instantiate page classes
		$this->general = new Post_Views_Counter_Settings_General();
		$this->display = new Post_Views_Counter_Settings_Display();
		$this->reports = new Post_Views_Counter_Settings_Reports();
		$this->integrations = new Post_Views_Counter_Settings_Integrations();
		$this->other = new Post_Views_Counter_Settings_Other( $this );
	}

	/**
	 * Magic method to proxy method calls to page classes for backward compatibility.
	 *
	 * @param string $method
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function __call( $method, $args ) {
		// Check if method exists in general page class
		if ( method_exists( $this->general, $method ) ) {
			return call_user_func_array( [ $this->general, $method ], $args );
		}

		// Check if method exists in display page class
		if ( method_exists( $this->display, $method ) ) {
			return call_user_func_array( [ $this->display, $method ], $args );
		}

		// Check if method exists in reports page class
		if ( method_exists( $this->reports, $method ) ) {
			return call_user_func_array( [ $this->reports, $method ], $args );
		}

		// Check if method exists in integrations page class
		if ( method_exists( $this->integrations, $method ) ) {
			return call_user_func_array( [ $this->integrations, $method ], $args );
		}

		// Check if method exists in other page class
		if ( method_exists( $this->other, $method ) ) {
			return call_user_func_array( [ $this->other, $method ], $args );
		}

		// Method not found
		throw new BadMethodCallException( "Method {$method} does not exist" );
	}

	/**
	 * Add hidden inputs to redirect to valid page after changing menu position.
	 *
	 * @param string $setting
	 * @param string $page_type
	 * @param string $url_page
	 * @param string $tab_key
	 *
	 * @return void
	 */
	public function settings_form( $setting, $page_type, $url_page, $tab_key ) {
		// get main instance
		$pvc = Post_Views_Counter();
		$menu_position = $pvc->get_menu_position();

		// topmenu referer
		$topmenu = '<input type="hidden" name="_wp_http_referer" data-pvc-menu="topmenu" value="' .esc_url( admin_url( 'admin.php?page=post-views-counter' . ( $tab_key !== '' ? '&tab=' . $tab_key : '' ) ) ) . '" />';

		// submenu referer
		$submenu = '<input type="hidden" name="_wp_http_referer" data-pvc-menu="submenu" value="' .esc_url( admin_url( 'options-general.php?page=post-views-counter' . ( $tab_key !== '' ? '&tab=' . $tab_key : '' ) ) ) . '" />';

		if ( $menu_position === 'sub' )
			echo $topmenu . $submenu;
		else
			echo $submenu . $topmenu;
	}

	/**
	 * Display settings sidebar.
	 *
	 * @return void
	 */
	public function settings_sidebar() {
		// get main instance
		$pvc = Post_Views_Counter();

		if ( ! class_exists( 'Post_Views_Counter_Pro' ) ) {
			echo '
			<div class="post-views-sidebar">
				<div class="post-views-credits">
					<div class="inside">
						<div class="inner">
							<div class="pvc-sidebar-info">
								<div class="pvc-sidebar-head">
									<h3 class="pvc-sidebar-title">Get Post Views Counter Pro</h3>
								</div>
								<div class="pvc-sidebar-body">
									<p><span class="pvc-icon pvc-icon-check"></span>' . __( '<b>Collect more accurate data</b> about the number of views of your site, regardless of what the user is visiting.', 'post-views-counter' ) . '</p>
									<p><span class="pvc-icon pvc-icon-check"></span>' . __( '<b>Unlock optimization features</b> and caching plugins compatibility to speed up view count tracking.', 'post-views-counter' ) . '</p>
									<p><span class="pvc-icon pvc-icon-check"></span>' . __( '<b>Take your insights to the next level</b> with customizable Views by Date, Post and Author reporting.', 'post-views-counter' ) . '</p>
									<p><span class="pvc-icon pvc-icon-check"></span>' . __( '<b>Order posts by views count</b> using built-in Elementor Pro, Divi Theme and GenerateBlocks integration.', 'post-views-counter' ) . '</p>
								</div>
								<div class="pvc-pricing-footer">
									<a href="https://postviewscounter.com/upgrade/?utm_source=post-views-counter-lite&utm_medium=button&utm_campaign=upgrade-to-pro" class="button button-secondary button-hero pvc-button" target="_blank">' . esc_html__( 'Upgrade to Pro', 'post-views-counter' ) . ' &rarr;</a>
									<p>Starting from $29 per year<br />14-day money back guarantee.</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>';
		}
	}

	/**
	 * Add settings data.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function settings_data( $settings ) {
		// add settings
		$settings['post-views-counter'] = [
			'label' => __( 'Post Views Counter Settings', 'post-views-counter' ),
			'form' => [
				'reports'	=> [
					'buttons'	=> false
				]
			],
			'option_name' => [
				'general'	=> 'post_views_counter_settings_general',
				'display'	=> 'post_views_counter_settings_display',
				'reports'	=> 'post_views_counter_settings_reports',
				'integrations'	=> 'post_views_counter_settings_integrations',
				'other'		=> 'post_views_counter_settings_other'
			],
			'validate' => [ $this, 'validate_settings' ],
			'sections' => array_merge(
				$this->general->get_sections(),
				$this->display->get_sections(),
				$this->reports->get_sections(),
				$this->other->get_sections(),
				$this->integrations->get_sections()
			),
			'fields' => array_merge(
				$this->general->get_fields(),
				$this->display->get_fields(),
				$this->reports->get_fields(),
				$this->other->get_fields(),
				$this->integrations->get_fields()
			)
		];

		return $settings;
	}
	
	/**
	 * Add settings page.
	 *
	 * @param array $pages
	 *
	 * @return array
	 */
	public function settings_page( $pages ) {
		// get main instance
		$pvc = Post_Views_Counter();
		$menu_position = $pvc->get_menu_position();

		// default page
		$pages['post-views-counter'] = [
			'menu_slug'		=> 'post-views-counter',
			'page_title'	=> __( 'Post Views Counter Settings', 'post-views-counter' ),
			'menu_title'	=> $menu_position === 'sub' ? __( 'Post Views Counter', 'post-views-counter' ) : __( 'Post Views', 'post-views-counter' ),
			'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
			'callback'		=> null,
			'tabs'			=> [
				'general'	 => [
					'label'			=> __( 'Counting', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_general'
				],
				'display'	 => [
					'label'			=> __( 'Display', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_display'
				],
				'reports'	=> [
					'label'			=> __( 'Reports', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_reports'
				],
				'integrations'	 => [
					'label'			=> __( 'Integrations', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_integrations'
				],
				'other'		=> [
					'label'			=> __( 'Other', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_other'
				]
			]
		];

		// update admin title
		add_filter( 'admin_title', [ $this, 'admin_title' ], 10, 2 );

		// submenu?
		if ( $menu_position === 'sub' ) {
			$pages['post-views-counter']['type'] = 'settings_page';
		// topmenu?
		} else {
			// highlight submenus
			add_filter( 'submenu_file', [ $this, 'submenu_file' ], 10, 2 );

			// add parameters
			$pages['post-views-counter']['type'] = 'page';
			$pages['post-views-counter']['icon'] = 'dashicons-chart-bar';
			$pages['post-views-counter']['position'] = '99.301';

			// add subpages
			$pages['post-views-counter-general'] = [
				'menu_slug'		=> 'post-views-counter',
				'parent_slug'	=> 'post-views-counter',
				'type'			=> 'subpage',
				'page_title'	=> __( 'Counting', 'post-views-counter' ),
				'menu_title'	=> __( 'Counting', 'post-views-counter' ),
				'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
				'callback'		=> null
			];

			$pages['post-views-counter-display'] = [
				'menu_slug'		=> 'post-views-counter&tab=display',
				'parent_slug'	=> 'post-views-counter',
				'type'			=> 'subpage',
				'page_title'	=> __( 'Display', 'post-views-counter' ),
				'menu_title'	=> __( 'Display', 'post-views-counter' ),
				'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
				'callback'		=> null
			];

			$pages['post-views-counter-reports'] = [
				'menu_slug'		=> 'post-views-counter&tab=reports',
				'parent_slug'	=> 'post-views-counter',
				'type'			=> 'subpage',
				'page_title'	=> __( 'Reports', 'post-views-counter' ),
				'menu_title'	=> __( 'Reports', 'post-views-counter' ),
				'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
				'callback'		=> null
			];

			$pages['post-views-counter-integrations'] = [
				'menu_slug'		=> 'post-views-counter&tab=integrations',
				'parent_slug'	=> 'post-views-counter',
				'type'			=> 'subpage',
				'page_title'	=> __( 'Integrations', 'post-views-counter' ),
				'menu_title'	=> self::mark_new( __( 'Integrations', 'post-views-counter' ) ),
				'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
				'callback'		=> null
			];

			$pages['post-views-counter-other'] = [
				'menu_slug'		=> 'post-views-counter&tab=other',
				'parent_slug'	=> 'post-views-counter',
				'type'			=> 'subpage',
				'page_title'	=> __( 'Other', 'post-views-counter' ),
				'menu_title'	=> __( 'Other', 'post-views-counter' ),
				'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
				'callback'		=> null
			];
		}

		return $pages;
	}

	/**
	 * Settings page CSS class(es).
	 *
	 * @param array $class
	 * @return array
	 */
	public function settings_page_class( $class ) {
		$is_pro = class_exists( 'Post_Views_Counter_Pro' );

		if ( ! $is_pro )
			$class[] = 'has-sidebar';

		return $class;
	}

	/**
	 * Highlight submenu items.
	 *
	 * @param string|null $submenu_file
	 * @param string $parent_file
	 *
	 * @return string|null
	 */
	public function submenu_file( $submenu_file, $parent_file ) {
		if ( $parent_file === 'post-views-counter' ) {
			$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

			if ( $tab !== 'general' )
				return 'post-views-counter&tab=' . $tab;
		}

		return $submenu_file;
	}

	/**
	 * Update admin title.
	 *
	 * @global array $submenu
	 * @global string $pagenow
	 *
	 * @param string $admin_title
	 * @param string $title
	 *
	 * @return string
	 */
	public function admin_title( $admin_title, $title ) {
		global $submenu, $pagenow;

		// get main instance
		$pvc = Post_Views_Counter();
		$menu_position = $pvc->get_menu_position();

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'post-views-counter' ) {
			if ( $menu_position === 'sub' && $pagenow === 'options-general.php' ) {
				// get tab
				$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

				// get settings pages
				$pages = $pvc->settings_api->get_pages();

				if ( array_key_exists( $tab, $pages['post-views-counter']['tabs'] ) ) {
					// update title
					$admin_title = preg_replace( '/' . $pages['post-views-counter']['page_title'] . '/', $pages['post-views-counter']['page_title'] . ' - ' . $pages['post-views-counter']['tabs'][$tab]['label'], $admin_title, 1 );
				}
			} else if ( $menu_position === 'top' && get_admin_page_parent() === 'post-views-counter' && ! empty( $submenu['post-views-counter'] ) ) {
				// get tab
				$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

				// get settings pages
				$pages = $pvc->settings_api->get_pages();

				if ( array_key_exists( 'post-views-counter-' . $tab, $pages ) ) {
					// update title
					$admin_title = $pages['post-views-counter']['page_title'] . ' - ' . preg_replace( '/' . $title . '/', $pages['post-views-counter-' . $tab]['page_title'], $admin_title, 1 );
				}
			}
		}

		return $admin_title;
	}

	/**
	 * Validate options.
	 *
	 * @global object $wpdb
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	public function validate_settings( $input ) {
		// check capability
		if ( ! current_user_can( 'manage_options' ) )
			return $input;

		global $wpdb;

		// get main instance
		$pvc = Post_Views_Counter();

		// use internal settings api to validate settings first
		$input = $pvc->settings_api->validate_settings( $input );

		// handle new provider-based import/analyse
		if ( isset( $_POST['post_views_counter_import_views'] ) || isset( $_POST['post_views_counter_analyse_views'] ) ) {
			// make sure we do not change anything in the settings
			$input = $pvc->options['other'];

			// delegate to import class
			$result = $pvc->import->handle_manual_action( $_POST );

			if ( isset( $result['message'] ) ) {
				add_settings_error( 'pvc_' . ( isset( $_POST['post_views_counter_analyse_views'] ) ? 'analyse' : 'import' ), 'pvc_' . ( isset( $_POST['post_views_counter_analyse_views'] ) ? 'analyse' : 'import' ), $result['message'], isset( $result['type'] ) ? $result['type'] : 'updated' );
			}

			if ( isset( $result['provider_settings'] ) ) {
				$input['import_provider_settings'] = $result['provider_settings'];
			}

			return $input;
		// delete all post views data
		} elseif ( isset( $_POST['post_views_counter_reset_views'] ) ) {
			// make sure we do not change anything in the settings
			$input = $pvc->options['other'];

			if ( $wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'post_views' ) )
				add_settings_error( 'reset_post_views', 'reset_post_views', __( 'All existing data deleted successfully.', 'post-views-counter' ), 'updated' );
			else
				add_settings_error( 'reset_post_views', 'reset_post_views', __( 'Error occurred. All existing data were not deleted.', 'post-views-counter' ), 'error' );
		// save general settings
		} elseif ( isset( $_POST['save_post_views_counter_settings_general'] ) ) {
			$input['update_version'] = $pvc->options['general']['update_version'];
			$input['update_notice'] = $pvc->options['general']['update_notice'];
			$input['update_delay_date'] = $pvc->options['general']['update_delay_date'];
		// reset general settings
		} elseif ( isset( $_POST['reset_post_views_counter_settings_general'] ) ) {
			$input['update_version'] = $pvc->options['general']['update_version'];
			$input['update_notice'] = $pvc->options['general']['update_notice'];
			$input['update_delay_date'] = $pvc->options['general']['update_delay_date'];
		// save other settings (handle provider inputs)
		} elseif ( isset( $_POST['save_post_views_counter_settings_other'] ) ) {
			$input['import_provider_settings'] = $pvc->import->prepare_provider_settings_from_request( $_POST );

			// keep menu position for backward compatibility with older add-ons expecting it under "other" settings
			if ( ! isset( $input['menu_position'] ) ) {
				$input['menu_position'] = $pvc->get_menu_position();
			}
		// save integrations settings
		} elseif ( isset( $_POST['save_post_views_counter_settings_integrations'] ) ) {
			// ensure integrations array exists
			if ( ! isset( $input['integrations'] ) ) {
				$input['integrations'] = [];
			}

			// get all known integrations
			$known_integrations = array_keys( Post_Views_Counter_Integrations::get_base_integrations() );

			// preserve unknown slugs from existing settings
			$existing = $pvc->options['integrations']['integrations'];
			foreach ( $existing as $slug => $status ) {
				if ( ! in_array( $slug, $known_integrations, true ) ) {
					$input['integrations'][$slug] = $status;
				}
			}

			// set missing known integrations to false (unchecked boxes don't submit)
			foreach ( $known_integrations as $slug ) {
				if ( ! isset( $input['integrations'][$slug] ) ) {
					$input['integrations'][$slug] = false;
				}
			}
		}

		return $input;
	}

	/**
	 * Register core PVC database tables for status checking.
	 *
	 * @param array $tables Existing table definitions
	 * @return array
	 */
	public function register_core_tables( $tables ) {
		$tables[] = [
			'name' => 'post_views',
			'label' => 'post_views'
		];

		return $tables;
	}

	/**
	 * Backward compatibility for section IDs.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function settings_sections_compat( $settings ) {
		if ( empty( $settings['post-views-counter']['fields'] ) )
			return $settings;

		$fields =& $settings['post-views-counter']['fields'];

		$compat_sections = [
			'technology_count' => [
				'legacy' => 'post_views_counter_general_settings',
				'current' => 'post_views_counter_general_tracking_targets'
			],
			'post_views_column' => [
				'legacy' => 'post_views_counter_display_settings',
				'current' => 'post_views_counter_display_admin'
			],
			'restrict_edit_views' => [
				'legacy' => 'post_views_counter_display_settings',
				'current' => 'post_views_counter_display_admin'
			],
			'menu_position' => [
				'legacy' => 'post_views_counter_other_management',
				'current' => 'post_views_counter_display_admin'
			]
		];

		foreach ( $compat_sections as $field => $map ) {
			if ( empty( $fields[$field] ) )
				continue;

			if ( isset( $fields[$field]['section'] ) && $fields[$field]['section'] === $map['legacy'] )
				$fields[$field]['section'] = $map['current'];
		}

		return $settings;
	}

	/**
	 * Mark menu item as new.
	 *
	 * @param string $text
	 * @return string
	 */
	public static function mark_new( $text ) {
		return sprintf(
			'%s<span class="pvc-admin-menu-new">&nbsp;%s</span>',
			$text,
			__( 'NEW!', 'post-views-counter' )
		);
	}
}

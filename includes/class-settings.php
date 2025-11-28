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
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'admin_init', [ $this, 'update_counter_mode' ], 12 );
		add_action( 'pvc_settings_sidebar', [ $this, 'settings_sidebar' ], 12 );
		add_action( 'pvc_settings_form', [ $this, 'settings_form' ], 10, 4 );

		// filters
		add_filter( 'post_views_counter_settings_data', [ $this, 'settings_data' ] );
		add_filter( 'post_views_counter_settings_data', [ $this, 'settings_sections_compat' ], 99 );
		add_filter( 'post_views_counter_settings_pages', [ $this, 'settings_page' ] );
		add_filter( 'post_views_counter_settings_page_class', [ $this, 'settings_page_class' ] );
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

		// topmenu referer
		$topmenu = '<input type="hidden" name="_wp_http_referer" data-pvc-menu="topmenu" value="' .esc_url( admin_url( 'admin.php?page=post-views-counter' . ( $tab_key !== '' ? '&tab=' . $tab_key : '' ) ) ) . '" />';

		// submenu referer
		$submenu = '<input type="hidden" name="_wp_http_referer" data-pvc-menu="submenu" value="' .esc_url( admin_url( 'options-general.php?page=post-views-counter' . ( $tab_key !== '' ? '&tab=' . $tab_key : '' ) ) ) . '" />';

		if ( $pvc->options['other']['menu_position'] === 'sub' )
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
	 * Update counter mode.
	 *
	 * @return void
	 */
	public function update_counter_mode() {
		// get main instance
		$pvc = Post_Views_Counter();

		// get settings
		$settings = $pvc->settings_api->get_settings();

		// fast ajax as active but not available counter mode?
		if ( $pvc->options['general']['counter_mode'] === 'ajax' && in_array( 'ajax', $settings['post-views-counter']['fields']['counter_mode']['disabled'], true ) ) {
			// set standard javascript ajax calls
			$pvc->options['general']['counter_mode'] = 'js';

			// update database options
			update_option( 'post_views_counter_settings_general', $pvc->options['general'] );
		}
	}

	/**
	 * Get available counter modes.
	 *
	 * @return array
	 */
	public function get_counter_modes() {
		// counter modes
		$modes = [
			'php'		=> __( 'PHP', 'post-views-counter' ),
			'js'		=> __( 'JavaScript', 'post-views-counter' ),
			'rest_api'	=> __( 'REST API', 'post-views-counter' ),
			'ajax'		=> __( 'Fast AJAX', 'post-views-counter' )
		];

		return apply_filters( 'pvc_get_counter_modes', $modes );
	}

	/**
	 * Add settings data.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function settings_data( $settings ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// time types
		$time_types = [
			'minutes'	=> __( 'minutes', 'post-views-counter' ),
			'hours'		=> __( 'hours', 'post-views-counter' ),
			'days'		=> __( 'days', 'post-views-counter' ),
			'weeks'		=> __( 'weeks', 'post-views-counter' ),
			'months'	=> __( 'months', 'post-views-counter' ),
			'years'		=> __( 'years', 'post-views-counter' )
		];

		// user groups
		$groups = [
			'robots'	=> __( 'crawlers', 'post-views-counter' ),
			'ai_bots'	=> __( 'AI bots', 'post-views-counter' ),
			'users'		=> __( 'logged in users', 'post-views-counter' ),
			'guests'	=> __( 'guests', 'post-views-counter' ),
			'roles'		=> __( 'selected user roles', 'post-views-counter' )
		];

		// get user roles
		$user_roles = $pvc->functions->get_user_roles();

		// get post types
		$post_types = $pvc->functions->get_post_types();

		// check object cache
		$wp_using_ext_object_cache = wp_using_ext_object_cache();

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
				'other'		=> 'post_views_counter_settings_other'
			],
			'validate' => [ $this, 'validate_settings' ],
			'sections' => [
				'post_views_counter_general_tracking_targets' => [
					'tab'      => 'general',
					'title'    => __( 'Tracking Targets', 'post-views-counter' ),
					'callback' => [ $this, 'section_tracking_targets' ],
				],
				'post_views_counter_general_tracking_behavior' => [
					'tab'      => 'general',
					'title'    => __( 'Tracking Behavior', 'post-views-counter' ),
					'callback' => [ $this, 'section_tracking_behavior' ],
				],
				'post_views_counter_general_exclusions' => [
					'tab'      => 'general',
					'title'    => __( 'Visitor Exclusions', 'post-views-counter' ),
					'callback' => [ $this, 'section_tracking_exclusions' ],
				],
				'post_views_counter_general_performance' => [
					'tab'      => 'general',
					'title'    => __( 'Performance & Caching', 'post-views-counter' ),
					'callback' => [ $this, 'section_tracking_performance' ],
				],
				'post_views_counter_display_appearance' => [
					'tab'      => 'display',
					'title'    => __( 'Counter Appearance', 'post-views-counter' ),
					'callback' => [ $this, 'section_display_appearance' ],
				],
				'post_views_counter_display_locations' => [
					'tab'   => 'display',
					'title' => __( 'Display Targets', 'post-views-counter' ),
					'callback' => [ $this, 'section_display_targets' ],
				],
				'post_views_counter_display_visibility' => [
					'tab'   => 'display',
					'title' => __( 'Display Audience', 'post-views-counter' ),
					'callback' => [ $this, 'section_display_audience' ],
				],
				'post_views_counter_display_admin' => [
					'tab'      => 'display',
					'title'    => __( 'Admin Interface', 'post-views-counter' ),
					'callback' => [ $this, 'section_display_admin' ],
				],
				'post_views_counter_reports_settings'	=> [
					'tab'		=> 'reports',
					'callback'  => [ $this, 'section_reports_placeholder' ]
				],
				'post_views_counter_other_status' => [
					'tab'      => 'other',
					'title'    => __( 'Plugin Status', 'post-views-counter' ),
					'callback' => [ $this, 'section_other_status' ]
				],
				'post_views_counter_other_import' => [
					'tab'      => 'other',
					'title'    => __( 'Data Import', 'post-views-counter' ),
					'callback' => [ $this, 'section_other_import' ]
				],
				'post_views_counter_other_management' => [
					'tab'      => 'other',
					'title'    => __( 'Admin & Cleanup', 'post-views-counter' ),
					'callback' => [ $this, 'section_other_management' ]
				]
			],
			'fields' => [
				'post_types_count' => [
					'tab'			=> 'general',
					'title'			=> __( 'Post Types', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_targets',
					'type'			=> 'checkbox',
					'display_type'	=> 'horizontal',
					'description'	=> __( 'Select post types whose views should be counted.', 'post-views-counter' ),
					'options'		=> $post_types
				],
				'taxonomies_count' => [
					'tab'			=> 'general',
					'title'			=> __( 'Taxonomies', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_targets',
					'type'			=> 'custom',
					'label'			=> __( 'Enable counting views on taxonomy term archive pages.', 'post-views-counter' ),
					'class'			=> 'pvc-pro',
					'skip_saving'	=> true,
					'callback'		=> [ $this, 'setting_taxonomies_count' ]
				],
				'users_count' => [
					'tab'			=> 'general',
					'title'			=> __( 'Author Archives', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_targets',
					'type'			=> 'custom',
					'label'			=> __( 'Enable counting views on author archive pages.', 'post-views-counter' ),
					'class'			=> 'pvc-pro',
					'skip_saving'	=> true,
					'callback'		=> [ $this, 'setting_users_count' ]
				],
				'other_count' => [
					'tab'			=> 'general',
					'title'			=> __( 'Other Pages', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_targets',
					'type'			=> 'boolean',
					'label'			=> __( 'Track views on the front page, post type archives, date archives, search results, and 404 pages.', 'post-views-counter' ),
					'class'			=> 'pvc-pro',
					'disabled'		=> true,
					'skip_saving'	=> true,
					'value'			=> false
				],
				'technology_count' => [
					'tab'			=> 'general',
					'title'			=> __( 'Traffic Sources', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_targets',
					'type'			=> 'boolean',
					'label'			=> __( 'Collect aggregate stats about visitors\' browsers, devices, operating systems and referrers.', 'post-views-counter' ),
					'class'			=> 'pvc-pro',
					'disabled'		=> true,
					'skip_saving'	=> true,
					'value'			=> false
				],
				'counter_mode' => [
					'tab'			=> 'general',
					'title'			=> __( 'Counter Mode', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_behavior',
					'type'			=> 'radio',
					'description'	=> __( 'Choose how views are recorded. If you use caching, select JavaScript, REST API or Fast AJAX (up to <code>10+</code> times faster).', 'post-views-counter' ),
					'class'			=> 'pvc-pro-extended',
					'options'		=> $this->get_counter_modes(),
					'disabled'		=> [ 'ajax' ]
				],
				'data_storage' => [
					'tab'			=> 'general',
					'title'			=> __( 'Data Storage', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_behavior',
					'type'			=> 'radio',
					'class'			=> 'pvc-pro',
					'skip_saving'	=> true,
					'description'	=> __( "Choose how to store the content views data in the user's browser - with or without cookies.", 'post-views-counter' ),
					'options'		=> [
						'cookies'		=> __( 'Cookies', 'post-views-counter' ),
						'cookieless'	=> __( 'Cookieless', 'post-views-counter' )
					],
					'disabled'		=> [ 'cookies', 'cookieless' ],
					'value'			=> 'cookies'
				],
				'amp_support' => [
					'tab'			=> 'general',
					'title'			=> __( 'AMP Support', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_behavior',
					'type'			=> 'boolean',
					'class'			=> 'pvc-pro',
					'disabled'		=> true,
					'skip_saving'	=> true,
					'value'			=> false,
					'label'			=> __( 'Enable support for Google AMP.', 'post-views-counter' ),
					'description'	=> sprintf( __( 'This feature requires the official %s plugin to be installed and activated.', 'post-views-counter' ), '<code><a href="https://wordpress.org/plugins/amp/" target="_blank">AMP</a></code>' )
				],
				'time_between_counts' => [
					'tab'			=> 'general',
					'title'			=> __( 'Count Interval', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_behavior',
					'type'			=> 'custom',
					'description'	=> '',
					'min'			=> 0,
					'max'			=> 999999,
					'options'		=> $time_types,
					'callback'		=> [ $this, 'setting_time_between_counts' ],
					'validate'		=> [ $this, 'validate_time_between_counts' ]
				],
				'count_time' => [
					'tab'			=> 'general',
					'title'			=> __( 'Count Time', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_behavior',
					'type'			=> 'radio',
					'class'			=> 'pvc-pro',
					'skip_saving'	=> true,
					'description'	=> __( 'Whether to store the views using GMT timezone or adjust it to the GMT offset of the site.', 'post-views-counter' ),
					'options'		=> [
						'gmt'		=> __( 'GMT Time', 'post-views-counter' ),
						'local'		=> __( 'Local Time', 'post-views-counter' )
					],
					'disabled'		=> [ 'gmt', 'local' ],
					'value'			=> 'gmt'
				],
				'strict_counts' => [
					'tab'			=> 'general',
					'title'			=> __( 'Strict Counts', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_behavior',
					'type'			=> 'boolean',
					'class'			=> 'pvc-pro',
					'disabled'		=> true,
					'skip_saving'	=> true,
					'value'			=> false,
					'description'	=> '',
					'label'			=> __( 'Prevent bypassing the count interval (for example by using incognito mode or clearing cookies).', 'post-views-counter' )
				],
				'reset_counts' => [
					'tab'			=> 'general',
					'title'			=> __( 'Cleanup Interval', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_tracking_behavior',
					'type'			=> 'custom',
					'description'	=> sprintf( __( 'Delete daily content view data older than the period specified above. Enter %s to keep data regardless of age. Cleanup runs once per day.', 'post-views-counter' ), '<code>0</code>' ),
					'min'			=> 0,
					'max'			=> 999999,
					'options'		=> $time_types,
					'callback'		=> [ $this, 'setting_reset_counts' ],
					'validate'		=> [ $this, 'validate_reset_counts' ]
				],
				'caching_compatibility' => [
					'tab'			=> 'general',
					'title'			=> __( 'Caching Compatibility', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_performance',
					'type'			=> 'boolean',
					'class'			=> 'pvc-pro',
					'disabled'		=> true,
					'value'			=> false,
					'skip_saving'	=> true,
					'label'			=> __( 'Enable compatibility tweaks for supported caching plugins.', 'post-views-counter' ),
					'description'	=> $this->get_caching_compatibility_description()
				],
				'object_cache' => [
					'tab'			=> 'general',
					'title'			=> __( 'Object Cache Support', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_performance',
					'type'			=> 'boolean',
					'class'			=> 'pvc-pro',
					'disabled'		=> true,
					'value'			=> false,
					'skip_saving'	=> true,
					'label'			=> __( 'Enable Redis or Memcached object cache optimization.', 'post-views-counter' ),
					'description'	=> sprintf( __( 'This feature requires a persistent object cache like %s or %s to be installed and activated.', 'post-views-counter' ), '<code>Redis</code>', '<code>Memcached</code>' ) . '<br />' . __( 'Current status', 'post-views-counter' ) . ': <span class="' . ( $wp_using_ext_object_cache ? '' : 'un' ) . 'available">' . ( $wp_using_ext_object_cache ? __( 'available', 'post-views-counter' ) : __( 'unavailable', 'post-views-counter' ) ) . '</span>.'
				],
				'exclude' => [
					'tab'			=> 'general',
					'title'			=> __( 'Exclude Visitors', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_exclusions',
					'type'			=> 'custom',
					'description'	=> '',
					'class'			=> 'pvc-pro-extended',
					'options'		=> [
						'groups'	=> $groups,
						'roles'		=> $user_roles
					],
					'disabled'		=> [
						'groups'	=> [ 'ai_bots' ]
					],
					'callback'		=> [ $this, 'setting_exclude' ],
					'validate'		=> [ $this, 'validate_exclude' ]
				],
				'exclude_ips' => [
					'tab'			=> 'general',
					'title'			=> __( 'Exclude IPs', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_exclusions',
					'type'			=> 'custom',
					'description'	=> '',
					'callback'		=> [ $this, 'setting_exclude_ips' ],
					'validate'		=> [ $this, 'validate_exclude_ips' ]
				],
				'label' => [
					'tab'			=> 'display',
					'title'			=> __( 'Views Label', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_appearance',
					'type'			=> 'input',
					'description'	=> __( 'Text shown next to the view count.', 'post-views-counter' ),
					'subclass'		=> 'regular-text',
					'validate'		=> [ $this, 'validate_label' ],
					'reset'			=> [ $this, 'reset_label' ]
				],
				'display_period' => [
					'tab'			=> 'display',
					'title'			=> __( 'Views Period', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_appearance',
					'type'			=> 'select',
					'class'			=> 'pvc-pro',
					'disabled'		=> true,
					'skip_saving'	=> true,
					'description'	=> __( 'Time range used when displaying the number of views. Only total views are available without Post Views Counter Pro.', 'post-views-counter' ),
					'options'		=> 	[
						'total'		=> __( 'Total Views', 'post-views-counter' )
					]
				],
				'display_style' => [
					'tab'			=> 'display',
					'title'			=> __( 'Display Style', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_appearance',
					'type'			=> 'custom',
					'description'	=> __( 'Choose whether to show an icon, label text, or both.', 'post-views-counter' ),
					'callback'		=> [ $this, 'setting_display_style' ],
					'validate'		=> [ $this, 'validate_display_style' ],
					'options'		=> [
						'icon'	=> __( 'Icon', 'post-views-counter' ),
						'text'	=> __( 'Label', 'post-views-counter' )
					]
				],
				'icon_class' => [
					'tab'			=> 'display',
					'title'			=> __( 'Icon Class', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_appearance',
					'type'			=> 'class',
					'default'		=> '',
					'description'	=> sprintf( __( 'Enter the CSS class for the views icon. Any Dashicons class is supported.', 'post-views-counter' ), 'https://developer.wordpress.org/resource/dashicons/' ),
					'subclass'		=> 'regular-text'
				],
				'position' => [
					'tab'			=> 'display',
					'title'			=> __( 'Position', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_locations',
					'type'			=> 'select',
					'description'	=> sprintf( __( 'Where to insert the counter automatically. Use %s shortcode for manual placement.', 'post-views-counter' ), '<code>[post-views]</code>' ),
					'options'		=> [
						'before'	=> __( 'Before the content', 'post-views-counter' ),
						'after'		=> __( 'After the content', 'post-views-counter' ),
						'manual'	=> __( 'Manual only', 'post-views-counter' )
					]
				],
				'post_views_column' => [
					'tab'			=> 'display',
					'title'			=> __( 'Admin Column', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_admin',
					'type'			=> 'boolean',
					'description'	=> '',
					'label'			=> __( 'Show a “Views” column on post and page list screens.', 'post-views-counter' )
				],
				'restrict_edit_views' => [
					'tab'			=> 'display',
					'title'			=> __( 'Admin Edit', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_admin',
					'type'			=> 'boolean',
					'description'	=> '',
					'label'			=> __( 'Allow editing the view count on the post edit screen.', 'post-views-counter' )
				],
				'dynamic_loading' => [
					'tab'			=> 'display',
					'title'			=> __( 'Dynamic Loading', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_appearance',
					'type'			=> 'boolean',
					'class'			=> 'pvc-pro',
					'disabled'		=> true,
					'skip_saving'	=> true,
					'value'			=> false,
					'label'			=> __( 'Load the view count dynamically to avoid caching the displayed value.', 'post-views-counter' )
				],
				'use_format' => [
					'tab'			=> 'display',
					'title'			=> __( 'Format Number', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_appearance',
					'type'			=> 'boolean',
					'label'			=> __( 'Format the view count according to the site locale (uses the WordPress number_format_i18n function).', 'post-views-counter' )
				],
				'taxonomies_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'Taxonomies', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_locations',
					'type'			=> 'custom',
					'class'			=> 'pvc-pro',
					'skip_saving'	=> true,
					'options'		=> $pvc->functions->get_taxonomies( 'labels' ),
					'callback'		=> [ $this, 'setting_taxonomies_display' ]
				],
				'user_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'Author Archives', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_locations',
					'type'			=> 'boolean',
					'class'			=> 'pvc-pro',
					'disabled'		=> true,
					'skip_saving'	=> true,
					'value'			=> false,
					'label'			=> __( 'Display the view count on author archive pages.', 'post-views-counter' )
				],
				'post_types_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'Post Types', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_locations',
					'type'			=> 'checkbox',
					'display_type'	=> 'horizontal',
					'description'	=> __( 'Select post types where the view counter will be displayed.', 'post-views-counter' ),
					'options'		=> $post_types
				],
				'page_types_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'Page Type', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_locations',
					'type'			=> 'checkbox',
					'display_type'	=> 'horizontal',
					'description'	=> __( 'Select page contexts where the view counter will be displayed.', 'post-views-counter' ),
					'options'		=> apply_filters(
						'pvc_page_types_display_options',
						[
							'home'		=> __( 'Home', 'post-views-counter' ),
							'archive'	=> __( 'Archives', 'post-views-counter' ),
							'singular'	=> __( 'Single posts and pages', 'post-views-counter' ),
							'search'	=> __( 'Search results', 'post-views-counter' ),
						]
					)
				],
				'restrict_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'User Type', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_visibility',
					'type'			=> 'custom',
					'description'	=> '',
					'options'		=> [
						'groups'	=> $groups,
						'roles'		=> $user_roles
					],
					'callback'		=> [ $this, 'setting_restrict_display' ],
					'validate'		=> [ $this, 'validate_restrict_display' ]
				],
				'toolbar_statistics' => [
					'tab'			=> 'display',
					'title'			=> __( 'Toolbar Chart', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_admin',
					'type'			=> 'boolean',
					'description'	=> __( 'A views chart is shown for content types that are being counted.', 'post-views-counter' ),
					'label'			=> __( 'Show a views chart in the admin toolbar.', 'post-views-counter' )
				],
				'license' => [
					'tab'			=> 'other',
					'title'			=> __( 'License', 'post-views-counter' ),
					'section'		=> 'post_views_counter_other_status',
					'disabled'		=> true,
					'value'			=> $pvc->options['other']['license'],
					'type'			=> 'input',
					'description'	=> __( 'Enter your Post Views Counter Pro license key (requires Pro version to be installed and active).', 'post-views-counter' ),
					'subclass'		=> 'regular-text',
					'validate'		=> [ $this, 'validate_license' ],
					'append'		=> '<span class="pvc-icon license-icon"></span>'
				],
				'menu_position' => [
					'tab'			=> 'other',
					'title'			=> __( 'Menu Position', 'post-views-counter' ),
					'section'		=> 'post_views_counter_other_management',
					'type'			=> 'radio',
					'options'		=> [
						'top'	=> __( 'Top menu', 'post-views-counter' ),
						'sub'	=> __( 'Settings submenu', 'post-views-counter' )
					],
					'description'	=> __( 'Choose where the plugin menu appears in the admin sidebar.', 'post-views-counter' ),
				],
				'import_views' => [
					'tab'			=> 'other',
					'title'			=> __( 'Import Views', 'post-views-counter' ),
					'section'		=> 'post_views_counter_other_import',
					'type'			=> 'custom',
					'description'	=> __( 'Import views from another plugin using this meta key.', 'post-views-counter' ),
					'skip_saving'	=> true,
					'callback'		=> [ $this, 'setting_import_views' ]
				],
				'delete_views' => [
					'tab'			=> 'other',
					'title'			=> __( 'Delete Views', 'post-views-counter' ),
					'section'		=> 'post_views_counter_other_import',
					'type'			=> 'custom',
					'description'	=> __( 'Permanently delete all stored view data. This cannot be undone.', 'post-views-counter' ),
					'skip_saving'	=> true,
					'callback'		=> [ $this, 'setting_delete_views' ]
				],
				'deactivation_delete' => [
					'tab'			=> 'other',
					'title'			=> __( 'Deactivation', 'post-views-counter' ),
					'section'		=> 'post_views_counter_other_management',
					'type'			=> 'boolean',
					'description'	=> __( 'When enabled, deactivating the plugin will delete all plugin data from the database, including all content view counts.', 'post-views-counter' ),
					'label'			=> __( 'Delete all plugin data on deactivation.', 'post-views-counter' )
				]
			]
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

		// default page
		$pages['post-views-counter'] = [
			'menu_slug'		=> 'post-views-counter',
			'page_title'	=> __( 'Post Views Counter Settings', 'post-views-counter' ),
			'menu_title'	=> $pvc->options['other']['menu_position'] === 'sub' ? __( 'Post Views Counter', 'post-views-counter' ) : __( 'Post Views', 'post-views-counter' ),
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
				'other'		=> [
					'label'			=> __( 'Other', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_other'
				]
			]
		];

		// update admin title
		add_filter( 'admin_title', [ $this, 'admin_title' ], 10, 2 );

		// submenu?
		if ( $pvc->options['other']['menu_position'] === 'sub' ) {
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

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'post-views-counter' ) {
			if ( $pvc->options['other']['menu_position'] === 'sub' && $pagenow === 'options-general.php' ) {
				// get tab
				$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

				// get settings pages
				$pages = $pvc->settings_api->get_pages();

				if ( array_key_exists( $tab, $pages['post-views-counter']['tabs'] ) ) {
					// update title
					$admin_title = preg_replace( '/' . $pages['post-views-counter']['page_title'] . '/', $pages['post-views-counter']['page_title'] . ' - ' . $pages['post-views-counter']['tabs'][$tab]['label'], $admin_title, 1 );
				}
			} else if ( $pvc->options['other']['menu_position'] === 'top' && get_admin_page_parent() === 'post-views-counter' && ! empty( $submenu['post-views-counter'] ) ) {
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

		// update meta key on save changes
		if ( isset( $_POST['post_views_counter_import_meta_key'] ) && isset( $_POST['save_post_views_counter_settings_other'] ) )
			$input['import_meta_key'] = sanitize_key( $_POST['post_views_counter_import_meta_key'] );

		// import post views data from another plugin
		if ( isset( $_POST['post_views_counter_import_wp_postviews'] ) ) {
			// make sure we do not change anything in the settings
			$input = $pvc->options['other'];

			// get views key
			$meta_key = sanitize_key( apply_filters( 'pvc_import_meta_key', ( isset( $_POST['post_views_counter_import_meta_key'] ) ? $_POST['post_views_counter_import_meta_key'] : $pvc->options['other']['import_meta_key'] ) ) );

			// set meta_key option
			$input['import_meta_key'] = $meta_key;

			// get views
			$views = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = %s", $meta_key ), ARRAY_A, 0 );

			// any views?
			if ( ! empty( $views ) ) {
				$sql = [];

				foreach ( $views as $view ) {
					$sql[] = $wpdb->prepare( "(%d, 4, 'total', %d)", (int) $view['post_id'], (int) $view['meta_value'] );
				}

				$wpdb->query( "INSERT INTO " . $wpdb->prefix . "post_views(id, type, period, count) VALUES " . implode( ',', $sql ) . " ON DUPLICATE KEY UPDATE count = " . ( isset( $_POST['post_views_counter_import_wp_postviews_override'] ) ? '' : 'count + ' ) . "VALUES(count)" );

				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'Post views data imported successfully.', 'post-views-counter' ), 'updated' );
			} else
				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'There was no post views data to import.', 'post-views-counter' ), 'updated' );
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
		}

		return $input;
	}

	/**
	 * Get caching compatibility description.
	 *
	 * @return array
	 */
	public function get_caching_compatibility_description() {
		// caching compatibility description
		$caching_compatibility_desc = '';

		// get active caching plugins
		$active_plugins = $this->get_active_caching_plugins();

		if ( ! empty( $active_plugins ) ) {
			$empty_active_caching_plugins = false;
			$active_plugins_html = [];

			$caching_compatibility_desc .= esc_html__( 'Currently detected active caching plugins', 'post-views-counter' ) . ': ';

			foreach ( $active_plugins as $plugin ) {
				$active_plugins_html[] = '<code>' . esc_html( $plugin ) . '</code>';
			}

			$caching_compatibility_desc .= implode( ', ', $active_plugins_html ) . '.';
		} else {
			$empty_active_caching_plugins = true;

			$caching_compatibility_desc .= esc_html__( 'No compatible caching plugins found.', 'post-views-counter' );
		}

		return $caching_compatibility_desc . '<br />' . __( 'Current status', 'post-views-counter' ) . ': <span class="' . ( ! $empty_active_caching_plugins ? '' : 'un' ) . 'available">' . ( ! $empty_active_caching_plugins ? __( 'available', 'post-views-counter' ) : __( 'unavailable', 'post-views-counter' ) ) . '</span>.';
	}

	/**
	 * Extend active caching plugins.
	 *
	 * @param string $plugins
	 *
	 * @return array
	 */
	public function extend_active_caching_plugins( $plugins ) {
		// breeze
		if ( $this->is_plugin_active( 'breeze' ) )
			$plugins[] = 'Breeze';

		return $plugins;
	}

	/**
	 * Check whether specified plugin is active.
	 *
	 * @param bool $is_plugin_active
	 * @param string $plugin
	 *
	 * @return bool
	 */
	public function extend_is_plugin_active( $is_plugin_active, $plugin ) {
		// breeze
		if ( $plugin === 'breeze' && class_exists( 'Breeze_PurgeCache' ) && class_exists( 'Breeze_Options_Reader' ) && function_exists( 'breeze_get_option' ) && function_exists( 'breeze_update_option' ) && defined( 'BREEZE_VERSION' ) && version_compare( BREEZE_VERSION, '2.0.30', '>=' ) )
			$is_plugin_active = true;

		return $is_plugin_active;
	}

	/**
	 * Get active caching plugins.
	 *
	 * @return array
	 */
	public function get_active_caching_plugins() {
		$active_plugins = [];

		// autoptimize
		if ( $this->is_plugin_active( 'autoptimize' ) )
			$active_plugins[] = 'Autoptimize';

		// hummingbird
		if ( $this->is_plugin_active( 'hummingbird' ) )
			$active_plugins[] = 'Hummingbird';

		// litespeed
		if ( $this->is_plugin_active( 'litespeed' ) )
			$active_plugins[] = 'LiteSpeed Cache';

		// speed optimizer
		if ( $this->is_plugin_active( 'speedoptimizer' ) )
			$active_plugins[] = 'Speed Optimizer';

		// speedycache
		if ( $this->is_plugin_active( 'speedycache' ) )
			$active_plugins[] = 'SpeedyCache';

		// wp fastest cache
		if ( $this->is_plugin_active( 'wpfastestcache' ) )
			$active_plugins[] = 'WP Fastest Cache';

		// wp-optimize
		if ( $this->is_plugin_active( 'wpoptimize' ) )
			$active_plugins[] = 'WP-Optimize';

		// wp rocket
		if ( $this->is_plugin_active( 'wprocket' ) )
			$active_plugins[] = 'WP Rocket';

		// wp super cache
		// if ( $this->is_plugin_active( 'wpsupercache' ) )
			// $active_plugins[] = 'WP Super Cache';

		return apply_filters( 'pvc_active_caching_plugins', $active_plugins );
	}

	/**
	 * Check whether specified plugin is active.
	 *
	 * @global object $siteground_optimizer_loader
	 * @global int $wpsc_version
	 *
	 * @param string $plugin
	 *
	 * @return bool
	 */
	public function is_plugin_active( $plugin = '' ) {
		// set default flag
		$is_plugin_active = false;

		switch ( $plugin ) {
			// autoptimize
			case 'autoptimize':
				if ( function_exists( 'autoptimize' ) && defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) && version_compare( AUTOPTIMIZE_PLUGIN_VERSION, '2.4', '>=' ) )
					$is_plugin_active = true;
				break;

			// hummingbird
			case 'hummingbird':
				if ( class_exists( 'Hummingbird\\WP_Hummingbird' ) && defined( 'WPHB_VERSION' ) && version_compare( WPHB_VERSION, '2.1.0', '>=' ) )
					$is_plugin_active = true;
				break;

			// litespeed
			case 'litespeed':
				if ( class_exists( 'LiteSpeed\Core' ) && defined( 'LSCWP_CUR_V' ) && version_compare( LSCWP_CUR_V, '3.0', '>=' ) )
					$is_plugin_active = true;
				break;

			// speed optimizer
			case 'speedoptimizer':
				global $siteground_optimizer_loader;

				if ( ! empty( $siteground_optimizer_loader ) && is_object( $siteground_optimizer_loader ) && is_a( $siteground_optimizer_loader, 'SiteGround_Optimizer\Loader\Loader' ) && defined( '\SiteGround_Optimizer\VERSION' ) && version_compare( \SiteGround_Optimizer\VERSION, '5.5', '>=' ) )
					$is_plugin_active = true;
				break;

			// speedycache
			case 'speedycache':
				if ( class_exists( 'SpeedyCache' ) && defined( 'SPEEDYCACHE_VERSION' ) && function_exists( 'speedycache_delete_cache' ) && version_compare( SPEEDYCACHE_VERSION, '1.0.0', '>=' ) )
					$is_plugin_active = true;
				break;

			// wp fastest cache
			case 'wpfastestcache':
				if ( function_exists( 'wpfc_clear_all_cache' ) )
					$is_plugin_active = true;
				break;

			// wp-optimize
			case 'wpoptimize':
				if ( function_exists( 'WP_Optimize' ) && defined( 'WPO_VERSION' ) && version_compare( WPO_VERSION, '3.0.12', '>=' ) )
					$is_plugin_active = true;
				break;

			// wp rocket
			case 'wprocket':
				if ( function_exists( 'rocket_init' ) && defined( 'WP_ROCKET_VERSION' ) && version_compare( WP_ROCKET_VERSION, '3.8', '>=' ) )
					$is_plugin_active = true;
				break;

			// wp super cache
			// case 'wpsupercache':
				// global $wpsc_version;

				// if ( ( ! empty( $wpsc_version ) && $wpsc_version >= 169 ) || ( defined( 'WPSC_VERSION' ) && version_compare( WPSC_VERSION, '1.6.9', '>=' ) ) )
					// $is_plugin_active = true;
				// break;

			// other caching plugin
			default:
				$is_plugin_active = apply_filters( 'pvc_is_plugin_active', false, $plugin );
		}

		return $is_plugin_active;
	}

	/**
	 * Setting: taxonomies count.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_taxonomies_count( $field ) {
		$html = '
			<label><input id="post_views_counter_general_taxonomies_count" type="checkbox" name="" value="" disabled />' . esc_html( $field['label'] ) . '</label>';

		return $html;
	}

	/**
	 * Setting: users count.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_users_count( $field ) {
		// get base instance
		$pvc = Post_Views_Counter();

		$html = '
			<label><input id="post_views_counter_general_users_count" type="checkbox" name="" value="" disabled />' . esc_html( $field['label'] ) . '</label>';

		return $html;
	}

	/**
	 * Setting: taxonomies count.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_taxonomies_display( $field ) {
		// get base instance
		$pvc = Post_Views_Counter();

		$html = '';

		foreach ( $field['options'] as $taxonomy => $label ) {
			$html .= '
			<label><input type="checkbox" name="" value="" disabled />' . esc_html( $label ) . '</label>';
		}

		$html .= '
			<p class="description">' . esc_html__( 'Select taxonomies where the view counter will be displayed.', 'post-views-counter' ) . '</p>';

		return $html;
	}

	/**
	 * Validate label.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_label( $input, $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		if ( ! isset( $input ) )
			$input = $pvc->defaults['display']['label'];

		// use internal settings API to validate settings first
		$input = $pvc->settings_api->validate_field( $input, 'input', $field );

		if ( function_exists( 'icl_register_string' ) )
			icl_register_string( 'Post Views Counter', 'Post Views Label', $input );

		return $input;
	}

	/**
	 * Restore post views label to default value.
	 *
	 * @param array $default
	 * @param array $field
	 * @return array
	 */
	public function reset_label( $default, $field ) {
		if ( function_exists( 'icl_register_string' ) )
			icl_register_string( 'Post Views Counter', 'Post Views Label', $default );

		return $default;
	}

	/**
	 * Setting: display style.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_display_style( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '
		<input type="hidden" name="post_views_counter_settings_display[display_style]" value="empty" />';

		foreach ( $field['options'] as $key => $label ) {
			$html .= '
			<label><input id="post_views_counter_display_display_style_' . esc_attr( $key ) . '" type="checkbox" name="post_views_counter_settings_display[display_style][]" value="' . esc_attr( $key ) . '" ' . checked( ! empty( $pvc->options['display']['display_style'][$key] ), true, false ) . ' />' . esc_html( $label ) . '</label> ';
		}

		return $html;
	}

	/**
	 * Validate display style.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_display_style( $input, $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$data = [];

		foreach ( $field['options'] as $value => $label ) {
			$data[$value] = false;
		}

		// any data?
		if ( ! empty( $input['display_style'] && $input['display_style'] !== 'empty' && is_array( $input['display_style'] ) ) ) {
			foreach ( $input['display_style'] as $value ) {
				if ( array_key_exists( $value, $field['options'] ) )
					$data[$value] = true;
			}
		}

		$input['display_style'] = $data;

		return $input;
	}

	/**
	 * Setting: count interval.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_time_between_counts( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '
		<input size="6" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="post_views_counter_settings_general[time_between_counts][number]" value="' . esc_attr( $pvc->options['general']['time_between_counts']['number'] ) . '" />
		<select name="post_views_counter_settings_general[time_between_counts][type]">';

		foreach ( $field['options'] as $type => $type_name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, $pvc->options['general']['time_between_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		$html .= '
		</select>
		<p class="description">' . sprintf( __( 'Minimum time between counting new views from the same visitor. Enter %s to count every page view.', 'post-views-counter' ), '<code>0</code>' ) . '</p>';

		return $html;
	}

	/**
	 * Validate count interval.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_time_between_counts( $input, $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// number
		$input['time_between_counts']['number'] = isset( $input['time_between_counts']['number'] ) ? (int) $input['time_between_counts']['number'] : $pvc->defaults['general']['time_between_counts']['number'];

		if ( $input['time_between_counts']['number'] < $field['min'] || $input['time_between_counts']['number'] > $field['max'] )
			$input['time_between_counts']['number'] = $pvc->defaults['general']['time_between_counts']['number'];

		// type
		$input['time_between_counts']['type'] = isset( $input['time_between_counts']['type'], $field['options'][$input['time_between_counts']['type']] ) ? $input['time_between_counts']['type'] : $pvc->defaults['general']['time_between_counts']['type'];

		return $input;
	}

	/**
	 * Setting: reset data interval.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_reset_counts( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '
		<input size="6" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="post_views_counter_settings_general[reset_counts][number]" value="' . esc_attr( $pvc->options['general']['reset_counts']['number'] ) . '" />
		<select name="post_views_counter_settings_general[reset_counts][type]">';

		foreach ( array_slice( $field['options'], 2, null, true ) as $type => $type_name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, $pvc->options['general']['reset_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		$html .= '
		</select>';

		return $html;
	}

	/**
	 * Validate reset data interval.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_reset_counts( $input, $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// number
		$input['reset_counts']['number'] = isset( $input['reset_counts']['number'] ) ? (int) $input['reset_counts']['number'] : $pvc->defaults['general']['reset_counts']['number'];

		if ( $input['reset_counts']['number'] < $field['min'] || $input['reset_counts']['number'] > $field['max'] )
			$input['reset_counts']['number'] = $pvc->defaults['general']['reset_counts']['number'];

		// type
		$input['reset_counts']['type'] = isset( $input['reset_counts']['type'], $field['options'][$input['reset_counts']['type']] ) ? $input['reset_counts']['type'] : $pvc->defaults['general']['reset_counts']['type'];

		// run cron on next visit?
		$input['cron_run'] = ( $input['reset_counts']['number'] > 0 );

		// cron update?
		$input['cron_update'] = ( $input['cron_run'] && ( $pvc->options['general']['reset_counts']['number'] !== $input['reset_counts']['number'] || $pvc->options['general']['reset_counts']['type'] !== $input['reset_counts']['type'] ) );

		return $input;
	}

	/**
	 * Setting: object cache.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_object_cache( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// check object cache
		$wp_using_ext_object_cache = wp_using_ext_object_cache();

		$html = '
		<input size="4" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="" value="0" disabled /> <span>' . __( 'minutes', 'post-views-counter' ) . '</span>
		<p class="">' . __( 'Persistent Object Cache', 'post-views-counter' ) . ': <span class="' . ( $wp_using_ext_object_cache ? '' : 'un' ) . 'available">' . ( $wp_using_ext_object_cache ? __( 'available', 'post-views-counter' ) : __( 'unavailable', 'post-views-counter' ) ) . '</span></p>
		<p class="description">' . sprintf( __( 'How often to flush cached view counts from the object cache into the database. This feature is used only if a persistent object cache like %s or %s is detected and the interval is greater than %s. When used, view counts will be collected and stored in the object cache instead of the database and will then be asynchronously flushed to the database according to the specified interval. The maximum value is %s which means 24 hours.%sNotice:%s Potential data loss may occur if the object cache is cleared/unavailable for the duration of the interval.', 'post-views-counter' ), '<code>Redis</code>', '<code>Memcached</code>', '<code>0</code>', '<code>1440</code>', '<br /><strong> ', '</strong>' ) . '</p>';

		return $html;
	}

	/**
	 * Setting: exclude visitors.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_exclude( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '';

		foreach ( $field['options']['groups'] as $type => $type_name ) {
			$is_disabled = ! empty( $field['disabled']['groups'] ) && in_array( $type, $field['disabled']['groups'], true );

			$html .= '
			<label for="' . esc_attr( 'pvc_exclude-' . $type ) . '"><input id="' . esc_attr( 'pvc_exclude-' . $type ) . '" type="checkbox" name="post_views_counter_settings_general[exclude][groups][' . esc_attr( $type ) . ']" value="1" ' . checked( in_array( $type, $pvc->options['general']['exclude']['groups'], true ) && ! $is_disabled, true, false ) . ' ' . disabled( $is_disabled, true, false ) . ' />' . esc_html( $type_name ) . '</label>';
		}

		$html .= '
			<p class="description">' . __( 'Use this to exclude specific visitor groups from counting views.', 'post-views-counter' ) . '</p>
			<div class="pvc_user_roles pvc_subfield"' . ( in_array( 'roles', $pvc->options['general']['exclude']['groups'], true ) ? '' : ' style="display: none;"' ) . '>';

		foreach ( $field['options']['roles'] as $role => $role_name ) {
			$html .= '
				<label><input type="checkbox" name="post_views_counter_settings_general[exclude][roles][' . $role . ']" value="1" ' . checked( in_array( $role, $pvc->options['general']['exclude']['roles'], true ), true, false ) . ' />' . esc_html( $role_name ) . '</label>';
		}

		$html .= '
				<p class="description">' . __( 'Use this to exclude specific user roles from counting views.', 'post-views-counter' ) . '</p>
			</div>';

		return $html;
	}

	/**
	 * Validate exclude visitors.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_exclude( $input, $field ) {
		// any groups?
		if ( isset( $input['exclude']['groups'] ) ) {
			$groups = [];

			foreach ( $input['exclude']['groups'] as $group => $set ) {
				// disallow disabled checkboxes
				if ( ! empty( $field['disabled']['groups'] ) && in_array( $group, $field['disabled']['groups'], true ) )
					continue;

				if ( isset( $field['options']['groups'][$group] ) )
					$groups[] = $group;
			}

			$input['exclude']['groups'] = array_unique( $groups );
		} else
			$input['exclude']['groups'] = [];

		// any roles?
		if ( in_array( 'roles', $input['exclude']['groups'], true ) && isset( $input['exclude']['roles'] ) ) {
			$roles = [];

			foreach ( $input['exclude']['roles'] as $role => $set ) {
				if ( isset( $field['options']['roles'][$role] ) )
					$roles[] = $role;
			}

			$input['exclude']['roles'] = array_unique( $roles );
		} else
			$input['exclude']['roles'] = [];

		return $input;
	}

	/**
	 * Setting: exclude IP addresses.
	 *
	 * @return string
	 */
	public function setting_exclude_ips() {
		// get ip addresses
		$ips = Post_Views_Counter()->options['general']['exclude_ips'];

		$html = '';

		// any ip addresses?
		if ( ! empty( $ips ) ) {
			foreach ( $ips as $key => $ip ) {
				$html .= '
			<div class="ip-box">
				<input type="text" name="post_views_counter_settings_general[exclude_ips][]" value="' . esc_attr( $ip ) . '" /> <a href="#" class="remove-exclude-ip" title="' . esc_attr__( 'Remove', 'post-views-counter' ) . '">' . esc_html__( 'Remove', 'post-views-counter' ) . '</a>
			</div>';
			}
		} else {
			$html .= '
			<div class="ip-box">
				<input type="text" name="post_views_counter_settings_general[exclude_ips][]" value="" /> <a href="#" class="remove-exclude-ip" title="' . esc_attr__( 'Remove', 'post-views-counter' ) . '" style="display: none">' . esc_html__( 'Remove', 'post-views-counter' ) . '</a>
			</div>';
		}

		$html .= '
			<p><input type="button" class="button button-secondary add-exclude-ip" value="' . esc_attr__( 'Add new', 'post-views-counter' ) . '" /> <input type="button" class="button button-secondary add-current-ip" value="' . esc_attr__( 'Add my current IP', 'post-views-counter' ) . '" data-rel="' . esc_attr( $_SERVER['REMOTE_ADDR'] ) . '" /></p>
			<p class="description">' . esc_html__( 'Add IP addresses or wildcards (e.g. 192.168.0.*) to exclude them from counting views.', 'post-views-counter' ) . '</p>';

		return $html;
	}

	/**
	 * Validate exclude IP addresses.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_exclude_ips( $input, $field ) {
		// any ip addresses?
		if ( isset( $input['exclude_ips'] ) ) {
			$ips = [];

			foreach ( $input['exclude_ips'] as $ip ) {
				if ( strpos( $ip, '*' ) !== false ) {
					$new_ip = str_replace( '*', '0', $ip );

					if ( filter_var( $new_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
						$ips[] = $ip;
				} elseif ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
					$ips[] = $ip;
			}

			$input['exclude_ips'] = array_unique( $ips );
		}

		return $input;
	}

	/**
	 * Setting: tools.
	 *
	 * @return string
	 */
	public function setting_import_views() {
		$html = '
		<div>
			<input type="text" class="regular-text" name="post_views_counter_import_meta_key" value="' . esc_attr( Post_Views_Counter()->options['other']['import_meta_key'] ) . '"/>
			<p class="description">' . esc_html__( 'Enter the meta key from which the views data is to be retrieved during import.', 'post-views-counter' ) . '</p>
		</div>
		<div class="pvc-subfield">
			<input type="submit" class="button button-secondary" name="post_views_counter_import_wp_postviews" value="' . esc_attr__( 'Import Views', 'post-views-counter' ) . '"/> <label><input id="pvc-wp-postviews" type="checkbox" name="post_views_counter_import_wp_postviews_override" value="1" />' . esc_html__( 'Override existing views data during import.', 'post-views-counter' ) . '</label>
			<p class="description">' . esc_html__( 'Click Import Views to start importing the views data.', 'post-views-counter' ) . '</p>
		</div>';

		return $html;
	}

	/**
	 * Setting: delete views.
	 *
	 * @return string
	 */
	public function setting_delete_views() {
		$html = '
		<input type="submit" class="button button-secondary" name="post_views_counter_reset_views" value="' . esc_attr__( 'Delete Views', 'post-views-counter' ) . '" />
		<p class="description">' . esc_html__( 'Delete ALL the existing post views data. Note that this is an irreversible process!', 'post-views-counter' ) . '</p>';

		return $html;
	}

	/**
	 * Setting: user type.
	 *
	 * @param array $field
	 *
	 * @return string
	 */
	public function setting_restrict_display( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '';

		foreach ( $field['options']['groups'] as $type => $type_name ) {
			if ( $type === 'robots' || $type === 'ai_bots' )
				continue;

			$html .= '
			<label><input id="pvc_restrict_display-' . esc_attr( $type ) . '" type="checkbox" name="post_views_counter_settings_display[restrict_display][groups][' . esc_attr( $type ) . ']" value="1" ' . checked( in_array( $type, $pvc->options['display']['restrict_display']['groups'], true ), true, false ) . ' />' . esc_html( $type_name ) . '</label>';
		}

		$html .= '
			<p class="description">' . __( 'Hide the view counter for selected visitor groups.', 'post-views-counter' ) . '</p>
			<div class="pvc_user_roles pvc-subfield"' . ( in_array( 'roles', $pvc->options['display']['restrict_display']['groups'], true ) ? '' : ' style="display: none;"' ) . '>';

		foreach ( $field['options']['roles'] as $role => $role_name ) {
			$html .= '
				<label><input type="checkbox" name="post_views_counter_settings_display[restrict_display][roles][' . esc_attr( $role ) . ']" value="1" ' . checked( in_array( $role, $pvc->options['display']['restrict_display']['roles'], true ), true, false ) . ' />' . esc_html( $role_name ) . '</label>';
		}

		$html .= '
				<p class="description">' . __( 'Hide the view counter for selected user roles.', 'post-views-counter' ) . '</p>
			</div>';

		return $html;
	}

	/**
	 * Validate user type.
	 *
	 * @param array $input
	 * @param array $field
	 *
	 * @return array
	 */
	public function validate_restrict_display( $input, $field ) {
		// any groups?
		if ( isset( $input['restrict_display']['groups'] ) ) {
			$groups = [];

			foreach ( $input['restrict_display']['groups'] as $group => $set ) {
				if ( $group === 'robots' || $group === 'ai_bots' )
					continue;

				if ( isset( $field['options']['groups'][$group] ) )
					$groups[] = $group;
			}

			$input['restrict_display']['groups'] = array_unique( $groups );
		} else
			$input['restrict_display']['groups'] = [];

		// any roles?
		if ( in_array( 'roles', $input['restrict_display']['groups'], true ) && isset( $input['restrict_display']['roles'] ) ) {
			$roles = [];

			foreach ( $input['restrict_display']['roles'] as $role => $set ) {
				if ( isset( $field['options']['roles'][$role] ) )
					$roles[] = $role;
			}

			$input['restrict_display']['roles'] = array_unique( $roles );
		} else
			$input['restrict_display']['roles'] = [];

		return $input;
	}

	/**
	 * Reports page placeholder.
	 */
	public function section_reports_placeholder() {
		echo '
		<form action="#">
			<div id="pvc-reports-placeholder">
				<img id="pvc-reports-bg" src="' . esc_url( POST_VIEWS_COUNTER_URL ) . '/css/page-reports.png" alt="Post Views Counter - Reports" />
				<div id="pvc-reports-upgrade">
					<div id="pvc-reports-modal">
						<h2>' . esc_html__( 'Display Reports and Export Views to CSV/XML', 'post-views-counter' ) . '</h2>
						<p>' . esc_html__( 'View detailed stats about the popularity of your content.', 'post-views-counter' ) . '</p>
						<p>' . esc_html__( 'Generate views reports in any date range you need.', 'post-views-counter' ) . '</p>
						<p>' . esc_html__( 'Export, download and share your website views data.', 'post-views-counter' ) . '</p>
						<p><a href="https://postviewscounter.com/upgrade/?utm_source=post-views-counter-lite&utm_medium=button&utm_campaign=upgrade-to-pro" class="button button-secondary button-hero pvc-button" target="_blank">' . esc_html__( 'Upgrade to Pro', 'post-views-counter' ) . '</a></p>
					</div>
				</div>
			</div>
		</form>';
	}

	/**
	 * Validate license.
	 *
	 * @param array $input
	 * @param array $field
	 * @return array
	 */
	public function validate_license( $input, $field ) {
		// save value from database
		return $field['value'];
	}

	/**
	 * Section description: display targets.
	 *
	 * @return void
	 */
	public function section_display_targets() {
		echo '<p class="description">' . esc_html__( 'Choose where the counter is inserted and which content types it attaches to.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: display audience.
	 *
	 * @return void
	 */
	public function section_display_audience() {
		echo '<p class="description">' . esc_html__( 'Control which visitor groups can see the counter. These rules apply on top of the display targets.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: counter appearance.
	 *
	 * @return void
	 */
	public function section_display_appearance() {
		echo '<p class="description">' . esc_html__( 'Adjust the label, period, icon, and formatting used when the counter is rendered.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: admin interface.
	 *
	 * @return void
	 */
	public function section_display_admin() {
		echo '<p class="description">' . esc_html__( 'Control how view counts are shown and managed in wp-admin (columns, edit permissions, toolbar chart).', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: other - status.
	 *
	 * @return void
	 */
	public function section_other_status() {
		echo '<p class="description">' . esc_html__( 'View license details and other status information.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: other - data import.
	 *
	 * @return void
	 */
	public function section_other_import() {
		echo '<p class="description">' . esc_html__( 'Import view counts when migrating from other analytics plugins.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: other - admin & cleanup.
	 *
	 * @return void
	 */
	public function section_other_management() {
		echo '<p class="description">' . esc_html__( 'Configure admin menu placement and what happens to data when the plugin is deactivated.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: tracking targets.
	 *
	 * @return void
	 */
	public function section_tracking_targets() {
		echo '<p class="description">' . esc_html__( 'Control which post types, archives and other content types are included in view counting.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: tracking behavior.
	 *
	 * @return void
	 */
	public function section_tracking_behavior() {
		echo '<p class="description">' . esc_html__( 'Control how views are recorded — counting mode, intervals, time zone, and cleanup.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: tracking exclusions.
	 *
	 * @return void
	 */
	public function section_tracking_exclusions() {
		echo '<p class="description">' . esc_html__( 'Exclude specific visitor groups or IP addresses from incrementing view counts.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: performance & caching.
	 *
	 * @return void
	 */
	public function section_tracking_performance() {
		echo '<p class="description">' . esc_html__( 'Configure caching compatibility and object-cache handling for counting.', 'post-views-counter' ) . '</p>';
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
}

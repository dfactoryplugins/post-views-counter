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
		add_action( 'admin_init', [ $this, 'update_counter_mode' ] );

		// filters
		add_filter( 'post_views_counter_settings_data', [ $this, 'settings_data' ] );
		add_filter( 'post_views_counter_settings_pages', [ $this, 'settings_page' ] );
	}

	/**
	 * Update counter mode.
	 *
	 * @return void
	 */
	public function update_counter_mode() {
		// get main instance
		$pvc = Post_Views_Counter();

		// Fast AJAX as active but not available counter mode?
		if ( $pvc->options['general']['counter_mode'] === 'ajax' && ! array_key_exists( 'ajax', $this->get_counter_modes() ) ) {
			// set standard JavaScript AJAX calls
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
			'php'	=> __( 'PHP', 'post-views-counter' ),
			'js'	=> __( 'JavaScript', 'post-views-counter' )
		];

		// WordPress 4.4+?
		if ( function_exists( 'register_rest_route' ) )
			$modes['rest_api'] = __( 'REST API', 'post-views-counter' );

		return apply_filters( 'pvc_get_counter_modes', $modes );
	}

	/**
	 * Add settings data.
	 *
	 * @param array $settings
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
			'robots'	=> __( 'robots', 'post-views-counter' ),
			'users'		=> __( 'logged in users', 'post-views-counter' ),
			'guests'	=> __( 'guests', 'post-views-counter' ),
			'roles'		=> __( 'selected user roles', 'post-views-counter' )
		];

		// get user roles
		$user_roles = $pvc->functions->get_user_roles();

		// get post types
		$post_types = $pvc->functions->get_post_types();

		// add settings
		$settings['post-views-counter'] = [
			'label' => __( 'Post Views Counter Settings', 'post-views-counter' ),
			'option_name' => [
				'general'	=> 'post_views_counter_settings_general',
				'display'	=> 'post_views_counter_settings_display'
			],
			'validate' => [ $this, 'validate_settings' ],
			'sections' => [
				'post_views_counter_general_settings' => [],
				'post_views_counter_display_settings' => []
			],
			'fields' => [
				'post_types_count' => [
					'tab'			=> 'general',
					'title'			=> __( 'Post Types Count', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'checkbox',
					'display_type'	=> 'horizontal',
					'description'	=> __( 'Select post types for which post views will be counted.', 'post-views-counter' ),
					'options'		=> $post_types
				],
				'counter_mode' => [
					'tab'			=> 'general',
					'title'			=> __( 'Counter Mode', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'radio',
					'description'	=> __( 'Select the method of collecting post views data. If you are using any of the caching plugins select JavaScript or REST API (if available).', 'post-views-counter' ),
					'options'		=> $this->get_counter_modes()
				],
				'post_views_column' => [
					'tab'			=> 'general',
					'title'			=> __( 'Post Views Column', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'boolean',
					'description'	=> '',
					'label'			=> __( 'Enable to display post views count column for each of the selected post types.', 'post-views-counter' )
				],
				'restrict_edit_views' => [
					'tab'			=> 'general',
					'title'			=> __( 'Restrict Edit', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'boolean',
					'description'	=> '',
					'label'			=> __( 'Enable to restrict post views editing to admins only.', 'post-views-counter' )
				],
				'time_between_counts' => [
					'tab'			=> 'general',
					'title'			=> __( 'Count Interval', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'min'			=> 0,
					'max'			=> 999999,
					'options'		=> $time_types,
					'callback'		=> [ $this, 'setting_time_between_counts' ],
					'validate'		=> [ $this, 'validate_time_between_counts' ]
				],
				'reset_counts' => [
					'tab'			=> 'general',
					'title'			=> __( 'Reset Data Interval', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'min'			=> 0,
					'max'			=> 999999,
					'options'		=> $time_types,
					'callback'		=> [ $this, 'setting_reset_counts' ],
					'validate'		=> [ $this, 'validate_reset_counts' ]
				],
				'flush_interval' => [
					'tab'			=> 'general',
					'title'			=> __( 'Flush Object Cache Interval', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'min'			=> 0,
					'max'			=> 999999,
					'options'		=> $time_types,
					'callback'		=> [ $this, 'setting_flush_interval' ],
					'validate'		=> [ $this, 'validate_flush_interval' ]
				],
				'exclude' => [
					'tab'			=> 'general',
					'title'			=> __( 'Exclude Visitors', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'options'		=> [
						'groups'	=> $groups,
						'roles'		=> $user_roles
					],
					'callback'		=> [ $this, 'setting_exclude' ],
					'validate'		=> [ $this, 'validate_exclude' ]
				],
				'exclude_ips' => [
					'tab'			=> 'general',
					'title'			=> __( 'Exclude IPs', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'callback'		=> [ $this, 'setting_exclude_ips' ],
					'validate'		=> [ $this, 'validate_exclude_ips' ]
				],
				'strict_counts' => [
					'tab'			=> 'general',
					'title'			=> __( 'Strict counts', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'boolean',
					'description'	=> '',
					'label'			=> __( 'Enable to prevent bypassing the counts interval (for e.g. using incognito browser window or by clearing cookies).', 'post-views-counter' )
				],
				'wp_postviews' => [
					'tab'			=> 'general',
					'title'			=> __( 'Tools', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'skip_saving'	=> true,
					'callback'		=> [ $this, 'setting_wp_postviews' ]
				],
				'deactivation_delete' => [
					'tab'			=> 'general',
					'title'			=> __( 'Deactivation', 'post-views-counter' ),
					'section'		=> 'post_views_counter_general_settings',
					'type'			=> 'boolean',
					'description'	=> '',
					'label'			=> __( 'Enable to delete all plugin data on deactivation.', 'post-views-counter' )
				],
				'label' => [
					'tab'			=> 'display',
					'title'			=> __( 'Post Views Label', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'input',
					'description'	=> __( 'Enter the label for the post views counter field.', 'post-views-counter' ),
					'subclass'		=> 'regular-text',
					'validate'		=> [ $this, 'validate_label' ],
					'reset'			=> [ $this, 'reset_label' ]
				],
				'post_types_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'Post Type', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'checkbox',
					'display_type'	=> 'horizontal',
					'description'	=> __( 'Select post types for which the views count will be displayed.', 'post-views-counter' ),
					'options'		=> $post_types
				],
				'page_types_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'Page Type', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'checkbox',
					'display_type'	=> 'horizontal',
					'description'	=> __( 'Select page types where the views count will be displayed.', 'post-views-counter' ),
					'options'		=> apply_filters(
						'pvc_page_types_display_options',
						[
							'home'		=> __( 'Home', 'post-views-counter' ),
							'archive'	=> __( 'Archives', 'post-views-counter' ),
							'singular'	=> __( 'Single pages', 'post-views-counter' ),
							'search'	=> __( 'Search results', 'post-views-counter' ),
						]
					)
				],
				'restrict_display' => [
					'tab'			=> 'display',
					'title'			=> __( 'User Type', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'custom',
					'description'	=> '',
					'options'		=> [
						'groups'	=> $groups,
						'roles'		=> $user_roles
					],
					'callback'		=> [ $this, 'setting_restrict_display' ],
					'validate'		=> [ $this, 'validate_restrict_display' ]
				],
				'position' => [
					'tab'			=> 'display',
					'title'			=> __( 'Position', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'select',
					'description'	=> sprintf( __( 'Select where would you like to display the post views counter. Use %s shortcode for manual display.', 'post-views-counter' ), '<code>[post-views]</code>' ),
					'options'		=> [
						'before'	=> __( 'before the content', 'post-views-counter' ),
						'after'		=> __( 'after the content', 'post-views-counter' ),
						'manual'	=> __( 'manual', 'post-views-counter' )
					]
				],
				'display_style' => [
					'tab'			=> 'display',
					'title'			=> __( 'Display Style', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'custom',
					'description'	=> __( 'Choose how to display the post views counter.', 'post-views-counter' ),
					'callback'		=> [ $this, 'setting_display_style' ],
					'validate'		=> [ $this, 'validate_display_style' ],
					'options'		=> [
						'icon'	=> __( 'icon', 'post-views-counter' ),
						'text'	=> __( 'label', 'post-views-counter' )
					]
				],
				'icon_class' => [
					'tab'			=> 'display',
					'title'			=> __( 'Icon Class', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'input',
					'description'	=> sprintf( __( 'Enter the post views icon class. Any of the <a href="%s" target="_blank">Dashicons</a> classes are available.', 'post-views-counter' ), 'https://developer.wordpress.org/resource/dashicons/' ),
					'subclass'		=> 'regular-text'
				],
				'toolbar_statistics' => [
					'tab'			=> 'display',
					'title'			=> __( 'Toolbar Chart', 'post-views-counter' ),
					'section'		=> 'post_views_counter_display_settings',
					'type'			=> 'boolean',
					'description'	=> __( 'The post views chart will be displayed for the post types that are being counted.', 'post-views-counter' ),
					'label'			=> __( 'Enable to display the post views chart at the toolbar.', 'post-views-counter' )
				]
			]
		];

		return $settings;
	}

	/**
	 * Add settings page.
	 *
	 * @param array $pages
	 * @return array
	 */
	public function settings_page( $pages ) {
		$pages['post-views-counter'] = [
			'type'			=> 'settings_page',
			'menu_slug'		=> 'post-views-counter',
			'page_title'	=> __( 'Post Views Counter Settings', 'post-views-counter' ),
			'menu_title'	=> __( 'Post Views Counter', 'post-views-counter' ),
			'capability'	=> apply_filters( 'pvc_settings_capability', 'manage_options' ),
			'callback'		=> null,
			'tabs'			=> [
				'general'	 => [
					'label'			=> __( 'General', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_general'
				],
				'display'	 => [
					'label'			=> __( 'Display', 'post-views-counter' ),
					'option_name'	=> 'post_views_counter_settings_display'
				]
			]
		];

		return $pages;
	}

	/**
	 * Validate options.
	 *
	 * @param array $input Settings data
	 * @return array
	 */
	public function validate_settings( $input ) {
		// check capability
		if ( ! current_user_can( 'manage_options' ) )
			return $input;

		// get main instance
		$pvc = Post_Views_Counter();

		// use internal settings API to validate settings first
		$input = $pvc->settings_api->validate_settings( $input );

		// import post views data from another plugin
		if ( isset( $_POST['post_views_counter_import_wp_postviews'] ) ) {
			// make sure we do not change anything in the settings
			$input = $pvc->options['general'];

			global $wpdb;

			// get views key
			$meta_key = esc_attr( apply_filters( 'pvc_import_meta_key', 'views' ) );

			// get views
			$views = $wpdb->get_results( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = '" . $meta_key . "'", ARRAY_A, 0 );

			// any views?
			if ( ! empty( $views ) ) {
				$sql = [];

				foreach ( $views as $view ) {
					$sql[] = "(" . $view['post_id'] . ", 4, 'total', " . ( (int) $view['meta_value'] ) . ")";
				}

				$wpdb->query( "INSERT INTO " . $wpdb->prefix . "post_views(id, type, period, count) VALUES " . implode( ',', $sql ) . " ON DUPLICATE KEY UPDATE count = " . ( isset( $_POST['post_views_counter_import_wp_postviews_override'] ) ? '' : 'count + ' ) . "VALUES(count)" );

				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'Post views data imported succesfully.', 'post-views-counter' ), 'updated' );
			} else
				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'There was no post views data to import.', 'post-views-counter' ), 'updated' );
		// delete all post views data
		} elseif ( isset( $_POST['post_views_counter_reset_views'] ) ) {
			// make sure we do not change anything in the settings
			$input = $pvc->options['general'];

			global $wpdb;

			if ( $wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'post_views' ) )
				add_settings_error( 'reset_post_views', 'reset_post_views', __( 'All existing data deleted succesfully.', 'post-views-counter' ), 'updated' );
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
	 * Validate label.
	 *
	 * @param array $input Input POST data
	 * @param array $field Field options
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
	 * @param array $default Default value
	 * @param array $field Field options
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
	 * @param array $field Field options
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
	 * @param array $input Input POST data
	 * @param array $field Field options
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
	 * @param array $field Field options
	 * @return string
	 */
	public function setting_time_between_counts( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '
		<input size="6" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="post_views_counter_settings_general[time_between_counts][number]" value="' . esc_attr( $pvc->options['general']['time_between_counts']['number'] ) . '" />
		<select class="pvc-chosen-short" name="post_views_counter_settings_general[time_between_counts][type]">';

		foreach ( $field['options'] as $type => $type_name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, $pvc->options['general']['time_between_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		$html .= '
		</select>
		<p class="description">' . __( 'Enter the time between single user visit count.', 'post-views-counter' ) . '</p>';

		return $html;
	}

	/**
	 * Validate count interval.
	 *
	 * @param array $input Input POST data
	 * @param array $field Field options
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
	 * @param array $field Field options
	 * @return string
	 */
	public function setting_reset_counts( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '
		<input size="6" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="post_views_counter_settings_general[reset_counts][number]" value="' . esc_attr( $pvc->options['general']['reset_counts']['number'] ) . '" />
		<select class="pvc-chosen-short" name="post_views_counter_settings_general[reset_counts][type]">';

		foreach ( array_slice( $field['options'], 2, null, true ) as $type => $type_name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, $pvc->options['general']['reset_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		$html .= '
		</select>
		<p class="description">' . sprintf( __( 'Delete single day post views data older than specified above. Enter %s if you want to preserve your data regardless of its age.', 'post-views-counter' ), '<code>0</code>' ) . '</p>';

		return $html;
	}

	/**
	 * Validate reset data interval.
	 *
	 * @param array $input Input POST data
	 * @param array $field Field options
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
	 * Setting: flush object cache interval.
	 *
	 * @param array $field Field options
	 * @return string
	 */
	public function setting_flush_interval( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '
		<input size="6" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="post_views_counter_settings_general[flush_interval][number]" value="' . esc_attr( $pvc->options['general']['flush_interval']['number'] ) . '" />
		<select class="pvc-chosen-short" name="post_views_counter_settings_general[flush_interval][type]">';

		foreach ( $field['options'] as $type => $type_name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, $pvc->options['general']['flush_interval']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		$html .= '
		</select>
		<p class="description">' . sprintf( __( 'How often to flush cached view counts from the object cache into the database. This feature is used only if a persistent object cache is detected and the interval is greater than %s. When used, view counts will be collected and stored in the object cache instead of the database and will then be asynchronously flushed to the database according to the specified interval.<br /><strong>Notice:</strong> Potential data loss may occur if the object cache is cleared/unavailable for the duration of the interval.', 'post-views-counter' ), '<code>0</code>' ) . '</p>';

		return $html;
	}

	/**
	 * Validate flush object cache interval.
	 *
	 * @param array $input Input POST data
	 * @param array $field Field options
	 * @return array
	 */
	public function validate_flush_interval( $input, $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// number
		$input['flush_interval']['number'] = isset( $input['flush_interval']['number'] ) ? (int) $input['flush_interval']['number'] : $pvc->defaults['general']['flush_interval']['number'];

		if ( $input['flush_interval']['number'] < $field['min'] || $input['flush_interval']['number'] > $field['max'] )
			$input['flush_interval']['number'] = $pvc->defaults['general']['flush_interval']['number'];

		// type
		$input['flush_interval']['type'] = isset( $input['flush_interval']['type'], $field['options'][$input['flush_interval']['type']] ) ? $input['flush_interval']['type'] : $pvc->defaults['general']['flush_interval']['type'];

		// Since the settings are about to be saved and cache flush interval could've changed,
		// we want to make sure that any changes done on the settings page are in effect immediately
		// (instead of having to wait for the previous schedule to occur).
		// We achieve that by making sure to clear any previous cache flush schedules and
		// schedule the new one if the specified interval is > 0
		$pvc->remove_cache_flush();

		if ( $input['flush_interval']['number'] > 0 )
			$pvc->schedule_cache_flush();

		return $input;
	}

	/**
	 * Setting: exclude visitors.
	 *
	 * @param array $field Field options
	 * @return string
	 */
	public function setting_exclude( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '';

		foreach ( $field['options']['groups'] as $type => $type_name ) {
			$html .= '
			<label><input id="' . esc_attr( 'pvc_exclude-' . $type ) . '" type="checkbox" name="post_views_counter_settings_general[exclude][groups][' . esc_attr( $type ) . ']" value="1" ' . checked( in_array( $type, $pvc->options['general']['exclude']['groups'], true ), true, false ) . ' />' . esc_html( $type_name ) . '</label>';
		}

		$html .= '
			<p class="description">' . __( 'Use it exclude specific user groups from post views count.', 'post-views-counter' ) . '</p>
			<div class="pvc_user_roles"' . ( in_array( 'roles', $pvc->options['general']['exclude']['groups'], true ) ? '' : ' style="display: none;"' ) . '>';

		foreach ( $field['options']['roles'] as $role => $role_name ) {
			$html .= '
				<label><input type="checkbox" name="post_views_counter_settings_general[exclude][roles][' . $role . ']" value="1" ' . checked( in_array( $role, $pvc->options['general']['exclude']['roles'], true ), true, false ) . '>' . esc_html( $role_name ) . '</label>';
		}

		$html .= '
				<p class="description">' . __( 'Use it exclude specific user roles from post views count.', 'post-views-counter' ) . '</p>
			</div>';

		return $html;
	}

	/**
	 * Validate exclude visitors.
	 *
	 * @param array $input Input POST data
	 * @param array $field Field options
	 * @return array
	 */
	public function validate_exclude( $input, $field ) {
		// any groups?
		if ( isset( $input['exclude']['groups'] ) ) {
			$groups = [];

			foreach ( $input['exclude']['groups'] as $group => $set ) {
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
				<input type="text" name="post_views_counter_settings_general[exclude_ips][]" value="" /> <a href="#" class="remove-exclude-ip" title="' . esc_attr__( 'Remove', 'post-views-counter' ) . '">' . esc_html__( 'Remove', 'post-views-counter' ) . '</a>
			</div>';
		}

		$html .= '
			<p><input type="button" class="button button-secondary add-exclude-ip" value="' . esc_attr__( 'Add new', 'post-views-counter' ) . '" /> <input type="button" class="button button-secondary add-current-ip" value="' . esc_attr__( 'Add my current IP', 'post-views-counter' ) . '" data-rel="' . esc_attr( $_SERVER['REMOTE_ADDR'] ) . '" /></p>
			<p class="description">' . esc_html__( 'Enter the IP addresses to be excluded from post views count.', 'post-views-counter' ) . '</p>';

		return $html;
	}

	/**
	 * Validate exclude IP addresses.
	 *
	 * @param array $input Input POST data
	 * @param array $field Field options
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
	public function setting_wp_postviews() {
		$html = '
		<input type="submit" class="button button-secondary" name="post_views_counter_import_wp_postviews" value="' . __( 'Import views', 'post-views-counter' ) . '"/> <label><input id="pvc-wp-postviews" type="checkbox" name="post_views_counter_import_wp_postviews_override" value="1" />' . __( 'Override existing views data.', 'post-views-counter' ) . '</label>
		<p class="description">' . __( 'Import post views data from WP-PostViews plugin.', 'post-views-counter' ) . '</p>
		<br /><input type="submit" class="button button-secondary" name="post_views_counter_reset_views" value="' . __( 'Delete views', 'post-views-counter' ) . '"/>
		<p class="description">' . __( 'Delete all the existing post views data.', 'post-views-counter' ) . '</p>';

		return $html;
	}

	/**
	 * Setting: user type.
	 *
	 * @param array $field Field options
	 * @return string
	 */
	public function setting_restrict_display( $field ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$html = '';

		foreach ( $field['options']['groups'] as $type => $type_name ) {
			if ( $type === 'robots' )
				continue;

			$html .= '
			<label><input id="pvc_restrict_display-' . esc_attr( $type ) . '" type="checkbox" name="post_views_counter_settings_display[restrict_display][groups][' . esc_html( $type ) . ']" value="1" ' . checked( in_array( $type, $pvc->options['display']['restrict_display']['groups'], true ), true, false ) . ' />' . esc_html( $type_name ) . '</label>';
		}

		$html .= '
			<p class="description">' . __( 'Use it to hide the post views counter from selected type of visitors.', 'post-views-counter' ) . '</p>
			<div class="pvc_user_roles"' . ( in_array( 'roles', $pvc->options['display']['restrict_display']['groups'], true ) ? '' : ' style="display: none;"' ) . '>';

		foreach ( $field['options']['roles'] as $role => $role_name ) {
			$html .= '
				<label><input type="checkbox" name="post_views_counter_settings_display[restrict_display][roles][' . esc_attr( $role ) . ']" value="1" ' . checked( in_array( $role, $pvc->options['display']['restrict_display']['roles'], true ), true, false ) . ' />' . esc_html( $role_name ) . '</label>';
		}

		$html .= '
				<p class="description">' . __( 'Use it to hide the post views counter from selected user roles.', 'post-views-counter' ) . '</p>
			</div>';

		return $html;
	}

	/**
	 * Validate user type.
	 *
	 * @param array $input Input POST data
	 * @param array $field Field options
	 * @return array
	 */
	public function validate_restrict_display( $input, $field ) {
		// any groups?
		if ( isset( $input['restrict_display']['groups'] ) ) {
			$groups = [];

			foreach ( $input['restrict_display']['groups'] as $group => $set ) {
				if ( $group === 'robots' )
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
}
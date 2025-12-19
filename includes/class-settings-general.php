<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Settings_General class.
 *
 * @class Post_Views_Counter_Settings_General
 */
class Post_Views_Counter_Settings_General {

	private $pvc;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->pvc = Post_Views_Counter();

		// actions
		add_action( 'admin_init', [ $this, 'update_counter_mode' ], 12 );
	}

	/**
	 * Update counter mode.
	 *
	 * @return void
	 */
	public function update_counter_mode() {
		// get settings
		$settings = $this->pvc->settings_api->get_settings();

		// fast ajax as active but not available counter mode?
		if ( $this->pvc->options['general']['counter_mode'] === 'ajax' && in_array( 'ajax', $settings['post-views-counter']['fields']['counter_mode']['disabled'], true ) ) {
			// set standard javascript ajax calls
			$this->pvc->options['general']['counter_mode'] = 'js';

			// update database options
			update_option( 'post_views_counter_settings_general', $this->pvc->options['general'] );
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
	 * Get sections for general tab.
	 *
	 * @return array
	 */
	public function get_sections() {
		return [
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
			]
		];
	}

	/**
	 * Get fields for general tab.
	 *
	 * @return array
	 */
	public function get_fields() {
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
		$user_roles = $this->pvc->functions->get_user_roles();

		// get post types
		$post_types = $this->pvc->functions->get_post_types();

		return [
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
				'description'	=> sprintf( __( 'This feature requires a persistent object cache like %s or %s to be installed and activated.', 'post-views-counter' ), '<code>Redis</code>', '<code>Memcached</code>' ) . '<br />' . __( 'Current status', 'post-views-counter' ) . ': <span class="' . ( wp_using_ext_object_cache() ? '' : 'un' ) . 'available">' . ( wp_using_ext_object_cache() ? __( 'available', 'post-views-counter' ) : __( 'unavailable', 'post-views-counter' ) ) . '</span>.'
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
			]
		];
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
		$html = '
			<label><input id="post_views_counter_general_users_count" type="checkbox" name="" value="" disabled />' . esc_html( $field['label'] ) . '</label>';

		return $html;
	}

	/**
	 * Setting: count interval.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_time_between_counts( $field ) {
		$html = '
		<input size="6" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="post_views_counter_settings_general[time_between_counts][number]" value="' . esc_attr( $this->pvc->options['general']['time_between_counts']['number'] ) . '" />
		<select name="post_views_counter_settings_general[time_between_counts][type]">';

		foreach ( $field['options'] as $type => $type_name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, $this->pvc->options['general']['time_between_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		$html .= '
		</select>
		<p class="description">' . __( 'Minimum time between counting new views from the same visitor. Enter <code>0</code> to count every page view.', 'post-views-counter' ) . '</p>';

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
		// number
		$input['time_between_counts']['number'] = isset( $input['time_between_counts']['number'] ) ? (int) $input['time_between_counts']['number'] : $this->pvc->defaults['general']['time_between_counts']['number'];

		if ( $input['time_between_counts']['number'] < $field['min'] || $input['time_between_counts']['number'] > $field['max'] )
			$input['time_between_counts']['number'] = $this->pvc->defaults['general']['time_between_counts']['number'];

		// type
		$input['time_between_counts']['type'] = isset( $input['time_between_counts']['type'], $field['options'][$input['time_between_counts']['type']] ) ? $input['time_between_counts']['type'] : $this->pvc->defaults['general']['time_between_counts']['type'];

		return $input;
	}

	/**
	 * Setting: reset data interval.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_reset_counts( $field ) {
		$html = '
		<input size="6" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="post_views_counter_settings_general[reset_counts][number]" value="' . esc_attr( $this->pvc->options['general']['reset_counts']['number'] ) . '" />
		<select name="post_views_counter_settings_general[reset_counts][type]">';

		foreach ( array_slice( $field['options'], 2, null, true ) as $type => $type_name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, $this->pvc->options['general']['reset_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
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
		// number
		$input['reset_counts']['number'] = isset( $input['reset_counts']['number'] ) ? (int) $input['reset_counts']['number'] : $this->pvc->defaults['general']['reset_counts']['number'];

		if ( $input['reset_counts']['number'] < $field['min'] || $input['reset_counts']['number'] > $field['max'] )
			$input['reset_counts']['number'] = $this->pvc->defaults['general']['reset_counts']['number'];

		// type
		$input['reset_counts']['type'] = isset( $input['reset_counts']['type'], $field['options'][$input['reset_counts']['type']] ) ? $input['reset_counts']['type'] : $this->pvc->defaults['general']['reset_counts']['type'];

		// run cron on next visit?
		$input['cron_run'] = ( $input['reset_counts']['number'] > 0 );

		// cron update?
		$input['cron_update'] = ( $input['cron_run'] && ( $this->pvc->options['general']['reset_counts']['number'] !== $input['reset_counts']['number'] || $this->pvc->options['general']['reset_counts']['type'] !== $input['reset_counts']['type'] ) );

		return $input;
	}

	/**
	 * Setting: object cache.
	 *
	 * @param array $field
	 * @return string
	 */
	public function setting_object_cache( $field ) {
		$html = '
		<input size="4" type="number" min="' . ( (int) $field['min'] ) . '" max="' . ( (int) $field['max'] ) . '" name="" value="0" disabled /> <span>' . __( 'minutes', 'post-views-counter' ) . '</span>
		<p class="">' . __( 'Persistent Object Cache', 'post-views-counter' ) . ': <span class="' . ( wp_using_ext_object_cache() ? '' : 'un' ) . 'available">' . ( wp_using_ext_object_cache() ? __( 'available', 'post-views-counter' ) : __( 'unavailable', 'post-views-counter' ) ) . '</span></p>
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
		$html = '';

		$html .= '<div class="pvc-field-group pvc-checkbox-group">';

		foreach ( $field['options']['groups'] as $type => $type_name ) {
			$is_disabled = ! empty( $field['disabled']['groups'] ) && in_array( $type, $field['disabled']['groups'], true );

			$html .= '
			<label for="' . esc_attr( 'pvc_exclude-' . $type ) . '"><input id="' . esc_attr( 'pvc_exclude-' . $type ) . '" type="checkbox" name="post_views_counter_settings_general[exclude][groups][' . esc_attr( $type ) . ']" value="1" ' . checked( in_array( $type, $this->pvc->options['general']['exclude']['groups'], true ) && ! $is_disabled, true, false ) . ' ' . disabled( $is_disabled, true, false ) . ' />' . esc_html( $type_name ) . '</label>';
		}

		$html .= '</div>';

		$html .= '
			<p class="description">' . __( 'Use this to exclude specific visitor groups from counting views.', 'post-views-counter' ) . '</p>';

		// user roles subfield
		$html .= '
			<div class="pvc_user_roles pvc_subfield pvc-field-group pvc-checkbox-group"' . ( in_array( 'roles', $this->pvc->options['general']['exclude']['groups'], true ) ? '' : ' style="display: none;"' ) . '>';

		foreach ( $field['options']['roles'] as $role => $role_name ) {
			$html .= '
				<label><input type="checkbox" name="post_views_counter_settings_general[exclude][roles][' . $role . ']" value="1" ' . checked( in_array( $role, $this->pvc->options['general']['exclude']['roles'], true ), true, false ) . ' />' . esc_html( $role_name ) . '</label>';
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
		$ips = $this->pvc->options['general']['exclude_ips'];

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
	 * Get caching compatibility description.
	 *
	 * @return string
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
	 * @param array $plugins
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

		return apply_filters( 'pvc_active_caching_plugins', $active_plugins );
	}

	/**
	 * Check whether specified plugin is active.
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

			// other caching plugin
			default:
				$is_plugin_active = apply_filters( 'pvc_is_plugin_active', false, $plugin );
		}

		return $is_plugin_active;
	}
}
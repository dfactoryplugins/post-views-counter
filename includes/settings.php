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

	private $tabs;
	private $choices;
	private $modes;
	private $time_types;
	private $groups;
	private $user_roles;
	private $positions;
	private $display_styles;
	public $post_types;
	public $page_types;

	public function __construct() {
		// actions
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu_options' ) );
		add_action( 'after_setup_theme', array( $this, 'load_defaults' ) );
		add_action( 'wp_loaded', array( $this, 'load_post_types' ) );
	}

	/**
	 * Load default settings.
	 */
	public function load_defaults() {

		if ( ! is_admin() )
			return;

		$this->modes = array(
			'php'		=> __( 'PHP', 'post-views-counter' ),
			'js'		=> __( 'JavaScript', 'post-views-counter' ),
			'ajax'		=> __( 'Fast AJAX', 'post-views-counter' )
		);
		
		if ( function_exists( 'register_rest_route' ) ) {
			$this->modes['rest_api'] = __( 'REST API', 'post-views-counter' );
		}

		$this->time_types = array(
			'minutes'	 => __( 'minutes', 'post-views-counter' ),
			'hours'		 => __( 'hours', 'post-views-counter' ),
			'days'		 => __( 'days', 'post-views-counter' ),
			'weeks'		 => __( 'weeks', 'post-views-counter' ),
			'months'	 => __( 'months', 'post-views-counter' ),
			'years'		 => __( 'years', 'post-views-counter' )
		);

		$this->groups = array(
			'robots' => __( 'robots', 'post-views-counter' ),
			'users'	 => __( 'logged in users', 'post-views-counter' ),
			'guests' => __( 'guests', 'post-views-counter' ),
			'roles'	 => __( 'selected user roles', 'post-views-counter' )
		);

		$this->positions = array(
			'before' => __( 'before the content', 'post-views-counter' ),
			'after'	 => __( 'after the content', 'post-views-counter' ),
			'manual' => __( 'manual', 'post-views-counter' )
		);

		$this->display_styles = array(
			'icon'	 => __( 'icon', 'post-views-counter' ),
			'text'	 => __( 'label', 'post-views-counter' )
		);

		$this->tabs = array(
			'general'	 => array(
				'name'	 => __( 'General', 'post-views-counter' ),
				'key'	 => 'post_views_counter_settings_general',
				'submit' => 'save_pvc_general',
				'reset'	 => 'reset_pvc_general'
			),
			'display'	 => array(
				'name'	 => __( 'Display', 'post-views-counter' ),
				'key'	 => 'post_views_counter_settings_display',
				'submit' => 'save_pvc_display',
				'reset'	 => 'reset_pvc_display'
			)
		);

		$this->user_roles = $this->get_user_roles();

		$this->page_types = apply_filters( 'pvc_page_types_display_options', array(
			'home'		 => __( 'Home', 'post-views-counter' ),
			'archive'	 => __( 'Archives', 'post-views-counter' ),
			'singular'	 => __( 'Single pages', 'post-views-counter' ),
			'search'	 => __( 'Search results', 'post-views-counter' ),
		) );
	}

	/**
	 * Get post types avaiable for counting.
	 */
	public function load_post_types() {

		if ( ! is_admin() )
			return;

		$post_types = array();

		// built in public post types
		foreach ( get_post_types( array( '_builtin' => true, 'public' => true ), 'objects', 'and' ) as $key => $post_type ) {
			if ( $key !== 'attachment' )
				$post_types[$key] = $post_type->labels->name;
		}

		// public custom post types
		foreach ( get_post_types( array( '_builtin' => false, 'public' => true ), 'objects', 'and' ) as $key => $post_type ) {
			$post_types[$key] = $post_type->labels->name;
		}

		// sort post types alphabetically with their keys
		asort( $post_types, SORT_STRING );

		$this->post_types = $post_types;
	}

	/**
	 * Get all user roles.
	 * 
	 * @global object $wp_roles
	 */
	public function get_user_roles() {
		global $wp_roles;

		$roles = array();

		foreach ( apply_filters( 'editable_roles', $wp_roles->roles ) as $role => $details ) {
			$roles[$role] = translate_user_role( $details['name'] );
		}

		asort( $roles, SORT_STRING );

		return $roles;
	}

	/**
	 * Add options page.
	 */
	public function admin_menu_options() {
		add_options_page(
		__( 'Post Views Counter', 'post-views-counter' ), __( 'Post Views Counter', 'post-views-counter' ), 'manage_options', 'post-views-counter', array( $this, 'options_page' )
		);
	}

	/**
	 * Options page callback.
	 * 
	 * @return mixed
	 */
	public function options_page() {
		$tab_key = (isset( $_GET['tab'] ) ? esc_attr( $_GET['tab'] ) : 'general');

		echo '
	    <div class="wrap">
		<h2>' . __( 'Post Views Counter', 'post-views-counter' ) . '</h2>
		<h2 class="nav-tab-wrapper">';

		foreach ( $this->tabs as $key => $name ) {
			echo '
		    <a class="nav-tab ' . ($tab_key == $key ? 'nav-tab-active' : '') . '" href="' . esc_url( admin_url( 'options-general.php?page=post-views-counter&tab=' . $key ) ) . '">' . $name['name'] . '</a>';
		}

		echo '
		</h2>
		<div class="post-views-counter-settings">
		    <div class="df-credits">
			<h3 class="hndle">' . __( 'Post Views Counter', 'post-views-counter' ) . ' ' . Post_Views_Counter()->defaults['version'] . '</h3>
			<div class="inside">
			    <h4 class="inner">' . __( 'Need support?', 'post-views-counter' ) . '</h4>
			    <p class="inner">' . sprintf( __( 'If you are having problems with this plugin, please browse it\'s <a href="%s" target="_blank">Documentation</a> or talk about them in the <a href="%s" target="_blank">Support forum</a>', 'post-views-counter' ), 'https://www.dfactory.eu/docs/post-views-counter/?utm_source=post-views-counter-settings&utm_medium=link&utm_campaign=docs', 'https://www.dfactory.eu/support/?utm_source=post-views-counter-settings&utm_medium=link&utm_campaign=support' ) . '</p>
			    <hr />
			    <h4 class="inner">' . __( 'Do you like this plugin?', 'post-views-counter' ) . '</h4>
				<p class="inner">' . sprintf( __( '<a href="%s" target="_blank">Rate it 5</a> on WordPress.org', 'post-views-counter' ), 'https://wordpress.org/support/plugin/post-views-counter/reviews/?filter=5' ) . '<br />' .
				sprintf( __( 'Blog about it & link to the <a href="%s" target="_blank">plugin page</a>.', 'post-views-counter' ), 'https://dfactory.eu/plugins/post-views-counter/?utm_source=post-views-counter-settings&utm_medium=link&utm_campaign=blog-about' ) . '<br />' .
				sprintf( __( 'Check out our other <a href="%s" target="_blank">WordPress plugins</a>.', 'post-views-counter' ), 'https://dfactory.eu/plugins/?utm_source=post-views-counter-settings&utm_medium=link&utm_campaign=other-plugins' ) . '
				</p>
			    <hr />
			    <p class="df-link inner">' . __( 'Created by', 'post-views-counter' ) . ' <a href="http://www.dfactory.eu/?utm_source=post-views-counter-settings&utm_medium=link&utm_campaign=created-by" target="_blank" title="dFactory - Quality plugins for WordPress"><img src="' . POST_VIEWS_COUNTER_URL . '/images/logo-dfactory.png' . '" title="dFactory - Quality plugins for WordPress" alt="dFactory - Quality plugins for WordPress" /></a></p>
			</div>
		    </div>
		    <form action="options.php" method="post">';

		wp_nonce_field( 'update-options' );
		settings_fields( $this->tabs[$tab_key]['key'] );
		do_settings_sections( $this->tabs[$tab_key]['key'] );

		echo '
			<p class="submit">';

		submit_button( '', 'primary', $this->tabs[$tab_key]['submit'], false );

		echo ' ';

		submit_button( __( 'Reset to defaults', 'post-views-counter' ), 'secondary reset_pvc_settings', $this->tabs[$tab_key]['reset'], false );

		echo '
			</p>
		    </form>
		</div>
	    <div class="clear"></div>
	    </div>';
	}

	/**
	 * Register settings callback.
	 */
	public function register_settings() {
		// general options
		register_setting( 'post_views_counter_settings_general', 'post_views_counter_settings_general', array( $this, 'validate_settings' ) );
		add_settings_section( 'post_views_counter_settings_general', __( 'General settings', 'post-views-counter' ), '', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_post_types_count', __( 'Post Types Count', 'post-views-counter' ), array( $this, 'post_types_count' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_counter_mode', __( 'Counter Mode', 'post-views-counter' ), array( $this, 'counter_mode' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_post_views_column', __( 'Post Views Column', 'post-views-counter' ), array( $this, 'post_views_column' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_restrict_edit_views', __( 'Restrict Edit', 'post-views-counter' ), array( $this, 'restrict_edit_views' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_time_between_counts', __( 'Count Interval', 'post-views-counter' ), array( $this, 'time_between_counts' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_reset_counts', __( 'Reset Data Interval', 'post-views-counter' ), array( $this, 'reset_counts' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_flush_interval', __( 'Flush Object Cache Interval', 'post-views-counter' ), array( $this, 'flush_interval' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_exclude', __( 'Exclude Visitors', 'post-views-counter' ), array( $this, 'exclude' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_exclude_ips', __( 'Exclude IPs', 'post-views-counter' ), array( $this, 'exclude_ips' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_strict_counts', __( 'Strict counts', 'post-views-counter' ), array( $this, 'strict_counts' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_wp_postviews', __( 'Tools', 'post-views-counter' ), array( $this, 'wp_postviews' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_deactivation_delete', __( 'Deactivation', 'post-views-counter' ), array( $this, 'deactivation_delete' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );

		// display options
		register_setting( 'post_views_counter_settings_display', 'post_views_counter_settings_display', array( $this, 'validate_settings' ) );
		add_settings_section( 'post_views_counter_settings_display', __( 'Display settings', 'post-views-counter' ), '', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_post_views_label', __( 'Post Views Label', 'post-views-counter' ), array( $this, 'post_views_label' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_post_types_display', __( 'Post Type', 'post-views-counter' ), array( $this, 'post_types_display' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_page_types_display', __( 'Page Type', 'post-views-counter' ), array( $this, 'page_types_display' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_restrict_display', __( 'User Type', 'post-views-counter' ), array( $this, 'restrict_display' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_position', __( 'Position', 'post-views-counter' ), array( $this, 'position' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_display_style', __( 'Display Style', 'post-views-counter' ), array( $this, 'display_style' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_icon_class', __( 'Icon Class', 'post-views-counter' ), array( $this, 'icon_class' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
	}

	/**
	 * Post views label option.
	 */
	public function post_views_label() {
		echo '
	<div id="pvc_post_views_label">
	    <fieldset>
		<input type="text" class="large-text" name="post_views_counter_settings_display[label]" value="' . esc_attr( Post_Views_Counter()->options['display']['label'] ) . '" />
		<p class="description">' . __( 'Enter the label for the post views counter field.', 'post-views-counter' ) . '</p>
	    </fieldset>
	</div>';
	}

	/**
	 * Post types to count option.
	 */
	public function post_types_count() {
		echo '
	<div id="pvc_post_types_count">';

		foreach ( $this->post_types as $post_type => $post_type_name ) {
			echo '
	    <label class="cb-checkbox"><input id="pvc_post_types_count-' . esc_attr( $post_type ) . '" type="checkbox" name="post_views_counter_settings_general[post_types_count][' . esc_attr( $post_type ) . ']" value="1" ' . checked( in_array( $post_type, Post_Views_Counter()->options['general']['post_types_count'], true ), true, false ) . ' />' . esc_html( $post_type_name ) . ' </label>';
		}

		echo '
	    <p class="description">' . __( 'Select post types for which post views will be counted.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Post types to display option.
	 */
	public function post_types_display() {
		echo '
	<div id="pvc_post_types_display">';

		foreach ( $this->post_types as $post_type => $post_type_name ) {
			echo '
	    <label class="cb-checkbox"><input id="pvc_post_types_display-' . esc_attr( $post_type ) . '" type="checkbox" name="post_views_counter_settings_display[post_types_display][' . esc_attr( $post_type ) . ']" value="1" ' . checked( in_array( $post_type, Post_Views_Counter()->options['display']['post_types_display'], true ), true, false ) . ' />' . esc_html( $post_type_name ) . '</label>';
		}

		echo '
	    <p class="description">' . __( 'Select post types for which the views count will be displayed.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Counter mode option.
	 */
	public function counter_mode() {
		echo '
	<div id="pvc_counter_mode">';

		foreach ( $this->modes as $key => $value ) {
			$key = esc_attr( $key );

			echo '
	    <label class="cb-radio"><input type="radio" name="post_views_counter_settings_general[counter_mode]" value="' . $key . '" ' . checked( $key, Post_Views_Counter()->options['general']['counter_mode'], false ) . ' />' . esc_html( $value ) . '</label>';
		}

		echo '
	    <p class="description">' . __( 'Select the method of collecting post views data. If you are using any of the caching plugins select Javascript or REST API (if available).', 'post-views-counter' ) . '<br />' . __( 'Optionally try the Fast AJAX experimental method, usually 10+ times faster than Javascript or REST API.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Post views column option.
	 */
	public function post_views_column() {
		echo '
	<div id="pvc_post_views_column">
	    <label class="cb-checkbox"><input id="pvc-post-views-column-enable" type="checkbox" name="post_views_counter_settings_general[post_views_column]" value="1" ' . checked( true, Post_Views_Counter()->options['general']['post_views_column'], false ) . ' />' . __( 'Enable to display post views count column for each of the selected post types.', 'post-views-counter' ) . '</label>
	</div>';
	}

	/**
	 * Time between counts option.
	 */
	public function time_between_counts() {
		echo '
	<div id="pvc_time_between_counts">
	    <input size="4" type="text" name="post_views_counter_settings_general[time_between_counts][number]" value="' . esc_attr( Post_Views_Counter()->options['general']['time_between_counts']['number'] ) . '" />
	    <select class="pvc-chosen-short" name="post_views_counter_settings_general[time_between_counts][type]">';

		foreach ( $this->time_types as $type => $type_name ) {
			echo '
		<option value="' . esc_attr( $type ) . '" ' . selected( $type, Post_Views_Counter()->options['general']['time_between_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		echo '
	    </select>
	    <p class="description">' . __( 'Enter the time between single user visit count.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Reset counts option.
	 */
	public function reset_counts() {
		echo '
	<div id="pvc_reset_counts">
	    <input size="4" type="text" name="post_views_counter_settings_general[reset_counts][number]" value="' . esc_attr( Post_Views_Counter()->options['general']['reset_counts']['number'] ) . '" />
	    <select class="pvc-chosen-short" name="post_views_counter_settings_general[reset_counts][type]">';

		foreach ( array_slice( $this->time_types, 2, null, true ) as $type => $type_name ) {
			echo '
		<option value="' . esc_attr( $type ) . '" ' . selected( $type, Post_Views_Counter()->options['general']['reset_counts']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		echo '
	    </select>
	    <p class="description">' . __( 'Delete single day post views data older than specified above. Enter 0 (number zero) if you want to preserve your data regardless of its age.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Flush interval option.
	 */
	public function flush_interval() {
		echo '
	<div id="pvc_flush_interval">
	    <input size="4" type="text" name="post_views_counter_settings_general[flush_interval][number]" value="' . esc_attr( Post_Views_Counter()->options['general']['flush_interval']['number'] ) . '" />
	    <select class="pvc-chosen-short" name="post_views_counter_settings_general[flush_interval][type]">';

		foreach ( $this->time_types as $type => $type_name ) {
			echo '
		<option value="' . esc_attr( $type ) . '" ' . selected( $type, Post_Views_Counter()->options['general']['flush_interval']['type'], false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		echo '
	    </select>
	    <p class="description">' . __( 'How often to flush cached view counts from the object cache into the database. This feature is used only if a persistent object cache is detected and the interval is greater than 0 (number zero)). When used, view counts will be collected and stored in the object cache instead of the database and will then be asynchronously flushed to the database according to the specified interval.<br /><strong>Notice:</strong> Potential data loss may occur if the object cache is cleared/unavailable for the duration of the interval.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Exlude user groups option.
	 */
	public function exclude() {
		echo '
	<div id="pvc_exclude">
	    <fieldset>';

		foreach ( $this->groups as $type => $type_name ) {
			echo '
		<label class="cb-checkbox"><input id="pvc_exclude-' . $type . '" type="checkbox" name="post_views_counter_settings_general[exclude][groups][' . $type . ']" value="1" ' . checked( in_array( $type, Post_Views_Counter()->options['general']['exclude']['groups'], true ), true, false ) . ' />' . esc_html( $type_name ) . '</label>';
		}

		echo '
		<p class="description">' . __( 'Use it to hide the post views counter from selected type of visitors.', 'post-views-counter' ) . '</p>
		<div class="pvc_user_roles"' . (in_array( 'roles', Post_Views_Counter()->options['general']['exclude']['groups'], true ) ? '' : ' style="display: none;"') . '>';

		foreach ( $this->user_roles as $role => $role_name ) {
			echo '
		    <label class="cb-checkbox"><input type="checkbox" name="post_views_counter_settings_general[exclude][roles][' . $role . ']" value="1" ' . checked( in_array( $role, Post_Views_Counter()->options['general']['exclude']['roles'], true ), true, false ) . '>' . esc_html( $role_name ) . '</label>';
		}

		echo '	    <p class="description">' . __( 'Use it to hide the post views counter from selected user roles.', 'post-views-counter' ) . '</p>
		</div>
	    </fieldset>
	</div>';
	}

	/**
	 * Exclude IPs option.
	 */
	public function exclude_ips() {
		// lovely php 5.2 limitations
		$ips = Post_Views_Counter()->options['general']['exclude_ips'];

		echo '
	<div id="pvc_exclude_ips">';

		if ( ! empty( $ips ) ) {
			foreach ( $ips as $key => $ip ) {
				echo '
	    <div class="ip-box">
		<input type="text" name="post_views_counter_settings_general[exclude_ips][]" value="' . esc_attr( $ip ) . '" /> <a href="#" class="remove-exclude-ip" title="' . esc_attr__( 'Remove', 'post-views-counter' ) . '">' . esc_attr__( 'Remove', 'post-views-counter' ) . '</a>
	    </div>';
			}
		} else {
			echo '
	    <div class="ip-box">
		<input type="text" name="post_views_counter_settings_general[exclude_ips][]" value="" /> <a href="#" class="remove-exclude-ip" title="' . esc_attr__( 'Remove', 'post-views-counter' ) . '">' . esc_attr__( 'Remove', 'post-views-counter' ) . '</a>
	    </div>';
		}

		echo '
	    <p><input type="button" class="button button-secondary add-exclude-ip" value="' . esc_attr__( 'Add new', 'post-views-counter' ) . '" /> <input type="button" class="button button-secondary add-current-ip" value="' . esc_attr__( 'Add my current IP', 'post-views-counter' ) . '" data-rel="' . esc_attr( $_SERVER['REMOTE_ADDR'] ) . '" /></p>
	    <p class="description">' . __( 'Enter the IP addresses to be excluded from post views count.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Strict counts option.
	 */
	public function strict_counts() {
		echo '
	<div id="pvc_strict_counts">
	    <label class="cb-checkbox"><input id="pvc-strict-counts" type="checkbox" name="post_views_counter_settings_general[strict_counts]" value="1" ' . checked( true, Post_Views_Counter()->options['general']['strict_counts'], false ) . ' />' . __( 'Enable to prevent bypassing the counts interval (for e.g. using incognito browser window or by clearing cookies).', 'post-views-counter' ) . '</label>
	</div>';
	}

	/**
	 * WP-PostViews import option.
	 */
	public function wp_postviews() {
		echo '
	<div id="pvc_wp_postviews">
	    <fieldset>
			<input type="submit" class="button button-secondary" name="post_views_counter_import_wp_postviews" value="' . __( 'Import views', 'post-views-counter' ) . '"/> <label class="cb-checkbox"><input id="pvc-wp-postviews" type="checkbox" name="post_views_counter_import_wp_postviews_override" value="1" />' . __( 'Override existing views data.', 'post-views-counter' ) . '</label>
			<p class="description">' . __( 'Import post views data from WP-PostViews plugin.', 'post-views-counter' ) . '</p>
			<input type="submit" class="button button-secondary" name="post_views_counter_reset_views" value="' . __( 'Delete views', 'post-views-counter' ) . '"/>
			<p class="description">' . __( 'Delete ALL the existing post views data.', 'post-views-counter' ) . '</p>
	    </fieldset>
	</div>';
	}

	/**
	 * Limit views edit to admins.
	 */
	public function restrict_edit_views() {
		echo '
	<div id="pvc_restrict_edit_views">
	    <label class="cb-checkbox"><input type="checkbox" name="post_views_counter_settings_general[restrict_edit_views]" value="1" ' . checked( true, Post_Views_Counter()->options['general']['restrict_edit_views'], false ) . ' />' . __( 'Enable to restrict post views editing to admins only.', 'post-views-counter' ) . '</label>
	</div>';
	}

	/**
	 * Plugin deactivation option.
	 */
	public function deactivation_delete() {
		echo '
	<div id="pvc_deactivation_delete">
	    <label class="cb-checkbox"><input type="checkbox" name="post_views_counter_settings_general[deactivation_delete]" value="1" ' . checked( true, Post_Views_Counter()->options['general']['deactivation_delete'], false ) . ' />' . __( 'Enable to delete all plugin data on deactivation.', 'post-views-counter' ) . '</label>
	</div>';
	}

	/**
	 * Visibility option.
	 */
	public function page_types_display() {
		echo '
	<div id="pvc_post_types_display">';

		foreach ( $this->page_types as $key => $label ) {
			echo '
	    <label class="cb-checkbox"><input id="pvc_page_types_display-' . esc_attr( $key ) . '" type="checkbox" name="post_views_counter_settings_display[page_types_display][' . esc_attr( $key ) . ']" value="1" ' . checked( in_array( $key, Post_Views_Counter()->options['display']['page_types_display'], true ), true, false ) . ' />' . esc_html( $label ) . '</label>';
		}

		echo '
	    <p class="description">' . __( 'Select page types where the views count will be displayed.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Counter position option.
	 */
	public function position() {
		echo '
	<div id="pvc_position">
	    <select class="pvc-chosen-short" name="post_views_counter_settings_display[position]">';

		foreach ( $this->positions as $position => $position_name ) {
			echo '
		<option value="' . esc_attr( $position ) . '" ' . selected( $position, Post_Views_Counter()->options['display']['position'], false ) . '>' . esc_html( $position_name ) . '</option>';
		}

		echo '
	    </select>
	    <p class="description">' . __( 'Select where would you like to display the post views counter. Use [post-views] shortcode for manual display.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Counter style option.
	 */
	public function display_style() {
		echo '
	<div id="pvc_display_style">';

		foreach ( $this->display_styles as $display => $style ) {
			$display = esc_attr( $display );

			echo '
	    <label class="cb-checkbox"><input type="checkbox" name="post_views_counter_settings_display[display_style][' . $display . ']" value="1" ' . checked( true, Post_Views_Counter()->options['display']['display_style'][$display], false ) . ' />' . esc_html( $style ) . '</label>';
		}

		echo '
	    <p class="description">' . __( 'Choose how to display the post views counter.', 'post-views-counter' ) . '</p>
	</div>';
	}

	/**
	 * Counter icon class option.
	 */
	public function icon_class() {
		echo '
	<div id="pvc_icon_class">
	    <input type="text" name="post_views_counter_settings_display[icon_class]" class="large-text" value="' . esc_attr( Post_Views_Counter()->options['display']['icon_class'] ) . '" />
	    <p class="description">' . sprintf( __( 'Enter the post views icon class. Any of the <a href="%s" target="_blank">Dashicons</a> classes are available.', 'post-views-counter' ), 'https://developer.wordpress.org/resource/dashicons/' ) . '</p>
	</div>';
	}

	/**
	 * Restrict display option.
	 */
	public function restrict_display() {
		echo '
	<div id="pvc_restrict_display">
	    <fieldset>';

		foreach ( $this->groups as $type => $type_name ) {

			if ( $type === 'robots' )
				continue;

			echo '
		<label class="cb-checkbox"><input  id="pvc_restrict_display-' . $type . '" type="checkbox" name="post_views_counter_settings_display[restrict_display][groups][' . esc_html( $type ) . ']" value="1" ' . checked( in_array( $type, Post_Views_Counter()->options['display']['restrict_display']['groups'], true ), true, false ) . ' />' . esc_html( $type_name ) . '</label>';
		}

		echo '
		<p class="description">' . __( 'Use it to hide the post views counter from selected type of visitors.', 'post-views-counter' ) . '</p>
		<div class="pvc_user_roles"' . (in_array( 'roles', Post_Views_Counter()->options['display']['restrict_display']['groups'], true ) ? '' : ' style="display: none;"') . '>';

		foreach ( $this->user_roles as $role => $role_name ) {
			echo '
		    <label class="cb-checkbox"><input type="checkbox" name="post_views_counter_settings_display[restrict_display][roles][' . $role . ']" value="1" ' . checked( in_array( $role, Post_Views_Counter()->options['display']['restrict_display']['roles'], true ), true, false ) . ' />' . esc_html( $role_name ) . '</label>';
		}

		echo '
		    <p class="description">' . __( 'Use it to hide the post views counter from selected user roles.', 'post-views-counter' ) . '</p>
		</div>
	    </fieldset>
	</div>';
	}

	/**
	 * Validate general settings.
	 */
	public function validate_settings( $input ) {
		if ( isset( $_POST['post_views_counter_import_wp_postviews'] ) ) {
			global $wpdb;

			$meta_key = esc_attr( apply_filters( 'pvc_import_meta_key', 'views' ) );

			$views = $wpdb->get_results( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = '" . $meta_key . "'", ARRAY_A, 0 );

			if ( ! empty( $views ) ) {
				$input = Post_Views_Counter()->defaults['general'];
				$input['wp_postviews_import'] = true;

				$sql = array();

				foreach ( $views as $view ) {
					$sql[] = "(" . $view['post_id'] . ", 4, 'total', " . $view['meta_value'] . ")";
				}

				$wpdb->query( "INSERT INTO " . $wpdb->prefix . "post_views(id, type, period, count) VALUES " . implode( ',', $sql ) . " ON DUPLICATE KEY UPDATE count = " . (isset( $_POST['post_views_counter_import_wp_postviews_override'] ) ? '' : 'count + ') . "VALUES(count)" );

				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'Post views data imported succesfully.', 'post-views-counter' ), 'updated' );
			} else {
				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'There was no post views data to import.', 'post-views-counter' ), 'updated' );
			}
		} elseif ( isset( $_POST['post_views_counter_reset_views'] ) ) {
			global $wpdb;

			if ( $wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . 'post_views' ) )
				add_settings_error( 'reset_post_views', 'reset_post_views', __( 'All existing data deleted succesfully.', 'post-views-counter' ), 'updated' );
			else
				add_settings_error( 'reset_post_views', 'reset_post_views', __( 'Error occurred. All existing data were not deleted.', 'post-views-counter' ), 'error' );
		} elseif ( isset( $_POST['save_pvc_general'] ) ) {
			// post types count
			if ( isset( $input['post_types_count'] ) ) {
				$post_types = array();

				foreach ( $input['post_types_count'] as $post_type => $set ) {
					if ( isset( $this->post_types[$post_type] ) )
						$post_types[] = $post_type;
				}

				$input['post_types_count'] = array_unique( $post_types );
			} else
				$input['post_types_count'] = array();

			// counter mode
			$input['counter_mode'] = isset( $input['counter_mode'], $this->modes[$input['counter_mode']] ) ? $input['counter_mode'] : Post_Views_Counter()->defaults['general']['counter_mode'];

			// post views column
			$input['post_views_column'] = isset( $input['post_views_column'] );

			// time between counts
			$input['time_between_counts']['number'] = (int) ( isset( $input['time_between_counts']['number'] ) ? $input['time_between_counts']['number'] : Post_Views_Counter()->defaults['general']['time_between_counts']['number'] );
			$input['time_between_counts']['type'] = isset( $input['time_between_counts']['type'], $this->time_types[$input['time_between_counts']['type']] ) ? $input['time_between_counts']['type'] : Post_Views_Counter()->defaults['general']['time_between_counts']['type'];

			// flush interval
			$input['flush_interval']['number'] = (int) ( isset( $input['flush_interval']['number'] ) ? $input['flush_interval']['number'] : Post_Views_Counter()->defaults['general']['flush_interval']['number'] );
			$input['flush_interval']['type'] = isset( $input['flush_interval']['type'], $this->time_types[$input['flush_interval']['type']] ) ? $input['flush_interval']['type'] : Post_Views_Counter()->defaults['general']['flush_interval']['type'];

			// Since the settings are about to be saved and cache flush interval could've changed,
			// we want to make sure that any changes done on the settings page are in effect immediately
			// (instead of having to wait for the previous schedule to occur).
			// We achieve that by making sure to clear any previous cache flush schedules and
			// schedule the new one if the specified interval is > 0
			Post_Views_Counter()->remove_cache_flush();

			if ( $input['flush_interval']['number'] > 0 ) {
				Post_Views_Counter()->schedule_cache_flush();
			}

			// reset counts
			$input['reset_counts']['number'] = (int) ( isset( $input['reset_counts']['number'] ) ? $input['reset_counts']['number'] : Post_Views_Counter()->defaults['general']['reset_counts']['number'] );
			$input['reset_counts']['type'] = isset( $input['reset_counts']['type'], $this->time_types[$input['reset_counts']['type']] ) ? $input['reset_counts']['type'] : Post_Views_Counter()->defaults['general']['reset_counts']['type'];

			// run cron on next visit?
			$input['cron_run'] = ($input['reset_counts']['number'] > 0 ? true : false);
			$input['cron_update'] = ($input['cron_run'] && (Post_Views_Counter()->options['general']['reset_counts']['number'] !== $input['reset_counts']['number'] || Post_Views_Counter()->options['general']['reset_counts']['type'] !== $input['reset_counts']['type']) ? true : false);

			// exclude
			if ( isset( $input['exclude']['groups'] ) ) {
				$groups = array();

				foreach ( $input['exclude']['groups'] as $group => $set ) {
					if ( isset( $this->groups[$group] ) )
						$groups[] = $group;
				}

				$input['exclude']['groups'] = array_unique( $groups );
			} else {
				$input['exclude']['groups'] = array();
			}

			if ( in_array( 'roles', $input['exclude']['groups'], true ) && isset( $input['exclude']['roles'] ) ) {
				$roles = array();

				foreach ( $input['exclude']['roles'] as $role => $set ) {
					if ( isset( $this->user_roles[$role] ) )
						$roles[] = $role;
				}

				$input['exclude']['roles'] = array_unique( $roles );
			} else
				$input['exclude']['roles'] = array();

			// exclude ips
			if ( isset( $input['exclude_ips'] ) ) {
				$ips = array();

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

			// restrict edit views
			$input['restrict_edit_views'] = isset( $input['restrict_edit_views'] );

			// strict counts
			$input['strict_counts'] = isset( $input['strict_counts'] );

			// deactivation delete
			$input['deactivation_delete'] = isset( $input['deactivation_delete'] );
		} elseif ( isset( $_POST['save_pvc_display'] ) ) {

			// post views label
			$input['label'] = isset( $input['label'] ) ? $input['label'] : Post_Views_Counter()->defaults['general']['label'];

			if ( function_exists( 'icl_register_string' ) )
				icl_register_string( 'Post Views Counter', 'Post Views Label', $input['label'] );

			// position
			$input['position'] = isset( $input['position'], $this->positions[$input['position']] ) ? $input['position'] : Post_Views_Counter()->defaults['general']['position'];

			// display style
			$input['display_style']['icon'] = isset( $input['display_style']['icon'] );
			$input['display_style']['text'] = isset( $input['display_style']['text'] );

			// link to post
			$input['link_to_post'] = isset( $input['link_to_post'] ) ? $input['link_to_post'] : Post_Views_Counter()->defaults['display']['link_to_post'];

			// icon class
			$input['icon_class'] = isset( $input['icon_class'] ) ? trim( $input['icon_class'] ) : Post_Views_Counter()->defaults['general']['icon_class'];

			// post types display
			if ( isset( $input['post_types_display'] ) ) {
				$post_types = array();

				foreach ( $input['post_types_display'] as $post_type => $set ) {
					if ( isset( $this->post_types[$post_type] ) )
						$post_types[] = $post_type;
				}

				$input['post_types_display'] = array_unique( $post_types );
			} else
				$input['post_types_display'] = array();

			// page types display
			if ( isset( $input['page_types_display'] ) ) {
				$page_types = array();

				foreach ( $input['page_types_display'] as $page_type => $set ) {
					if ( isset( $this->page_types[$page_type] ) )
						$page_types[] = $page_type;
				}

				$input['page_types_display'] = array_unique( $page_types );
			} else
				$input['page_types_display'] = array();

			// restrict display
			if ( isset( $input['restrict_display']['groups'] ) ) {
				$groups = array();

				foreach ( $input['restrict_display']['groups'] as $group => $set ) {
					if ( $group === 'robots' )
						continue;

					if ( isset( $this->groups[$group] ) )
						$groups[] = $group;
				}

				$input['restrict_display']['groups'] = array_unique( $groups );
			} else
				$input['restrict_display']['groups'] = array();

			if ( in_array( 'roles', $input['restrict_display']['groups'], true ) && isset( $input['restrict_display']['roles'] ) ) {
				$roles = array();

				foreach ( $input['restrict_display']['roles'] as $role => $set ) {
					if ( isset( $this->user_roles[$role] ) )
						$roles[] = $role;
				}

				$input['restrict_display']['roles'] = array_unique( $roles );
			} else
				$input['restrict_display']['roles'] = array();
		} elseif ( isset( $_POST['reset_pvc_general'] ) ) {
			$input = Post_Views_Counter()->defaults['general'];

			add_settings_error( 'reset_general_settings', 'settings_reset', __( 'General settings restored to defaults.', 'post-views-counter' ), 'updated' );
		} elseif ( isset( $_POST['reset_pvc_display'] ) ) {
			$input = Post_Views_Counter()->defaults['display'];

			add_settings_error( 'reset_general_settings', 'settings_reset', __( 'Display settings restored to defaults.', 'post-views-counter' ), 'updated' );
		}

		return $input;
	}
}
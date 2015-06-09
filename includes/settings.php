<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

new Post_Views_Counter_Settings();

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

	public function __construct() {
		// set instance
		Post_Views_Counter()->add_instance( 'settings', $this );

		// actions
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
		add_action( 'admin_menu', array( &$this, 'admin_menu_options' ) );
		add_action( 'after_setup_theme', array( &$this, 'load_defaults' ) );
		add_action( 'wp_loaded', array( &$this, 'load_post_types' ) );
	}

	/**
	 * Load default settings.
	 */
	public function load_defaults() {
		
		$this->modes = array(
			'php'	 => __( 'PHP', 'post-views-counter' ),
			'js'	 => __( 'JavaScript', 'post-views-counter' )
		);

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
	}

	/**
	 * Get post types avaiable for counting.
	 */
	public function load_post_types() {
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
			__( 'Post Views Counter', 'post-views-counter' ), __( 'Post Views Counter', 'post-views-counter' ), 'manage_options', 'post-views-counter', array( &$this, 'options_page' )
		);
	}

	/**
	 * Options page callback.
	 */
	public function options_page() {
		$tab_key = (isset( $_GET['tab'] ) ? $_GET['tab'] : 'general');

		echo '
		<div class="wrap">' . screen_icon() . '
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
					<h3 class="hndle">' . __( 'Post Views Counter', 'post-views-counter' ) . ' ' . Post_Views_Counter()->get_attribute( 'defaults', 'version' ) . '</h3>
					<div class="inside">
						<h4 class="inner">' . __( 'Need support?', 'post-views-counter' ) . '</h4>
						<p class="inner">' . __( 'If you are having problems with this plugin, please talk about them in the', 'post-views-counter' ) . ' <a href="http://www.dfactory.eu/support/?utm_source=post-views-counter-settings&utm_medium=link&utm_campaign=support" target="_blank" title="' . __( 'Support forum', 'post-views-counter' ) . '">' . __( 'Support forum', 'post-views-counter' ) . '</a></p>
						<hr />
						<h4 class="inner">' . __( 'Do you like this plugin?', 'post-views-counter' ) . '</h4>
						<p class="inner"><a href="http://wordpress.org/support/view/plugin-reviews/post-views-counter" target="_blank" title="' . __( 'Rate it 5', 'post-views-counter' ) . '">' . __( 'Rate it 5', 'post-views-counter' ) . '</a> ' . __( 'on WordPress.org', 'post-views-counter' ) . '<br />' .
		__( 'Blog about it & link to the', 'post-views-counter' ) . ' <a href="http://www.dfactory.eu/plugins/post-views-counter/?utm_source=post-views-counter-settings&utm_medium=link&utm_campaign=blog-about" target="_blank" title="' . __( 'plugin page', 'post-views-counter' ) . '">' . __( 'plugin page', 'post-views-counter' ) . '</a><br/>' .
		__( 'Check out our other', 'post-views-counter' ) . ' <a href="http://www.dfactory.eu/plugins/?utm_source=post-views-counter-settings&utm_medium=link&utm_campaign=other-plugins" target="_blank" title="' . __( 'WordPress plugins', 'post-views-counter' ) . '">' . __( 'WordPress plugins', 'post-views-counter' ) . '</a>
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
		register_setting( 'post_views_counter_settings_general', 'post_views_counter_settings_general', array( &$this, 'validate_settings' ) );
		add_settings_section( 'post_views_counter_settings_general', __( 'General settings', 'post-views-counter' ), '', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_post_types_count', __( 'Post Types Count', 'post-views-counter' ), array( &$this, 'post_types_count' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_counter_mode', __( 'Counter Mode', 'post-views-counter' ), array( &$this, 'counter_mode' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_post_views_column', __( 'Post Views Column', 'post-views-counter' ), array( &$this, 'post_views_column' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_restrict_edit_views', __( 'Restrict Edit', 'post-views-counter' ), array( &$this, 'restrict_edit_views' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_time_between_counts', __( 'Time Between Counts', 'post-views-counter' ), array( &$this, 'time_between_counts' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_reset_counts', __( 'Reset Data Interval', 'post-views-counter' ), array( &$this, 'reset_counts' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_flush_interval', __( 'Flush Object Cache Interval', 'post-views-counter' ), array( &$this, 'flush_interval' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_exclude', __( 'Exclude Visitors', 'post-views-counter' ), array( &$this, 'exclude' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_exclude_ips', __( 'Exclude IPs', 'post-views-counter' ), array( &$this, 'exclude_ips' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );
		add_settings_field( 'pvc_wp_postviews', __( 'WP-PostViews', 'post-views-counter' ), array( &$this, 'wp_postviews' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );	
		add_settings_field( 'pvc_deactivation_delete', __( 'Deactivation', 'post-views-counter' ), array( &$this, 'deactivation_delete' ), 'post_views_counter_settings_general', 'post_views_counter_settings_general' );

		// display options
		register_setting( 'post_views_counter_settings_display', 'post_views_counter_settings_display', array( &$this, 'validate_settings' ) );
		add_settings_section( 'post_views_counter_settings_display', __( 'Display settings', 'post-views-counter' ), '', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_post_views_label', __( 'Post Views Label', 'post-views-counter' ), array( &$this, 'post_views_label' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_post_types_display', __( 'Post Types Display', 'post-views-counter' ), array( &$this, 'post_types_display' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_restrict_display', __( 'Restrict Display', 'post-views-counter' ), array( &$this, 'restrict_display' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_position', __( 'Position', 'post-views-counter' ), array( &$this, 'position' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_display_style', __( 'Display Style', 'post-views-counter' ), array( &$this, 'display_style' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
		add_settings_field( 'pvc_icon_class', __( 'Icon Class', 'post-views-counter' ), array( &$this, 'icon_class' ), 'post_views_counter_settings_display', 'post_views_counter_settings_display' );
	}

	/**
	 * Post views label option.
	 */
	public function post_views_label() {
		echo '
		<div id="pvc_post_views_label">
			<fieldset>
				<input type="text" class="large-text" name="post_views_counter_settings_display[label]" value="' . esc_attr( Post_Views_Counter()->get_attribute( 'options', 'display', 'label' ) ) . '" />
				<br/>
				<span class="description">' . esc_html__( 'Enter the label for the post views counter field.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Post types to count option.
	 */
	public function post_types_count() {
		echo '
		<div id="pvc_post_types_count">
			<fieldset>
				<select class="pvc-chosen" data-placeholder="' . esc_attr__( 'Select post types', 'post-views-counter' ) . '" name="post_views_counter_settings_general[post_types_count][]" multiple="multiple">';

		foreach ( $this->post_types as $post_type => $post_type_name ) {
			echo '
					<option value="' . esc_attr( $post_type ) . '" ' . selected( in_array( $post_type, Post_Views_Counter()->get_attribute( 'options', 'general', 'post_types_count' ), true ), true, false ) . '>' . esc_html( $post_type_name ) . '</option>';
		}

		echo '
				</select>
				<br/>
				<span class="description">' . esc_html__( 'Select post types for which post views will be counted.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Post types to display option.
	 */
	public function post_types_display() {
		echo '
		<div id="pvc_post_types_display">
			<fieldset>
				<select class="pvc-chosen" data-placeholder="' . esc_attr__( 'Select groups', 'post-views-counter' ) . '" name="post_views_counter_settings_display[post_types_display][]" multiple="multiple">';

		foreach ( $this->post_types as $post_type => $post_type_name ) {
			echo '
					<option value="' . esc_attr( $post_type ) . '" ' . selected( in_array( $post_type, Post_Views_Counter()->get_attribute( 'options', 'display', 'post_types_display' ), true ), true, false ) . '>' . esc_html( $post_type_name ) . '</option>';
		}

		echo '
				</select>
				<br/>
				<span class="description">' . esc_html__( 'Select post types for which post views will be displayed.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Counter mode option.
	 */
	public function counter_mode() {
		echo '
		<div id="pvc_counter_mode">
			<fieldset>';

		foreach ( $this->modes as $key => $value ) {
			$key = esc_attr( $key );

			echo '
				<input id="pvc-counter-mode-' . $key . '" type="radio" name="post_views_counter_settings_general[counter_mode]" value="' . $key . '" ' . checked( $key, Post_Views_Counter()->get_attribute( 'options', 'general', 'counter_mode' ), false ) . ' /><label for="pvc-counter-mode-' . $key . '">' . esc_html( $value ) . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . esc_html__( 'Select the method of collecting post views data. If you are using any of the caching plugins select Javascript.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Post views column option.
	 */
	public function post_views_column() {
		echo '
		<div id="pvc_post_views_column">
			<input id="pvc-post-views-column-enable" type="checkbox" name="post_views_counter_settings_general[post_views_column]" value="1" ' . checked( true, (bool) Post_Views_Counter()->get_attribute( 'options', 'general', 'post_views_column' ), false ) . ' /><label for="pvc-post-views-column-enable">' . esc_html__( 'Enable to display post views count column for each of the selected post types.', 'post-views-counter' ) . '</label>
		</div>';
	}

	/**
	 * Time between counts option.
	 */
	public function time_between_counts() {
		echo '
		<div id="pvc_time_between_counts">
			<fieldset>
				<input size="4" type="text" name="post_views_counter_settings_general[time_between_counts][number]" value="' . esc_attr( Post_Views_Counter()->get_attribute( 'options', 'general', 'time_between_counts', 'number' ) ) . '" />
				<select class="pvc-chosen-short" name="post_views_counter_settings_general[time_between_counts][type]">';

		foreach ( $this->time_types as $type => $type_name ) {
			echo '
					<option value="' . esc_attr( $type ) . '" ' . selected( $type, Post_Views_Counter()->get_attribute( 'options', 'general', 'time_between_counts', 'type' ), false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		echo '
				</select>
				<br/>
				<span class="description">' . esc_html__( 'Enter the time between single user visit count.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Reset counts option.
	 */
	public function reset_counts() {
		echo '
		<div id="pvc_reset_counts">
			<fieldset>
				<input size="4" type="text" name="post_views_counter_settings_general[reset_counts][number]" value="' . esc_attr( Post_Views_Counter()->get_attribute( 'options', 'general', 'reset_counts', 'number' ) ) . '" />
				<select class="pvc-chosen-short" name="post_views_counter_settings_general[reset_counts][type]">';

		foreach ( $this->time_types as $type => $type_name ) {
			echo '
					<option value="' . esc_attr( $type ) . '" ' . selected( $type, Post_Views_Counter()->get_attribute( 'options', 'general', 'reset_counts', 'type' ), false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		echo '
				</select>
				<br/>
				<span class="description">' . esc_html__( 'Delete single day post views data older than specified above. Enter 0 (number zero) if you want to preserve your data regardless of its age.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Flush interval option.
	 */
	public function flush_interval() {
		echo '
		<div id="pvc_flush_interval">
			<fieldset>
				<input size="4" type="text" name="post_views_counter_settings_general[flush_interval][number]" value="' . esc_attr( Post_Views_Counter()->get_attribute( 'options', 'general', 'flush_interval', 'number' ) ) . '" />
				<select class="pvc-chosen-short" name="post_views_counter_settings_general[flush_interval][type]">';

		foreach ( $this->time_types as $type => $type_name ) {
			echo '
					<option value="' . esc_attr( $type ) . '" ' . selected( $type, Post_Views_Counter()->get_attribute( 'options', 'general', 'flush_interval', 'type' ), false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		echo '
				</select>
				<br/>
				<span class="description">' . __( 'How often to flush cached view counts from the object cache into the database. This feature is used only if a persistent object cache is detected and the interval is greater than 0 (number zero)). When used, view counts will be collected and stored in the object cache instead of the database and will then be asynchronously flushed to the database according to the specified interval.<br /><strong>Notice:</strong> Potential data loss may occur if the object cache is cleared/unavailable for the duration of the interval.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Exlude user groups option.
	 */
	public function exclude() {
		echo '
		<div id="pvc_exclude">
			<fieldset>
				<select class="pvc-chosen" data-placeholder="' . esc_attr__( 'Select groups', 'post-views-counter' ) . '" name="post_views_counter_settings_general[exclude][groups][]" multiple="multiple">';

		foreach ( $this->groups as $type => $type_name ) {
			echo '
					<option value="' . esc_attr( $type ) . '" ' . selected( in_array( $type, Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude', 'groups' ), true ), true, false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		echo '
				</select>
				<br/>
				<div class="pvc_user_roles"' . (in_array( 'roles', Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude', 'groups' ), true ) ? '' : ' style="display: none;"') . '>
					<select class="pvc-chosen" data-placeholder="' . esc_attr__( 'Select user roles', 'post-views-counter' ) . '" name="post_views_counter_settings_general[exclude][roles][]" multiple="multiple">';

		foreach ( $this->user_roles as $role => $role_name ) {
			echo '
						<option value="' . esc_attr( $role ) . '" ' . selected( in_array( $role, Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude', 'roles' ), true ), true, false ) . '>' . esc_html( $role_name ) . '</option>';
		}

		echo '
					</select>
					<br/>
				</div>
				<span class="description">' . esc_html__( 'Select the type of visitors to be excluded from post views count.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Exclude IPs option.
	 */
	public function exclude_ips() {
		echo '
		<div id="pvc_exclude_ips">
			<fieldset>';

		foreach ( Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude_ips' ) as $key => $ip ) {
			echo '
				<div class="ip-box">
					<input type="text" name="post_views_counter_settings_general[exclude_ips][]" value="' . esc_attr( $ip ) . '" /> <input type="button" class="button button-secondary remove-exclude-ip" value="' . esc_attr__( 'Remove', 'post-views-counter' ) . '" />
				</div>';
		}

		// lovely php 5.2 limitations
		$ips = Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude_ips' );

		echo '
				<div class="ip-box">
					<input type="text" name="post_views_counter_settings_general[exclude_ips][]" value="" /> <input type="button" class="button button-secondary remove-exclude-ip" value="' . esc_attr__( 'Remove', 'post-views-counter' ) . '"' . (empty( $ips ) ? ' style="display: none;"' : '') . ' /> <input type="button" class="button button-secondary add-exclude-ip" value="' . esc_attr__( 'Add new', 'post-views-counter' ) . '" /> <input type="button" class="button button-secondary add-current-ip" value="' . esc_attr__( 'Add my current IP', 'post-views-counter' ) . '" data-rel="' . esc_attr( $_SERVER['REMOTE_ADDR'] ) . '" />
				</div>
				<span class="description">' . esc_html__( 'Enter the IP addresses to be excluded from post views count.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * WP-PostViews import option.
	 */
	public function wp_postviews() {
		echo '
		<div id="pvc_wp_postviews">
			<fieldset>
				<input type="submit" class="button button-secondary" name="post_views_counter_import_wp_postviews" value="' . __( 'Import', 'post-views-counter' ) . '"/>
				<br/>
				<p class="description">' . esc_html__( 'Import post views data from WP-PostViews plugin.', 'post-views-counter' ) . '</p>
				<input id="pvc-wp-postviews" type="checkbox" name="post_views_counter_import_wp_postviews_override" value="1" /><label for="pvc-wp-postviews">' . esc_html__( 'Override existing Post Views Counter data.', 'post-views-counter' ) . '</label>
			</fieldset>
		</div>';
	}
	
	/**
	 * Limit views edit to admins.
	 */
	public function restrict_edit_views() {
		echo '
		<div id="pvc_restrict_edit_views">
			<input id="pvc-restrict-edit-views-enable" type="checkbox" name="post_views_counter_settings_general[restrict_edit_views]" value="1" ' . checked( true, (bool) Post_Views_Counter()->get_attribute( 'options', 'general', 'restrict_edit_views' ), false ) . ' /><label for="pvc-restrict-edit-views-enable">' . esc_html__( 'Enable to restrict post views editing to admins only.', 'post-views-counter' ) . '</label>
		</div>';
	}

	/**
	 * Plugin deactivation option.
	 */
	public function deactivation_delete() {
		echo '
		<div id="pvc_deactivation_delete">
			<input id="pvc-deactivation-delete-enable" type="checkbox" name="post_views_counter_settings_general[deactivation_delete]" value="1" ' . checked( true, (bool) Post_Views_Counter()->get_attribute( 'options', 'general', 'deactivation_delete' ), false ) . ' /><label for="pvc-deactivation-delete-enable">' . esc_html__( 'Enable to delete all plugin data on deactivation.', 'post-views-counter' ) . '</label>
		</div>';
	}

	/**
	 * Counter position option.
	 */
	public function position() {
		echo '
		<div id="pvc_position">
			<fieldset>
				<select class="pvc-chosen-short" name="post_views_counter_settings_display[position]">';

		foreach ( $this->positions as $position => $position_name ) {
			echo '
					<option value="' . esc_attr( $position ) . '" ' . selected( $position, Post_Views_Counter()->get_attribute( 'options', 'display', 'position' ), false ) . '>' . esc_html( $position_name ) . '</option>';
		}

		echo '
				</select>
				<br/>
				<span class="description">' . esc_html__( 'Select where would you like to display the post views counter. Use [post-views] shortcode for manual display.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Counter style option.
	 */
	public function display_style() {
		echo '
		<div id="pvc_display_style">
			<fieldset>';

		foreach ( $this->display_styles as $display => $style ) {
			$display = esc_attr( $display );

			echo '
				<input id="pvc-display-style-' . $display . '" type="checkbox" name="post_views_counter_settings_display[display_style][' . $display . ']" value="' . $display . '" ' . checked( true, Post_Views_Counter()->get_attribute( 'options', 'display', 'display_style', $display ), false ) . ' /><label for="pvc-display-style-' . $display . '">' . esc_html( $style ) . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . esc_html__( 'Choose how to display the post views counter.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Counter icon class option.
	 */
	public function icon_class() {
		echo '
		<div id="pvc_icon_class">
			<fieldset>
				<input type="text" name="post_views_counter_settings_display[icon_class]" class="large-text" value="' . esc_attr( Post_Views_Counter()->get_attribute( 'options', 'display', 'icon_class' ) ) . '"/>
				<br/>
				<span class="description">' . sprintf( __( 'Enter the post views icon class. Any of the <a href="%s" target="_blank">Dashicons</a> classes are available.', 'post-views-counter' ), 'http://melchoyce.github.io/dashicons/' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Restrict display option.
	 */
	public function restrict_display() {
		echo '
		<div id="pvc_restrict_display">
			<fieldset>
				<select class="pvc-chosen" data-placeholder="' . esc_attr__( 'Select groups', 'post-views-counter' ) . '" name="post_views_counter_settings_display[restrict_display][groups][]" multiple="multiple">';

		foreach ( $this->groups as $type => $type_name ) {

			if ( $type === 'robots' )
				continue;

			echo '
					<option value="' . esc_attr( $type ) . '" ' . selected( in_array( $type, Post_Views_Counter()->get_attribute( 'options', 'display', 'restrict_display', 'groups' ), true ), true, false ) . '>' . esc_html( $type_name ) . '</option>';
		}

		echo '
				</select>
				<br/>
				<div class="pvc_user_roles"' . (in_array( 'roles', Post_Views_Counter()->get_attribute( 'options', 'display', 'restrict_display', 'groups' ), true ) ? '' : ' style="display: none;"') . '>
					<select class="pvc-chosen" data-placeholder="' . esc_attr__( 'Select user roles', 'post-views-counter' ) . '" name="post_views_counter_settings_display[restrict_display][roles][]" multiple="multiple">';

		foreach ( $this->user_roles as $role => $role_name ) {
			echo '
						<option value="' . esc_attr( $role ) . '" ' . selected( in_array( $role, Post_Views_Counter()->get_attribute( 'options', 'display', 'restrict_display', 'roles' ), true ), true, false ) . '>' . esc_html( $role_name ) . '</option>';
		}

		echo '
					</select>
					<br/>
				</div>
				<span class="description">' . esc_html__( 'Use it to hide the post views counter from selected type of visitors.', 'post-views-counter' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Validate general settings.
	 */
	public function validate_settings( $input ) {
		if ( isset( $_POST['post_views_counter_import_wp_postviews'] ) ) {
			global $wpdb;

			$views = $wpdb->get_results(
				"SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = 'views'", ARRAY_A, 0
			);

			if ( ! empty( $views ) ) {
				$input = Post_Views_Counter()->get_attribute( 'defaults', 'general' );
				$input['wp_postviews_import'] = true;

				$sql = '';

				foreach ( $views as $view ) {
					$sql[] = "(" . $view['post_id'] . ", 4, 'total', " . $view['meta_value'] . ")";
				}

				$wpdb->query( "INSERT INTO " . $wpdb->prefix . "post_views(id, type, period, count) VALUES " . implode( ',', $sql ) . " ON DUPLICATE KEY UPDATE count = " . (isset( $_POST['post_views_counter_import_wp_postviews_override'] ) ? '' : 'count + ') . "VALUES(count)" );

				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'WP-PostViews data imported succesfully.', 'post-views-counter' ), 'updated' );
			} else {
				add_settings_error( 'wp_postviews_import', 'wp_postviews_import', __( 'There was no data to import.', 'post-views-counter' ), 'updated' );
			}
		} elseif ( isset( $_POST['save_pvc_general'] ) ) {
			// post types count
			if ( isset( $input['post_types_count'] ) ) {
				$post_types = array();

				foreach ( $input['post_types_count'] as $post_type ) {
					if ( isset( $this->post_types[$post_type] ) )
						$post_types[] = $post_type;
				}

				$input['post_types_count'] = array_unique( $post_types );
			} else
				$input['post_types_count'] = array();

			// counter mode
			$input['counter_mode'] = (isset( $input['counter_mode'], $this->modes[$input['counter_mode']] ) ? $input['counter_mode'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'counter_mode' ));

			// post views column
			$input['post_views_column'] = (isset( $input['post_views_column'] ) ? (bool) $input['post_views_column'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'post_views_column' ));

			// time between counts
			$input['time_between_counts']['number'] = (int) (isset( $input['time_between_counts']['number'] ) ? $input['time_between_counts']['number'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'time_between_counts', 'number' ));
			$input['time_between_counts']['type'] = (isset( $input['time_between_counts']['type'], $this->time_types[$input['time_between_counts']['type']] ) ? $input['time_between_counts']['type'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'time_between_counts', 'type' ));

			// flush interval
			$input['flush_interval']['number'] = (int) (isset( $input['flush_interval']['number'] ) ? $input['flush_interval']['number'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'flush_interval', 'number' ));
			$input['flush_interval']['type'] = (isset( $input['flush_interval']['type'], $this->time_types[$input['flush_interval']['type']] ) ? $input['flush_interval']['type'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'flush_interval', 'type' ));

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
			$input['reset_counts']['number'] = (int) (isset( $input['reset_counts']['number'] ) ? $input['reset_counts']['number'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'reset_counts', 'number' ));
			$input['reset_counts']['type'] = (isset( $input['reset_counts']['type'], $this->time_types[$input['reset_counts']['type']] ) ? $input['reset_counts']['type'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'reset_counts', 'type' ));

			// run cron on next visit?
			$input['cron_run'] = ($input['reset_counts']['number'] > 0 ? true : false);
			$input['cron_update'] = ($input['cron_run'] && (Post_Views_Counter()->get_attribute( 'options', 'general', 'reset_counts', 'number' ) !== $input['reset_counts']['number'] || Post_Views_Counter()->get_attribute( 'options', 'general', 'reset_counts', 'type' ) !== $input['reset_counts']['type']) ? true : false);

			// exclude
			if ( isset( $input['exclude']['groups'] ) ) {
				$groups = array();

				foreach ( $input['exclude']['groups'] as $group ) {
					if ( isset( $this->groups[$group] ) )
						$groups[] = $group;
				}

				$input['exclude']['groups'] = array_unique( $groups );
			} else
				$input['exclude']['groups'] = array();

			if ( in_array( 'roles', $input['exclude']['groups'], true ) && isset( $input['exclude']['roles'] ) ) {
				$roles = array();

				foreach ( $input['exclude']['roles'] as $role ) {
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
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) )
						$ips[] = $ip;
				}

				$input['exclude_ips'] = array_unique( $ips );
			}
			
			// restrict edit viewa
			$input['restrict_edit_views'] = (isset( $input['restrict_edit_views'] ) ? (bool) $input['restrict_edit_views'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'restrict_edit_views' ));

			// deactivation delete
			$input['deactivation_delete'] = (isset( $input['deactivation_delete'] ) ? (bool) $input['deactivation_delete'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'deactivation_delete' ));
		} elseif ( isset( $_POST['save_pvc_display'] ) ) {

			// post views label
			$input['label'] = (isset( $input['label'] ) ? $input['label'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'label' ));

			if ( function_exists( 'icl_register_string' ) )
				icl_register_string( 'Post Views Counter', 'Post Views Label', $input['label'] );

			// position
			$input['position'] = (isset( $input['position'], $this->positions[$input['position']] ) ? $input['position'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'position' ));

			// display style
			$input['display_style']['icon'] = (isset( $input['display_style']['icon'] ) ? true : false);
			$input['display_style']['text'] = (isset( $input['display_style']['text'] ) ? true : false);

			// link to post
			$input['link_to_post'] = (isset( $input['link_to_post'] ) ? (bool) $input['link_to_post'] : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'link_to_post' ));

			// icon class
			$input['icon_class'] = (isset( $input['icon_class'] ) ? trim( $input['icon_class'] ) : Post_Views_Counter()->get_attribute( 'defaults', 'general', 'icon_class' ));

			// post types display
			if ( isset( $input['post_types_display'] ) ) {
				$post_types = array();

				foreach ( $input['post_types_display'] as $post_type ) {
					if ( isset( $this->post_types[$post_type] ) )
						$post_types[] = $post_type;
				}

				$input['post_types_display'] = array_unique( $post_types );
			} else
				$input['post_types_display'] = array();

			// restrict display
			if ( isset( $input['restrict_display']['groups'] ) ) {
				$groups = array();

				foreach ( $input['restrict_display']['groups'] as $group ) {
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

				foreach ( $input['restrict_display']['roles'] as $role ) {
					if ( isset( $this->user_roles[$role] ) )
						$roles[] = $role;
				}

				$input['restrict_display']['roles'] = array_unique( $roles );
			} else
				$input['restrict_display']['roles'] = array();
		} elseif ( isset( $_POST['reset_pvc_general'] ) ) {
			$input = Post_Views_Counter()->get_attribute( 'defaults', 'general' );

			add_settings_error( 'reset_general_settings', 'settings_reset', __( 'General settings restored to defaults.', 'post-views-counter' ), 'updated' );
		} elseif ( isset( $_POST['reset_pvc_display'] ) ) {
			$input = Post_Views_Counter()->get_attribute( 'defaults', 'display' );

			add_settings_error( 'reset_general_settings', 'settings_reset', __( 'Display settings restored to defaults.', 'post-views-counter' ), 'updated' );
		}

		return $input;
	}

}

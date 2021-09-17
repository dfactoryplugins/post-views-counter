<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Pro_Settings_API class.
 * 
 * @class Post_Views_Counter_Pro_Settings_API
 */
class Post_Views_Counter_Pro_Settings_API {

	private $settings;
	private $pages;
	private $page_types;
	private $prefix;
	private $domain;
	private $object;
	private $plugin;
	private $plugin_url;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct( $args ) {
		// set initial data
		$this->prefix = $args['prefix'];
		$this->domain = $args['domain'];
		$this->object = $args['object'];
		$this->plugin = $args['plugin'];
		$this->plugin_url = $args['plugin_url'];

		// actions
		add_action( 'admin_menu', [ $this, 'admin_menu_options' ], 11 );
		add_action( 'admin_init', [ $this, 'register_settings' ], 11 );
	}

	/**
	 * Get prefix.
	 *
	 * @return string
	 */
	public function get_prefix() {
		return $this->prefix;
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Add menu pages.
	 *
	 * @return void
	 */
	public function admin_menu_options() {
		$this->pages = apply_filters( $this->prefix . '_settings_pages', [] );
		$types = [
			'subpage'	=> [],
			'settings'	=> []
		];

		foreach ( $this->pages as $page => $data ) {
			// get page type
			$type = empty( $data['type'] ) ? 'subpage' : $data['type'];

			if ( $type === 'subpage' ) {
				add_submenu_page( $data['parent_slug'], $data['page_title'], $data['menu_title'], $data['capability'], $data['menu_slug'], ! empty( $data['callback'] ) ? $data['callback'] : [ $this, 'options_page' ] );

				// add subpage type
				$types['subpage'][] = $page;
			} elseif ( $type === 'settings' ) {
				add_options_page( $data['page_title'], $data['menu_title'], $data['capability'], $data['menu_slug'], ! empty( $data['callback'] ) ? $data['callback'] : [ $this, 'options_page' ] );

				// add settings type
				$types['settings'][] = $data['menu_slug'];
			}
		}

		// set page types
		$this->page_types = $types;
	}

	/**
	 * Render settings.
	 *
	 * @return void
	 */
	public function options_page() {
		global $pagenow;

		// get current screen
		$screen = get_current_screen();

		// display top level settings page?
		if ( ! ( $pagenow === 'admin.php' && preg_match( '/^(?:toplevel|' . $this->prefix . ')_page_' . $this->prefix . '-(' . implode( '|', $this->page_types['subpage'] ) . ')-settings$/', $screen->base, $matches ) === 1 && ! empty( $matches[1] ) ) )
			return;

		// display settings page?
		if ( ! ( $pagenow === 'options-general.php' && preg_match( '/^(?:settings_page_)-(' . implode( '|', $this->page_types['settings'] ) . ')$/', $screen->base, $matches ) === 1 ) )
			return;

		echo '
			<div class="wrap">
				<h2>' . $this->settings[$matches[1]]['label'] . '</h2>';

		if ( 
				<h2 class="nav-tab-wrapper">';

		foreach ( $this->tabs as $key => $name ) {
			echo '
		    <a class="nav-tab ' . ($tab_key == $key ? 'nav-tab-active' : '') . '" href="' . esc_url( admin_url( 'options-general.php?page=post-views-counter&tab=' . $key ) ) . '">' . $name['name'] . '</a>';
		}

		echo '
		</h2>

			settings_errors();

			echo '
				<div class="' . $this->prefix . '-settings">
					<div class="df-credits">
						<h3 class="hndle">' . $this->plugin . ' ' . $this->object->defaults['version'] . '</h3>
						<div class="inside">
							<h4 class="inner">' . __( 'Need support?', $this->domain ) . '</h4>
							<p class="inner">' . sprintf( __( 'If you are having problems with this plugin, please browse it\'s <a href="%s" target="_blank">Documentation</a> or talk about them in the <a href="%s" target="_blank">Support forum</a>', $this->domain ), 'https://www.dfactory.eu/docs/' . $this->prefix . '/?utm_source=' . $this->prefix . '-settings&utm_medium=link&utm_campaign=docs', 'https://www.dfactory.eu/support/?utm_source=' . $this->prefix . '-settings&utm_medium=link&utm_campaign=support' ) . '</p>
							<hr />
							<h4 class="inner">' . __( 'Do you like this plugin?', $this->domain ) . '</h4>
							<p class="inner">' . sprintf( __( '<a href="%s" target="_blank">Rate it 5</a> on WordPress.org', $this->domain ), 'https://wordpress.org/support/plugin/' . $this->prefix . '/reviews/?filter=5' ) . '<br />' .
							sprintf( __( 'Blog about it & link to the <a href="%s" target="_blank">plugin page</a>.', $this->domain ), 'https://dfactory.eu/plugins/' . $this->prefix . '/?utm_source=' . $this->prefix . '-settings&utm_medium=link&utm_campaign=blog-about' ) . '<br />' .
							sprintf( __( 'Check out our other <a href="%s" target="_blank">WordPress plugins</a>.', $this->domain ), 'https://dfactory.eu/plugins/?utm_source=' . $this->prefix . '-settings&utm_medium=link&utm_campaign=other-plugins' ) . '
							</p>
							<hr />
							<p class="df-link inner">' . __( 'Created by', $this->domain ) . ' <a href="http://www.dfactory.eu/?utm_source=' . $this->prefix . '-settings&utm_medium=link&utm_campaign=created-by" target="_blank" title="dFactory - Quality plugins for WordPress"><img src="' . constant( $this->plugin_url ) . '/images/logo-dfactory.png' . '" title="dFactory - Quality plugins for WordPress" alt="dFactory - Quality plugins for WordPress" /></a></p>
						</div>
					</div>
					<form action="options.php" method="post">';

			settings_fields( $this->prefix . '_' . $matches[1] . '_settings' );
			do_settings_sections( $this->prefix . '_' . $matches[1] . '_settings' );

			echo '
						<p class="submit">';

			submit_button( '', 'primary', 'save_' . $this->prefix . '_' . $matches[1] . '_settings', false );

			echo ' ';

			submit_button( __( 'Reset to defaults', $this->domain ), 'secondary reset_' . $this->prefix . '_' . $matches[1] . '_settings', 'reset_' . $this->prefix . '_' . $matches[1] . '_settings', false );

			echo '
						</p>
					</form>
				</div>
				<div class="clear"></div>
			</div>';
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		$this->settings = apply_filters( $this->prefix . '_settings_data', [] );

		// check settings
		foreach ( $this->settings as $setting_id => $setting ) {
			// register setting
			register_setting( $setting['option_name'], $setting['option_name'], ! empty( $setting['validate'] ) ? $setting['validate'] : [ $this, 'validate_settings' ] );

			// register setting sections
			if ( ! empty( $setting['sections'] ) ) {
				foreach ( $setting['sections'] as $section_id => $section ) {
					add_settings_section(
						$section_id,
						! empty( $section['title'] ) ? esc_html( $section['title'] ) : '',
						! empty( $section['callback'] ) ? $section['callback'] : null,
						! empty( $section['page'] ) ? $section['page'] : $setting['option_name']
					);
				}
			}

			// register setting fields
			if ( ! empty( $setting['fields'] ) ) {
				foreach ( $setting['fields'] as $field_key => $field ) {
					// set field ID
					$field_id = implode( '_', [ $this->prefix, $setting_id, $field_key ] );

					// skip rendering this field?
					if ( ! empty( $field['skip_rendering'] ) )
						continue;

					add_settings_field(
						$field_id,
						! empty( $field['title'] ) ? esc_html( $field['title'] ) : '',
						[ $this, 'render_field' ],
						$setting['option_name'],
						! empty( $field['section'] ) ? esc_attr( $field['section'] ) : '',
						array_merge( $this->prepare_field_args( $field, $field_id, $field_key, $setting_id, $setting['option_name'] ), $field )
					);
				}
			}
		}
	}

	/**
	 * Prepare field arguments.
	 *
	 * @param array $args
	 * @return array
	 */
	public function prepare_field_args( $field, $field_id, $field_key, $setting_id, $setting_name ) {
		// get field type
		$field_type = ! empty( $field['type'] ) ? $field['type'] : '';

		return [
			'id'			=> $field_id,
			'name'			=> $setting_name . '[' . $field_key . ']',
			'class'			=> ! empty( $field['class'] ) ? $field['class'] : '',
			'type'			=> $field_type,
			'label'			=> ! empty( $field['label'] ) ? $field['label'] : '',
			'description'	=> ! empty( $field['description'] ) ? $field['description'] : '',
			'text'			=> ! empty( $field['text'] ) ? $field['text'] : '',
			'min'			=> ! empty( $field['min'] ) ? (int) $field['min'] : 0,
			'max'			=> ! empty( $field['max'] ) ? (int) $field['max'] : 0,
			'options'		=> ! empty( $field['options'] ) ? $field['options'] : [],
			'callback'		=> ! empty( $field['callback'] ) ? $field['callback'] : null,
			'sanitize'		=> ! empty( $field['sanitize'] ) ? $field['sanitize'] : null,
			'callback_args'	=> ! empty( $field['callback_args'] ) ? $field['callback_args'] : [],
			'default'		=> $field_type !== 'custom' ? $this->object->defaults[$setting_id][$field_key] : null,
			'value'			=> $field_type !== 'custom' ? $this->object->options[$setting_id][$field_key] : null
			/*
			after_field
			before_field
			*/
		];
	}

	/**
	 * Render settings field.
	 *
	 * @param array $args
	 * @return void|string
	 */
	public function render_field( $args ) {
		if ( empty( $args ) || ! is_array( $args ) )
			return;

		$html = '<div id="' . $args['id'] . '_setting"' . ( ! empty( $args['class'] ) ? ' class="' . $args['class'] . '"' : '' ) . '>';

		if ( ! empty ( $args['before_field'] ) )
			$html .= $args['before_field'];

		switch ( $args['type'] ) {
			case 'boolean':
				$html .= '<input type="hidden" name="' . $args['name'] . '" value="false" />';
				$html .= '<label><input id="' . $args['id'] . '" type="checkbox" name="' . $args['name'] . '" value="true" ' . checked( (bool) $args['value'], true, false ) . ' ' . disabled( empty( $args['disabled'] ), false, false ) . ' />' . $args['label'] . '</label>';
				break;

			case 'radio':
				foreach ( $args['options'] as $key => $name ) {
					$html .= '<label><input id="' . $args['id'] . '_' . $key . '" type="radio" name="' . $args['name'] . '" value="' . $key . '" ' . checked( $key, $args['value'], false ) . ' ' . disabled( empty( $args['disabled'] ), false, false ) . ' />' . $name . '</label> ';
				}
				break;

			case 'checkbox':
				$display_type = ! empty( $args['display_type'] ) && in_array( $args['display_type'], [ 'horizontal', 'vertical' ], true ) ? $args['display_type'] : 'horizontal';

				$html .= '<input type="hidden" name="' . $args['name'] . '" value="empty" />';

				foreach ( $args['options'] as $key => $name ) {
					$html .= '<label><input id="' . $args['id'] . '_' . $key . '" type="checkbox" name="' . $args['name'] . '[]" value="' . $key . '" ' . checked( in_array( $key, $args['value'] ), true, false ) . ' ' . disabled( empty( $args['disabled'] ), false, false ) . ' />' . $name . '</label>' . ( $display_type === 'horizontal' ? ' ' : '<br />' );
				}
				break;

			case 'select':
				$html .= '<select id="' . $args['id'] . '" name="' . $args['name'] . '" value="' . $args['value'] . '" />';

				foreach ( $args['options'] as $key => $name ) {
					$html .= '<option value="' . $key . '" ' . selected( $args['value'], $key, false ) . '>' . $name . '</option>';
				}

				$html .= '</select>';
				break;

			case 'range':
				$html .= '<input id="' . $args['id'] . '_slider" type="range" name="' . $args['name'] . '" value="' . $args['value'] . '" min="' . $args['min'] . '" max="' . $args['max'] . '" oninput="this.form.' . $args['id'] . '_range.value = this.value" /><output class="' . $this->prefix . '-range" name="' . $args['id'] . '_range">' . (int) $args['value'] . '</output>';
				break;

			case 'number':
				$html .= ( ! empty( $args['prepend'] ) ? '<span>' . $args['prepend'] . '</span> ' : '' );
				$html .= '<input id="' . $args['id'] . '" type="text" value="' . $args['value'] . '" name="' . $args['name'] . '" />';
				$html .= ( ! empty( $args['append'] ) ? ' <span>' . $args['append'] . '</span>' : '' );
				break;

			case 'custom':
				$html .= call_user_func( $args['callback'], $args );
				break;

			case 'info':
				$html .= '<span class="' . $args['class'] . '">' . $args['text'] . '</span>';
				break;

			case 'input':
			default:
				$html .= ( ! empty( $args['prepend'] ) ? '<span>' . $args['prepend'] . '</span> ' : '' );
				$html .= '<input id="' . $args['id'] . '" class="' . $args['class'] . '" type="text" value="' . $args['value'] . '" name="' . $args['name'] . '" />';
				$html .= ( ! empty( $args['append'] ) ? ' <span>' . $args['append'] . '</span>' : '' );
		}

		if ( ! empty ( $args['after_field'] ) )
			$html .= $args['after_field'];

		if ( ! empty ( $args['description'] ) )
			$html .= '<p class="description">' . $args['description'] . '</p>';

		$html .= '</div>';

		if ( ! empty( $args['return'] ) )
			return $html;
		else
			echo $html;
	}

	/**
	 * Sanitize settings field.
	 *
	 * @param mixed $value
	 * @param string $type
	 * @param array $args
	 * @return mixed
	 */
	public function sanitize_field( $value = null, $type = '', $args = [] ) {
		if ( is_null( $value ) )
			return null;

		switch ( $type ) {
			case 'boolean':
				// possible value: 'true' or 'false'
				$value = ( $value === 'true' || $value === true );
				break;

			case 'radio':
				$value = is_array( $value ) ? false : sanitize_text_field( $value );
				break;

			case 'checkbox':
				// possible value: 'empty' or array
				$value = is_array( $value ) && ! empty( $value ) ? array_map( 'sanitize_text_field', $value ) : [];
				break;

			case 'number':
				$value = (int) $value;

				// is value lower than?
				if ( isset( $args['min'] ) && $value < $args['min'] )
					$value = $args['min'];

				// is value greater than?
				if ( isset( $args['max'] ) && $value > $args['max'] )
					$value = $args['max'];
				break;

			case 'info':
				$value = '';
				break;

			case 'custom':
				// do nothing
				break;

			case 'input':
			case 'select':
			default:
				$value = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_text_field( $value );
				break;
		}

		return stripslashes_deep( $value );
	}

	/**
	 * Validate settings.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate_settings( $input ) {
		// check capability
//@TODO dodac tu capability
		// if ( ! current_user_can( apply_filters( 'rl_lightbox_settings_capability', 'edit_lightbox_settings' ) ) )
			// return $input;

		// check capability
		if ( ! current_user_can( 'manage_options' ) )
			return $input;

		// check option page
		if ( empty( $_POST['option_page'] ) )
			return $input;

		// try to get setting name and ID
		foreach ( $this->settings as $id => $setting ) {
			// found valid setting?
			if ( $setting['option_name'] === $_POST['option_page'] ) {
				// assign setting ID
				$setting_id = $id;

				// assign setting name
				$setting_name = $setting['option_name'];

				// already found setting, no need to check the rest
				break;
			}
		}

		// check setting id, no need to check $setting_name since it was at the same stage
		if ( empty( $setting_id ) )
			return $input;

		// save settings
		if ( isset( $_POST['save_' . $setting_name] ) ) {
			$input = $this->validate_input_settings( $setting_id, $input );

			add_settings_error( $setting_name, 'settings_saved', __( 'Settings saved.', $this->domain ), 'updated' );
		// reset settings
		} elseif ( isset( $_POST['reset_' . $setting_name] ) ) {
			// get default values
			$input = $this->object->defaults[$setting_id];

			// check custom reset functions
			if ( ! empty( $this->settings[$setting_id]['fields'] ) ) {
				foreach ( $this->settings[$setting_id]['fields'] as $field_id => $field ) {
					if ( ! empty( $field['reset'] ) ) {
						// valid function? no need to check "else" since all default values are set
						if ( $this->callback_function_exists( $field['reset'] ) ) {
							if ( $field['type'] === 'custom' )
								$input = call_user_func( $field['reset'], $input, $field );
							else
								$input[$field_id] = call_user_func( $field['reset'], $input[$field_id], $field );
						}
					}
				}
			}

			add_settings_error( $setting_name, 'settings_restored', __( 'Settings restored to defaults.', $this->domain ), 'updated' );
		}

		return $input;
	}

	/**
	 * Validate input settings.
	 *
	 * @param string $setting_id
	 * @param array $input
	 * @return array
	 */
	public function validate_input_settings( $setting_id, $input ) {
		if ( ! empty( $this->settings[$setting_id]['fields'] ) ) {
			foreach ( $this->settings[$setting_id]['fields'] as $field_id => $field ) {
				// skip saving this field?
				if ( ! empty( $field['skip_saving'] ) )
					continue;

				// custom sanitize function?
				if ( ! empty( $field['sanitize'] ) ) {
					// valid function?
					if ( $this->callback_function_exists( $field['sanitize'] ) ) {
						if ( $field['type'] === 'custom' )
							$input = call_user_func( $field['sanitize'], $input, $field );
						else
							$input[$field_id] = isset( $input[$field_id] ) ? call_user_func( $field['sanitize'], $input[$field_id], $field ) : $this->object->defaults[$setting_id][$field_id];
					} else
						$input[$field_id] = $this->object->defaults[$setting_id][$field_id];
				} else {
					// field data?
					if ( isset( $input[$field_id] ) )
						$input[$field_id] = $this->sanitize_field( $input[$field_id], $field['type'] );
					else
						$input[$field_id] = $this->object->defaults[$setting_id][$field_id];
				}
			}
		}

		return $input;
	}

	/**
	 * Check whether callback is a valid function.
	 *
	 * @param string|array $callback
	 * @return bool
	 */
	public function callback_function_exists( $callback ) {
		// function as array?
		if ( is_array( $callback ) ) {
			list( $object, $function ) = $callback;

			// check function existence
			$function_exists = method_exists( $object, $function );
		// function as string?
		} elseif ( is_string( $callback ) ) {
			// check function existence
			$function_exists = function_exists( $callback );
		} else
			$function_exists = false;

		return $function_exists;
	}

	/**
	 * Get value based on minimum and maximum.
	 *
	 * @param array $data
	 * @param string $setting_name
	 * @param int $default
	 * @param int $min
	 * @param int $max
	 * @return void
	 */
	public function get_int_value( $data, $setting_name, $default, $min, $max ) {
		// check existence of value
		$value = array_key_exists( $setting_name, $data ) ? (int) $data[$setting_name] : $default;

		if ( $value > $max || $value < $min )
			$value = $default;

		return $value;
	}
}
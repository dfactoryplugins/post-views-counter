<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Settings_API class.
 *
 * @class Post_Views_Counter_Settings_API
 */
class Post_Views_Counter_Settings_API {

	private $settings = [];
	private $input_settings = [];
	private $validated_settings = [];
	private $pages = [];
	private $page_types = [];
	private $prefix = '';
	private $slug = '';
	private $domain = '';
	private $short = '';
	private $plugin = '';
	private $plugin_url = '';
	private $object;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct( $args ) {
		// set initial data
		$this->prefix = $args['prefix'];
		$this->domain = $args['domain'];

		// empty slug?
		if ( empty( $args['slug'] ) )
			$this->slug = $args['domain'];
		else
			$this->slug = $args['slug'];

		// empty short name?
		if ( empty( $args['short'] ) ) {
			$short = '';

			// prepare short name based on prefix
			$parts = explode( '_', $this->prefix );

			foreach ( $parts as $string ) {
				$short .= substr( $string, 0, 1 );
			}

			// set short name
			$this->short = $short;
		} else
			$this->short = $args['short'];

		$this->object = $args['object'];
		$this->plugin = $args['plugin'];
		$this->plugin_url = $args['plugin_url'];

		// actions
		add_action( 'admin_menu', [ $this, 'admin_menu_options' ], 11 );
		add_action( 'admin_init', [ $this, 'register_settings' ], 11 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
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
	 * Get pages.
	 *
	 * @return array
	 */
	public function get_pages() {
		return $this->pages;
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
	 * Get current input settings during saving.
	 *
	 * @return array
	 */
	public function get_input_settings() {
		return $this->input_settings;
	}

	/**
	 * Get already validated setting fields during saving.
	 *
	 * @return array
	 */
	public function get_validated_settings() {
		return $this->validated_settings;
	}

	/**
	 * Load default scripts and styles.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$handler = $this->short . '-settings-api-style';

		// register and enqueue styles
		wp_register_style( $handler, false );
		wp_enqueue_style( $handler );

		// add styles
		wp_add_inline_style( $handler, '.nav-tab-wrapper span.nav-span-disabled {
			cursor: not-allowed;
			float: left;
		}
		body.rtl .nav-tab-wrapper span.nav-span-disabled {
			float: right;
		}
		.nav-tab-wrapper a.nav-tab.nav-tab-disabled {
			pointer-events: none;
		}
		.nav-tab-wrapper a.nav-tab.nav-tab-disabled:hover {
			cursor: not-allowed;
		}' );
	}

	/**
	 * Add menu pages.
	 *
	 * @return void
	 */
	public function admin_menu_options() {
		$this->pages = apply_filters( $this->prefix . '_settings_pages', [] );
		$types = [
			'page'			=> [],
			'subpage'		=> [],
			'settings_page'	=> []
		];

		foreach ( $this->pages as $page => $data ) {
			// skip invalid page types
			if ( empty( $data['type'] ) || ! array_key_exists( $data['type'], $types ) )
				continue;

			if ( $data['type'] === 'page' ) {
				add_menu_page( $data['page_title'], $data['menu_title'], $data['capability'], $data['menu_slug'], ! empty( $data['callback'] ) ? $data['callback'] : [ $this, 'options_page' ], $data['icon'], $data['position'] );

				// add page type
				$types['page'][$data['menu_slug']] = $page;
			// menu subpage?
			} elseif ( $data['type'] === 'subpage' ) {
				add_submenu_page( $data['parent_slug'], $data['page_title'], $data['menu_title'], $data['capability'], $data['menu_slug'], ! empty( $data['callback'] ) ? $data['callback'] : [ $this, 'options_page' ] );

				// add subpage type
				$types['subpage'][$data['menu_slug']] = $page;
			// menu settings page?
			} elseif ( $data['type'] === 'settings_page' ) {
				add_options_page( $data['page_title'], $data['menu_title'], $data['capability'], $data['menu_slug'], ! empty( $data['callback'] ) ? $data['callback'] : [ $this, 'options_page' ] );

				// add settings type
				$types['settings_page'][$data['menu_slug']] = $page;
			}
		}

		// set page types
		$this->page_types = $types;
	}

	/**
	 * Render settings.
	 *
	 * @global string $pagenow
	 *
	 * @return void
	 */
	public function options_page() {
		global $pagenow;

		$valid_page = false;

		// get current screen
		$screen = get_current_screen();

		// display top level settings page?
		if ( $pagenow === 'admin.php' && preg_match( '/^toplevel_page_(' . implode( '|', $this->page_types['page'] ) . ')$/', $screen->base, $matches ) === 1 && ! empty( $matches[1] ) ) {
			$valid_page = true;
			$page_type = 'page';
			$url_page = 'admin.php';
		}

		// display sub level settings page?
		if ( ! $valid_page && $pagenow === 'admin.php' && preg_match( '/^(?:toplevel|' . $this->prefix . ')_page_' . $this->prefix . '-(' . implode( '|', $this->page_types['subpage'] ) . ')-settings$/', $screen->base, $matches ) === 1 && ! empty( $matches[1] ) ) {
			$valid_page = true;
			$page_type = 'subpage';
			$url_page = 'admin.php';
		}

		// display settings page?
		if ( ! $valid_page && $pagenow === 'options-general.php' && preg_match( '/^(?:settings_page_)(' . implode( '|', array_keys( $this->page_types['settings_page'] ) ) . ')$/', $screen->base, $matches ) === 1 ) {
			$valid_page = true;
			$page_type = 'settings_page';
			$url_page = 'options-general.php';
		}

		// skip invalid pages
		if ( ! $valid_page )
			return;

		echo '
		<div class="wrap">
			<h2>' . esc_html( $this->settings[$matches[1]]['label'] ) . '</h2>';

		$tab_key = '';

		// any tabs?
		if ( array_key_exists( 'tabs', $this->pages[$this->page_types[$page_type][$matches[1]]] ) ) {
			// get tabs
			$tabs = $this->pages[$this->page_types[$page_type][$matches[1]]]['tabs'];

			// reset tabs
			reset( $tabs );

			// get first default tab
			$first_tab = key( $tabs );

			// get current tab
			$tab_key = ! empty( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? $_GET['tab'] : $first_tab;

			// check current tab
			if ( ! empty( $_GET['tab'] ) )
				$tab_key = sanitize_key( $_GET['tab'] );

			// invalid tab?
			if ( ! array_key_exists( $tab_key, $tabs ) )
				$tab_key = $first_tab;

			echo '
			<h2 class="nav-tab-wrapper">';

			foreach ( $tabs as $key => $tab ) {
				if ( ! empty( $tab['disabled'] ) )
					$url = '';
				else
					$url = admin_url( $url_page . '?page=' . $matches[1] . '&tab=' . $key );

				if ( ! empty( $tab['disabled'] ) )
					echo '<span class="nav-span-disabled">';

				echo '
				<a class="nav-tab' . ( $tab_key === $key ? ' nav-tab-active' : '' ) . ( ! empty( $tab['disabled'] ) ? ' nav-tab-disabled' : '' ) . ( ! empty( $tab['class'] ) ? ' ' . esc_attr( $tab['class'] ) : '' ) . '" href="' . ( $url !== '' ? esc_url( $url ) : '#' ) . '">' . esc_html( $tab['label'] ) . '</a>';

				if ( ! empty( $tab['disabled'] ) )
					echo '</span>';
			}

			echo '
			</h2>';
		}

		// skip for internal options page
		if ( $page_type !== 'settings_page' )
			settings_errors();

		// get settings page classes
		$settings_class = apply_filters( $this->prefix . '_settings_page_class', [ $this->slug . '-settings', $tab_key . '-settings' ] );

		// sanitize settings page classes
		$settings_class = array_unique( array_filter( array_map( 'sanitize_html_class', $settings_class ) ) );

		echo '
			<div class="' . implode( ' ', array_map( 'esc_attr', $settings_class ) ) . '">';

		$display_form = true;

		// check form attribute
		if ( ! empty( $this->settings[$matches[1]]['form'] ) && ! empty( $tab_key ) && ! empty( $this->settings[$matches[1]]['form'][$tab_key] ) ) {
			$form = $this->settings[$matches[1]]['form'][$tab_key];

			if ( isset( $form['buttons'] ) && ! $form['buttons'] )
				$display_form = false;
		}

		// any tabs?
		if ( ! empty( $tab_key ) ) {
			if ( ! empty( $tabs[$tab_key]['option_name'] ) )
				$setting = $tabs[$tab_key]['option_name'];
			else
				$setting = $this->prefix . '_' . $tab_key . '_settings';
		} else
			$setting = $this->prefix . '_' . $matches[1] . '_settings';

		do_action( $this->short . '_settings_sidebar', $setting, $page_type, $url_page, $tab_key );

		if ( $display_form ) {
			echo '
				<form action="options.php" method="post">';
		}

		settings_fields( $setting );

		if ( $display_form )
			do_action( $this->short . '_settings_form', $setting, $page_type, $url_page, $tab_key );

		do_settings_sections( $setting );

		if ( $display_form ) {
			echo '
					<p class="submit">';

			submit_button( '', 'primary', 'save_' . $setting, false );

			echo ' ';

			submit_button( __( 'Reset to defaults', $this->domain ), 'secondary reset_' . $setting . ' reset_' . $this->short . '_settings', 'reset_' . $setting, false );

			echo '
					</p>
				</form>';
		}

		echo '
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
			// tabs?
			if ( is_array( $setting['option_name'] ) ) {
				foreach ( $setting['option_name'] as $tab => $option_name ) {
					$this->register_setting_fields( $tab, $setting, $option_name );
				}
			} else
				$this->register_setting_fields( $setting_id, $setting );
		}
	}

	/**
	 * Register setting with sections and fields.
	 *
	 * @return void
	 */
	public function register_setting_fields( $setting_id, $setting, $option_name = '' ) {
		if ( empty( $option_name ) )
			$option_name = $setting['option_name'];

		// register setting
		register_setting( $option_name, $option_name, ! empty( $setting['validate'] ) ? $setting['validate'] : [ $this, 'validate_settings' ] );

		// register setting sections
		if ( ! empty( $setting['sections'] ) ) {
			foreach ( $setting['sections'] as $section_id => $section ) {
				// skip unwanted sections
				if ( ! empty( $section['tab'] ) && $section['tab'] !== $setting_id )
					continue;

				add_settings_section(
					$section_id,
					! empty( $section['title'] ) ? esc_html( $section['title'] ) : '',
					! empty( $section['callback'] ) ? $section['callback'] : null,
					! empty( $section['page'] ) ? $section['page'] : $option_name
				);
			}
		}

		// register setting fields
		if ( ! empty( $setting['fields'] ) ) {
			foreach ( $setting['fields'] as $field_key => $field ) {
				// skip unwanted fields
				if ( ! empty( $field['tab'] ) && $field['tab'] !== $setting_id )
					continue;

				// set field ID
				$field_id = implode( '_', [ $this->prefix, $setting_id, $field_key ] );

				// skip rendering this field?
				if ( ! empty( $field['skip_rendering'] ) )
					continue;

				add_settings_field(
					$field_id,
					! empty( $field['title'] ) ? esc_html( $field['title'] ) : '',
					[ $this, 'render_field' ],
					$option_name,
					! empty( $field['section'] ) ? esc_attr( $field['section'] ) : '',
					array_merge( $this->prepare_field_args( $field, $field_id, $field_key, $setting_id, $option_name ), $field )
				);
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
			'validate'		=> ! empty( $field['validate'] ) ? $field['validate'] : null,
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

		$html = '<div id="' . esc_attr( $args['id'] ) . '_setting"' . ( ! empty( $args['class'] ) ? ' class="' . esc_attr( $args['class'] ) . '"' : '' ) . '>';

		if ( ! empty ( $args['before_field'] ) )
			$html .= $args['before_field'];

		switch ( $args['type'] ) {
			case 'boolean':
				if ( empty( $args['disabled'] ) )
					$html .= '<input type="hidden" name="' . esc_attr( $args['name'] ) . '" value="false" />';

				$html .= '<label><input id="' . esc_attr( $args['id'] ) . '" type="checkbox" name="' . esc_attr( $args['name'] ) . '" value="true" ' . checked( (bool) $args['value'], true, false ) . ' ' . disabled( empty( $args['disabled'] ), false, false ) . ' />' . esc_html( $args['label'] ) . '</label>';
				break;

			case 'radio':
				foreach ( $args['options'] as $key => $name ) {
					$html .= '<label for="' . esc_attr( $args['id'] . '_' . $key ) . '"><input id="' . esc_attr( $args['id'] . '_' . $key ) . '" type="radio" name="' . esc_attr( $args['name'] ) . '" value="' . esc_attr( $key ) . '" ' . checked( $key, $args['value'], false ) . ' ' . disabled( ! empty( $args['disabled'] ) && in_array ( $key, $args['disabled'], true ), true, false ) . ' />' . esc_html( $name ) . '</label> ';
				}
				break;

			case 'checkbox':
				// possible "empty" value
				if ( $args['value'] === 'empty' )
					$args['value'] = [];

				$display_type = ! empty( $args['display_type'] ) && in_array( $args['display_type'], [ 'horizontal', 'vertical' ], true ) ? $args['display_type'] : 'horizontal';

				$html .= '<input type="hidden" name="' . esc_attr( $args['name'] ) . '" value="empty" />';

				foreach ( $args['options'] as $key => $name ) {
					$html .= '<label><input id="' . esc_attr( $args['id'] . '_' . $key ) . '" type="checkbox" name="' . esc_attr( $args['name'] ) . '[]" value="' . esc_attr( $key ) . '" ' . checked( in_array( $key, $args['value'] ), true, false ) . ' ' . disabled( ! empty( $args['disabled'] ) && in_array ( $key, $args['disabled'], true ), true, false ) . ' />' . esc_html( $name ) . '</label>' . ( $display_type === 'horizontal' ? ' ' : '<br />' );
				}
				break;

			case 'select':
				$html .= '<select id="' . esc_attr( $args['id'] ) . '" name="' . esc_attr( $args['name'] ) . '" ' . disabled( empty( $args['disabled'] ), false, false ) . '/>';

				foreach ( $args['options'] as $key => $name ) {
					$html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $args['value'], $key, false ) . '>' . esc_html( $name ) . '</option>';
				}

				$html .= '</select>';
				break;

			case 'range':
				$html .= '<input id="' . esc_attr( $args['id'] . '_slider' ) . '" type="range" name="' . esc_attr( $args['name'] ) . '" value="' . esc_attr( $args['value'] ) . '" min="' . esc_attr( $args['min'] ) . '" max="' . esc_attr( $args['max'] ) . '" oninput="this.form.' . esc_attr( $args['id'] ) . '_range.value = this.value" /><output class="' . esc_attr( $this->slug ) . '-range" name="' . esc_attr( $args['id'] ) . '_range">' . ( (int) $args['value'] ) . '</output>';
				break;

			case 'number':
				$html .= ( ! empty( $args['prepend'] ) ? wp_kses_post( $args['prepend'] ) : '' );
				$html .= '<input id="' . $args['id'] . '" type="text" value="' . esc_attr( $args['value'] ) . '" name="' . esc_attr( $args['name'] ) . '" />';
				$html .= ( ! empty( $args['append'] ) ? wp_kses_post( $args['append'] ) : '' );
				break;

			case 'custom':
				$html .= call_user_func( $args['callback'], $args );
				break;

			case 'info':
				$html .= '<span' . ( ! empty( $args['subclass'] ) ? ' class="' . esc_attr( $args['subclass'] ) . '"' : '' ) . '>' . esc_html( $args['text'] ) . '</span>';
				break;

			case 'class':
			case 'input':
			default:
				$empty_disabled = empty( $args['disabled'] );

				$html .= ( ! empty( $args['prepend'] ) ? wp_kses_post( $args['prepend'] ) : '' );
				$html .= '<input id="' . $args['id'] . '"' . ( ! empty( $args['subclass'] ) ? ' class="' . esc_attr( $args['subclass'] ) . '"' : '' ) . ' type="text" value="' . esc_attr( $args['value'] ) . '" name="' . esc_attr( $args['name'] ) . '" ' . disabled( $empty_disabled, false, false ) . '/>';
				$html .= ( ! empty( $args['append'] ) ? wp_kses_post( $args['append'] ) : '' );

				if ( ! $empty_disabled )
					$html .= '<input' . ( $empty_disabled ? '' : ' class="hidden"' ) . ' type="text" value="' . esc_attr( $args['value'] ) . '" name="' . esc_attr( $args['name'] ) . '">';
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
	 * Validate settings field.
	 *
	 * @param mixed $value
	 * @param string $type
	 * @param array $args
	 * @return mixed
	 */
	public function validate_field( $value = null, $type = '', $args = [] ) {
		if ( is_null( $value ) )
			return null;

		switch ( $type ) {
			case 'boolean':
				// possible value: 'true' or 'false'
				$value = ( $value === 'true' || $value === true );
				break;

			case 'radio':
				$value = is_array( $value ) ? $args['default'] : sanitize_key( $value );

				// disallow disabled radios
				if ( ! empty( $args['disabled'] ) && in_array( $value, $args['disabled'], true ) )
					$value = $args['default'];
				break;

			case 'checkbox':
				// possible value: 'empty' or array
				if ( $value === 'empty' )
					$value = [];
				else {
					if ( is_array( $value ) && ! empty( $value ) ) {
						$value = array_map( 'sanitize_key', $value );
						$values = [];

						foreach ( $value as $single_value ) {
							if ( array_key_exists( $single_value, $args['options'] ) )
								$values[] = $single_value;
						}

						$value = $values;
					} else
						$value = [];
				}
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

			case 'class':
				$value = trim( $value );

				// more than 1 class?
				if ( strpos( $value, ' ' ) !== false ) {
					// get unique valid HTML classes
					$value = array_unique( array_filter( array_map( 'sanitize_html_class', explode( ' ', $value ) ) ) );

					if ( ! empty( $value ) )
						$value = implode( ' ', $value );
					else
						$value = '';
				// single class
				} else
					$value = sanitize_html_class( $value, $args['default'] );
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
		if ( ! current_user_can( 'manage_options' ) )
			return $input;

		// check option page
		if ( empty( $_POST['option_page'] ) )
			return $input;

		// try to get setting name and ID
		foreach ( $this->settings as $id => $setting ) {
			// tabs?
			if ( is_array( $setting['option_name'] ) ) {
				foreach ( $setting['option_name'] as $tab => $option_name ) {
					// found valid setting?
					if ( $option_name === $_POST['option_page'] ) {
						// assign setting ID
						$setting_id = $tab;

						// assign setting name
						$setting_name = $option_name;

						// assign setting key
						$setting_key = $id;

						// already found setting, no need to check the rest
						break 2;
					}
				}
			} else {
				// found valid setting?
				if ( $setting['option_name'] === $_POST['option_page'] ) {
					// assign setting ID and key
					$setting_key = $setting_id = $id;

					// assign setting name
					$setting_name = $setting['option_name'];

					// already found setting, no need to check the rest
					break;
				}
			}
		}

		// check setting id, no need to check $setting_name since it was at the same stage
		if ( empty( $setting_id ) )
			return $input;

		// save settings
		if ( isset( $_POST['save_' . $setting_name] ) ) {
			$input = $this->validate_input_settings( $setting_id, $setting_key, $input );

			add_settings_error( $setting_name, 'settings_saved', __( 'Settings saved.', $this->domain ), 'updated' );
		// reset settings
		} elseif ( isset( $_POST['reset_' . $setting_name] ) ) {
			// get default values
			$input = $this->object->defaults[$setting_id];

			// check custom reset functions
			if ( ! empty( $this->settings[$setting_key]['fields'] ) ) {
				foreach ( $this->settings[$setting_key]['fields'] as $field_id => $field ) {
					// skip invalid tab field if any
					if ( ! empty( $field['tab'] ) && $field['tab'] !== $setting_id )
						continue;

					// custom reset function?
					if ( ! empty( $field['reset'] ) ) {
						// valid function? no need to check "else" since all default values are already set
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
	public function validate_input_settings( $setting_id, $setting_key, $input ) {
		if ( ! empty( $this->settings[$setting_key]['fields'] ) ) {
			foreach ( $this->settings[$setting_key]['fields'] as $field_id => $field ) {
				// skip saving this field?
				if ( ! empty( $field['skip_saving'] ) )
					continue;

				// skip invalid tab field if any
				if ( ! empty( $field['tab'] ) && $field['tab'] !== $setting_id )
					continue;

				// custom validate function?
				if ( ! empty( $field['validate'] ) ) {
					// valid function?
					if ( $this->callback_function_exists( $field['validate'] ) ) {
						if ( $field['type'] === 'custom' )
							$input = call_user_func( $field['validate'], $input, $field );
						else
							$input[$field_id] = isset( $input[$field_id] ) ? call_user_func( $field['validate'], $input[$field_id], $field ) : $this->object->defaults[$setting_id][$field_id];
					} else
						$input[$field_id] = $this->object->defaults[$setting_id][$field_id];
				} else {
					// field data?
					if ( isset( $input[$field_id] ) ) {
						// make sure default value is available
						if ( ! isset( $field['default'] ) )
							$field['default'] = $this->object->defaults[$setting_id][$field_id];

						$input[$field_id] = $this->validate_field( $input[$field_id], $field['type'], $field );
					} else
						$input[$field_id] = $this->object->defaults[$setting_id][$field_id];
				}

				// update input data
				$this->input_settings = $input;

				// add this field as validated
				$this->validated_settings[] = $field_id;
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

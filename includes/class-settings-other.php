<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Settings_Other class.
 *
 * @class Post_Views_Counter_Settings_Other
 */
class Post_Views_Counter_Settings_Other {

	private $pvc;
	private $settings;

	/**
	 * Class constructor.
	 *
	 * @param Post_Views_Counter_Settings $settings
	 * @return void
	 */
	public function __construct( $settings = null ) {
		$this->pvc = Post_Views_Counter();
		$this->settings = $settings;
	}

	/**
	 * Get sections for other tab.
	 *
	 * @return array
	 */
	public function get_sections() {
		return [
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
				'title'    => __( 'Data Removal', 'post-views-counter' ),
				'callback' => [ $this, 'section_other_management' ]
			]
		];
	}

	/**
	 * Get fields for other tab.
	 *
	 * @return array
	 */
	public function get_fields() {
		return [
			'license' => [
				'tab'			=> 'other',
				'title'			=> __( 'License Key', 'post-views-counter' ),
				'section'		=> 'post_views_counter_other_status',
				'disabled'		=> true,
				'value'			=> $this->pvc->options['other']['license'],
				'type'			=> 'input',
				'description'	=> sprintf( __( 'Enter your %s license key (requires Pro version to be installed and active).', 'post-views-counter' ), '<a href="https://postviewscounter.com/" target="_blank">Post Views Counter Pro</a>' ),
				'subclass'		=> 'regular-text',
				'validate'		=> [ $this, 'validate_license' ],
				'append'		=> '<span class="pvc-status-icon"></span>'
			],
			'import_from' => [
				'tab'			=> 'other',
				'title'			=> __( 'Import From', 'post-views-counter' ),
				'section'		=> 'post_views_counter_other_import',
				'type'			=> 'custom',
				'skip_saving'	=> true,
				'callback'		=> [ $this, 'setting_import_from' ]
			],
			'import_strategy' => [
				'tab'			=> 'other',
				'title'			=> __( 'Import Strategy', 'post-views-counter' ),
				'section'		=> 'post_views_counter_other_import',
				'class'			=> 'pvc-pro-extended',
				'type'			=> 'custom',
				'skip_saving'	=> true,
				'callback'		=> [ $this, 'setting_import_strategy' ]
			],
			'import_actions' => [
				'tab'			=> 'other',
				'title'			=> '',
				'section'		=> 'post_views_counter_other_import',
				'type'			=> 'custom',
				'description'	=> __( 'Click Analyse Views to check how many views are available for import, or Import Views to begin the import process.', 'post-views-counter' ),
				'skip_saving'	=> true,
				'callback'		=> [ $this, 'setting_import_actions' ]
			],
			'delete_views' => [
				'tab'			=> 'other',
				'title'			=> __( 'Delete Views', 'post-views-counter' ),
				'section'		=> 'post_views_counter_other_management',
				'type'			=> 'custom',
				'description'	=> __( 'Delete ALL the existing post views data. Note that this is an irreversible process!', 'post-views-counter' ),
				'skip_saving'	=> true,
				'callback'		=> [ $this, 'setting_delete_views' ]
			],
			'deactivation_delete' => [
				'tab'			=> 'other',
				'title'			=> __( 'Delete on Deactivation', 'post-views-counter' ),
				'section'		=> 'post_views_counter_other_management',
				'type'			=> 'boolean',
				'description'	=> __( 'When enabled, deactivating the plugin will delete all plugin data from the database, including all content view counts.', 'post-views-counter' ),
				'label'			=> __( 'Delete all plugin data on deactivation.', 'post-views-counter' )
			]
		];
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
	 * Setting: import from.
	 *
	 * @return string
	 */
	public function setting_import_from() {
		// get all providers (not just available ones)
		$all_providers = $this->pvc->import->get_all_providers();

		// get currently selected provider
		$selected_provider = isset( $this->pvc->options['other']['import_provider_settings']['provider'] ) ? $this->pvc->options['other']['import_provider_settings']['provider'] : 'custom_meta_key';

		// if selected provider is not available, fallback to custom_meta_key
		if ( isset( $all_providers[$selected_provider] ) ) {
			$is_selected_available = is_callable( $all_providers[$selected_provider]['is_available'] ) && call_user_func( $all_providers[$selected_provider]['is_available'] );
			if ( ! $is_selected_available ) {
				$selected_provider = 'custom_meta_key';
			}
		}

		$html = '<div class="pvc-import-provider-selection">';
		$html .= '<div class="pvc-field-group pvc-radio-group">';

		foreach ( $all_providers as $slug => $provider ) {
			$is_available = is_callable( $provider['is_available'] ) && call_user_func( $provider['is_available'] );
			$is_checked = ( $selected_provider === $slug );
			$disabled_attr = ! $is_available ? ' disabled="disabled"' : '';
			$disabled_class = ! $is_available ? ' class="pvc-provider-disabled"' : '';
			$tooltip = ! $is_available ? ' title="' . esc_attr( sprintf( __( '%s is not currently available. Please install and activate the required plugin.', 'post-views-counter' ), $provider['label'] ) ) . '"' : '';

			$html .= '
			<label' . $disabled_class . $tooltip . '>
				<input type="radio" name="pvc_import_provider" value="' . esc_attr( $slug ) . '" ' . checked( $is_checked, true, false ) . $disabled_attr . ' />
				' . esc_html( $provider['label'] ) . '
			</label>';
		}

		$html .= '</div>';
		$html .= '<p class="description">' . esc_html__( 'Choose a data source to import existing view counts from.', 'post-views-counter' ) . '</p>';

		$html .= '</div><div class="pvc-import-provider-fields">';

		foreach ( $all_providers as $slug => $provider ) {
			$is_available = is_callable( $provider['is_available'] ) && call_user_func( $provider['is_available'] );
			$is_active = ( $selected_provider === $slug );
			$provider_html = '';

			if ( $is_available && is_callable( $provider['render'] ) ) {
				$provider_html = call_user_func( $provider['render'] );
			} elseif ( ! $is_available ) {
				$provider_html = '<p class="description pvc-provider-unavailable">' . sprintf( __( '%s is not available. Please install and activate the required plugin to use this import source.', 'post-views-counter' ), '<strong>' . esc_html( $provider['label'] ) . '</strong>' ) . '</p>';
			}

			$html .= '
			<div class="pvc-provider-content pvc-provider-' . esc_attr( $slug ) . '" ' . ( ! $is_active ? 'style="display:none;"' : '' ) . '>
				' . $provider_html . '
			</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Setting: import strategy.
	 *
	 * @return string
	 */
	public function setting_import_strategy() {
		// get import strategy
		$import_strategy = isset( $this->pvc->options['other']['import_provider_settings']['strategy'] ) ? $this->pvc->import->normalize_strategy( $this->pvc->options['other']['import_provider_settings']['strategy'] ) : $this->pvc->import->get_default_strategy();
		$strategies = $this->pvc->import->get_import_strategies();

		$html = '<div class="pvc-field-group pvc-radio-group">';

		foreach ( $strategies as $slug => $strategy ) {
			$label = isset( $strategy['label'] ) ? $strategy['label'] : ucwords( str_replace( '_', ' ', $slug ) );
			$description = isset( $strategy['description'] ) ? $strategy['description'] : '';
			$is_enabled = $this->pvc->import->is_strategy_enabled( $slug );
			$input_id = 'pvc_import_strategy_' . $slug;

			if ( $slug === $import_strategy ) {
				$current_description = $description;
			}

			$html .= '<label for="' . esc_attr( $input_id ) . '" class="pvc-import-strategy-option" data-description="' . esc_attr( $description ) . '">
				<input type="radio" id="' . esc_attr( $input_id ) . '" name="pvc_import_strategy" value="' . esc_attr( $slug ) . '" ' . checked( $import_strategy, $slug, false ) . ' ' . disabled( ! $is_enabled, true, false ) . ' />
				' . esc_html( $label );

			// Future idea: display description text next to each strategy option.
			// if ( $description !== '' ) {
			// 	$html .= '<span class="description pvc-import-strategy-description">' . esc_html( $description ) . '</span>';
			// }

			$html .= '</label>';
		}

		$html .= '</div>';

		$html .= '</div>';

		$html .= '<p class="description">' . esc_html__( 'Choose how to handle existing view counts when importing.', 'post-views-counter' ) . '</p>';

		$html .= '<div class="pvc-provider-fields pvc-import-strategy-details">';

		foreach ( $strategies as $slug => $strategy ) {
			$description = isset( $strategy['description'] ) ? $strategy['description'] : '';
			$is_active = ( $slug === $import_strategy );

			$html .= '<div class="pvc-strategy-content pvc-strategy-' . esc_attr( $slug ) . '"' . ( $is_active ? '' : ' style="display:none;"' ) . '>
				<p class="description">' . esc_html( $description ) . '</p>
			</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Setting: import actions.
	 *
	 * @return string
	 */
	public function setting_import_actions() {
		$html = '<input type="submit" class="button button-secondary" name="post_views_counter_analyse_views" value="' . esc_attr__( 'Analyse Views', 'post-views-counter' ) . '" />
		<input type="submit" class="button button-secondary" name="post_views_counter_import_views" value="' . esc_attr__( 'Import Views', 'post-views-counter' ) . '" />';

		return $html;
	}

	/**
	 * Setting: delete views.
	 *
	 * @return string
	 */
	public function setting_delete_views() {
		$html = '
		<input type="submit" class="button button-secondary" name="post_views_counter_reset_views" value="' . esc_attr__( 'Delete Views', 'post-views-counter' ) . '" />';

		return $html;
	}

	/**
	 * Section description: other - status.
	 *
	 * @return void
	 */
	public function section_other_status() {
		echo '<p class="description">' . esc_html__( 'View license details and other status information.', 'post-views-counter' ) . '</p>';

		// render plugin status rows
		$rows = $this->get_plugin_status_rows();

		echo '<table class="form-table pvc-status-table"><tbody>'; 
		foreach ( (array) $rows as $row ) {
			$label = isset( $row['label'] ) ? $row['label'] : '';
			$value = isset( $row['value'] ) ? $row['value'] : '';
			$active = isset( $row['active'] ) ? (bool) $row['active'] : null;
			$tables = isset( $row['tables'] ) ? $row['tables'] : null;

			echo '<tr>';
				echo '<th scope="row">' . esc_html( $label ) . '</th>';
				echo '<td>';

			// handle tables structure with individual badges
			if ( is_array( $tables ) && ! empty( $tables ) ) {
				foreach ( $tables as $table ) {
					$table_name = isset( $table['name'] ) ? esc_html( $table['name'] ) : '';
					$table_label = isset( $table['label'] ) ? esc_html( $table['label'] ) : $table_name;
					$table_exists = isset( $table['exists'] ) ? (bool) $table['exists'] : false;

					echo '<p>';
					echo $table_label;
					if ( $table_exists ) {
						echo ' <span class="pvc-status pvc-status-active">&#10003;</span>';
					} else {
						echo ' <span class="pvc-status pvc-status-missing">&#10007;</span>';
					}
					echo '</p>';
				}
			// handle lines array
			} elseif ( isset( $row['lines'] ) && is_array( $row['lines'] ) ) {
				foreach ( $row['lines'] as $line ) {
					echo '<p>' . wp_kses( $line, [ 'span' => [ 'class' => [] ] ] ) . '</p>';
				}
			// handle boolean active status
			} elseif ( $active === true ) {
				 echo '<span class="pvc-status pvc-status-active">&#10003; ' . esc_html__( 'Active', 'post-views-counter' ) . '</span>';
			} elseif ( $active === false ) {
				 echo '<span class="pvc-status pvc-status-missing">&#10007; ' . esc_html__( 'Not Detected', 'post-views-counter' ) . '</span>';
			// handle plain text value
			} else {
				echo wp_kses( $value, [ 'br' => [] ] );
			}				echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Prepare an array with plugin status rows.
	 *
	 * Rows should be associative arrays with: label, value (string) and optional active (bool) key.
	 * The returned rows will be filtered by 'pvc_plugin_status_rows' which allows extensions to add/modify rows.
	 *
	 * @return array
	 */
	protected function get_plugin_status_rows() {
		global $wpdb;

		$version = isset( $this->pvc->defaults['version'] ) ? $this->pvc->defaults['version'] : '';

		if ( empty( $version ) ) {
			$version = esc_html__( 'unknown', 'post-views-counter' );
		}

		// detect pro activation status
		$pvc_pro_active = class_exists( 'Post_Views_Counter_Pro' );

		// get pro version
		$pro_version = $pvc_pro_active ? get_option( 'post_views_counter_pro_version', '1.0.0' ) : '<span class="pvc-status pvc-status-missing">✗</span>';

		// get database tables via filter
		$tables = $this->get_plugin_status_tables();

		$rows = [
			[
				'label' => __( 'Plugin Version', 'post-views-counter' ),
				'lines' => [ 'Post Views Counter: ' . $version, 'Post Views Counter Pro: ' . $pro_version ]
			]
		];

		// add database tables row if any tables are defined
		if ( ! empty( $tables ) ) {
			$rows[] = [
				'label' => __( 'Database Tables', 'post-views-counter' ),
				'tables' => $tables
			];
		}

		/**
		 * Filter the plugin status rows.
		 *
		 * Allows extensions to add or modify status rows displayed in the settings page.
		 *
		 * @since 1.5.9
		 * @param array $rows Status rows
		 * @param Post_Views_Counter_Settings $this Instance of settings class
		 */
		$rows = apply_filters( 'pvc_plugin_status_rows', $rows, $this->settings );

		return $rows;
	}

	/**
	 * Get database tables for status display.
	 *
	 * Collects table definitions via filter, validates that table names contain 'post_views',
	 * checks actual existence in database, and returns formatted array.
	 *
	 * @return array
	 */
	protected function get_plugin_status_tables() {
		global $wpdb;

		/**
		 * Filter the database tables to check for plugin status.
		 *
		 * @since 1.5.9
		 * @param array $table_definitions Array of table definitions
		 * @param Post_Views_Counter_Settings $this Instance of settings class
		 */
		$table_definitions = apply_filters( 'pvc_plugin_status_tables', [], $this->settings );

		if ( empty( $table_definitions ) || ! is_array( $table_definitions ) ) {
			return [];
		}

		$validated_tables = [];

		foreach ( $table_definitions as $table_def ) {
			// validate structure
			if ( ! is_array( $table_def ) || empty( $table_def['name'] ) ) {
				continue;
			}

			$table_name = sanitize_key( $table_def['name'] );

			// security: only allow tables with 'post_views' in the name
			if ( strpos( $table_name, 'post_views' ) === false ) {
				continue;
			}

			// check if table exists
			$full_table_name = $wpdb->prefix . $table_name;
			$exists = (bool) $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $full_table_name ) );

			// use provided label or fallback to table name
			$label = ! empty( $table_def['label'] ) ? $table_def['label'] : $table_name;

			$validated_tables[] = [
				'name' => $table_name,
				'label' => $label,
				'exists' => $exists
			];
		}

		return $validated_tables;
	}

	/**
	 * Section description: other - data import.
	 *
	 * @return void
	 */
	public function section_other_import() {
		echo '<p class="description">' . esc_html__( 'Import view counts when migrating from custom meta key or other analytics plugins.', 'post-views-counter' ) . '</p>';
	}

	/**
	 * Section description: other - admin & cleanup.
	 *
	 * @return void
	 */
	public function section_other_management() {
		echo '<p class="description">' . esc_html__( 'Choose what happens to stored view data on uninstall, and manage other removal-related tools.', 'post-views-counter' ) . '</p>';
	}
}

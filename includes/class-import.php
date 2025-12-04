<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Import class.
 *
 * @class Post_Views_Counter_Import
 */
class Post_Views_Counter_Import {

	/**
	 * Import providers registry.
	 *
	 * @var array
	 */
	private $import_providers = [];

	/**
	 * Whether providers have been initialized.
	 *
	 * @var bool
	 */
	private $import_providers_initialized = false;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// register import providers after translations are available
		add_action( 'init', [ $this, 'initialize_import_providers' ], 5 );
	}

	/**
	 * Initialize import providers.
	 *
	 * @return void
	 */
	public function initialize_import_providers() {
		if ( $this->import_providers_initialized )
			return;

		$this->register_import_providers();
		$this->import_providers_initialized = true;
	}

	/**
	 * Register import providers.
	 *
	 * @return void
	 */
	private function register_import_providers() {
		// custom meta key provider (always available)
		$this->import_providers['custom_meta_key'] = [
			'slug'			=> 'custom_meta_key',
			'label'			=> __( 'Custom Meta Key', 'post-views-counter' ),
			'is_available'	=> '__return_true',
			'render'		=> [ $this, 'render_provider_custom_meta_key' ],
			'sanitize'		=> [ $this, 'sanitize_provider_custom_meta_key' ],
			'analyse'		=> [ $this, 'analyse_provider_custom_meta_key' ],
			'import'		=> [ $this, 'import_provider_custom_meta_key' ]
		];

		// wp-postviews provider (conditional)
		$this->import_providers['wp_postviews'] = [
			'slug'			=> 'wp_postviews',
			'label'			=> __( 'WP-PostViews', 'post-views-counter' ),
			'is_available'	=> [ $this, 'is_wp_postviews_available' ],
			'render'		=> [ $this, 'render_provider_wp_postviews' ],
			'sanitize'		=> [ $this, 'sanitize_provider_wp_postviews' ],
			'analyse'		=> [ $this, 'analyse_provider_wp_postviews' ],
			'import'		=> [ $this, 'import_provider_wp_postviews' ]
		];

		// statify provider (conditional)
		$this->import_providers['statify'] = [
			'slug'			=> 'statify',
			'label'			=> __( 'Statify', 'post-views-counter' ),
			'is_available'	=> [ $this, 'is_statify_available' ],
			'render'		=> [ $this, 'render_provider_statify' ],
			'sanitize'		=> [ $this, 'sanitize_provider_statify' ],
			'analyse'		=> [ $this, 'analyse_provider_statify' ],
			'import'		=> [ $this, 'import_provider_statify' ]
		];

		// allow extensions to register additional providers
		$this->import_providers = apply_filters( 'pvc_import_providers', $this->import_providers );
	}

	/**
	 * Ensure import providers are loaded.
	 *
	 * @return void
	 */
	private function ensure_import_providers_loaded() {
		if ( ! $this->import_providers_initialized && did_action( 'init' ) )
			$this->initialize_import_providers();
	}

	/**
	 * Get all import providers.
	 *
	 * @return array
	 */
	public function get_all_providers() {
		$this->ensure_import_providers_loaded();
		return $this->import_providers;
	}

	/**
	 * Get available import providers.
	 *
	 * @return array
	 */
	public function get_available_providers() {
		$this->ensure_import_providers_loaded();

		$available = [];

		foreach ( $this->import_providers as $slug => $provider ) {
			if ( is_callable( $provider['is_available'] ) && call_user_func( $provider['is_available'] ) ) {
				$available[$slug] = $provider;
			}
		}

		return $available;
	}

	/**
	 * Get a specific provider.
	 *
	 * @param string $slug
	 * @return array|null
	 */
	public function get_provider( $slug ) {
		$this->ensure_import_providers_loaded();
		return isset( $this->import_providers[$slug] ) ? $this->import_providers[$slug] : null;
	}

	/**
	 * Handle manual import/analyse action.
	 *
	 * @param array $request
	 * @return array
	 */
	public function handle_manual_action( $request ) {
		// get provider selection
		$provider_slug = isset( $request['pvc_import_provider'] ) ? sanitize_key( $request['pvc_import_provider'] ) : 'custom_meta_key';

		// get import strategy and validate
		$strategy = isset( $request['pvc_import_strategy'] ) ? sanitize_key( $request['pvc_import_strategy'] ) : 'merge';

		// validate strategy is one of the allowed values
		if ( ! in_array( $strategy, [ 'override', 'merge' ], true ) ) {
			$strategy = 'merge';
		}

		// get provider inputs
		$provider_inputs = isset( $request['pvc_import_provider_inputs'] ) ? $request['pvc_import_provider_inputs'] : [];

		// get available providers
		$providers = $this->get_available_providers();

		// validate provider exists
		if ( ! isset( $providers[$provider_slug] ) ) {
			return [
				'success' => false,
				'message' => __( 'Invalid import provider selected.', 'post-views-counter' ),
				'type' => 'error'
			];
		}

		$provider = $providers[$provider_slug];

		// sanitize provider inputs
		$sanitized_inputs = [];
		if ( is_callable( $provider['sanitize'] ) ) {
			$sanitized_inputs = call_user_func( $provider['sanitize'], $provider_inputs );
		}

		// get main instance
		$pvc = Post_Views_Counter();

		// preserve existing provider settings, only update current provider
		$existing_settings = isset( $pvc->options['other']['import_provider_settings'] ) ? $pvc->options['other']['import_provider_settings'] : [];

		$provider_settings = array_merge(
			$existing_settings,
			[
				'provider' => $provider_slug,
				'strategy' => $strategy,
				$provider_slug => $sanitized_inputs
			]
		);

		$result = [];

		// handle analyse
		if ( isset( $request['post_views_counter_analyse_views'] ) ) {
			if ( is_callable( $provider['analyse'] ) ) {
				$analyse_result = call_user_func( $provider['analyse'], $sanitized_inputs );

				if ( isset( $analyse_result['message'] ) ) {
					$result = [
						'success' => true,
						'message' => $analyse_result['message'],
						'type' => 'updated',
						'provider_settings' => $provider_settings
					];
				}
			}
		// handle import
		} elseif ( isset( $request['post_views_counter_import_views'] ) ) {
			if ( is_callable( $provider['import'] ) ) {
				$import_result = call_user_func( $provider['import'], $sanitized_inputs, $strategy );

				if ( isset( $import_result['success'] ) && $import_result['success'] ) {
					$result = [
						'success' => true,
						'message' => $import_result['message'],
						'type' => 'updated',
						'provider_settings' => $provider_settings
					];
				} else if ( isset( $import_result['message'] ) ) {
					$result = [
						'success' => false,
						'message' => $import_result['message'],
						'type' => isset( $import_result['success'] ) && ! $import_result['success'] ? 'updated' : 'error',
						'provider_settings' => $provider_settings
					];
				}
			}
		}

		return $result;
	}

	/**
	 * Prepare provider settings from request.
	 *
	 * @param array $request
	 * @return array
	 */
	public function prepare_provider_settings_from_request( $request ) {
		// get existing provider settings or initialize
		$pvc = Post_Views_Counter();
		$existing_settings = isset( $pvc->options['other']['import_provider_settings'] ) ? $pvc->options['other']['import_provider_settings'] : [];

		// check if provider inputs were submitted
		if ( isset( $request['pvc_import_provider'], $request['pvc_import_provider_inputs'], $request['pvc_import_strategy'] ) ) {
			$provider_slug = sanitize_key( $request['pvc_import_provider'] );
			$provider_inputs = $request['pvc_import_provider_inputs'];
			$strategy = sanitize_key( $request['pvc_import_strategy'] );

			// validate strategy
			if ( ! in_array( $strategy, [ 'override', 'merge' ], true ) ) {
				$strategy = 'merge';
			}

			// get available providers
			$providers = $this->get_available_providers();

			// validate provider exists
			if ( isset( $providers[$provider_slug] ) ) {
				$provider = $providers[$provider_slug];

				// sanitize provider inputs
				$sanitized_inputs = [];
				if ( is_callable( $provider['sanitize'] ) ) {
					$sanitized_inputs = call_user_func( $provider['sanitize'], $provider_inputs );
				}

				// update provider settings
				return array_merge(
					$existing_settings,
					[
						'provider' => $provider_slug,
						'strategy' => $strategy,
						$provider_slug => $sanitized_inputs
					]
				);
			}
		}

		// preserve existing settings if not changed
		return $existing_settings;
	}

	/**
	 * Check if WP-PostViews is available.
	 *
	 * @return bool
	 */
	public function is_wp_postviews_available() {
		return function_exists( 'the_views' );
	}

	/**
	 * Render custom meta key provider fields.
	 *
	 * @return string
	 */
	public function render_provider_custom_meta_key() {
		// get main instance
		$pvc = Post_Views_Counter();

		// get saved meta key or default
		$meta_key = isset( $pvc->options['other']['import_provider_settings']['custom_meta_key']['meta_key'] ) ? $pvc->options['other']['import_provider_settings']['custom_meta_key']['meta_key'] : 'views';

		$html = '
		<div class="pvc-provider-fields">
			<label for="pvc_import_meta_key">' . esc_html__( 'Meta Key', 'post-views-counter' ) . '</label>
			<input type="text" id="pvc_import_meta_key" class="regular-text" name="pvc_import_provider_inputs[meta_key]" value="' . esc_attr( $meta_key ) . '" />
			<p class="description">' . esc_html__( 'Enter the meta key from which the views data is to be retrieved during import.', 'post-views-counter' ) . '</p>
		</div>';

		return $html;
	}

	/**
	 * Sanitize custom meta key provider inputs.
	 *
	 * @param array $inputs
	 * @return array
	 */
	public function sanitize_provider_custom_meta_key( $inputs ) {
		$sanitized = [];

		if ( isset( $inputs['meta_key'] ) ) {
			$sanitized['meta_key'] = sanitize_key( $inputs['meta_key'] );
		}

		return $sanitized;
	}

	/**
	 * Analyse custom meta key provider.
	 *
	 * @global object $wpdb
	 *
	 * @param array $inputs
	 * @return array
	 */
	public function analyse_provider_custom_meta_key( $inputs ) {
		global $wpdb;

		$meta_key = isset( $inputs['meta_key'] ) ? sanitize_key( $inputs['meta_key'] ) : 'views';

		// get views data
		$views = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = %s AND meta_value > 0", $meta_key ), ARRAY_A );

		if ( empty( $views ) ) {
			return [
				'count' => 0,
				'message' => sprintf( __( 'No valid views data found for %s.', 'post-views-counter' ), sprintf( __( 'meta key: %s', 'post-views-counter' ), esc_html( $meta_key ) ) )
			];
		}

		// calculate total views
		$total_views = 0;
		foreach ( $views as $view ) {
			$total_views += (int) $view['meta_value'];
		}

		// generate detailed message
		$stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $views ),
			'additional_info' => sprintf( __( 'Source: meta key %s', 'post-views-counter' ), esc_html( $meta_key ) )
		];

		return [
			'count' => count( $views ),
			'message' => $this->generate_import_message( $stats, 'analyze' )
		];
	}

	/**
	 * Import custom meta key provider.
	 *
	 * @global object $wpdb
	 *
	 * @param array $inputs
	 * @param string $strategy
	 * @return array
	 */
	public function import_provider_custom_meta_key( $inputs, $strategy ) {
		global $wpdb;

		$meta_key = isset( $inputs['meta_key'] ) ? sanitize_key( $inputs['meta_key'] ) : 'views';

		// get views
		$views = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = %s AND meta_value > 0", $meta_key ), ARRAY_A, 0 );

		if ( empty( $views ) ) {
			return [
				'success' => false,
				'message' => __( 'No valid post data found to import.', 'post-views-counter' )
			];
		}

		$sql = [];

		foreach ( $views as $view ) {
			$sql[] = $wpdb->prepare( "(%d, 4, 'total', %d)", (int) $view['post_id'], (int) $view['meta_value'] );
		}

		// build SQL based on strategy
		$on_duplicate = ( $strategy === 'override' ) ? 'count = VALUES(count)' : 'count = count + VALUES(count)';

		$wpdb->query( "INSERT INTO " . $wpdb->prefix . "post_views(id, type, period, count) VALUES " . implode( ',', $sql ) . " ON DUPLICATE KEY UPDATE " . $on_duplicate );

		// calculate total views
		$total_views = 0;
		foreach ( $views as $view ) {
			$total_views += (int) $view['meta_value'];
		}

		// generate detailed message
		$stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $views ),
			'additional_info' => sprintf( __( 'Source: meta key %s', 'post-views-counter' ), esc_html( $meta_key ) )
		];

		return [
			'success' => true,
			'message' => $this->generate_import_message( $stats )
		];
	}

	/**
	 * Render WP-PostViews provider fields.
	 *
	 * @return string
	 */
	public function render_provider_wp_postviews() {
		$html = '
		<div class="pvc-provider-fields">
			<p class="description">' . esc_html__( 'WP-PostViews post views data will be automatically imported from the meta key used by that plugin.', 'post-views-counter' ) . '</p>
		</div>';

		return $html;
	}

	/**
	 * Sanitize WP-PostViews provider inputs.
	 *
	 * @param array $inputs
	 * @return array
	 */
	public function sanitize_provider_wp_postviews( $inputs ) {
		// no inputs needed for WP-PostViews
		return [];
	}

	/**
	 * Analyse WP-PostViews provider.
	 *
	 * @global object $wpdb
	 *
	 * @param array $inputs
	 * @return array
	 */
	public function analyse_provider_wp_postviews( $inputs ) {
		global $wpdb;

		// wp-postviews uses 'views' meta key
		$views = $wpdb->get_results( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = 'views' AND meta_value > 0", ARRAY_A );

		if ( empty( $views ) ) {
			return [
				'count' => 0,
				'message' => sprintf( __( 'No valid views data found for %s.', 'post-views-counter' ), 'WP-PostViews' )
			];
		}

		// calculate total views
		$total_views = 0;
		foreach ( $views as $view ) {
			$total_views += (int) $view['meta_value'];
		}

		// generate detailed message
		$stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $views ),
			'additional_info' => sprintf( __( 'Source: %s', 'post-views-counter' ), 'WP-PostViews' )
		];

		return [
			'count' => count( $views ),
			'message' => $this->generate_import_message( $stats, 'analyze' )
		];
	}

	/**
	 * Import WP-PostViews provider.
	 *
	 * @global object $wpdb
	 *
	 * @param array $inputs
	 * @param string $strategy
	 * @return array
	 */
	public function import_provider_wp_postviews( $inputs, $strategy ) {
		global $wpdb;

		// wp-postviews uses 'views' meta key
		$views = $wpdb->get_results( "SELECT post_id, meta_value FROM " . $wpdb->postmeta . " WHERE meta_key = 'views' AND meta_value > 0", ARRAY_A, 0 );

		if ( empty( $views ) ) {
			return [
				'success' => false,
				'message' => __( 'No valid post data found to import.', 'post-views-counter' )
			];
		}

		$sql = [];

		foreach ( $views as $view ) {
			$sql[] = $wpdb->prepare( "(%d, 4, 'total', %d)", (int) $view['post_id'], (int) $view['meta_value'] );
		}

		// build SQL based on strategy
		$on_duplicate = ( $strategy === 'override' ) ? 'count = VALUES(count)' : 'count = count + VALUES(count)';

		$wpdb->query( "INSERT INTO " . $wpdb->prefix . "post_views(id, type, period, count) VALUES " . implode( ',', $sql ) . " ON DUPLICATE KEY UPDATE " . $on_duplicate );

		// calculate total views
		$total_views = 0;
		foreach ( $views as $view ) {
			$total_views += (int) $view['meta_value'];
		}

		// generate detailed message
		$stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $views ),
			'additional_info' => sprintf( __( 'Source: %s', 'post-views-counter' ), 'WP-PostViews' )
		];

		return [
			'success' => true,
			'message' => $this->generate_import_message( $stats )
		];
	}

	/**
	 * Check if Statify is available.
	 *
	 * @return bool
	 */
	public function is_statify_available() {
		global $wpdb;
		$table = esc_sql( isset( $wpdb->statify ) ? $wpdb->statify : $wpdb->prefix . 'statify' );
		return class_exists( 'Statify' ) && $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;
	}

	/**
	 * Render Statify provider fields.
	 *
	 * @return string
	 */
	public function render_provider_statify() {
		$html = '
		<div class="pvc-provider-fields">
			<p class="description">' . esc_html__( 'Statify visit data will be aggregated and imported into PVC\'s daily, weekly, monthly, yearly, and total view counts.', 'post-views-counter' ) . '</p>
		</div>';

		return $html;
	}

	/**
	 * Sanitize Statify provider inputs.
	 *
	 * @param array $inputs
	 * @return array
	 */
	public function sanitize_provider_statify( $inputs ) {
		// no inputs needed for Statify
		return [];
	}

	/**
	 * Analyse Statify provider.
	 *
	 * @global object $wpdb
	 *
	 * @param array $inputs
	 * @return array
	 */
	public function analyse_provider_statify( $inputs ) {
		global $wpdb;

		$table = esc_sql( isset( $wpdb->statify ) ? $wpdb->statify : $wpdb->prefix . 'statify' );
		$pvc_settings = Post_Views_Counter()->options['general'];
		$use_gmt = $pvc_settings['count_time'] === 'gmt';
		$tracked_post_types = array_map( 'sanitize_key', (array) $pvc_settings['post_types_count'] );

		// get aggregated data to analyze
		$rows = $wpdb->get_results( "SELECT target, created, COUNT(*) AS views FROM `{$table}` GROUP BY target, created", ARRAY_A );

		if ( empty( $rows ) ) {
			return [
				'count' => 0,
				'message' => sprintf( __( 'No valid views data found for %s.', 'post-views-counter' ), 'Statify' )
			];
		}

		$stats = [];
		$skipped_targets = [];
		$total_views = 0;
		$period_counts = [
			'daily' => 0,
			'weekly' => 0,
			'monthly' => 0,
			'yearly' => 0
		];

		foreach ( $rows as $row ) {
			$post_id = $this->map_target_to_post_id( $row['target'], $tracked_post_types );
			if ( ! $post_id ) {
				$skipped_targets[] = $row['target'];
				continue;
			}

			$ts = strtotime( $row['created'] );
			if ( $use_gmt ) {
				$date = gmdate( 'W-d-m-Y-o', $ts );
			} else {
				$date = date( 'W-d-m-Y-o', $ts );
			}
			$parts = explode( '-', $date );

			$day_key = $parts[3] . $parts[2] . $parts[1];
			$week_key = $parts[4] . $parts[0];
			$month_key = $parts[3] . $parts[2];
			$year_key = $parts[3];

			if ( ! isset( $stats[$post_id] ) ) {
				$stats[$post_id] = [
					'daily' => [],
					'weekly' => [],
					'monthly' => [],
					'yearly' => [],
					'total' => 0
				];
			}

			if ( ! isset( $stats[$post_id]['daily'][$day_key] ) ) {
				$period_counts['daily']++;
			}
			if ( ! isset( $stats[$post_id]['weekly'][$week_key] ) ) {
				$period_counts['weekly']++;
			}
			if ( ! isset( $stats[$post_id]['monthly'][$month_key] ) ) {
				$period_counts['monthly']++;
			}
			if ( ! isset( $stats[$post_id]['yearly'][$year_key] ) ) {
				$period_counts['yearly']++;
			}

			$stats[$post_id]['daily'][$day_key] = ( $stats[$post_id]['daily'][$day_key] ?? 0 ) + $row['views'];
			$stats[$post_id]['weekly'][$week_key] = ( $stats[$post_id]['weekly'][$week_key] ?? 0 ) + $row['views'];
			$stats[$post_id]['monthly'][$month_key] = ( $stats[$post_id]['monthly'][$month_key] ?? 0 ) + $row['views'];
			$stats[$post_id]['yearly'][$year_key] = ( $stats[$post_id]['yearly'][$year_key] ?? 0 ) + $row['views'];
			$stats[$post_id]['total'] += $row['views'];
			$total_views += $row['views'];
		}

		// prepare statistics for message generation
		$message_stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $stats ),
			'periods' => $period_counts
		];

		// add skipped URLs info if any
		if ( ! empty( $skipped_targets ) ) {
			$unique_skipped = array_unique( $skipped_targets );
			$message_stats['additional_info'] = sprintf( __( 'Would skip %s non-post URLs. Source: %s', 'post-views-counter' ), number_format_i18n( count( $unique_skipped ) ), 'Statify' );
		} else {
			$message_stats['additional_info'] = sprintf( __( 'Source: %s', 'post-views-counter' ), 'Statify' );
		}

		return [
			'count' => $total_views,
			'message' => $this->generate_import_message( $message_stats, 'analyze' )
		];
	}

	/**
	 * Import Statify provider.
	 *
	 * @global object $wpdb
	 *
	 * @param array $inputs
	 * @param string $strategy
	 * @return array
	 */
	public function import_provider_statify( $inputs, $strategy ) {
		global $wpdb;

		$table = esc_sql( isset( $wpdb->statify ) ? $wpdb->statify : $wpdb->prefix . 'statify' );

		$pvc_settings = Post_Views_Counter()->options['general'];
		$use_gmt = $pvc_settings['count_time'] === 'gmt';
		$tracked_post_types = array_map( 'sanitize_key', (array) $pvc_settings['post_types_count'] );

		// Fetch aggregated data in chunks
		$offset = 0;
		$limit = 1000; // Adjust for memory
		$stats = [];
		$skipped_targets = [];

		while ( true ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT target, created, COUNT(*) AS views FROM `{$table}` GROUP BY target, created ORDER BY target, created LIMIT %d OFFSET %d",
					$limit, $offset
				),
				ARRAY_A
			);

			if ( empty( $rows ) ) break;

			foreach ( $rows as $row ) {
				$post_id = $this->map_target_to_post_id( $row['target'], $tracked_post_types );
				if ( ! $post_id ) {
					$skipped_targets[] = $row['target'];
					continue;
				}

				$ts = strtotime( $row['created'] );
				if ( $use_gmt ) {
					$date = gmdate( 'W-d-m-Y-o', $ts );
				} else {
					$date = date( 'W-d-m-Y-o', $ts );
				}
				$parts = explode( '-', $date );

				$day_key = $parts[3] . $parts[2] . $parts[1];
				$week_key = $parts[4] . $parts[0];
				$month_key = $parts[3] . $parts[2];
				$year_key = $parts[3];

				if ( ! isset( $stats[$post_id] ) ) {
					$stats[$post_id] = [
						'daily' => [],
						'weekly' => [],
						'monthly' => [],
						'yearly' => [],
						'total' => 0
					];
				}

				$stats[$post_id]['daily'][$day_key] = ( $stats[$post_id]['daily'][$day_key] ?? 0 ) + $row['views'];
				$stats[$post_id]['weekly'][$week_key] = ( $stats[$post_id]['weekly'][$week_key] ?? 0 ) + $row['views'];
				$stats[$post_id]['monthly'][$month_key] = ( $stats[$post_id]['monthly'][$month_key] ?? 0 ) + $row['views'];
				$stats[$post_id]['yearly'][$year_key] = ( $stats[$post_id]['yearly'][$year_key] ?? 0 ) + $row['views'];
				$stats[$post_id]['total'] += $row['views'];
			}

			$offset += $limit;
		}

		// Batch insert and collect statistics
		$sql_parts = [];
		$total_views = 0;
		$posts_processed = 0;
		$period_counts = [
			'daily' => 0,
			'weekly' => 0,
			'monthly' => 0,
			'yearly' => 0
		];

		foreach ( $stats as $post_id => $periods ) {
			$posts_processed++;

			foreach ( $periods['daily'] as $period => $count ) {
				$sql_parts[] = $wpdb->prepare( "(%d, 0, %s, %d)", $post_id, $period, $count );
				$period_counts['daily']++;
			}
			foreach ( $periods['weekly'] as $period => $count ) {
				$sql_parts[] = $wpdb->prepare( "(%d, 1, %s, %d)", $post_id, $period, $count );
				$period_counts['weekly']++;
			}
			foreach ( $periods['monthly'] as $period => $count ) {
				$sql_parts[] = $wpdb->prepare( "(%d, 2, %s, %d)", $post_id, $period, $count );
				$period_counts['monthly']++;
			}
			foreach ( $periods['yearly'] as $period => $count ) {
				$sql_parts[] = $wpdb->prepare( "(%d, 3, %s, %d)", $post_id, $period, $count );
				$period_counts['yearly']++;
			}
			$sql_parts[] = $wpdb->prepare( "(%d, 4, 'total', %d)", $post_id, $periods['total'] );
			$total_views += $periods['total'];
		}

		if ( empty( $sql_parts ) ) {
			$debug_message = __( 'No valid post data found to import.', 'post-views-counter' );

			// add helpful debug info
			if ( empty( $tracked_post_types ) ) {
				$debug_message .= ' ' . __( 'No post types are selected for tracking in settings.', 'post-views-counter' );
			} elseif ( ! empty( $skipped_targets ) ) {
				$unique_skipped = array_unique( $skipped_targets );
				$sample_targets = array_slice( $unique_skipped, 0, 5 );
				$debug_message .= ' ' . sprintf(
					__( 'All %s URLs were skipped. Sample URLs: %s', 'post-views-counter' ),
					number_format_i18n( count( $unique_skipped ) ),
					implode( ', ', array_map( 'esc_html', $sample_targets ) )
				);
			}

			return [
				'success' => false,
				'message' => $debug_message
			];
		}

		$on_duplicate = ( $strategy === 'override' ) ? 'count = VALUES(count)' : 'count = count + VALUES(count)';
		$wpdb->query( "INSERT INTO {$wpdb->prefix}post_views (id, type, period, count) VALUES " . implode( ',', $sql_parts ) . " ON DUPLICATE KEY UPDATE {$on_duplicate}" );

		$this->flush_pvc_caches();

		// prepare statistics for message generation
		$stats = [
			'total_views' => $total_views,
			'posts_processed' => $posts_processed,
			'periods' => $period_counts
		];

		// add skipped URLs info if any
		if ( ! empty( $skipped_targets ) ) {
			$unique_skipped = array_unique( $skipped_targets );
			$stats['additional_info'] = sprintf( __( 'Skipped %s non-post URLs. Source: %s', 'post-views-counter' ), number_format_i18n( count( $unique_skipped ) ), 'Statify' );
		} else {
			$stats['additional_info'] = sprintf( __( 'Source: %s', 'post-views-counter' ), 'Statify' );
		}

		return [
			'success' => true,
			'message' => $this->generate_import_message( $stats )
		];
	}

	/**
	 * Generate import/analyze message with statistics.
	 *
	 * @param array $stats Import statistics
	 * @param string $mode Mode: 'import' or 'analyze'
	 * @return string
	 */
	private function generate_import_message( $stats, $mode = 'import' ) {
		$message_parts = [];

		// main success message
		if ( isset( $stats['total_views'] ) && isset( $stats['posts_processed'] ) ) {
			if ( $mode === 'analyze' ) {
				$message_parts[] = sprintf(
					__( 'Found %s total views for %s posts.', 'post-views-counter' ),
					number_format_i18n( $stats['total_views'] ),
					number_format_i18n( $stats['posts_processed'] )
				);
			} else {
				$message_parts[] = sprintf(
					__( 'Successfully imported %s total views for %s posts.', 'post-views-counter' ),
					number_format_i18n( $stats['total_views'] ),
					number_format_i18n( $stats['posts_processed'] )
				);
			}
		}

		// period breakdown (only if period data exists)
		if ( ! empty( $stats['periods'] ) ) {
			$period_parts = [];
			if ( isset( $stats['periods']['daily'] ) && $stats['periods']['daily'] > 0 ) {
				$period_parts[] = sprintf( __( '%s daily', 'post-views-counter' ), number_format_i18n( $stats['periods']['daily'] ) );
			}
			if ( isset( $stats['periods']['weekly'] ) && $stats['periods']['weekly'] > 0 ) {
				$period_parts[] = sprintf( __( '%s weekly', 'post-views-counter' ), number_format_i18n( $stats['periods']['weekly'] ) );
			}
			if ( isset( $stats['periods']['monthly'] ) && $stats['periods']['monthly'] > 0 ) {
				$period_parts[] = sprintf( __( '%s monthly', 'post-views-counter' ), number_format_i18n( $stats['periods']['monthly'] ) );
			}
			if ( isset( $stats['periods']['yearly'] ) && $stats['periods']['yearly'] > 0 ) {
				$period_parts[] = sprintf( __( '%s yearly', 'post-views-counter' ), number_format_i18n( $stats['periods']['yearly'] ) );
			}

			if ( ! empty( $period_parts ) ) {
				$message_parts[] = __( 'Period records:', 'post-views-counter' ) . ' ' . implode( ', ', $period_parts ) . '.';
			}
		}

		// additional info (like skipped URLs)
		if ( ! empty( $stats['additional_info'] ) ) {
			$message_parts[] = $stats['additional_info'];
		}

		return implode( ' ', $message_parts );
	}

	/**
	 * Map Statify target URL to post ID.
	 *
	 * @param string $target
	 * @param array $tracked_post_types
	 * @return int
	 */
	private function map_target_to_post_id( $target, $tracked_post_types ) {
		// handle empty tracked post types
		if ( empty( $tracked_post_types ) ) {
			return 0;
		}

		// handle homepage
		if ( $target === '/' ) {
			$post_id = get_option( 'page_on_front' );
			if ( $post_id && in_array( get_post_type( $post_id ), $tracked_post_types, true ) ) {
				return (int) $post_id;
			}
		}

		// build full URL from relative target path
		$url = home_url( $target );
		$post_id = url_to_postid( $url );

		// verify post exists and is a tracked type
		if ( $post_id ) {
			$post_type = get_post_type( $post_id );
			if ( $post_type && in_array( $post_type, $tracked_post_types, true ) ) {
				return (int) $post_id;
			}
		}

		return 0;
	}

	/**
	 * Flush caches specific to Post Views Counter.
	 *
	 * @return void
	 */
	private function flush_pvc_caches() {
		global $wp_object_cache;

		if ( ! is_object( $wp_object_cache ) || ! property_exists( $wp_object_cache, 'cache' ) ) {
			return;
		}

		$groups = [ 'pvc', 'pvc-get_post_views', 'pvc-get_views' ];

		foreach ( $groups as $group ) {
			if ( empty( $wp_object_cache->cache[$group] ) || ! is_array( $wp_object_cache->cache[$group] ) ) {
				continue;
			}

			foreach ( array_keys( $wp_object_cache->cache[$group] ) as $key ) {
				wp_cache_delete( $key, $group );
			}
		}
	}
}
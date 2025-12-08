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
	private $import_provider_labels = [];
	private $import_strategies = null;
	private $default_import_strategy = 'merge';

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
			'supports'		=> [ 'total', 'post_types' ],
			'is_available'	=> '__return_true',
			'render'		=> [ $this, 'render_provider_custom_meta_key' ],
			'sanitize'		=> [ $this, 'sanitize_provider_custom_meta_key' ],
			'analyse'		=> [ $this, 'analyse_provider_custom_meta_key' ],
			'import'		=> [ $this, 'import_provider_custom_meta_key' ]
		];

		// wp-postviews provider (conditional)
		$this->import_providers['wp_postviews'] = [
			'slug'			=> 'wp_postviews',
			'label'			=> 'WP-PostViews',
			'supports'		=> [ 'total', 'post_types' ],
			'is_available'	=> [ $this, 'is_wp_postviews_available' ],
			'render'		=> [ $this, 'render_provider_wp_postviews' ],
			'sanitize'		=> [ $this, 'sanitize_provider_wp_postviews' ],
			'analyse'		=> [ $this, 'analyse_provider_wp_postviews' ],
			'import'		=> [ $this, 'import_provider_wp_postviews' ]
		];

		// statify provider (conditional)
		$this->import_providers['statify'] = [
			'slug'			=> 'statify',
			'label'			=> 'Statify',
			'supports'		=> [ 'total', 'yearly', 'monthly', 'weekly', 'daily', 'post_types', 'taxonomies', 'authors', 'other_pages' ],
			'is_available'	=> [ $this, 'is_statify_available' ],
			'render'		=> [ $this, 'render_provider_statify' ],
			'sanitize'		=> [ $this, 'sanitize_provider_statify' ],
			'analyse'		=> [ $this, 'analyse_provider_statify' ],
			'import'		=> [ $this, 'import_provider_statify' ]
		];

		// page views count provider (conditional)
		$this->import_providers['page_views_count'] = [
			'slug'			=> 'page_views_count',
			'label'			=> 'Page Views Count',
			'supports'		=> [ 'total', 'yearly', 'monthly', 'weekly', 'daily', 'post_types' ],
			'is_available'	=> [ $this, 'is_page_views_count_available' ],
			'render'		=> [ $this, 'render_provider_page_views_count' ],
			'sanitize'		=> [ $this, 'sanitize_provider_page_views_count' ],
			'analyse'		=> [ $this, 'analyse_provider_page_views_count' ],
			'import'		=> [ $this, 'import_provider_page_views_count' ]
		];

		// allow extensions to register additional providers without overriding core ones
		$additional_providers = apply_filters( 'pvc_import_providers', [] );

		if ( is_array( $additional_providers ) ) {
			foreach ( $additional_providers as $slug => $provider ) {
				if ( ! is_string( $slug ) || $slug === '' || isset( $this->import_providers[ $slug ] ) ) {
					continue;
				}

				$this->import_providers[ $slug ] = $provider;
			}
		}

		// ensure third-party providers have default supports
		foreach ( $this->import_providers as $slug => $provider ) {
			if ( ! isset( $provider['supports'] ) || ! is_array( $provider['supports'] ) ) {
				$this->import_providers[ $slug ]['supports'] = [ 'total', 'post_types' ];
			}
		}

		foreach ( $this->import_providers as $slug => $provider ) {
			$this->import_provider_labels[ $slug ] = isset( $provider['label'] ) ? $provider['label'] : $slug;
		}
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
	 * Get supports for a specific provider.
	 *
	 * @param string $slug
	 * @return array
	 */
	public function get_provider_supports( $slug ) {
		$provider = $this->get_provider( $slug );
		$supports = $provider && isset( $provider['supports'] ) ? $provider['supports'] : [];
		return apply_filters( 'pvc_import_provider_supports', $supports, $slug );
	}

	/**
	 * Get registered import strategies.
	 *
	 * @return array
	 */
	public function get_import_strategies() {
		if ( $this->import_strategies === null ) {
			$this->import_strategies = [
				'override' => [
					'label' => __( 'Override existing views', 'post-views-counter' ),
					'description' => __( 'Replace stored counts with the imported values.', 'post-views-counter' ),
					'pro_only' => false,
				],
				'merge' => [
					'label' => __( 'Merge with existing views', 'post-views-counter' ),
					'description' => __( 'Add imported counts on top of the existing values.', 'post-views-counter' ),
					'pro_only' => false,
				],
				'skip_existing' => [
					'label' => __( 'Skip Existing', 'post-views-counter' ),
					'description' => __( 'Only import data when the target record does not exist yet.', 'post-views-counter' ),
					'pro_only' => true,
				],
				'keep_higher_count' => [
					'label' => __( 'Keep Higher Count', 'post-views-counter' ),
					'description' => __( 'Keep whichever value is higher when comparing imported and stored counts.', 'post-views-counter' ),
					'pro_only' => true,
				],
				'fill_empty_only' => [
					'label' => __( 'Fill Empty Counts', 'post-views-counter' ),
					'description' => __( 'Only import data for posts or periods that currently store zero views.', 'post-views-counter' ),
					'pro_only' => true,
				],
			];

			/**
			 * Filter the available import strategies.
			 *
			 * @since 1.5.10
			 *
			 * @param array $strategies Strategy definitions.
			 * @param Post_Views_Counter_Import $importer Import handler instance.
			 */
			$this->import_strategies = apply_filters( 'pvc_import_strategies', $this->import_strategies, $this );
		}

		return $this->import_strategies;
	}

	/**
	 * Get default import strategy.
	 *
	 * @return string
	 */
	public function get_default_strategy() {
		return $this->default_import_strategy;
	}

	/**
	 * Normalize import strategy against current availability.
	 *
	 * @param string $strategy
	 * @return string
	 */
	public function normalize_strategy( $strategy ) {
		$strategy = sanitize_key( $strategy );

		if ( $this->is_strategy_enabled( $strategy ) ) {
			return $strategy;
		}

		return $this->get_default_strategy();
	}

	/**
	 * Check if a strategy can be used in the current environment.
	 *
	 * @param string $strategy
	 * @return bool
	 */
	public function is_strategy_enabled( $strategy ) {
		$strategy = sanitize_key( $strategy );
		$definition = $this->get_strategy_definition( $strategy );

		if ( $definition === null ) {
			return false;
		}

		if ( ! empty( $definition['pro_only'] ) && ! $this->is_pro_active() ) {
			return false;
		}

		return true;
	}

	/**
	 * Get strategy definition.
	 *
	 * @param string $strategy
	 * @return array|null
	 */
	private function get_strategy_definition( $strategy ) {
		$strategy = sanitize_key( $strategy );
		$strategies = $this->get_import_strategies();

		return isset( $strategies[ $strategy ] ) ? $strategies[ $strategy ] : null;
	}

	/**
	 * Check if Post Views Counter Pro is active.
	 *
	 * @return bool
	 */
	private function is_pro_active() {
		return class_exists( 'Post_Views_Counter_Pro' );
	}

	/**
	 * Generate a description for a provider based on its supports.
	 *
	 * @param array $supports
	 * @return string
	 */
	private function generate_provider_description( $supports ) {
		$parts = [];

		// Date periods
		$dates = [];
		if ( in_array( 'total', $supports, true ) ) {
			$dates[] = _x( 'total', 'view_counts', 'post-views-counter' );
		}
		if ( in_array( 'yearly', $supports, true ) ) {
			$dates[] = _x( 'yearly', 'view_counts', 'post-views-counter' );
		}
		if ( in_array( 'monthly', $supports, true ) ) {
			$dates[] = _x( 'monthly', 'view_counts', 'post-views-counter' );
		}
		if ( in_array( 'weekly', $supports, true ) ) {
			$dates[] = _x( 'weekly', 'view_counts', 'post-views-counter' );
		}
		if ( in_array( 'daily', $supports, true ) ) {
			$dates[] = _x( 'daily', 'view_counts', 'post-views-counter' );
		}

		// Content types
		$content_labels = [
			'post_types' => _x( 'post types', 'view_counts', 'post-views-counter' ),
			'taxonomies' => _x( 'taxonomies', 'view_counts', 'post-views-counter' ),
			'authors' => _x( 'author archives', 'view_counts', 'post-views-counter' ),
			'other_pages' => _x( 'other pages', 'view_counts', 'post-views-counter' ),
			'traffic_sources' => _x( 'traffic sources', 'view_counts', 'post-views-counter' )
		];

		$content = [];
		$pro_only_keys = [ 'taxonomies', 'authors', 'other_pages', 'traffic_sources' ];
		$content_keys = [ 'post_types' ];

		foreach ( $content_keys as $key ) {
			if ( in_array( $key, $supports, true ) && isset( $content_labels[ $key ] ) ) {
				$content[] = $content_labels[ $key ];
			}
		}

		if ( ! empty( $dates ) && ! empty( $content ) ) {
			$parts[] = sprintf( __( 'Imports %s view counts for %s', 'post-views-counter' ), $this->format_list( $dates ), $this->format_list( $content ) );
		} elseif ( ! empty( $dates ) ) {
			$parts[] = sprintf( __( 'Imports %s view counts', 'post-views-counter' ), $this->format_list( $dates ) );
		}

		$pro_content = [];

		foreach ( $pro_only_keys as $key ) {
			if ( in_array( $key, $supports, true ) && isset( $content_labels[ $key ] ) ) {
				$pro_content[] = $content_labels[ $key ];
			}
		}

		if ( ! empty( $pro_content ) ) {
			$parts[] = sprintf( __( 'PVC Pro additionally imports %s view counts', 'post-views-counter' ), $this->format_list( $pro_content ) );
		}

		if ( empty( $parts ) ) {
			return '';
		}

		return implode( '. ', $parts ) . '.';
	}

	/**
	 * Format a list of items into a human-readable string.
	 *
	 * @param array $items
	 * @return string
	 */
	private function format_list( $items ) {
		if ( count( $items ) === 1 ) {
			return $items[0];
		}

		$last = array_pop( $items );
		return implode( ', ', $items ) . ' ' . _x( 'and', 'view_counts', 'post-views-counter' ) . ' ' . $last;
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
		$strategy = isset( $request['pvc_import_strategy'] ) ? $this->normalize_strategy( $request['pvc_import_strategy'] ) : $this->get_default_strategy();

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
			$strategy = $this->normalize_strategy( $request['pvc_import_strategy'] );

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

		// get provider
		$provider = $this->get_provider( 'custom_meta_key' );

		// generate description
		$description = $this->generate_provider_description( $provider['supports'] ) . ' ' . esc_html__( 'Enter the meta key from which the views data is to be retrieved during import.', 'post-views-counter' );

		$html = '
		<div class="pvc-provider-fields">
			<input type="text" id="pvc_import_meta_key" class="regular-text" name="pvc_import_provider_inputs[meta_key]" value="' . esc_attr( $meta_key ) . '" />
			<p class="description">' . $description . '</p>
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

		$stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $views ),
			'source' => $this->get_provider_label( 'custom_meta_key' ),
			'additional_info' => sprintf( __( 'Meta key "%s".', 'post-views-counter' ), esc_html( $meta_key ) )
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
		$totals_map = [];
		$total_views = 0;

		foreach ( $views as $view ) {
			$post_id = (int) $view['post_id'];
			$count = (int) $view['meta_value'];

			$sql[] = $wpdb->prepare( "(%d, 4, 'total', %d)", $post_id, $count );
			$total_views += $count;
			$totals_map[ $post_id ] = ( isset( $totals_map[ $post_id ] ) ? $totals_map[ $post_id ] : 0 ) + $count;
		}

		$this->execute_provider_insert_query( $sql, $strategy, 'custom_meta_key' );

		$stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $totals_map ),
			'source' => $this->get_provider_label( 'custom_meta_key' ),
			'additional_info' => sprintf( __( 'Meta key "%s".', 'post-views-counter' ), esc_html( $meta_key ) )
		];

		$this->apply_skip_statistics( $stats, $totals_map, $strategy );

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
		// get provider
		$provider = $this->get_provider( 'wp_postviews' );

		// generate description
		$description = $this->generate_provider_description( $provider['supports'] );

		$html = '
		<div class="pvc-provider-fields">
			<p class="description">' . $description . '</p>
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

		$stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $views ),
			'source' => $this->get_provider_label( 'wp_postviews' )
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
		$totals_map = [];
		$total_views = 0;

		foreach ( $views as $view ) {
			$post_id = (int) $view['post_id'];
			$count = (int) $view['meta_value'];

			$sql[] = $wpdb->prepare( "(%d, 4, 'total', %d)", $post_id, $count );
			$total_views += $count;
			$totals_map[ $post_id ] = ( isset( $totals_map[ $post_id ] ) ? $totals_map[ $post_id ] : 0 ) + $count;
		}

		$this->execute_provider_insert_query( $sql, $strategy, 'wp_postviews' );

		$stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $totals_map ),
			'source' => $this->get_provider_label( 'wp_postviews' )
		];

		$this->apply_skip_statistics( $stats, $totals_map, $strategy );

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
		// get provider
		$provider = $this->get_provider( 'statify' );

		// generate description
		$description = $this->generate_provider_description( $provider['supports'] );

		$html = '
		<div class="pvc-provider-fields">
			<p class="description">' . $description . '</p>
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
			$content = $this->map_target_to_content( $row['target'], $tracked_post_types, 'statify' );
			$post_id = isset( $content['content_id'] ) ? (int) $content['content_id'] : 0;
			if ( ! $post_id ) {
				$skipped_targets[] = $row['target'];
				continue;
			}

			$ts = strtotime( $row['created'] );
			$period_keys = $this->get_period_keys_from_timestamp( $ts, $use_gmt );

			if ( isset( $content['content_type'] ) && $content['content_type'] !== 'post' ) {
				/**
				 * Allow to capture provider rows that map to non-post content.
				 *
				 * Returning true from this filter stops default processing for the row.
				 *
				 * @since 1.5.10
				 *
				 * @param bool $handled Whether the row was fully processed.
				 * @param array $context Contextual data about the provider row.
				 * @param Post_Views_Counter_Import $importer Import handler instance.
				 */
				$handled = apply_filters( 'pvc_import_handle_non_post_row', false, [
					'mode' => 'analyze',
					'source' => 'statify',
					'row' => $row,
					'content' => $content,
					'period_keys' => $period_keys,
					'timestamp' => $ts,
					'use_gmt' => $use_gmt
				], $this );

				if ( $handled )
					continue;

				$skipped_targets[] = $row['target'];
				continue;
			}

			$day_key = $period_keys['day'];
			$week_key = $period_keys['week'];
			$month_key = $period_keys['month'];
			$year_key = $period_keys['year'];

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
		$provider_slug = 'statify';
		$provider_label = isset( $this->import_provider_labels[ $provider_slug ] ) ? $this->import_provider_labels[ $provider_slug ] : $provider_slug;

		$message_stats = [
			'total_views' => $total_views,
			'posts_processed' => count( $stats ),
			'periods' => $period_counts,
			'source' => $provider_label
		];

		// add skipped URLs info if any
		if ( ! empty( $skipped_targets ) ) {
			$unique_skipped = array_unique( $skipped_targets );
			$message_stats['additional_info'] = sprintf( __( 'Would skip %s non-post URLs.', 'post-views-counter' ), number_format_i18n( count( $unique_skipped ) ) );
		}

		/**
		 * Allow to adjust provider analysis statistics before displaying the message.
		 *
		 * @since 1.5.10
		 * @since 1.5.11 Provider parameter moved to the third position.
		 *
		 * @param array $message_stats Prepared statistics for the UI.
		 * @param array $context Additional context such as mode/source.
		 */
		$message_stats = apply_filters( 'pvc_import_message_stats', $message_stats, [
			'mode' => 'analyze',
			'source' => 'statify'
		] );

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
				$content = $this->map_target_to_content( $row['target'], $tracked_post_types, 'statify' );
				$post_id = isset( $content['content_id'] ) ? (int) $content['content_id'] : 0;
				if ( ! $post_id ) {
					$skipped_targets[] = $row['target'];
					continue;
				}

				$ts = strtotime( $row['created'] );
				$period_keys = $this->get_period_keys_from_timestamp( $ts, $use_gmt );

				if ( isset( $content['content_type'] ) && $content['content_type'] !== 'post' ) {
					/** This filter is documented above. */
					$handled = apply_filters( 'pvc_import_handle_non_post_row', false, [
						'mode' => 'import',
						'source' => 'statify',
						'row' => $row,
						'content' => $content,
						'period_keys' => $period_keys,
						'timestamp' => $ts,
						'use_gmt' => $use_gmt,
						'strategy' => $strategy
					], $this );

					if ( $handled )
						continue;

					$skipped_targets[] = $row['target'];
					continue;
				}

				$day_key = $period_keys['day'];
				$week_key = $period_keys['week'];
				$month_key = $period_keys['month'];
				$year_key = $period_keys['year'];

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

		$totals_map = [];

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
			$totals_map[ $post_id ] = $periods['total'];
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

		$this->execute_provider_insert_query( $sql_parts, $strategy, 'statify' );

		/**
		 * Fires after a provider's post rows have been inserted.
		 *
		 * @since 1.5.10
		 *
		 * @param array $context {
		 *     @type string $strategy Selected merge strategy.
		 *     @type string $source   Provider slug (statify).
		 *     @type bool   $use_gmt  Whether GMT dates were used.
		 * }
		 */
		do_action( 'pvc_import_after_provider', [
			'strategy' => $strategy,
			'source' => 'statify',
			'use_gmt' => $use_gmt
		] );

		$this->flush_pvc_caches();

		// prepare statistics for message generation
		$provider_slug = 'statify';
		$provider_label = isset( $this->import_provider_labels[ $provider_slug ] ) ? $this->import_provider_labels[ $provider_slug ] : $provider_slug;

		$message_stats = [
			'total_views' => $total_views,
			'posts_processed' => $posts_processed,
			'periods' => $period_counts,
			'source' => $provider_label
		];

		// add skipped URLs info if any
		if ( ! empty( $skipped_targets ) ) {
			$unique_skipped = array_unique( $skipped_targets );
			$message_stats['additional_info'] = sprintf( __( 'Skipped %s non-post URLs.', 'post-views-counter' ), number_format_i18n( count( $unique_skipped ) ) );
		}

		$this->apply_skip_statistics( $message_stats, $totals_map, $strategy );

		/**
		 * Allow to adjust provider import statistics before displaying the message.
		 *
		 * @since 1.5.10
		 *
		 * @param array $stats Prepared statistics for the UI.
		 * @param array $context Additional context such as mode/source.
		 */
		$message_stats = apply_filters( 'pvc_import_message_stats', $message_stats, [
			'mode' => 'import',
			'source' => 'statify'
		] );

		return [
			'success' => true,
			'message' => $this->generate_import_message( $message_stats )
		];
	}

	/**
	 * Check if Page Views Count is available.
	 *
	 * @return bool
	 */
	public function is_page_views_count_available() {
		global $wpdb;
		$table_total = $wpdb->prefix . 'pvc_total';
		$table_daily = $wpdb->prefix . 'pvc_daily';
		return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_total ) ) === $table_total &&
			$wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_daily ) ) === $table_daily;
	}

	/**
	 * Render Page Views Count provider fields.
	 *
	 * @return string
	 */
	public function render_provider_page_views_count() {
		// get provider
		$provider = $this->get_provider( 'page_views_count' );

		// generate description
		$description = $this->generate_provider_description( $provider['supports'] );

		$html = '
		<div class="pvc-provider-fields">
			<p class="description">' . $description . '</p>
		</div>';

		return $html;
	}

	/**
	 * Sanitize Page Views Count provider inputs.
	 *
	 * @param array $inputs
	 * @return array
	 */
	public function sanitize_provider_page_views_count( $inputs ) {
		// no inputs needed for Page Views Count
		return [];
	}

	/**
	 * Analyse Page Views Count provider.
	 *
	 * @global object $wpdb
	 *
	 * @param array $inputs
	 * @return array
	 */
	public function analyse_provider_page_views_count( $inputs ) {
		global $wpdb;

		$table_total = $wpdb->prefix . 'pvc_total';
		$table_daily = $wpdb->prefix . 'pvc_daily';

		$pvc_settings = Post_Views_Counter()->options['general'];
		$tracked_post_types = array_map( 'sanitize_key', (array) $pvc_settings['post_types_count'] );

		$post_types_cache = [];

		// get total views
		$totals = $wpdb->get_results( "SELECT postnum, postcount FROM {$table_total}", ARRAY_A );
		$total_views = 0;
		$valid_posts_total = [];
		foreach ( $totals as $row ) {
			$post_id = (int) $row['postnum'];
			if ( ! isset( $post_types_cache[$post_id] ) ) {
				$post_types_cache[$post_id] = get_post_type( $post_id );
			}
			if ( $post_types_cache[$post_id] && in_array( $post_types_cache[$post_id], $tracked_post_types, true ) ) {
				$total_views += (int) $row['postcount'];
				$valid_posts_total[] = $post_id;
			}
		}

		// get daily views
		$dailies = $wpdb->get_results( "SELECT time, postnum, postcount FROM {$table_daily}", ARRAY_A );
		$daily_views = 0;
		$valid_posts_daily = [];
		foreach ( $dailies as $row ) {
			$post_id = (int) $row['postnum'];
			if ( ! isset( $post_types_cache[$post_id] ) ) {
				$post_types_cache[$post_id] = get_post_type( $post_id );
			}
			if ( $post_types_cache[$post_id] && in_array( $post_types_cache[$post_id], $tracked_post_types, true ) ) {
				$daily_views += (int) $row['postcount'];
				$valid_posts_daily[] = $post_id;
			}
		}

		$posts_processed = count( array_unique( array_merge( $valid_posts_total, $valid_posts_daily ) ) );

		$stats = [
			'total_views' => $total_views,
			'posts_processed' => $posts_processed,
			'source' => $this->get_provider_label( 'page_views_count' ),
		];

		return [
			'count' => $total_views + $daily_views,
			'message' => $this->generate_import_message( $stats, 'analyze' )
		];
	}

	/**
	 * Import Page Views Count provider.
	 *
	 * @global object $wpdb
	 *
	 * @param array $inputs
	 * @param string $strategy
	 * @return array
	 */
	public function import_provider_page_views_count( $inputs, $strategy ) {
		global $wpdb;

		$table_total = $wpdb->prefix . 'pvc_total';
		$table_daily = $wpdb->prefix . 'pvc_daily';

		$pvc_settings = Post_Views_Counter()->options['general'];
		$tracked_post_types = array_map( 'sanitize_key', (array) $pvc_settings['post_types_count'] );

		$post_types_cache = [];

		$results = $wpdb->get_results(
			"SELECT t.postnum, t.postcount AS total, d.time, d.postcount AS daily_count
			FROM {$table_total} AS t
			LEFT JOIN {$table_daily} AS d ON t.postnum = d.postnum
			ORDER BY t.postnum, d.time",
			ARRAY_A
		);

		$sql_parts = [];
		$stats = [];
		$totals_map = [];
		$total_views_imported = 0;

		foreach ( $results as $row ) {
			$post_id = (int) $row['postnum'];

			if ( ! isset( $post_types_cache[ $post_id ] ) ) {
				$post_types_cache[ $post_id ] = get_post_type( $post_id );
			}

			$post_type = $post_types_cache[ $post_id ];

			if ( ! $post_type || ! in_array( $post_type, $tracked_post_types, true ) ) {
				continue;
			}

			if ( isset( $row['total'] ) && ! isset( $stats[ $post_id ]['total_imported'] ) ) {
				$count = (int) $row['total'];
				$total_views_imported += $count;
				$sql_parts[] = $wpdb->prepare( "(%d, 4, 'total', %d)", $post_id, $count );
				$totals_map[ $post_id ] = $count;
				$stats[ $post_id ]['total_imported'] = true;
			}

			if ( empty( $row['time'] ) ) {
				continue;
			}

			$ts = strtotime( $row['time'] . ' 00:00:00' );
			$period_keys = $this->get_period_keys_from_timestamp( $ts, false ); // Use site time as data is in site time

			if ( ! isset( $stats[ $post_id ] ) ) {
				$stats[ $post_id ] = [
					'periods' => [
						'daily' => [],
						'weekly' => [],
						'monthly' => [],
						'yearly' => []
					]
				];
			}

			$stats[ $post_id ]['periods']['daily'][ $period_keys['day'] ] = ( $stats[ $post_id ]['periods']['daily'][ $period_keys['day'] ] ?? 0 ) + (int) $row['daily_count'];
			$stats[ $post_id ]['periods']['weekly'][ $period_keys['week'] ] = ( $stats[ $post_id ]['periods']['weekly'][ $period_keys['week'] ] ?? 0 ) + (int) $row['daily_count'];
			$stats[ $post_id ]['periods']['monthly'][ $period_keys['month'] ] = ( $stats[ $post_id ]['periods']['monthly'][ $period_keys['month'] ] ?? 0 ) + (int) $row['daily_count'];
			$stats[ $post_id ]['periods']['yearly'][ $period_keys['year'] ] = ( $stats[ $post_id ]['periods']['yearly'][ $period_keys['year'] ] ?? 0 ) + (int) $row['daily_count'];
		}

		if ( empty( $sql_parts ) ) {
			return [
				'success' => false,
				'message' => __( 'No valid post data found to import.', 'post-views-counter' )
			];
		}

		$period_counts = [
			'daily' => 0,
			'weekly' => 0,
			'monthly' => 0,
			'yearly' => 0
		];

		foreach ( $stats as $post_id => $data ) {
			foreach ( $data['periods']['daily'] as $period => $count ) {
				$sql_parts[] = $wpdb->prepare( "(%d, 0, %s, %d)", $post_id, $period, $count );
				$period_counts['daily']++;
			}
			foreach ( $data['periods']['weekly'] as $period => $count ) {
				$sql_parts[] = $wpdb->prepare( "(%d, 1, %s, %d)", $post_id, $period, $count );
				$period_counts['weekly']++;
			}
			foreach ( $data['periods']['monthly'] as $period => $count ) {
				$sql_parts[] = $wpdb->prepare( "(%d, 2, %s, %d)", $post_id, $period, $count );
				$period_counts['monthly']++;
			}
			foreach ( $data['periods']['yearly'] as $period => $count ) {
				$sql_parts[] = $wpdb->prepare( "(%d, 3, %s, %d)", $post_id, $period, $count );
				$period_counts['yearly']++;
			}
		}

		$posts_processed = count( array_keys( $stats ) );

		$this->execute_provider_insert_query( $sql_parts, $strategy, 'page_views_count' );

		do_action( 'pvc_import_after_provider', [
			'strategy' => $strategy,
			'source' => 'page_views_count',
			'use_gmt' => false
		] );

		$this->flush_pvc_caches();

		$provider_slug = 'page_views_count';
		$provider_label = isset( $this->import_provider_labels[ $provider_slug ] ) ? $this->import_provider_labels[ $provider_slug ] : $provider_slug;

		$message_stats = [
			'total_views' => $total_views_imported,
			'posts_processed' => $posts_processed,
			'periods' => $period_counts,
			'source' => $provider_label
		];

		$this->apply_skip_statistics( $message_stats, $totals_map, $strategy );

		$message_stats = apply_filters( 'pvc_import_message_stats', $message_stats, [
			'mode' => 'import',
			'source' => 'page_views_count'
		] );

		return [
			'success' => true,
			'message' => $this->generate_import_message( $message_stats )
		];
	}

	/**
	 * Get SQL clause for the provided strategy.
	 *
	 * @param string $strategy
	 * @param string $provider
	 * @return string
	 */
	private function get_strategy_on_duplicate_clause( $strategy, $provider ) {
		$strategy_key = sanitize_key( $strategy );

		$clauses = [
			'override' => 'count = VALUES(count)',
			'merge' => 'count = count + VALUES(count)'
		];

		$clause = isset( $clauses[ $strategy_key ] ) ? $clauses[ $strategy_key ] : $clauses['merge'];

		/**
		 * Filter the SQL ON DUPLICATE KEY UPDATE clause used during import.
		 *
		 * @since 1.5.10
		 *
		 * @param string $clause SQL clause for the duplicate key handler.
		 * @param array  $context {
		 *     @type string $strategy Selected strategy key.
		 *     @type string $provider Provider slug.
		 * }
		 * @param Post_Views_Counter_Import $importer Import handler instance.
		 */
		return apply_filters( 'pvc_import_strategy_clause', $clause, [
			'strategy' => $strategy_key,
			'provider' => $provider
		], $this );
	}

	/**
	 * Execute a provider insert query.
	 *
	 * @param array $sql_parts Prepared SQL value tuples.
	 * @param string $strategy Selected strategy.
	 * @param string $provider Provider slug.
	 * @return int|false Number of rows affected by the query or false when skipped.
	 */
	private function execute_provider_insert_query( $sql_parts, $strategy, $provider ) {
		global $wpdb;

		if ( empty( $sql_parts ) ) {
			return false;
		}

		$on_duplicate = $this->get_strategy_on_duplicate_clause( $strategy, $provider );
		$query = "INSERT INTO {$wpdb->prefix}post_views (id, type, period, count) VALUES " . implode( ',', $sql_parts ) . " ON DUPLICATE KEY UPDATE {$on_duplicate}";

		/**
		 * Filter the SQL query used when inserting provider data.
		 *
		 * Returning false will skip executing the default query. Extensions can run
		 * their custom SQL before returning false.
		 *
		 * @since 1.5.10
		 *
		 * @param string|false $query SQL query string or false to skip execution.
		 * @param array        $context {
		 *     @type string $strategy Selected strategy.
		 *     @type string $provider Provider slug.
		 *     @type array  $sql_parts Prepared SQL tuples.
		 *     @type string $on_duplicate Default ON DUPLICATE KEY clause.
		 * }
		 * @param Post_Views_Counter_Import $importer Import handler instance.
		 */
		$query = apply_filters( 'pvc_import_provider_query', $query, [
			'strategy' => sanitize_key( $strategy ),
			'provider' => $provider,
			'sql_parts' => $sql_parts,
			'on_duplicate' => $on_duplicate
		], $this );

		if ( $query === false ) {
			return false;
		}

		return $wpdb->query( $query );
	}

	/**
	 * Adjust import statistics to include skipped totals information.
	 *
	 * @param array $stats
	 * @param array $totals_map
	 * @param string $strategy
	 * @return void
	 */
	private function apply_skip_statistics( &$stats, $totals_map, $strategy ) {
		if ( empty( $stats ) || empty( $totals_map ) ) {
			return;
		}

		$skip_stats = $this->calculate_total_skip_stats( $totals_map, $strategy );

		if ( empty( $skip_stats ) ) {
			return;
		}

		$stats['skipped'] = $skip_stats;

		if ( isset( $stats['total_views'] ) ) {
			$stats['total_views'] = max( 0, (int) $stats['total_views'] - $skip_stats['views'] );
		}

		if ( isset( $stats['posts_processed'] ) ) {
			$stats['posts_processed'] = max( 0, (int) $stats['posts_processed'] - $skip_stats['posts'] );
		}
	}

	/**
	 * Calculate how many totals will be skipped by the given strategy.
	 *
	 * @param array $totals_map Array of post_id => total_count.
	 * @param string $strategy
	 * @return array
	 */
	private function calculate_total_skip_stats( $totals_map, $strategy ) {
		$strategy = sanitize_key( $strategy );
		$advanced_strategies = [ 'skip_existing', 'keep_higher_count', 'fill_empty_only' ];

		if ( empty( $totals_map ) || ! in_array( $strategy, $advanced_strategies, true ) ) {
			return [];
		}

		$existing = $this->get_existing_total_counts( array_keys( $totals_map ) );
		$skipped_views = 0;
		$skipped_posts = 0;

		foreach ( $totals_map as $post_id => $value ) {
			$current = isset( $existing[ $post_id ] ) ? (int) $existing[ $post_id ] : null;
			$skip = false;

			if ( $strategy === 'skip_existing' && $current !== null ) {
				$skip = true;
			} elseif ( $strategy === 'keep_higher_count' && $current !== null && $current >= $value ) {
				$skip = true;
			} elseif ( $strategy === 'fill_empty_only' && $current !== null && $current > 0 ) {
				$skip = true;
			}

			if ( $skip ) {
				$skipped_views += (int) $value;
				$skipped_posts++;
			}
		}

		if ( $skipped_posts === 0 ) {
			return [];
		}

		return [
			'views' => $skipped_views,
			'posts' => $skipped_posts
		];
	}

	/**
	 * Get existing total counts for selected posts.
	 *
	 * @param array $post_ids
	 * @return array
	 */
	private function get_existing_total_counts( $post_ids ) {
		global $wpdb;

		$post_ids = array_filter( array_map( 'intval', (array) $post_ids ) );
		$post_ids = array_values( array_unique( $post_ids ) );

		if ( empty( $post_ids ) ) {
			return [];
		}

		$existing = [];

		foreach ( array_chunk( $post_ids, 500 ) as $chunk ) {
			$placeholders = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );
			$sql = $wpdb->prepare(
				"SELECT id, count FROM {$wpdb->prefix}post_views WHERE type = 4 AND period = 'total' AND id IN ({$placeholders})",
				$chunk
			);
			$rows = $wpdb->get_results( $sql, ARRAY_A );

			foreach ( (array) $rows as $row ) {
				$existing[ (int) $row['id'] ] = (int) $row['count'];
			}
		}

		return $existing;
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

		$source_label = '';

		if ( ! empty( $stats['source'] ) ) {
			$source_label = $stats['source'];
		}

		if ( $source_label !== '' ) {
			if ( $mode === 'analyze' ) {
				$message_parts[] = sprintf( __( 'Analysis of %s:', 'post-views-counter' ), $source_label );
			} else {
				$message_parts[] = sprintf( __( 'Import from %s:', 'post-views-counter' ), $source_label );
			}
		}

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

		// additional info (like skipped URLs)
		if ( ! empty( $stats['additional_info'] ) ) {
			$message_parts[] = $stats['additional_info'];
		}

		if ( isset( $stats['skipped']['views'], $stats['skipped']['posts'] ) ) {
			$message_parts[] = sprintf(
				__( 'Skipped %1$s total views for %2$s posts.', 'post-views-counter' ),
				number_format_i18n( $stats['skipped']['views'] ),
				number_format_i18n( $stats['skipped']['posts'] )
			);
		}

		return implode( ' ', $message_parts );
	}

	/**
	 * Map provider target URL to content data.
	 *
	 * @param string $target
	 * @param array $tracked_post_types
	 * @param string $provider
	 * @return array
	 */
	private function map_target_to_content( $target, $tracked_post_types, $provider ) {
		$content = [
			'content_type' => 'post',
			'content_id' => $this->map_target_to_post_id( $target, $tracked_post_types )
		];

		/**
		 * Filter provider content mapping so it's possible to process non-post URLs.
		 *
		 * @since 1.5.10
		 *
		 * @param array $content {
		 *     @type string $content_type Resolved content type.
		 *     @type int    $content_id   Target identifier.
		 * }
		 * @param string $target Target path recorded by the provider.
		 * @param string $provider Current import provider slug.
		 * @param array  $tracked_post_types Allowed post types for PVC.
		 * @param Post_Views_Counter_Import $importer Import handler instance.
		 */
		return apply_filters( 'pvc_import_map_target_to_content', $content, $target, $provider, $tracked_post_types, $this );
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

	/**
	 * Retrieve the human readable label for a provider.
	 *
	 * @param string $slug
	 * @return string
	 */
	private function get_provider_label( $slug ) {
		if ( isset( $this->import_provider_labels[ $slug ] ) ) {
			return $this->import_provider_labels[ $slug ];
		}

		return ucwords( str_replace( '_', ' ', $slug ) );
	}

	/**
	 * Build period keys for a timestamp.
	 *
	 * @param int $timestamp
	 * @param bool $use_gmt
	 * @return array
	 */
	private function get_period_keys_from_timestamp( $timestamp, $use_gmt ) {
		$date = $use_gmt ? gmdate( 'W-d-m-Y-o', $timestamp ) : date( 'W-d-m-Y-o', $timestamp );
		$parts = explode( '-', $date );

		return [
			'day' => $parts[3] . $parts[2] . $parts[1],
			'week' => $parts[4] . $parts[0],
			'month' => $parts[3] . $parts[2],
			'year' => $parts[3]
		];
	}
}

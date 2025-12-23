<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Integration_Gutenberg class.
 *
 * Handles Gutenberg block editor integration for ordering posts by views.
 *
 * @class Post_Views_Counter_Integration_Gutenberg
 */
class Post_Views_Counter_Integration_Gutenberg {
	/**
	 * Stack of active Latest Posts blocks using post_views ordering.
	 *
	 * @var array
	 */
	private $latest_posts_stack = [];

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize integration hooks.
	 *
	 * @return void
	 */
	public function init() {
		// bail if integration is disabled
		if ( ! Post_Views_Counter_Integrations::is_integration_enabled( 'gutenberg' ) )
			return;

		// frontend query filters
		add_filter( 'query_loop_block_query_vars', [ $this, 'query_loop_block_query_vars' ], 10, 3 );
		add_filter( 'render_block_data', [ $this, 'render_block_data_latest_posts' ], 10, 2 );
		add_filter( 'render_block', [ $this, 'render_block_latest_posts_cleanup' ], 10, 2 );
		add_action( 'pre_get_posts', [ $this, 'modify_latest_posts_query' ], 10, 1 );

		// REST API support for editor preview
		add_action( 'rest_api_init', [ $this, 'register_rest_orderby' ] );

		// enqueue block editor assets
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
	}

	/**
	 * Filter Query Loop block query vars to add post views ordering.
	 *
	 * @param array $query
	 * @param WP_Block $block
	 * @param int $page
	 *
	 * @return array
	 */
	public function query_loop_block_query_vars( $query, $block, $page ) {
		// check if orderBy is set to post_views in the Query block
		if ( empty( $block->context['query']['orderBy'] ) || $block->context['query']['orderBy'] !== 'post_views' )
			return $query;

		// set orderby to post_views
		$query['orderby'] = 'post_views';

		// handle include zero views setting (default: true)
		// check for custom attribute first, fallback to true
		$include_zero_views = true;

		if ( isset( $block->context['query']['pvcIncludeZeroViews'] ) )
			$include_zero_views = (bool) $block->context['query']['pvcIncludeZeroViews'];

		if ( ! isset( $query['views_query'] ) )
			$query['views_query'] = [];

		$query['views_query']['hide_empty'] = ! $include_zero_views;

		return $query;
	}

	/**
	 * Track Latest Posts blocks before rendering to adjust query parameters.
	 *
	 * @param array $parsed_block
	 * @param array $source_block
	 *
	 * @return array
	 */
	public function render_block_data_latest_posts( $parsed_block, $source_block ) {
		// only process Latest Posts block
		if ( empty( $parsed_block['blockName'] ) || $parsed_block['blockName'] !== 'core/latest-posts' )
			return $parsed_block;

		// check if orderBy is set to post_views
		if ( empty( $parsed_block['attrs']['orderBy'] ) || $parsed_block['attrs']['orderBy'] !== 'post_views' )
			return $parsed_block;

		// handle include zero views setting (default: true)
		$include_zero_views = true;

		if ( isset( $parsed_block['attrs']['pvcIncludeZeroViews'] ) )
			$include_zero_views = (bool) $parsed_block['attrs']['pvcIncludeZeroViews'];

		$this->latest_posts_stack[] = [
			'include_zero_views' => $include_zero_views
		];

		return $parsed_block;
	}

	/**
	 * Clear Latest Posts block context after rendering.
	 *
	 * @param string $block_content
	 * @param array $block
	 *
	 * @return string
	 */
	public function render_block_latest_posts_cleanup( $block_content, $block ) {
		if ( empty( $block['blockName'] ) || $block['blockName'] !== 'core/latest-posts' )
			return $block_content;

		if ( empty( $block['attrs']['orderBy'] ) || $block['attrs']['orderBy'] !== 'post_views' )
			return $block_content;

		array_pop( $this->latest_posts_stack );

		return $block_content;
	}

	/**
	 * Modify WP_Query for Latest Posts block to add post views ordering.
	 *
	 * @param WP_Query $query
	 *
	 * @return void
	 */
	public function modify_latest_posts_query( $query ) {
		if ( empty( $this->latest_posts_stack ) )
			return;

		// only modify if orderby is already set to post_views
		if ( empty( $query->query_vars['orderby'] ) || $query->query_vars['orderby'] !== 'post_views' )
			return;

		// mark query for Post_Views_Counter_Query to handle
		$query->pvc_orderby = true;

		// handle include zero views setting (default: true)
		$context = end( $this->latest_posts_stack );
		$include_zero_views = isset( $context['include_zero_views'] ) ? (bool) $context['include_zero_views'] : true;

		if ( ! isset( $query->query_vars['views_query'] ) )
			$query->query_vars['views_query'] = [];

		$query->query_vars['views_query']['hide_empty'] = ! $include_zero_views;
		$query->query['views_query'] = $query->query_vars['views_query'];
	}

	/**
	 * Register REST API support for post_views orderby.
	 *
	 * @return void
	 */
	public function register_rest_orderby() {
		// get main instance
		$pvc = Post_Views_Counter();

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		if ( empty( $post_types ) )
			return;

		foreach ( $post_types as $post_type ) {
			// register post_views as valid orderby parameter
			add_filter( "rest_{$post_type}_collection_params", [ $this, 'rest_collection_params' ], 10, 1 );

			// modify query when orderby=post_views
			add_filter( "rest_{$post_type}_query", [ $this, 'rest_query' ], 10, 2 );
		}
	}

	/**
	 * Add post_views to REST API collection parameters.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function rest_collection_params( $params ) {
		if ( isset( $params['orderby']['enum'] ) && is_array( $params['orderby']['enum'] ) ) {
			$params['orderby']['enum'][] = 'post_views';
		}

		return $params;
	}

	/**
	 * Modify REST API query for post_views orderby.
	 *
	 * @param array $args
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function rest_query( $args, $request ) {
		if ( $request->get_param( 'orderby' ) !== 'post_views' )
			return $args;

		// set orderby
		$args['orderby'] = 'post_views';

		// handle include zero views (default: true for consistency)
		if ( ! isset( $args['views_query'] ) )
			$args['views_query'] = [];

		// check for custom parameter (camelCase used by Query Loop restQueryArgs)
		$include_zero_views = $request->get_param( 'pvc_include_zero_views' );

		if ( $include_zero_views === null )
			$include_zero_views = $request->get_param( 'pvcIncludeZeroViews' );

		if ( $include_zero_views !== null )
			$args['views_query']['hide_empty'] = ! rest_sanitize_boolean( $include_zero_views );
		else
			$args['views_query']['hide_empty'] = false; // default: include zero views

		return $args;
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// check if JS file exists
		$js_file = POST_VIEWS_COUNTER_PATH . 'js/integration-gutenberg.js';

		if ( ! file_exists( $js_file ) )
			return;

		// get main instance
		$pvc = Post_Views_Counter();
		$version = $pvc->defaults['version'];

		$file_version = @filemtime( $js_file );
		if ( $file_version )
			$version = $file_version;

		// enqueue script
		wp_enqueue_script(
			'pvc-integration-gutenberg',
			POST_VIEWS_COUNTER_URL . '/js/integration-gutenberg.js',
			[ 'wp-element', 'wp-components', 'wp-block-editor', 'wp-hooks', 'wp-compose', 'wp-data', 'wp-i18n' ],
			$version,
			true
		);

		// add inline script with configuration
		wp_add_inline_script(
			'pvc-integration-gutenberg',
			'var pvcGutenbergIntegration = ' . wp_json_encode( [
				'enabled' => true,
				'defaultIncludeZeroViews' => true
			] ) . ';',
			'before'
		);
	}
}

new Post_Views_Counter_Integration_Gutenberg();

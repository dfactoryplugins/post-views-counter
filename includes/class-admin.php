<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Admin class.
 *
 * @class Post_Views_Counter_Admin
 */
class Post_Views_Counter_Admin {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'plugins_loaded', [ $this, 'init_block_editor' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_chartjs' ], 9 );
	}

	/**
	 * Register Chart.js.
	 *
	 * @return void
	 */
	public function register_chartjs() {
		wp_register_script( 'pvc-chartjs', POST_VIEWS_COUNTER_URL . '/assets/chartjs/chart.min.js', [ 'jquery' ], '4.4.0', true );
	}

	/**
	 * Init block editor actions.
	 *
	 * @return void
	 */
	public function init_block_editor() {
		add_action( 'rest_api_init', [ $this, 'block_editor_rest_api_init' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'block_editor_enqueue_scripts' ] );
	}

	/**
	 * Register REST API block editor endpoints.
	 *
	 * @return void
	 */
	public function block_editor_rest_api_init() {
		// get views route
		register_rest_route(
			'post-views-counter',
			'/update-post-views/',
			[
				'methods'				=> [ 'POST' ],
				'callback'				=> [ $this, 'block_editor_update_callback' ],
				'permission_callback'	=> [ $this, 'check_rest_route_permissions' ],
				'args'					=> [
					'id' => [
						'sanitize_callback'	=> 'absint',
					]
				]
			]
		);
	}

	/**
	 * Check whether user has permissions to perform post views update in block editor.
	 *
	 * @param object $request WP_REST_Request
	 * @return bool|WP_Error
	 */
	public function check_rest_route_permissions( $request ) {
		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', (int) $request->get_param( 'id' ) ) )
			return new WP_Error( 'pvc-user-not-allowed', __( 'You are not allowed to edit this item.', 'post-views-counter' ) );

		// break if views editing is restricted
		if ( (bool) Post_Views_Counter()->options['general']['restrict_edit_views'] === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			return new WP_Error( 'pvc-user-not-allowed', __( 'You are not allowed to edit this item.', 'post-views-counter' ) );

		return true;
	}

	/**
	 * REST API callback for block editor endpoint.
	 *
	 * @param array $data
	 * @return string|int
	 */
	public function block_editor_update_callback( $data ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// cast post ID
		$post_id = ! empty( $data['id'] ) ? (int) $data['id'] : 0;

		// cast post views
		$post_views = ! empty( $data['post_views'] ) ? (int) $data['post_views'] : 0;

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// check if post exists
		$post = get_post( $post_id );

		// whether to count this post type or not
		if ( empty( $post_types ) || empty( $post ) || ! in_array( $post->post_type, $post_types, true ) )
			return wp_send_json_error( __( 'Invalid post ID.', 'post-views-counter' ) );

		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return wp_send_json_error( __( 'You are not allowed to edit this item.', 'post-views-counter' ) );

		// break if views editing is restricted
		if ( (bool) $pvc->options['general']['restrict_edit_views'] === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			return wp_send_json_error( __( 'You are not allowed to edit this item.', 'post-views-counter' ) );

		// update post views
		pvc_update_post_views( $post_id, $post_views );

		do_action( 'pvc_after_update_post_views_count', $post_id );

		return $post_id;
	}

	/**
	 * Enqueue frontend and editor JavaScript and CSS.
	 *
	 * @global string $pagenow
	 * @global string $wp_version
	 *
	 * @return void
	 */
	public function block_editor_enqueue_scripts() {
		global $pagenow, $wp_version;

		// get main instance
		$pvc = Post_Views_Counter();

		// skip widgets and customizer pages
		if ( $pagenow === 'widgets.php' || $pagenow === 'customize.php' )
			return;

		// enqueue the bundled block JS file
		wp_enqueue_script( 'pvc-block-editor', POST_VIEWS_COUNTER_URL . '/js/block-editor.min.js', [ 'wp-element', 'wp-components', 'wp-edit-post', 'wp-data', 'wp-plugins' ], $pvc->defaults['version'] );

		// restrict editing
		$restrict = (bool) $pvc->options['general']['restrict_edit_views'];

		// prepare script data
		$script_data = [
			'postID'		=> get_the_ID(),
			'postViews'		=> pvc_get_post_views( get_the_ID() ),
			'canEdit'		=> ( $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) ) ),
			'nonce'			=> wp_create_nonce( 'wp_rest' ),
			'wpGreater53'	=> version_compare( $wp_version, '5.3', '>=' ),
			'textPostViews'	=> esc_html__( 'Post Views', 'post-views-counter' ),
			'textHelp'		=> esc_html__( 'Adjust the views count for this post.', 'post-views-counter' ),
			'textCancel'	=> esc_html__( 'Cancel', 'post-views-counter' )
		];

		wp_add_inline_script( 'pvc-block-editor', 'var pvcEditorArgs = ' . wp_json_encode( $script_data ) . ";\n", 'before' );

		// enqueue frontend and editor block styles
		wp_enqueue_style( 'pvc-block-editor', POST_VIEWS_COUNTER_URL . '/css/block-editor.min.css', '', $pvc->defaults['version'] );
	}
}

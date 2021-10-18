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
		// cast post ID
		$post_id = ! empty( $data['id'] ) ? (int) $data['id'] : 0;

		// cast post views
		$post_views = ! empty( $data['post_views'] ) ? (int) $data['post_views'] : 0;

		// get countable post types
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		// check if post exists
		$post = get_post( $post_id );

		// whether to count this post type or not
		if ( empty( $post_types ) || empty( $post ) || ! in_array( $post->post_type, $post_types, true ) )
			return wp_send_json_error( __( 'Invalid post ID.', 'post-views-counter' ) );

		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return wp_send_json_error( __( 'You are not allowed to edit this item.', 'post-views-counter' ) );

		// break if views editing is restricted
		if ( (bool) Post_Views_Counter()->options['general']['restrict_edit_views'] === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			return wp_send_json_error( __( 'You are not allowed to edit this item.', 'post-views-counter' ) );

		// update post views
		pvc_update_post_views( $post_id, $post_views );

		do_action( 'pvc_after_update_post_views_count', $post_id );

		return $post_id;
	}

	/**
	 * Enqueue frontend and editor JavaScript and CSS.
	 *
	 * @return void
	 */
	public function block_editor_enqueue_scripts() {
		global $pagenow, $wp_version;

		// skip widgets and customizer pages
		if ( $pagenow === 'widgets.php' || $pagenow === 'customize.php' )
			return;

		// enqueue the bundled block JS file
		wp_enqueue_script( 'pvc-block-editor', POST_VIEWS_COUNTER_URL . '/js/block-editor.min.js', [ 'wp-element', 'wp-components', 'wp-edit-post', 'wp-data', 'wp-plugins' ], Post_Views_Counter()->defaults['version'] );

		// restrict editing
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

		wp_localize_script(
			'pvc-block-editor',
			'pvcEditorArgs',
			[
				'postID'		=> get_the_ID(),
				'postViews'		=> pvc_get_post_views( get_the_ID() ),
				'canEdit'		=> ( $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) ) ),
				'nonce'			=> wp_create_nonce( 'wp_rest' ),
				'wpGreater53'	=> version_compare( $wp_version, '5.3', '>=' ),
				'textPostViews'	=> __( 'Post Views', 'post-views-counter' ),
				'textHelp'		=> __( 'Adjust the views count for this post.', 'post-views-counter' ),
				'textCancel'	=> __( 'Cancel', 'post-views-counter' )
			]
		);

		// enqueue frontend and editor block styles
		wp_enqueue_style( 'pvc-block-editor', POST_VIEWS_COUNTER_URL . '/css/block-editor.min.css', '', Post_Views_Counter()->defaults['version'] );
	}
}
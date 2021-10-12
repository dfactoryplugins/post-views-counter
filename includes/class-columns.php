<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Columns class.
 * 
 * @class Post_Views_Counter_Columns
 */
class Post_Views_Counter_Columns {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'admin_init', [ $this, 'register_new_column' ] );
		add_action( 'post_submitbox_misc_actions', [ $this, 'submitbox_views' ] );
		add_action( 'attachment_submitbox_misc_actions', [ $this, 'submitbox_views' ] );
		add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
		add_action( 'edit_attachment', [ $this, 'save_post' ], 10 );
		add_action( 'bulk_edit_custom_box', [ $this, 'quick_edit_custom_box' ], 10, 2 );
		add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_custom_box' ], 10, 2 );
		add_action( 'wp_ajax_save_bulk_post_views', [ $this, 'save_bulk_post_views' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 100 );
		add_action( 'wp', [ $this, 'admin_bar_maybe_add_style' ] );
		add_action( 'admin_init', [ $this, 'admin_bar_maybe_add_style' ] );
		add_action( 'plugins_loaded', [ $this, 'init_gutenberg' ] );
	}

	/**
	 * Init block editor actions.
	 *
	 * @return void
	 */
	public function init_gutenberg() {
		$block_editor = has_action( 'enqueue_block_assets' );
		$gutenberg = function_exists( 'gutenberg_can_edit_post_type' );

		if ( ! $block_editor && ! $gutenberg  )
			return;

		add_action( 'add_meta_boxes', [ $this, 'gutenberg_add_meta_box' ] );
		add_action( 'rest_api_init', [ $this, 'gutenberg_rest_api_init' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'gutenberg_enqueue_scripts' ] );
	}

	/**
	 * Register block editor metabox.
	 *
	 * @return void
	 */
	public function gutenberg_add_meta_box() {
		add_meta_box(
			'post_views_meta_box',
			__( 'Post Views', 'post-views-counter' ),
			'',
			'post',
			'',
			'',
			[
				'__back_compat_meta_box'				=> false,
				'__block_editor_compatible_meta_box'	=> true
			]
		);
	}

	/**
	 * Register REST API block editor endpoints.
	 *
	 * @return void
	 */
	public function gutenberg_rest_api_init() {
		// get views route
		register_rest_route(
			'post-views-counter',
			'/update-post-views/',
			[
				'methods'				=> [ 'POST' ],
				'callback'				=> [ $this, 'gutenberg_update_callback' ],
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
	public function gutenberg_update_callback( $data ) {
		// cast ID
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

		global $wpdb;

		pvc_update_post_views( $post_id, $post_views );

		do_action( 'pvc_after_update_post_views_count', $post_id );

		return $post_id;
	}

	/**
	 * Enqueue front end and editor JavaScript and CSS.
	 *
	 * @return void
	 */
	public function gutenberg_enqueue_scripts() {
		// enqueue the bundled block JS file
		wp_enqueue_script(
			'pvc-gutenberg',
			POST_VIEWS_COUNTER_URL . '/js/gutenberg.min.js',
			[ 'wp-i18n', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-api' ],
			Post_Views_Counter()->defaults['version']
		);

		// restrict editing
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

		$js_args = [
			'postID'		=> get_the_ID(),
			'postViews'		=> pvc_get_post_views( get_the_ID() ),
			'canEdit'		=> ( $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) ) ),
			'nonce'			=> wp_create_nonce( 'wp_rest' ),
			'textPostViews'	=> __( 'Post Views', 'post-views-counter' ),
			'textHelp'		=> __( 'Adjust the views count for this post.', 'post-views-counter' ),
			'textCancel'	=> __( 'Cancel', 'post-views-counter' )
		];

		wp_localize_script(
			'pvc-gutenberg',
			'pvcEditorArgs',
			$js_args
		);

		// enqueue frontend and editor block styles
		wp_enqueue_style(
			'pvc-gutenberg', 
			POST_VIEWS_COUNTER_URL . '/css/gutenberg.min.css', '', 
			Post_Views_Counter()->defaults['version']
		);
	}

	/**
	 * Output post views for single post.
	 *
	 * @global object $post
	 * @return void 
	 */
	public function submitbox_views() {
		global $post;

		// incorrect post type?
		if ( ! in_array( $post->post_type, (array) Post_Views_Counter()->options['general']['post_types_count'] ) )
			return;

		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		// get total post views
		$count = (int) pvc_get_post_views( $post->ID ); ?>

		<div class="misc-pub-section" id="post-views">

			<?php wp_nonce_field( 'post_views_count', 'pvc_nonce' ); ?>

			<span id="post-views-display">
				<?php echo __( 'Post Views', 'post-views-counter' ) . ': <b>' . number_format_i18n( $count ) . '</b>'; ?>
			</span>

			<?php
			// restrict editing
			$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

			if ( $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) ) ) {
				?>
				<a href="#post-views" class="edit-post-views hide-if-no-js"><?php _e( 'Edit', 'post-views-counter' ); ?></a>

				<div id="post-views-input-container" class="hide-if-js">

					<p><?php _e( 'Adjust the views count for this post.', 'post-views-counter' ); ?></p>
					<input type="hidden" name="current_post_views" id="post-views-current" value="<?php echo $count; ?>" />
					<input type="text" name="post_views" id="post-views-input" value="<?php echo $count; ?>"/><br />
					<p>
						<a href="#post-views" class="save-post-views hide-if-no-js button"><?php _e( 'OK', 'post-views-counter' ); ?></a>
						<a href="#post-views" class="cancel-post-views hide-if-no-js"><?php _e( 'Cancel', 'post-views-counter' ); ?></a>
					</p>

				</div>
				<?php
			}
			?>

		</div>
		<?php
	}

	/**
	 * Save post views data.
	 *
	 * @param int $post_id
	 * @param object $post
	 * @return int
	 */
	public function save_post( $post_id, $post = null ) {
		// break if doing autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return $post_id;

		// is post views set
		if ( ! isset( $_POST['post_views'] ) )
			return $post_id;

		// cast numeric post views
		$post_views = (int) $_POST['post_views'];

		// unchanged post views value?
		if ( isset( $_POST['current_post_views'] ) && $post_views === (int) $_POST['current_post_views'] )
			return $post_id;

		// break if post views in not one of the selected
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		if ( is_null( $post ) )
			$post_type = get_post_type( $post_id );
		else
			$post_type = $post->post_type;

		if ( ! in_array( $post_type, (array) $post_types ) )
			return $post_id;

		// break if views editing is restricted
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

		if ( $restrict === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			return $post_id;

		// validate data
		if ( ! isset( $_POST['pvc_nonce'] ) || ! wp_verify_nonce( $_POST['pvc_nonce'], 'post_views_count' ) )
			return $post_id;

		pvc_update_post_views( $post_id, $post_views );

		do_action( 'pvc_after_update_post_views_count', $post_id );
	}

	/**
	 * Register post views column for specific post types.
	 *
	 * @return void
	 */
	public function register_new_column() {
		// get post types
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		// any post types?
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				if ( $post_type === 'attachment' ) {
					// actions
					add_action( 'manage_media_custom_column', [ $this, 'add_new_column_content' ], 10, 2 );

					// filters
					add_filter( 'manage_media_columns', [ $this, 'add_new_column' ] );
					add_filter( 'manage_upload_sortable_columns', [ $this, 'register_sortable_custom_column' ] );
				} else {
					// actions
					add_action( 'manage_' . $post_type . '_posts_custom_column', [ $this, 'add_new_column_content' ], 10, 2 );

					// filters
					add_filter( 'manage_' . $post_type . '_posts_columns', [ $this, 'add_new_column' ] );
					add_filter( 'manage_edit-' . $post_type . '_sortable_columns', [ $this, 'register_sortable_custom_column' ] );

					// bbPress?
					if ( class_exists( 'bbPress' ) ) {
						if ( $post_type === 'forum' )
							add_filter( 'bbp_admin_forums_column_headers', [ $this, 'add_new_column' ] );
						elseif ( $post_type === 'topic' )
							add_filter( 'bbp_admin_topics_column_headers', [ $this, 'add_new_column' ] );
					}
				}
			}
		}
	}

	/**
	 * Register sortable post views column.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function register_sortable_custom_column( $columns ) {
		// add new sortable column
		$columns['post_views'] = 'post_views';

		return $columns;
	}

	/**
	 * Add post views column.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function add_new_column( $columns ) {
		$offset = 0;

		if ( isset( $columns['date'] ) )
			$offset++;

		if ( isset( $columns['comments'] ) )
			$offset++;

		if ( $offset > 0 ) {
			$date = array_slice( $columns, -$offset, $offset, true );

			foreach ( $date as $column => $name ) {
				unset( $columns[$column] );
			}

			$columns['post_views'] = '<span class="dash-icon dashicons dashicons-chart-bar" title="' . __( 'Post Views', 'post-views-counter' ) . '"></span><span class="dash-title">' . __( 'Post Views', 'post-views-counter' ) . '</span>';

			foreach ( $date as $column => $name ) {
				$columns[$column] = $name;
			}
		} else
			$columns['post_views'] = '<span class="dash-icon dashicons dashicons-chart-bar" title="' . __( 'Post Views', 'post-views-counter' ) . '"></span><span class="dash-title">' . __( 'Post Views', 'post-views-counter' ) . '</span>';

		return $columns;
	}

	/**
	 * Add post views column content.
	 *
	 * @param string $column_name
	 * @param int $id
	 * @return void
	 */
	public function add_new_column_content( $column_name, $id ) {
		if ( $column_name === 'post_views' ) {
			// get total post views
			$count = pvc_get_post_views( $id );

			echo $count;
		}
	}

	/**
	 * Handle quick edit.
	 *
	 * @global string $pagenow
	 * @param string $column_name
	 * @param string $post_type
	 * @return void
	 */
	function quick_edit_custom_box( $column_name, $post_type ) {
		global $pagenow;

		if ( $pagenow !== 'edit.php' )
			return;

		if ( $column_name !== 'post_views' )
			return;

		if ( ! Post_Views_Counter()->options['general']['post_views_column'] || ! in_array( $post_type, Post_Views_Counter()->options['general']['post_types_count'] ) )
			return;

		// break if views editing is restricted
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

		if ( $restrict === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			return;

		?>
		<fieldset class="inline-edit-col-left">
			<div id="inline-edit-post_views" class="inline-edit-col">
				<label class="inline-edit-group">
					<span class="title"><?php _e( 'Post Views', 'post-views-counter' ); ?></span>
					<span class="input-text-wrap"><input type="text" name="post_views" class="title text" value=""></span>
					<input type="hidden" name="current_post_views" value="" />
					<?php wp_nonce_field( 'post_views_count', 'pvc_nonce' ); ?>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Bulk save post views.
	 *
	 * @global object $wpdb;
	 * @return void
	 */
	function save_bulk_post_views() {
		if ( ! isset( $_POST['post_views'] ) )
			$count = null;
		else {
			$count = trim( $_POST['post_views'] );

			if ( is_numeric( $_POST['post_views'] ) ) {
				$count = (int) $_POST['post_views'];

				if ( $count < 0 )
					$count = 0;
			} else
				$count = null;
		}

		// check post IDs
		$post_ids = ( ! empty( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) ? array_map( 'absint', $_POST['post_ids'] ) : [];

		if ( is_null( $count ) )
			exit;

		// break if views editing is restricted
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

		if ( $restrict === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			exit;

		// any post IDs?
		if ( ! empty( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {

				// break if current user can't edit this post
				if ( ! current_user_can( 'edit_post', $post_id ) )
					continue;

				global $wpdb;

				// insert or update db post views count
				$wpdb->query(
					$wpdb->prepare( "
						INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count)
						VALUES (%d, %d, %s, %d)
						ON DUPLICATE KEY UPDATE count = %d", $post_id, 4, 'total', $count, $count
					)
				);
			}
		}

		exit;
	}

	/**
	 * Add admin bar stats to a post.
	 *
	 * @return void
	 */
	public function admin_bar_menu( $admin_bar ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// statistics enabled?
		if ( ! apply_filters( 'pvc_display_toolbar_statistics', $pvc->options['display']['toolbar_statistics'] ) )
			return;

		$post = null;

		if ( is_admin() && ! wp_doing_ajax() ) {
			global $pagenow;

			$post = $pagenow == 'post.php' && ! empty( $_GET['post'] ) ? get_post( (int) $_GET['post'] ) : $post;
		} elseif ( is_singular() )
			global $post;

		// get countable post types
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		// whether to count this post type or not
		if ( empty( $post_types ) || empty( $post ) || ! in_array( $post->post_type, $post_types, true ) )
			return;

		$dt = new DateTime();

		// get post views
		$views = pvc_get_views(
			[
				'post_id'		=> $post->ID,
				'post_type'		=> $post->post_type,
				'fields'		=> 'date=>views',
				'views_query'	=> [
					'year'	=> $dt->format( 'Y' ),
					'month'	=> $dt->format( 'm' )
				]
			]
		);

		$graph = '';

		// get highest value
		$views_copy = $views;

		arsort( $views_copy, SORT_NUMERIC );

		$highest = reset( $views_copy );

		// find the multiplier
		$multiplier = $highest * 0.05;

		// generate ranges
		$ranges = [];

		for ( $i = 1; $i <= 20; $i ++  ) {
			$ranges[$i] = round( $multiplier * $i );
		}

		// create graph
		foreach ( $views as $date => $count ) {
			$count_class = 0;

			if ( $count > 0 ) {
				foreach ( $ranges as $index => $range ) {
					if ( $count <= $range ) {
						$count_class = $index;
						break;
					}
				}
			}

			$graph .= '<span class="pvc-line-graph pvc-line-graph-' . $count_class . '" title="' . sprintf( _n( '%s post view', '%s post views', $count, 'post-views-counter' ), number_format_i18n( $count ) ) . '"></span>';
		}

		$admin_bar->add_menu(
			[
				'id'	=> 'pvc-post-views',
				'title'	=> '<span class="pvc-graph-container">' . $graph . '</span>',
				'href'	=> false,
				'meta'	=> [
					'title' => false
				]
			]
		);
	}

	/**
	 * Maybe add admin CSS.
	 *
	 * @return void
	 */
	public function admin_bar_maybe_add_style() {
		// get main instance
		$pvc = Post_Views_Counter();

		// statistics enabled?
		if ( ! $pvc->options['display']['toolbar_statistics'] )
			return;

		$post = null;

		if ( is_admin() && ! wp_doing_ajax() ) {
			global $pagenow;

			$post = ( $pagenow === 'post.php' && ! empty( $_GET['post'] ) ) ? get_post( (int) $_GET['post'] ) : $post;
		} elseif ( is_singular() )
			global $post;

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// whether to count this post type or not
		if ( empty( $post_types ) || empty( $post ) || ! in_array( $post->post_type, $post_types, true ) )
			return;

		// on backend area
		add_action( 'admin_head', [ $this, 'admin_bar_css' ] );

		// on frontend area
		add_action( 'wp_head', [ $this, 'admin_bar_css' ] );
	}

	/**
	 * Add admin CSS.
	 *
	 * @return void
	 */
	public function admin_bar_css() {
		$html = '
		<style type="text/css">
			#wp-admin-bar-pvc-post-views .pvc-graph-container { padding-top: 6px; padding-bottom: 6px; position: relative; display: block; height: 100%; box-sizing: border-box; }
			#wp-admin-bar-pvc-post-views .pvc-line-graph {
				display: inline-block;
				width: 1px;
				margin-right: 1px;
				background-color: #ccc;
				vertical-align: baseline;
			}
			#wp-admin-bar-pvc-post-views .pvc-line-graph:hover { background-color: #eee; }
			#wp-admin-bar-pvc-post-views .pvc-line-graph-0 { height: 1% }';

		for ( $i = 1; $i <= 20; $i ++  ) {
			$html .= '
			#wp-admin-bar-pvc-post-views .pvc-line-graph-' . $i . ' { height: ' . $i * 5 . '% }';
		}

		$html .= '
		</style>';

		echo $html;
	}
}
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

	public function __construct() {
		// actions
		add_action( 'admin_init', array( $this, 'register_new_column' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'submitbox_views' ) );
		add_action( 'attachment_submitbox_misc_actions', array( $this, 'submitbox_views' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'edit_attachment', array( $this, 'save_post' ), 10 );
		add_action( 'bulk_edit_custom_box', array( $this, 'quick_edit_custom_box' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_custom_box' ), 10, 2 );
		add_action( 'wp_ajax_save_bulk_post_views', array( $this, 'save_bulk_post_views' ) );

		// gutenberg
		add_action( 'plugins_loaded', array( $this, 'init_gutemberg' ) );
	}
	
	/**
	 * Init Gutenberg
	 */
	public function init_gutemberg() {
		$block_editor = has_action( 'enqueue_block_assets' );
		$gutenberg = function_exists( 'gutenberg_can_edit_post_type' );
		
		if ( ! $block_editor && ! $gutenberg  ) {
			return;
		}
		
		add_action( 'add_meta_boxes', array( $this, 'gutenberg_add_meta_box' ) );
		add_action( 'rest_api_init', array( $this, 'gutenberg_rest_api_init' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'gutenberg_enqueue_scripts' ) );
	}
	
	/**
	 * Register Gutenberg Metabox.
	 */
	public function gutenberg_add_meta_box() {
		add_meta_box( 'post_views_meta_box', __( 'Post Views', 'post-views-counter' ), '', 'post', '', '', array(
			'__back_compat_meta_box' => false,
			'__block_editor_compatible_meta_box' => true
		) );
	}
	
	/**
	 * Register REST API Gutenberg endpoints.
	 */
	public function gutenberg_rest_api_init() {
		// get views route
		register_rest_route(
			'post-views-counter',
			'/update-post-views/',
			array(
				'methods'	=> array( 'POST' ),
				'callback'	=> array( $this, 'gutenberg_update_callback' ),
				'args'		=> array(
					'id' => array(
						'sanitize_callback' => 'absint',
					)
				)
			)
		);
	}
	
	/**
	 * REST API Callback for Gutenberg endpoint.
	 * 
	 * @param array $data
	 * @return array|int
	 */
	public function gutenberg_update_callback( $data ) {
		$post_id = ! empty( $data['id'] ) ? (int) $data['id'] : 0;
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
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

		if ( $restrict === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			return wp_send_json_error( __( 'You are not allowed to edit this item.', 'post-views-counter' ) );

		global $wpdb;

		pvc_update_post_views( $post_id, $post_views );

		do_action( 'pvc_after_update_post_views_count', $post_id );

		return $post_id;
	}
	
	/**
	 * Enqueue front end and editor JavaScript and CSS
	 */
	public function gutenberg_enqueue_scripts() {
		// enqueue the bundled block JS file
		wp_enqueue_script(
			'pvc-gutenberg', 
			POST_VIEWS_COUNTER_URL . '/js/gutenberg.min.js', 
			array( 'wp-i18n', 'wp-edit-post', 'wp-element', 'wp-editor', 'wp-components', 'wp-data', 'wp-plugins', 'wp-api' ), 
			Post_Views_Counter()->defaults['version']
		);

		// restrict editing
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];
		$can_edit = $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) );
		
		$js_args = array(
			'postID'			=> get_the_ID(),
			'postViews'			=> pvc_get_post_views( get_the_ID() ),
			'canEdit'			=> $can_edit,
			'nonce'				=> wp_create_nonce( 'wp_rest' ),
			'textPostViews'		=> __( 'Post Views', 'post-views-counter' ),
			'textHelp' 			=> __( 'Adjust the views count for this post.', 'post-views-counter' ),
			'textCancel' 		=> __( 'Cancel', 'post-views-counter' )
		);
		
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
	 * @return mixed 
	 */
	public function submitbox_views() {
		global $post;

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
	 */
	public function save_post( $post_id, $post = null ) {
		if ( is_null( $post ) )
			$post_type = get_post_type( $post_id );
		else
			$post_type = $post->post_type;

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
	 * Register post views column for specific post types
	 */
	public function register_new_column() {
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				if ( $post_type === 'attachment' ) {
					// actions
					add_action( 'manage_media_custom_column', array( $this, 'add_new_column_content' ), 10, 2 );

					// filters
					add_filter( 'manage_media_columns', array( $this, 'add_new_column' ) );
					add_filter( 'manage_upload_sortable_columns', array( $this, 'register_sortable_custom_column' ) );
				} else {
					// actions
					add_action( 'manage_' . $post_type . '_posts_custom_column', array( $this, 'add_new_column_content' ), 10, 2 );

					// filters
					add_filter( 'manage_' . $post_type . '_posts_columns', array( $this, 'add_new_column' ) );
					add_filter( 'manage_edit-' . $post_type . '_sortable_columns', array( $this, 'register_sortable_custom_column' ) );

					if ( class_exists( 'bbPress' ) ) {
						if ( $post_type === 'forum' )
							add_filter( 'bbp_admin_forums_column_headers', array( $this, 'add_new_column' ) );
						elseif ( $post_type === 'topic' )
							add_filter( 'bbp_admin_topics_column_headers', array( $this, 'add_new_column' ) );
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
	 * @return muxed
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
	 * @return mixed
	 */
	function quick_edit_custom_box( $column_name, $post_type ) {
		global $pagenow, $post;

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
	 * @return type
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

		$post_ids = ( ! empty( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) ? array_map( 'absint', $_POST['post_ids'] ) : array();

		if ( is_null( $count ) )
			exit;

		// break if views editing is restricted
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

		if ( $restrict === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			exit;

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
}

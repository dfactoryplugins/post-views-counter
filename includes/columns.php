<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

new Post_Views_Counter_Columns();

class Post_Views_Counter_Columns {

	public function __construct() {
		// actions
		add_action( 'current_screen', array( &$this, 'register_new_column' ) );
		add_action( 'post_submitbox_misc_actions', array( &$this, 'submitbox_views' ) );
		add_action( 'save_post', array( &$this, 'save_post' ), 10, 2 );
	}

	/**
	 * Output post views for single post.
	 */
	public function submitbox_views() {
		global $post;

		$post_types = Post_Views_Counter()->get_attribute( 'options', 'general', 'post_types_count' );

		if ( ! in_array( $post->post_type, (array) $post_types ) )
			return;
		
		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		global $wpdb;

		// get total post views
		$views = $wpdb->get_var(
			$wpdb->prepare( "
				SELECT count
				FROM " . $wpdb->prefix . "post_views
				WHERE id = %d AND type = 4", absint( $post->ID )
			)
		);
		?>

		<div class="misc-pub-section" id="post-views">

			<?php wp_nonce_field( 'post_views_count', 'pvc_nonce' ); ?>

			<span id="post-views-display">

				<?php echo __( 'Post Views', 'post-views-counter' ) . ': <b>' . number_format_i18n( (int) $views ) . '</b>'; ?>

			</span>
			
			<?php // restrict editing
			$restrict = (bool) Post_Views_Counter()->get_attribute( 'options', 'general', 'restrict_edit_views' );
			
			if ( $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) ) ) {
				?>
				<a href="#post-views" class="edit-post-views hide-if-no-js"><?php _e( 'Edit', 'post-views-counter' ); ?></a>
	
				<div id="post-views-input-container" class="hide-if-js">
	
					<p><?php _e( 'Adjust the views count for this post.', 'post-views-counter' ); ?></p>
					<input type="hidden" name="current_post_views" id="post-views-current" value="<?php echo (int) $views; ?>" />
					<input type="text" name="post_views" id="post-views-input" value="<?php echo (int) $views; ?>"/><br />
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
	 * Save post views data
	 */
	public function save_post( $post_id, $post ) {

		// break if doing autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return $post_id;

		// is post views set			
		if ( ! isset( $_POST['post_views'] ) )
			return $post_id;

		// break if post views in not one of the selected
		$post_types = Post_Views_Counter()->get_attribute( 'options', 'general', 'post_types_count' );

		if ( ! in_array( $post->post_type, (array) $post_types ) )
			return $post_id;
		
		// break if views editing is restricted
		$restrict = (bool) Post_Views_Counter()->get_attribute( 'options', 'general', 'restrict_edit_views' );
			
		if ( $restrict === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			return $post_id;
		
		// validate data		
		if ( ! isset( $_POST['pvc_nonce'] ) || ! wp_verify_nonce( $_POST['pvc_nonce'], 'post_views_count' ) )
			return $post_id;

		global $wpdb;

		$count = apply_filters( 'pvc_update_post_views_count', absint( $_POST['post_views'] ), $post_id );

		// insert or update db post views count
		$wpdb->query(
			$wpdb->prepare( "
				INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count)
				VALUES (%d, %d, %s, %d)
				ON DUPLICATE KEY UPDATE count = %d", $post_id, 4, 'total', $count, $count
			)
		);

		do_action( 'pvc_after_update_post_views_count', $post_id );
	}

	/**
	 * Register post views column for specific post types
	 */
	public function register_new_column() {
		$screen = get_current_screen();

		if ( Post_Views_Counter()->get_attribute( 'options', 'general', 'post_views_column' ) && ($screen->base == 'edit' && in_array( $screen->post_type, Post_Views_Counter()->get_attribute( 'options', 'general', 'post_types_count' ) )) ) {

			foreach ( Post_Views_Counter()->get_attribute( 'options', 'general', 'post_types_count' ) as $post_type ) {

				if ( $post_type === 'page' && $screen->post_type === 'page' ) {
					// actions
					add_action( 'manage_pages_custom_column', array( &$this, 'add_new_column_content' ), 10, 2 );

					// filters
					add_filter( 'manage_pages_columns', array( &$this, 'add_new_column' ) );
					add_filter( 'manage_edit-page_sortable_columns', array( &$this, 'register_sortable_custom_column' ) );
				} elseif ( $post_type === 'post' && $screen->post_type === 'post' ) {
					// actions
					add_action( 'manage_posts_custom_column', array( &$this, 'add_new_column_content' ), 10, 2 );

					// filters
					add_filter( 'manage_posts_columns', array( &$this, 'add_new_column' ) );
					add_filter( 'manage_edit-post_sortable_columns', array( &$this, 'register_sortable_custom_column' ) );
				} elseif ( $screen->post_type === $post_type ) {
					// actions
					add_action( 'manage_' . $post_type . '_posts_custom_column', array( &$this, 'add_new_column_content' ), 10, 2 );

					// filters
					add_filter( 'manage_' . $post_type . '_posts_columns', array( &$this, 'add_new_column' ) );
					add_filter( 'manage_edit-' . $post_type . '_sortable_columns', array( &$this, 'register_sortable_custom_column' ) );
				}
			}
		}
	}

	/**
	 * Register sortable post views column
	 */
	public function register_sortable_custom_column( $columns ) {
		// add new sortable column
		$columns['post_views'] = 'post_views';

		return $columns;
	}

	/**
	 * Add post views column
	 */
	public function add_new_column( $columns ) {
		$offset = 0;

		if ( isset( $columns['date'] ) )
			$offset ++;

		if ( isset( $columns['comments'] ) )
			$offset ++;

		if ( $offset > 0 ) {
			$date = array_slice( $columns, -$offset, $offset, true );

			foreach ( $date as $column => $name ) {
				unset( $columns[$column] );
			}

			$columns['post_views'] = __( 'Post Views', 'post-views-counter' );

			foreach ( $date as $column => $name ) {
				$columns[$column] = $name;
			}
		} else
			$columns['post_views'] = __( 'Post Views', 'post-views-counter' );

		return $columns;
	}

	/**
	 * Add post views column content
	 */
	public function add_new_column_content( $column_name, $id ) {

		if ( $column_name === 'post_views' ) {

			global $wpdb;

			// get total post views
			$views = $wpdb->get_var(
				$wpdb->prepare( "
					SELECT count
					FROM " . $wpdb->prefix . "post_views
					WHERE id = %d AND type = 4", $id
				)
			);

			echo number_format_i18n( (int) $views );
		}
	}

}

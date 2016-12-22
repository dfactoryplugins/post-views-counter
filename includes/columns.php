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
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( $this, 'quick_edit_custom_box' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_custom_box' ), 10, 2 );
		add_action( 'wp_ajax_save_bulk_post_views', array( $this, 'save_bulk_post_views' ) );
	}

	/**
	 * Output post views for single post.
	 * 
	 * @global object $post
	 * @return mixed 
	 */
	public function submitbox_views() {
		global $post;

		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		if ( ! in_array( $post->post_type, (array) $post_types ) )
			return;

		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		// get total post views
		$count = pvc_get_post_views( $post->ID );
		?>

		<div class="misc-pub-section" id="post-views">

			<?php wp_nonce_field( 'post_views_count', 'pvc_nonce' ); ?>

			<span id="post-views-display">
				<?php echo __( 'Post Views', 'post-views-counter' ) . ': <b>' . number_format_i18n( (int) $count ) . '</b>'; ?>
			</span>

			<?php
			// restrict editing
			$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

			if ( $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) ) ) {
				?>
				<a href="#post-views" class="edit-post-views hide-if-no-js"><?php _e( 'Edit', 'post-views-counter' ); ?></a>

				<div id="post-views-input-container" class="hide-if-js">

					<p><?php _e( 'Adjust the views count for this post.', 'post-views-counter' ); ?></p>
					<input type="hidden" name="current_post_views" id="post-views-current" value="<?php echo (int) $count; ?>" />
					<input type="text" name="post_views" id="post-views-input" value="<?php echo (int) $count; ?>"/><br />
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
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		if ( ! in_array( $post->post_type, (array) $post_types ) )
			return $post_id;

		// break if views editing is restricted
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

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
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				// actions
				add_action( 'manage_' . $post_type . '_posts_custom_column', array( $this, 'add_new_column_content' ), 10, 2 );

				// filters
				add_filter( 'manage_' . $post_type . '_posts_columns', array( $this, 'add_new_column' ) );
				add_filter( 'manage_edit-' . $post_type . '_sortable_columns', array( $this, 'register_sortable_custom_column' ) );
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
			$offset ++;

		if ( isset( $columns['comments'] ) )
			$offset ++;

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
		
		if ( $column_name != 'post_views' )
			return;

		if ( ! Post_Views_Counter()->options['general']['post_views_column'] || ! in_array( $post_type, Post_Views_Counter()->options['general']['post_types_count'] ) )
			return;

		// break if views editing is restricted
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

		if ( $restrict === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			return;

		// get total post views
		$count = $count = pvc_get_post_views( $post->ID );
		?>
		<fieldset class="inline-edit-col-left">
			<div id="inline-edit-post_views" class="inline-edit-col">
				<label class="inline-edit-group">
					<span class="title"><?php _e( 'Post Views', 'post-views-counter' ); ?></span>
					<span class="input-text-wrap"><input type="text" name="post_views" class="title text" value="<?php echo absint( $count ); ?>"></span>
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

		$post_ids = ( ! empty( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) ? array_map( 'absint', $_POST['post_ids'] ) : array();
		$count = ( ! empty( $_POST['post_views'] ) ) ? absint( $_POST['post_views'] ) : null;

		// break if views editing is restricted
		$restrict = (bool) Post_Views_Counter()->options['general']['restrict_edit_views'];

		if ( $restrict === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			die();

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
		die();
	}

}

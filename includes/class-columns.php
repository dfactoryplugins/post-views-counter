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
		add_action( 'wp_loaded', [ $this, 'maybe_load_admin_bar_menu' ] );
	}

	/**
	 * Output post views for single post.
	 *
	 * @global object $post
	 *
	 * @return void
	 */
	public function submitbox_views() {
		global $post;

		// get main instance
		$pvc = Post_Views_Counter();

		// incorrect post type?
		if ( ! in_array( $post->post_type, (array) $pvc->options['general']['post_types_count'] ) )
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
			$restrict = (bool) $pvc->options['general']['restrict_edit_views'];

			if ( $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) ) ) {
				?>
				<a href="#post-views" class="edit-post-views hide-if-no-js"><?php _e( 'Edit', 'post-views-counter' ); ?></a>

				<div id="post-views-input-container" class="hide-if-js">

					<p><?php _e( 'Adjust the views count for this post.', 'post-views-counter' ); ?></p>
					<input type="hidden" name="current_post_views" id="post-views-current" value="<?php echo esc_attr( $count ); ?>" />
					<input type="text" name="post_views" id="post-views-input" value="<?php echo esc_attr( $count ); ?>"/><br />
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
	 * @return void
	 */
	public function save_post( $post_id, $post = null ) {
		// break if doing autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		// is post views set
		if ( ! isset( $_POST['post_views'] ) )
			return;

		// cast numeric post views
		$post_views = (int) $_POST['post_views'];

		// unchanged post views value?
		if ( isset( $_POST['current_post_views'] ) && $post_views === (int) $_POST['current_post_views'] )
			return;

		// get main instance
		$pvc = Post_Views_Counter();

		// break if post views in not one of the selected
		$post_types = (array) $pvc->options['general']['post_types_count'];

		// get post type
		if ( is_null( $post ) )
			$post_type = get_post_type( $post_id );
		else
			$post_type = $post->post_type;

		// invalid post type?
		if ( ! in_array( $post_type, $post_types, true ) )
			return;

		// break if views editing is restricted
		if ( (bool) $pvc->options['general']['restrict_edit_views'] === true && ! current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ) )
			return;

		// validate data
		if ( ! isset( $_POST['pvc_nonce'] ) || ! wp_verify_nonce( $_POST['pvc_nonce'], 'post_views_count' ) )
			return;

		// update post views
		pvc_update_post_views( $post_id, $post_views );

		do_action( 'pvc_after_update_post_views_count', $post_id );
	}

	/**
	 * Register post views column for specific post types.
	 *
	 * @return void
	 */
	public function register_new_column() {
		// get main instance
		$pvc = Post_Views_Counter();

		// is posts views column active?
		if ( ! $pvc->options['general']['post_views_column'] )
			return;

		// get post types
		$post_types = $pvc->options['general']['post_types_count'];

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
		// date column exists?
		if ( isset( $columns['date'] ) ) {
			// store date column
			$date = $columns['date'];

			// unset date column
			unset( $columns['date'] );
		}

		// comments column exists?
		if ( isset( $columns['comments'] ) ) {
			// store comments column
			$comments = $columns['comments'];

			// unset comments column
			unset( $columns['comments'] );
		}

		// add post views column
		$columns['post_views'] = '<span class="dash-icon dashicons dashicons-chart-bar" title="' . esc_attr__( 'Post Views', 'post-views-counter' ) . '"><span class="screen-reader-text">' . esc_attr__( 'Post Views', 'post-views-counter' ) . '</span></span>';

		// restore date column
		if ( isset( $date ) )
			$columns['date'] = $date;

		// restore comments column
		if ( isset( $comments ) )
			$columns['comments'] = $comments;

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

			echo esc_html( $count );
		}
	}

	/**
	 * Handle quick edit.
	 *
	 * @global string $pagenow
	 *
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

		// get main instance
		$pvc = Post_Views_Counter();

		if ( ! $pvc->options['general']['post_views_column'] || ! in_array( $post_type, $pvc->options['general']['post_types_count'] ) )
			return;

		// break if views editing is restricted
		$restrict = (bool) $pvc->options['general']['restrict_edit_views'];

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
	 * @global object $wpdb
	 *
	 * @return void
	 */
	function save_bulk_post_views() {
		$count = null;

		if ( isset( $_POST['post_views'] ) ) {
			if ( is_numeric( trim( $_POST['post_views'] ) ) ) {
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
				$wpdb->query( $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count) VALUES (%d, %d, %s, %d) ON DUPLICATE KEY UPDATE count = %d", $post_id, 4, 'total', $count, $count ) );
			}
		}

		exit;
	}

	/**
	 * Add admin bar stats to a post.
	 *
	 * @return void
	 */
	public function maybe_load_admin_bar_menu() {
		// get main instance
		$pvc = Post_Views_Counter();

		// statistics disabled?
		if ( ! apply_filters( 'pvc_display_toolbar_statistics', $pvc->options['display']['toolbar_statistics'] ) )
			return;

		// skip for not logged in users
		if ( ! is_user_logged_in() )
			return;

		// skip users with turned off admin bar at frontend
		if ( ! is_admin() && get_user_option( 'show_admin_bar_front' ) !== 'true' )
			return;

		if ( is_admin() )
			add_action( 'admin_init', [ $this, 'admin_bar_maybe_add_style' ] );
		else
			add_action( 'wp', [ $this, 'admin_bar_maybe_add_style' ] );
	}

	/**
	 * Add admin bar stats to a post.
	 *
	 * @global string $pagenow
	 * @global string $post
	 *
	 * @param object $admin_bar
	 * @return void
	 */
	public function admin_bar_menu( $admin_bar ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// set empty post
		$post = null;

		// admin?
		if ( is_admin() && ! wp_doing_ajax() ) {
			global $pagenow;

			$post = ( $pagenow === 'post.php' && ! empty( $_GET['post'] ) ) ? get_post( (int) $_GET['post'] ) : $post;
		// frontend?
		} elseif ( is_singular() )
			global $post;

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// whether to allow this post type or not
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
	 * @global string $pagenow
	 * @global string $post
	 *
	 * @return void
	 */
	public function admin_bar_maybe_add_style() {
		// get main instance
		$pvc = Post_Views_Counter();

		// set empty post
		$post = null;

		// admin?
		if ( is_admin() && ! wp_doing_ajax() ) {
			global $pagenow;

			$post = ( $pagenow === 'post.php' && ! empty( $_GET['post'] ) ) ? get_post( (int) $_GET['post'] ) : $post;
		// frontend?
		} elseif ( is_singular() )
			global $post;

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// whether to allow this post type or not
		if ( empty( $post_types ) || empty( $post ) || ! in_array( $post->post_type, $post_types, true ) )
			return;

		// add admin bar
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 100 );

		// backend
		if ( current_action() === 'admin_init' )
			add_action( 'admin_head', [ $this, 'admin_bar_css' ] );
		// frontend
		elseif ( current_action() === 'wp' )
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

		echo wp_kses( $html, [ 'style' => [] ] );
	}
}

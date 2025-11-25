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
	add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_chart_modal_assets' ] );
	add_action( 'wp_ajax_pvc_column_chart', [ $this, 'ajax_column_chart' ] );
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

		// break if display is not allowed
		if ( ! $pvc->options['display']['post_views_column'] || ! in_array( $post->post_type, $pvc->options['general']['post_types_count'] ) )
			return;

		// check if user can see post stats
		if ( apply_filters( 'pvc_admin_display_post_views', true, $post->ID ) === false )
			return;

		// get total post views
		$count = (int) pvc_get_post_views( $post->ID ); ?>

		<div class="misc-pub-section" id="post-views">

			<?php wp_nonce_field( 'post_views_count', 'pvc_nonce' ); ?>

			<span id="post-views-display">
				<?php echo __( 'Post Views', 'post-views-counter' ) . ': <b>' . number_format_i18n( $count ) . '</b>'; ?>
			</span>

			<?php
			// allow editing
			$allow_edit = (bool) $pvc->options['display']['restrict_edit_views'];

			// allow editing condition
			$allow_edit_condition = (bool) current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ); 

			if ( $allow_edit === true && $allow_edit_condition === true ) {
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
		
		// allow editing
		$allow_edit = (bool) $pvc->options['display']['restrict_edit_views'];

		// allow editing condition
		$allow_edit_condition = (bool) current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ); 

		// break if views editing not allowed or editing condition not met
		if ( $allow_edit === false || $allow_edit_condition === false )
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
		if ( ! $pvc->options['display']['post_views_column'] )
			return false;

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
		global $post_type;
		
		// get main instance
		$pvc = Post_Views_Counter();

		// break if display is disabled
		if ( ! $pvc->options['display']['post_views_column'] || ! in_array( $post_type, $pvc->options['general']['post_types_count'] ) )
			return $columns;

		// check if user can see stats
		if ( apply_filters( 'pvc_admin_display_post_views', true ) === false )
			return $columns;

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
			
			// check if user can see stats
			if ( apply_filters( 'pvc_admin_display_post_views', true, $id ) === false ) {
				echo '—';
				return;
			}

			// get post title
			$post_title = get_the_title( $id );

			if ( $post_title === '' )
				$post_title = __( '(no title)', 'post-views-counter' );

			// get post type labels
			$post_type_object = get_post_type_object( get_post_type( $id ) );

			if ( $post_type_object ) {
				$post_type_labels = get_post_type_labels( $post_type_object );
			}

			if ( $post_type_labels ) {
				$post_title = $post_type_labels->singular_name . ': ' . $post_title;
			}

			// clickable link (modal opening handled via JavaScript)
			echo '<a href="#" class="pvc-view-chart" data-post-id="' . esc_attr( $id ) . '" data-post-title="' . esc_attr( $post_title ) . '">' . esc_html( $count ) . '</a>';
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
		global $pagenow, $post;

		if ( $pagenow !== 'edit.php' )
			return;

		if ( $column_name !== 'post_views' )
			return;

		if ( ! $post )
			return;
		
		// get main instance
		$pvc = Post_Views_Counter();

		// break if display is not allowed
		if ( ! $pvc->options['display']['post_views_column'] || ! in_array( $post_type, $pvc->options['general']['post_types_count'] ) )
			return;
		
		// check if user can see stats
		if ( apply_filters( 'pvc_admin_display_post_views', true, $post->ID ) === false )
			return;

		// allow editing
		$allow_edit = (bool) $pvc->options['display']['restrict_edit_views'];

		// allow editing condition
		$allow_edit_condition = (bool) current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ); 
		?>
		<fieldset class="inline-edit-col-left">
			<div id="inline-edit-post_views" class="inline-edit-col">
				<label class="inline-edit-group">
					<span class="title"><?php _e( 'Post Views', 'post-views-counter' ); ?></span>
					<?php if ( $allow_edit === true && $allow_edit_condition === true ) { ?>
						<span class="input-text-wrap"><input type="text" name="post_views" class="title text" value=""></span>
						<input type="hidden" name="current_post_views" value="" />
						<?php wp_nonce_field( 'post_views_count', 'pvc_nonce' ); ?>
					<?php } else { ?>
						<span class="input-text-wrap"><input type="text" name="post_views" class="title text" value="" disabled readonly /></span>
					<?php } ?>
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
		global $wpdb;

		// check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'pvc_save_bulk_post_views' ) )
			exit;

		$count = null;

		if ( isset( $_POST['post_views'] ) && is_numeric( trim( $_POST['post_views'] ) ) ) {
			$count = (int) $_POST['post_views'];

			if ( $count < 0 )
				$count = 0;
		}

		// check post ids
		$post_ids = ( ! empty( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] ) ) ? array_map( 'absint', $_POST['post_ids'] ) : [];

		if ( is_null( $count ) )
			exit;

		// allow editing
		$allow_edit = (bool) $pvc->options['display']['restrict_edit_views'];

		// allow editing condition
		$allow_edit_condition = (bool) current_user_can( apply_filters( 'pvc_restrict_edit_capability', 'manage_options' ) ); 

		// break if views editing not allowed or editing condition not met
		if ( $allow_edit === false || $allow_edit_condition === false )
			exit;

		// any post ids?
		if ( ! empty( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				// break if current user can't edit this post
				if ( ! current_user_can( 'edit_post', $post_id ) )
					continue;

				// insert or update db post views count
				$wpdb->query( $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count) VALUES (%d, %d, %s, %d) ON DUPLICATE KEY UPDATE count = %d", $post_id, 4, 'total', $count, $count ) );
			}
		}

		exit;
	}

	/**
	 * Enqueue chart modal assets on post list screens.
	 *
	 * @param string $page
	 * @return void
	 */
	public function enqueue_chart_modal_assets( $page ) {
		// only on edit.php and upload.php
		if ( ! in_array( $page, [ 'edit.php', 'upload.php' ], true ) )
			return;

		$screen = get_current_screen();
		$pvc = Post_Views_Counter();

		// break if display is not allowed
		if ( ! $pvc->options['display']['post_views_column'] || ! in_array( $screen->post_type, $pvc->options['general']['post_types_count'], true ) )
			return;

		// check if user can see stats
		if ( apply_filters( 'pvc_admin_display_post_views', true ) === false )
			return;

		// enqueue Micromodal
		wp_enqueue_script( 'pvc-micromodal', POST_VIEWS_COUNTER_URL . '/assets/micromodal/micromodal.min.js', [], '0.4.10', true );

		// enqueue Chart.js (already registered)
		wp_enqueue_script( 'pvc-chartjs' );

		// enqueue modal assets
		wp_enqueue_style( 'pvc-column-modal', POST_VIEWS_COUNTER_URL . '/css/column-modal.css', [], $pvc->defaults['version'] );
		wp_enqueue_script( 'pvc-column-modal', POST_VIEWS_COUNTER_URL . '/js/column-modal.js', [ 'jquery', 'pvc-chartjs', 'pvc-micromodal' ], $pvc->defaults['version'], true );

		// localize script
		wp_add_inline_script( 'pvc-column-modal', 'var pvcColumnModal = ' . wp_json_encode( [
			'ajaxURL'	=> admin_url( 'admin-ajax.php' ),
			'nonce'		=> wp_create_nonce( 'pvc-column-modal' ),
			'i18n'		=> [
				'loading'		=> __( 'Loading...', 'post-views-counter' ),
				'close'			=> __( 'Close', 'post-views-counter' ), 
				'error'			=> __( 'An error occurred while loading data.', 'post-views-counter' ),
				'summary'		=> __( 'Total views in this period:', 'post-views-counter' ),
				'view'			=> __( 'view', 'post-views-counter' ),
				'views'			=> __( 'views', 'post-views-counter' )
			]
		] ) . "\n", 'before' );

		// add modal HTML to footer
		add_action( 'admin_footer', [ $this, 'render_modal_html' ] );
	}

	/**
	 * AJAX handler for column chart data.
	 *
	 * @return void
	 */
	public function ajax_column_chart() {
		// permission & nonce check
		if ( ! check_ajax_referer( 'pvc-column-modal', 'nonce', false ) )
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'post-views-counter' ) ] );

		// get PVC instance
		$pvc = Post_Views_Counter();

		// get post ID
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		
		if ( ! $post_id )
			wp_send_json_error( [ 'message' => __( 'Invalid post ID.', 'post-views-counter' ) ] );

		// check post exists
		$post = get_post( $post_id );

		if ( ! $post )
			wp_send_json_error( [ 'message' => __( 'Post not found.', 'post-views-counter' ) ] );

		// break if display is not allowed
		if ( ! $pvc->options['display']['post_views_column'] )
			wp_send_json_error( [ 'message' => __( 'Admin column disabled.', 'post-views-counter' ) ] );

		// ensure post type is tracked
		if ( ! in_array( $post->post_type, $pvc->options['general']['post_types_count'], true ) )
			wp_send_json_error( [ 'message' => __( 'Post type is not tracked.', 'post-views-counter' ) ] );

		// check display permission for this specific post
		if ( apply_filters( 'pvc_admin_display_post_views', true, $post_id ) === false )
			wp_send_json_error( [ 'message' => __( 'Access denied for this post.', 'post-views-counter' ) ] );

		// get period (format: YYYYMM or empty for current month)
		$period_str = isset( $_POST['period'] ) && ! empty( $_POST['period'] ) ? preg_replace( '/[^0-9]/', '', $_POST['period'] ) : '';

		// parse period or use current
		if ( $period_str && strlen( $period_str ) === 6 ) {
			$year = substr( $period_str, 0, 4 );
			$month = substr( $period_str, 4, 2 );
			$date = DateTime::createFromFormat( 'Y-m', $year . '-' . $month, wp_timezone() );
			
			if ( ! $date )
				$date = new DateTime( 'now', wp_timezone() );
		} else {
			$date = new DateTime( 'now', wp_timezone() );
		}

		$year = $date->format( 'Y' );
		$month = $date->format( 'm' );
		$last_day = $date->format( 't' );

		// fetch views data
		$views = pvc_get_views( [
			'post_id'		=> $post_id,
			'post_type'		=> $post->post_type,
			'fields'		=> 'date=>views',
			'views_query'	=> [
				'year'	=> (int) $year,
				'month'	=> (int) $month
			]
		] );

		// get colors
		$colors = $pvc->functions->get_colors();

		// prepare response data
		$data = [
			'post_id'	=> $post_id,
			'post_title'=> get_the_title( $post_id ),
			'period'	=> $year . $month,
			'design'		=> [
				'fill'					=> true,
				'backgroundColor'		=> 'rgba(' . $colors['r'] . ',' . $colors['g'] . ',' . $colors['b'] . ', 0.2)',
				'borderColor'			=> 'rgba(' . $colors['r'] . ',' . $colors['g'] . ',' . $colors['b'] . ', 1)',
				'borderWidth'			=> 1.2,
				'borderDash'			=> [],
				'pointBorderColor'		=> 'rgba(' . $colors['r'] . ',' . $colors['g'] . ',' . $colors['b'] . ', 1)',
				'pointBackgroundColor'	=> 'rgba(255, 255, 255, 1)',
				'pointBorderWidth'		=> 1.2
			],
			'data'		=> [
				'labels'	=> [],
				'dates'		=> [],
				'datasets'	=> [
					[
						'label'	=> get_the_title( $post_id ),
						'data'	=> []
					]
				]
			]
		];

		// generate dates and data
		for ( $i = 1; $i <= $last_day; $i++ ) {
			$date_key = $year . $month . str_pad( $i, 2, '0', STR_PAD_LEFT );
			
			// labels: show only odd days
			$data['data']['labels'][] = ( $i % 2 === 0 ? '' : $i );
			
			// formatted dates for tooltips
			$data['data']['dates'][] = date_i18n( get_option( 'date_format' ), strtotime( $year . '-' . $month . '-' . str_pad( $i, 2, '0', STR_PAD_LEFT ) ) );
			
			// view count
			$data['data']['datasets'][0]['data'][] = isset( $views[$date_key] ) ? (int) $views[$date_key] : 0;
		}

		// calculate total views for the period
		$data['total_views'] = array_sum( $data['data']['datasets'][0]['data'] );

		// check if there is any period-specific data
		$period_has_data = false;
		foreach ( $data['data']['datasets'][0]['data'] as $val ) {
			if ( (int) $val > 0 ) {
				$period_has_data = true;
				break;
			}
		}

		$data['period_has_data'] = $period_has_data;

		// generate date navigation HTML
		$data['dates_html'] = $this->generate_modal_dates( (int) $year, (int) $month );

		wp_send_json_success( $data );
	}

	/**
	 * Generate month navigation for modal.
	 *
	 * @param int $year
	 * @param int $month
	 * @return string
	 */
	private function generate_modal_dates( $year, $month ) {
		// previous month
		$prev_date = DateTime::createFromFormat( 'Y-m', $year . '-' . $month, wp_timezone() );
		$prev_date->modify( '-1 month' );
		
		// next month
		$next_date = DateTime::createFromFormat( 'Y-m', $year . '-' . $month, wp_timezone() );
		$next_date->modify( '+1 month' );
		
		// current
		$current_date = DateTime::createFromFormat( 'Y-m', $year . '-' . $month, wp_timezone() );
		
		// check if next is in the future
		$now = new DateTime( 'now', wp_timezone() );
		$can_go_next = $next_date <= $now;
		
		$html = '<div class="pvc-modal-nav">';
		$html .= '<a href="#" class="pvc-modal-nav-prev" data-period="' . $prev_date->format( 'Ym' ) . '">‹ ' . date_i18n( 'F Y', $prev_date->getTimestamp() ) . '</a>';
		$html .= '<span class="pvc-modal-nav-current">' . date_i18n( 'F Y', $current_date->getTimestamp() ) . '</span>';
		
		if ( $can_go_next )
			$html .= '<a href="#" class="pvc-modal-nav-next" data-period="' . $next_date->format( 'Ym' ) . '">' . date_i18n( 'F Y', $next_date->getTimestamp() ) . ' ›</a>';
		else
			$html .= '<span class="pvc-modal-nav-next pvc-disabled">' . date_i18n( 'F Y', $next_date->getTimestamp() ) . ' ›</span>';
		
		$html .= '</div>';
		
		return $html;
	}

	/**
	 * Render modal HTML in admin footer.
	 *
	 * @return void
	 */
	public function render_modal_html() {
	?>
		<div id="pvc-chart-modal" class="pvc-modal micromodal-slide" aria-hidden="true">
			<div class="pvc-modal__overlay" tabindex="-1" data-micromodal-close>
				<div class="pvc-modal__container" role="dialog" aria-modal="true" aria-labelledby="pvc-modal-title">
					<header class="pvc-modal__header">
						<h2 class="pvc-modal__title" id="pvc-modal-title"></h2>
						<button class="pvc-modal__close" aria-label="<?php esc_attr_e( 'Close', 'post-views-counter' ); ?>" data-micromodal-close></button>
					</header>
					<div class="pvc-modal__content">
						<div class="pvc-modal-content-top">
							<div class="pvc-modal-summary">
								<span class="pvc-modal-views-label"></span>
								<span class="pvc-modal-views-data">
									<span class="pvc-modal-count"></span>
								</span>
							</div>
						</div>
						<div class="pvc-modal-chart-container">
						<canvas id="pvc-modal-chart" height="200"></canvas>
							<span class="spinner"></span>
						</div>
						<div class="pvc-modal-content-bottom pvc-modal-dates"></div>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
}

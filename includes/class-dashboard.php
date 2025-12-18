<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Dashboard class.
 *
 * @class Post_Views_Counter_Dashboard
 */
class Post_Views_Counter_Dashboard {

	private $widget_items = [];

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'admin_init', [ $this, 'init_admin_dashboard' ] );
	}

	/**
	 * Dashboard initialization.
	 *
	 * @global string $pagenow
	 *
	 * @return void
	 */
	public function init_admin_dashboard() {
		global $pagenow;

		// setup widget items
		$this->setup_widget_items();

		// do it only on dashboard page
		if ( $pagenow === 'index.php' ) {
			// filter user_can_see_stats
			if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) )
				return;

			add_action( 'wp_dashboard_setup', [ $this, 'wp_dashboard_setup' ], 1 );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts_styles' ] );
		// ajax endpoints
		} elseif ( $pagenow === 'admin-ajax.php' ) {
			add_action( 'wp_ajax_pvc_dashboard_post_most_viewed', [ $this, 'dashboard_post_most_viewed' ] );
			add_action( 'wp_ajax_pvc_dashboard_post_views_chart', [ $this, 'dashboard_post_views_chart' ] );
			add_action( 'wp_ajax_pvc_dashboard_user_options', [ $this, 'update_dashboard_user_options' ] );
		}
	}

	/**
	 * Add dashboard widget.
	 *
	 * @return void
	 */
	public function wp_dashboard_setup() {
		// add dashboard widget
		wp_add_dashboard_widget( 'pvc_dashboard', __( 'Post Views', 'post-views-counter' ), [ $this, 'dashboard_widget' ] );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @return void
	 */
	public function admin_scripts_styles() {
		// get main instance
		$pvc = Post_Views_Counter();

		// styles
		wp_enqueue_style( 'pvc-admin-dashboard', POST_VIEWS_COUNTER_URL . '/css/admin-dashboard.css', [], $pvc->defaults['version'] );
		wp_enqueue_style( 'pvc-microtip', POST_VIEWS_COUNTER_URL . '/assets/microtip/microtip.min.css', [], '1.0.0' );

		// scripts
		wp_enqueue_script( 'pvc-admin-dashboard', POST_VIEWS_COUNTER_URL . '/js/admin-dashboard.js', [ 'jquery', 'pvc-chartjs' ], $pvc->defaults['version'], true );

		// prepare script data
		$script_data = [
			'ajaxURL'	=> admin_url( 'admin-ajax.php' ),
			'nonce'		=> wp_create_nonce( 'pvc-dashboard-widget' ),
			'nonceUser'	=> wp_create_nonce( 'pvc-dashboard-user-options' )
		];

		$script_data = apply_filters( 'pvc_admin_dashboard_script_data', $script_data );

		wp_add_inline_script( 'pvc-admin-dashboard', 'var pvcArgs = ' . wp_json_encode( $script_data ) . ";\n", 'before' );
	}

	/**
	 * Setup dashboard widget items.
	 *
	 * @return void
	 */
	private function setup_widget_items() {
		// standard items
		$items = [
			[
				'id'			=> 'post-views',
				'title'			=> __( 'Post Views', 'post-views-counter' ),
				'description'	=> __( 'Displays a chart of most viewed post types.', 'post-views-counter' ),
				'content'		=> '<canvas id="pvc-post-views-chart" height="' . (int) $this->calculate_canvas_size( Post_Views_Counter()->options['general']['post_types_count'] ) . '"></canvas>',
				'position'		=> 2
			],
			[
				'id'			=> 'post-most-viewed',
				'title'			=> __( 'Top Posts', 'post-views-counter' ),
				'description'	=> __( 'Displays a list of most viewed single posts or pages.', 'post-views-counter' ),
				'content'		=> '<div id="pvc-post-most-viewed-content" class="pvc-table-responsive"></div>',
				'position'		=> 3
			]
		];

		// filter items, do not allow to remove main items
		$new_items = apply_filters( 'pvc_dashboard_widget_items', [] );

		// any new items?
		if ( is_array( $new_items ) && ! empty( $new_items ) ) {
			foreach ( $new_items as $item ) {
				// add new item
				array_push( $items, $item );
			}
		}

		// sort dashboard items by position
		array_multisort( array_column( $items, 'position' ), SORT_ASC, SORT_NUMERIC, $items );

		// set widget items
		$this->widget_items = $items;
	}

	public function get_widget_items() {
		// return widget items
		return $this->widget_items;	
	}

	/**
	 * Calculate canvas height based on number of legend items.
	 *
	 * @param array $data
	 * @param bool $expression
	 * @return int
	 */
	public function calculate_canvas_size( $data, $expression = true ) {
		if ( $expression && ! empty( $data ) ) {
			// treat every 4 legend items as 1 line - 23 pixels
			$height = 23 * ( (int) ceil( count( $data ) / 4 ) - 1 );
		} else
			$height = 0;

		return (int) ( 170 + $height );
	}

	/**
	 * Render dashboard widget.
	 *
	 * @return void
	 */
	public function dashboard_widget() {
		// get user options
		$user_options = get_user_meta( get_current_user_id(), 'pvc_dashboard', true );

		// empty options?
		if ( empty( $user_options ) || ! is_array( $user_options ) )
			$user_options = [];

		// sanitize options
		$user_options = map_deep( $user_options, 'sanitize_text_field' );

		// get menu items
		$menu_items = ! empty( $user_options['menu_items'] ) ? $user_options['menu_items'] : [];

		// get widget items
		$items = $this->widget_items;

		$html = '
		<div id="pvc-dashboard-accordion" class="pvc-accordion">';

		foreach ( $items as $item ) {
			$html .= $this->generate_dashboard_widget_item( $item, $menu_items );
		}

		$html .= '
			<div class="pvc-dashboard-block"><span>' . esc_html__( 'Powered by', 'post-views-counter' ) . ' <a href="https://postviewscounter.com/?utm_source=post-views-counter-lite&utm_medium=link&utm_campaign=powered-by" target="_blank">Post Views Counter</a></span></div>
		</div>';

		echo $html;
	}

	/**
	 * Generate dashboard widget item HTML.
	 *
	 * @param array $item
	 * @param array $menu_items
	 *
	 * @return string
	 */
	public function generate_dashboard_widget_item( $item, $menu_items ) {
		// get allowed html tags
		$allowed_html = wp_kses_allowed_html( 'post' );
		$allowed_html['canvas'] = [
			'id' => [],
			'height' => []
		];

		ob_start(); ?>

		<div id="pvc-<?php esc_attr_e( $item['id'] ); ?>" class="pvc-accordion-item<?php echo ( in_array( $item['id'], $menu_items, true ) ? ' pvc-collapsed' : '' ); ?>">
			<div class="pvc-accordion-header">
				<div class="pvc-accordion-toggle"><span class="pvc-accordion-title"><?php esc_html_e( $item['title'] ); ?></span><span class="pvc-tooltip" aria-label="<?php esc_html_e( $item['description'] ); ?>" data-microtip-position="top" data-microtip-size="large" role="tooltip"><span class="pvc-tooltip-icon"></span></span></div>
				<div class="pvc-accordion-actions">
					<!--<a href="javascript:void(0);" class="pvc-accordion-action dashicons dashicons-admin-generic"></a>-->
					<a href="javascript:void(0);" class="pvc-accordion-action pvc-toggle-indicator"></a>
				</div>
			</div>
			<div class="pvc-accordion-content">
				<div class="pvc-dashboard-container loading">
					<div class="pvc-dashboard-content-top">
						<?php do_action( 'pvc_dashboard_widget_content_top', $item['id'] ); ?>
					</div>
					<div class="pvc-data-container">
						<?php echo wp_kses( $item['content'], $allowed_html ); ?>
						<span class="spinner"></span>
					</div>
					<div class="pvc-dashboard-content-bottom">
						<div class="pvc-date-nav pvc-months">
							<?php 
							// generate dates
							echo wp_kses_post( $this->generate_months( current_time( 'timestamp', false ), $item['id'] ) );
							?>
						</div>
						<?php do_action( 'pvc_dashboard_widget_content_bottom', $item['id'] ); ?>
					</div>
				</div>
			</div>
		</div>
		
		<?php
		// Output current buffer
		return ob_get_clean();
	}

	/**
	 * Render dashboard widget with post views.
	 *
	 * @return void
	 */
	public function dashboard_post_views_chart() {
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) || ! check_ajax_referer( 'pvc-dashboard-widget', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		// get period
		$period = isset( $_POST['period'] ) && ! empty( $_POST['period'] ) ? preg_replace( '/[^a-z0-9_|]/', '', $_POST['period'] ) : apply_filters( 'pvc_dashboard_widget_default_period', 'this_month', 'post-views' );

		// get post types
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];
		
		// empty options?
		if ( empty( $post_types ) || ! is_array( $post_types ) )
			$post_types = [];

		// sanitize post_types
		$post_types = map_deep( $post_types, 'sanitize_text_field' );

		// get dashboard user options
		$user_options = $this->get_dashboard_user_options( get_current_user_id(), 'post_types' );
		
		// empty options?
		if ( empty( $user_options ) || ! is_array( $user_options ) )
			$user_options = [];

		// sanitize options
		$user_options = map_deep( $user_options, 'sanitize_text_field' );

		// get colors
		$colors = Post_Views_Counter()->functions->get_colors();
		
		// parameters to be used in filter
		$args = [
			'widget'		=> 'post_views',
			'period'		=> $period,
			'post_types'	=> $post_types,
			'user_options'	=> $user_options
		];
		
		$data = [
			'widget'	=> 'post-views',
			'design'	=> [
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
				'datasets'	=> [
					[
						'label'			=> __( 'Total Views', 'post-views-counter' ),
						'post_type'	=> '_pvc_total_views',
						'hidden'		=> in_array( '_pvc_total_views', $user_options, true ),
						'data'			=> []
					]
				]
			],
		];
		
		// convert period
		$time = pvc_period2timestamp( $period );

		// get date chunks
		$date = date( 'Y m W d t', $time );
		$date_chunks = explode( ' ', $date );

		// get date
		$year = (int) $date_chunks[0];
		$month = sanitize_text_field( $date_chunks[1] );
		$dates_number = (int) $date_chunks[4];

		// get previous date chunks
		$previous_time = strtotime( '-1 months', $time );
		$previous_date = date( 'Y m W d t', $previous_time );
		$previous_date_chunks = explode( ' ', $previous_date );

		// get current date
		$current_date = date_create( 'now', wp_timezone() )->format('Y m W d t');
		$current_date_chunks = explode( ' ', $current_date );

		// generate dates
		$data['dates'] = $this->generate_months( $time );

		// generate chart data
		$sum = [];

		// any post types?
		if ( ! empty( $post_types ) ) {
			// reindex post types
			$post_types = array_combine( range( 1, count( $post_types ) ), array_values( $post_types ) );

			$post_type_data = [];

			foreach ( $post_types as $id => $post_type ) {
				$post_type_obj = get_post_type_object( $post_type );

				// unrecognized post type? (mainly from deactivated plugins)
				if ( empty( $post_type_obj ) )
					$label = $post_type;
				else
					$label = $post_type_obj->labels->name;

				$data['data']['datasets'][$id]['label'] = $label;
				$data['data']['datasets'][$id]['post_type'] = $post_type;
				$data['data']['datasets'][$id]['hidden'] = in_array( $post_type, $user_options, true );
				$data['data']['datasets'][$id]['data'] = [];

				// get month views
				$post_type_data[$id] = array_values(
					pvc_get_views( apply_filters( 'pvc_dashboard_post_views_query_args', 
						[
							'fields'		=> 'date=>views',
							'post_type'		=> $post_type,
							'views_query'	=> [
								'year'			=> $year,
								'month'			=> $month,
								'hide_empty'	=> true
							]
						], $period
					) )
				);
			}

			foreach ( $post_type_data as $post_type_id => $post_views ) {
				foreach ( $post_views as $id => $views ) {
					// generate chart data for specific post types
					$data['data']['datasets'][$post_type_id]['data'][] = $views;

					if ( ! array_key_exists( $id, $sum ) )
						$sum[$id] = 0;

					$sum[$id] += $views;
				}
			}
		}

		// this month all days
		for ( $i = 1; $i <= $dates_number; $i++ ) {
			// generate chart data
			$data['data']['labels'][] = ( $i % 2 === 0 ? '' : $i );
			$data['data']['dates'][] = date_i18n( get_option( 'date_format' ), strtotime( $year . '-' . $month . '-' . str_pad( $i, 2, '0', STR_PAD_LEFT ) ) );
			$data['data']['datasets'][0]['data'][] = ( array_key_exists( $i - 1, $sum ) ? $sum[$i - 1] : 0 );
		}

		echo wp_json_encode( apply_filters( 'pvc_dashboard_post_views_data', $data, $args ) );
		exit;
	}

	/**
	 * Render dashboard widget with most viewed posts.
	 *
	 * @return void
	 */
	public function dashboard_post_most_viewed() {
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) || ! check_ajax_referer( 'pvc-dashboard-widget', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		// get main instance
		$pvc = Post_Views_Counter();

		// get post types
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];
		
		// empty options?
		if ( empty( $post_types ) || ! is_array( $post_types ) )
			$post_types = [];

		// sanitize post_types
		$post_types = map_deep( $post_types, 'sanitize_text_field' );

		// get period
		$period = isset( $_POST['period'] ) && ! empty( $_POST['period'] ) ? preg_replace( '/[^a-z0-9_|]/', '', $_POST['period'] ) : apply_filters( 'pvc_dashboard_widget_default_period', 'this_month', 'post-most-viewed' );
		
		// parameters to be used in filter
		$args = [
			'widget'		=> 'post_most_viewed',
			'period'		=> $period,
			'post_types'	=> $post_types
		];

		$data = [
			'widget'	=> 'post-most-viewed',
			'html'		=> ''
		];

		// convert period
		$time = pvc_period2timestamp( $period );

		// get date chunks
		$date = date( 'Y m W d t', $time );
		$date_chunks = explode( ' ', $date );

		// get date
		$year = (int) $date_chunks[0];
		$month = sanitize_text_field( $date_chunks[1] );
		
		// generate dates
		$data['dates'] = $this->generate_months( $time );

		// query args
		$query_args = apply_filters( 'pvc_dashboard_post_most_viewed_query_args', [
			'post_type'			=> $post_types,
			'posts_per_page'	=> 10,
			'paged'				=> false,
			'suppress_filters'	=> false,
			'no_found_rows'		=> true,
			'views_query'		=> [
				'year'			=> $year,
				'month'			=> $month,
				'hide_empty'	=> true
			]
		], $period );

		$posts = pvc_get_most_viewed_posts( $query_args );
		
		$html = '
		<table id="pvc-post-most-viewed-table" class="pvc-table pvc-table-hover">
			<thead>
				<tr>
					<th scope="col">#</th>
					<th scope="col">' . esc_html__( 'Post', 'post-views-counter' ) . '</th>
					<th scope="col">' . esc_html__( 'Views', 'post-views-counter' ) . '</th>
				</tr>
			</thead>
			<tbody>';

		if ( $posts ) {
			// active post types
			$active_post_types = [];

			foreach ( $posts as $index => $post ) {
				setup_postdata( $post );

				$html .= '
				<tr>
					<th scope="col">' . ( $index + 1 ) . '</th>';

				// check post type existence
				if ( array_key_exists( $post->post_type, $active_post_types ) )
					$post_type_exists = $active_post_types[$post->post_type];
				else
					$post_type_exists = $active_post_types[$post->post_type] = post_type_exists( $post->post_type );

				$title = get_the_title( $post );

				if ( $title === '' )
					$title = __( '(no title)' );

				// post link
				$link = '<a href="' . esc_url( get_permalink( $post->ID ) ) . '" target="_blank">' . esc_html( $title ) . '</a>';

				// edit post link
				if ( $post_type_exists && current_user_can( 'edit_post', $post->ID ) ) {
					$link .= ' <a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '" class="cn-edit-link" target="_blank">' . esc_html__( 'Edit', 'post-views-counter' ) . '</a>';
				}

				$html .= '
					<td>' . $link . '</td>';

				$html .= '
					<td>' . number_format_i18n( $post->post_views ) . '</td>
				</tr>';
			}
		} else {
			$html .= '
				<tr class="no-posts">
					<td colspan="3">' . esc_html__( 'No most viewed posts found.', 'post-views-counter' ) . '</td>
				</tr>';
		}

		$html .= '
			</tbody>
		</table>';
		
		$data['html'] = $html;

		echo wp_json_encode( apply_filters( 'pvc_dashboard_post_most_viewed_data', $data, $args ) );
		exit;
	}

	/**
	 * Generate dashboard widget months HTML.
	 *
	 * @param int $timestamp
	 * @return string
	 */
	public function generate_months( $timestamp, $item = '' ) {
		$dates = [
			explode( ' ', date( 'm F Y', strtotime( '-1 months', $timestamp ) ) ),
			explode( ' ', date( 'm F Y', $timestamp ) ),
			explode( ' ', date( 'm F Y', strtotime( '+1 months', $timestamp ) ) )
		];

		$current = date( 'Ym', current_time( 'timestamp', false ) );

		if ( (int) $current <= (int) ( $dates[1][2] . $dates[1][0] ) )
			$next = '<span class="next">' . $dates[2][1] . ' ' . $dates[2][2] . ' ›</span>';
		else
			$next = '<a class="next" href="#" data-date="' . ( $dates[2][2] . $dates[2][0] ) . '">' . $dates[2][1] . ' ' . $dates[2][2] . ' ›</a>';

		$dates_formatted = apply_filters( 'pvc_dashboard_widget_generate_months_formatted', [
			'prev'		=> '<a class="prev" href="#" data-date="' . ( $dates[0][2] . $dates[0][0] ) . '">‹ ' . $dates[0][1] . ' ' . $dates[0][2] . '</a>',
			'current'	=> '<span class="current">' . $dates[1][1] . ' ' . $dates[1][2] . '</span>',
			'next'		=> $next
		], $timestamp, $item );

		return wp_kses_post( apply_filters( 'pvc_dashboard_widget_generate_months_html', $dates_formatted['prev'] . $dates_formatted['current'] . $dates_formatted['next'], $timestamp, $item ) );
	}

	/**
	 * Update dashboard widget user options.
	 *
	 * @return void
	 */
	public function update_dashboard_user_options() {
		if ( ! check_ajax_referer( 'pvc-dashboard-user-options', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		// valid data?
		if ( isset( $_POST['nonce'], $_POST['options'] ) && ! empty( $_POST['options'] ) ) {
			// get sanitized options
			$update = map_deep( $_POST['options'], 'sanitize_text_field' );

			// get user ID
			$user_id = get_current_user_id();

			// get user dashboard data
			$user_options = get_user_meta( $user_id, 'pvc_dashboard', true );

			// empty userdata?
			if ( ! is_array( $user_options ) || empty( $user_options ) )
				$user_options = [];

			// empty post types?
			if ( ! array_key_exists( 'post_types', $user_options ) || ! is_array( $user_options['post_types'] ) )
				$user_options['post_types'] = [];

			// hide post type?
			if ( ! empty( $update['post_type'] ) ) {
				// get allowed post types
				$allowed_post_types = Post_Views_Counter()->options['general']['post_types_count'];

				// simulate total post views as post type
				$allowed_post_types[] = '_pvc_total_views';

				if ( in_array( $update['post_type'], $allowed_post_types, true ) ) {
					if ( isset( $update['hidden'] ) && $update['hidden'] === 'true' ) {
						if ( ! in_array( $update['post_type'], $user_options['post_types'], true ) )
							$user_options['post_types'][] = $update['post_type'];
					} else {
						if ( ( $key = array_search( $update['post_type'], $user_options['post_types'] ) ) !== false )
							unset( $user_options['post_types'][$key] );
					}
				}
			}

			// empty menu items?
			if ( ! array_key_exists( 'menu_items', $user_options ) || ! is_array( $user_options['menu_items'] ) )
				$user_options['menu_items'] = [];

			if ( ! empty( $update['menu_items'] ) && is_array( $update['menu_items'] ) ) {
				$user_options['menu_items'] = [];

				// get allowed menu items
				$allowed_menu_items = array_column( $this->widget_items, 'id' );

				foreach ( $update['menu_items'] as $menu_item => $hidden ) {
					if ( in_array( $menu_item, $allowed_menu_items, true ) && $hidden === 'true' )
						$user_options['menu_items'][] = $menu_item;
				}
			}

			// filter user options
			$user_options = apply_filters( 'pvc_update_dashboard_user_options', $user_options, $update, $user_id );

			// update userdata
			update_user_meta( $user_id, 'pvc_dashboard', $user_options );
			
			echo wp_send_json_success();
		}

		echo wp_send_json_error();
		exit;
	}

	/**
	 * Get user dashboard data.
	 *
	 * @param int $user_id
	 * @param string $data_type
	 * @return array
	 */
	public function get_dashboard_user_options( $user_id = 0, $data_type = '' ) {
		$user_options = get_user_meta( $user_id, 'pvc_dashboard', true );

		if ( ! is_array( $user_options ) || empty( $user_options ) )
			$user_options = [];

		if ( ! array_key_exists( $data_type, $user_options ) || ! is_array( $user_options[$data_type] ) )
			$user_options[$data_type] = [];

		return $user_options[$data_type];
	}
	
	/**
	 * Convert period to timestamp.
	 * 
	 * @deprecated
	 * @param string $period
	 * @return int
	 */
	public function period2timestamp( $period ) {
		return pvc_period2timestamp( $period );
	}
}

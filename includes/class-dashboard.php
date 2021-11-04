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

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'wp_dashboard_setup', [ $this, 'wp_dashboard_setup' ], 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts_styles' ] );
		add_action( 'wp_ajax_pvc_dashboard_most_viewed', [ $this, 'dashboard_get_most_viewed' ] );
		add_action( 'wp_ajax_pvc_dashboard_chart', [ $this, 'dashboard_get_chart' ] );
		add_action( 'wp_ajax_pvc_dashboard_user_options', [ $this, 'update_dashboard_user_options' ] );
	}

	/**
	 * Initialize widget.
	 *
	 * @return void
	 */
	public function wp_dashboard_setup() {
		// filter user_can_see_stats
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) )
			return;

		// add dashboard post views chart widget
		wp_add_dashboard_widget( 'pvc_dashboard', __( 'Post Views Counter', 'post-views-counter' ), [ $this, 'dashboard_widget' ] );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $pagenow
	 * @return void
	 */
	public function admin_scripts_styles( $pagenow ) {
		if ( $pagenow !== 'index.php' )
			return;

		// filter user_can_see_stats
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) )
			return;

		// styles
		wp_enqueue_style( 'pvc-admin-dashboard', POST_VIEWS_COUNTER_URL . '/css/admin-dashboard.css', [], Post_Views_Counter()->defaults['version'] );
		wp_enqueue_style( 'pvc-chartjs', POST_VIEWS_COUNTER_URL . '/assets/chartjs/chart.min.css', [], Post_Views_Counter()->defaults['version'] );
		wp_enqueue_style( 'pvc-microtip', POST_VIEWS_COUNTER_URL . '/assets/microtip/microtip.min.css', [], Post_Views_Counter()->defaults['version'] );

		// scripts
		wp_register_script( 'pvc-chartjs', POST_VIEWS_COUNTER_URL . '/assets/chartjs/chart.min.js', [ 'jquery' ], Post_Views_Counter()->defaults['version'], true );
		wp_enqueue_script( 'pvc-admin-dashboard', POST_VIEWS_COUNTER_URL . '/js/admin-dashboard.js', [ 'jquery', 'pvc-chartjs' ], Post_Views_Counter()->defaults['version'], true );

		wp_localize_script(
			'pvc-admin-dashboard',
			'pvcArgs',
			[
				'ajaxURL'	=> admin_url( 'admin-ajax.php' ),
				'nonce'		=> wp_create_nonce( 'pvc-dashboard-chart' ),
				'nonceUser'	=> wp_create_nonce( 'pvc-dashboard-user-options' )
			]
		);
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
		$menu_items = ! empty( $user_options['menu_items'] ) ? $user_options['menu_items'] : [];

		// generate months
		$months_html = $this->generate_months( current_time( 'timestamp', false ) );

		?>
		<div id="pvc-dashboard-accordion" class="pvc-accordion">
			<div id="pvc-post-views" class="pvc-accordion-item<?php echo in_array( 'post-views', $menu_items ) ? ' pvc-collapsed' : ''; ?>">
				<div class="pvc-accordion-header">
					<div class="pvc-accordion-toggle"><span class="pvc-accordion-title"><?php _e( 'Post Views', 'post-views-counter' ); ?></span><span class="pvc-tooltip" aria-label="<?php _e( 'Displays the chart of most viewed post types for a selected time period.', 'post-views-counter' ); ?>" data-microtip-position="top" data-microtip-size="large" role="tooltip"><span class="pvc-tooltip-icon"></span></span></div>
					<?php /*
					<div class="pvc-accordion-actions">
						<a href="javascript:void(0);" class="pvc-accordion-action dashicons dashicons-admin-generic"></a>
					</div>
					*/ ?>
				</div>
				<div class="pvc-accordion-content">
					<div class="pvc-dashboard-container loading">
						<div id="pvc-chart-container" class="pvc-data-container">
							<canvas id="pvc-chart" height="175"></canvas>
							<span class="spinner"></span>
						</div>
						<div class="pvc-months">
							<?php echo wp_kses_post( $months_html ); ?>
						</div>
					</div>
				</div>
			</div>
			<div id="pvc-most-viewed" class="pvc-accordion-item<?php echo in_array( 'most-viewed', $menu_items ) ? ' pvc-collapsed' : ''; ?>">
				<div class="pvc-accordion-header">
					<div class="pvc-accordion-toggle"><span class="pvc-accordion-title"><?php _e( 'Top Posts', 'post-views-counter' ); ?></span><span class="pvc-tooltip" aria-label="<?php _e( 'Displays the list of most viewed posts and pages on your website.', 'post-views-counter' ); ?>" data-microtip-position="top" data-microtip-size="large" role="tooltip"><span class="pvc-tooltip-icon"></span></span></div>
				</div>
				<div class="pvc-accordion-content">
					<div class="pvc-dashboard-container loading">
						<div class="pvc-data-container">
							<div id="pvc-viewed" class="pvc-table-responsive"></div>
							<span class="spinner"></span>
						</div>
						<div class="pvc-months">
							<?php echo wp_kses_post( $months_html ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Dashboard widget chart data function.
	 *
	 * @global $_wp_admin_css_colors
	 * @return void
	 */
	public function dashboard_get_chart() {
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) )
			wp_die( _( 'You do not have permission to access this page.', 'post-views-counter' ) );

		if ( ! check_ajax_referer( 'pvc-dashboard-chart', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		// get period
		$period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : 'this_month';

		// get post types
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		// get stats
		$query_args = [
			'post_type'			=> $post_types,
			'posts_per_page'	=> -1,
			'paged'				=> false,
			'orderby'			=> 'post_views',
			'suppress_filters'	=> false,
			'no_found_rows'		=> true
		];

		// $now = getdate( current_time( 'timestamp', get_option( 'gmt_offset' ) ) );
		$now = getdate( current_time( 'timestamp', get_option( 'gmt_offset' ) ) - 2592000 );

		// get color scheme global
		global $_wp_admin_css_colors;

		// set default color;
		$color = [
			'r'	=> 105,
			'g'	=> 168,
			'b'	=> 187
		];

		if ( ! empty( $_wp_admin_css_colors ) ) {
			// get current admin color scheme name
			$current_color_scheme = get_user_option( 'admin_color' );

			if ( empty( $current_color_scheme ) )
				$current_color_scheme = 'fresh';

			if ( isset( $_wp_admin_css_colors[$current_color_scheme] ) )
				$color = $this->hex2rgb( $_wp_admin_css_colors[$current_color_scheme]->colors[2] );
		}

		// set chart labels
		switch ( $period ) {
			case 'this_week':
				$data = [
					'text' => [
						'xAxes'	=> date_i18n( 'F Y' ),
						'yAxes'	=> __( 'Post Views', 'post-views-counter' )
					]
				];

				for ( $day = 0; $day <= 6; $day ++ ) {
					$date = strtotime( $now['mday'] . '-' . $now['mon'] . '-' . $now['year'] . ' + ' . $day . ' days - ' . $now['wday'] . ' days' );
					$query = new WP_Query(
						wp_parse_args(
							$query_args,
							[
								'views_query' => [
									'year'	=> date( 'Y', $date ),
									'month'	=> date( 'n', $date ),
									'day'	=> date( 'd', $date )
								]
							]
						)
					);

					$data['data']['labels'][] = date_i18n( 'j', $date );
					$data['data']['datasets'][$type_name]['label'] = __( 'Post Views', 'post-views-counter' );
					$data['data']['datasets'][0]['data'][] = $query->total_views;
				}
				break;

			case 'this_year':
				$data = [
					'text'		=> [
						'xAxes'	=> __( 'Year', 'post-views-counter' ) . date( ' Y' ),
						'yAxes'	=> __( 'Post Views', 'post-views-counter' ),
					],
					'design'	=> [
						'fill'					=> true,
						'backgroundColor'		=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 0.2)',
						'borderColor'			=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'borderWidth'			=> 1.2,
						'borderDash'			=> [],
						'pointBorderColor'		=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'pointBackgroundColor'	=> 'rgba(255, 255, 255, 1)',
						'pointBorderWidth'		=> 1.2
					]
				];

				$data['data']['datasets'][0]['label'] = __( 'Total Views', 'post-views-counter' );
				$data['data']['datasets'][0]['post_type'] = '_pvc_total_views';

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
					$data['data']['datasets'][$id]['data'] = [];

					// get month views
					$post_type_data[$id] = array_values(
						pvc_get_views(
							[
								'fields'		=> 'date=>views',
								'post_type'		=> $post_type,
								'views_query'	=> [
									'year'	=> date( 'Y' ),
									'month'	=> '',
									'week'	=> '',
									'day'	=> ''
								]
							]
						)
					);
				}

				$sum = [];

				foreach ( $post_type_data as $post_type_id => $post_views ) {
					foreach ( $post_views as $id => $views ) {
						// generate chart data for specific post types
						$data['data']['datasets'][$post_type_id]['data'][] = $views;

						if ( ! array_key_exists( $id, $sum ) )
							$sum[$id] = 0;

						$sum[$id] += $views;
					}
				}

				// this month all days
				for ( $i = 1; $i <= 12; $i ++ ) {
					// generate chart data
					$data['data']['labels'][] = $i;
					$data['data']['dates'][] = date_i18n( 'F Y', strtotime( date( 'Y' ) . '-' . str_pad( $i, 2, '0', STR_PAD_LEFT ) . '-01' ) );
					$data['data']['datasets'][0]['data'][] = $sum[$i - 1];
				}
				break;

			case 'this_month':
			default:
				$user_options = $this->get_dashboard_user_options( get_current_user_id(), 'post_types' );

				if ( $period !== 'this_month' ) {
					$date = explode( '|', $period, 2 );
					$months = strtotime( (string) $date[1] . '-' . (string) $date[0] . '-13' );
				} else
					$months = current_time( 'timestamp', false );

				// get date chunks
				$date = explode( ' ', date( "m Y t F", $months ) );

				$data = [
					'months'	=> $this->generate_months( $months ),
					'text'		=> [
						'xAxes'	=> $date[3] . ' ' . $date[1],
						'yAxes'	=> __( 'Post Views', 'post-views-counter' ),
					],
					'design'	=> [
						'fill'					=> true,
						'backgroundColor'		=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 0.2)',
						'borderColor'			=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'borderWidth'			=> 1.2,
						'borderDash'			=> [],
						'pointBorderColor'		=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'pointBackgroundColor'	=> 'rgba(255, 255, 255, 1)',
						'pointBorderWidth'		=> 1.2
					]
				];

				$data['data']['datasets'][0]['label'] = __( 'Total Views', 'post-views-counter' );
				$data['data']['datasets'][0]['post_type'] = '_pvc_total_views';
				$data['data']['datasets'][0]['hidden'] = in_array( '_pvc_total_views', $user_options, true );

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
						pvc_get_views(
							[
								'fields'		=> 'date=>views',
								'post_type'		=> $post_type,
								'views_query'	=> [
									'year'	=> $date[1],
									'month'	=> $date[0],
									'week'	=> '',
									'day'	=> ''
								]
							]
						)
					);
				}

				$sum = [];

				foreach ( $post_type_data as $post_type_id => $post_views ) {
					foreach ( $post_views as $id => $views ) {
						// generate chart data for specific post types
						$data['data']['datasets'][$post_type_id]['data'][] = $views;

						if ( ! array_key_exists( $id, $sum ) )
							$sum[$id] = 0;

						$sum[$id] += $views;
					}
				}

				// this month all days
				for ( $i = 1; $i <= $date[2]; $i ++ ) {
					// generate chart data
					$data['data']['labels'][] = ( $i % 2 === 0 ? '' : $i );
					$data['data']['dates'][] = date_i18n( get_option( 'date_format' ), strtotime( $date[1] . '-' . $date[0] . '-' . str_pad( $i, 2, '0', STR_PAD_LEFT ) ) );
					$data['data']['datasets'][0]['data'][] = $sum[$i - 1];
				}
				break;
		}

		echo json_encode( $data );

		exit;
	}
	
	/**
	 * Dashboard widget chart data function.
	 * 
	 * @global $_wp_admin_css_colors
	 * @return void
	 */
	public function dashboard_get_most_viewed() {
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) )
			wp_die( _( 'You do not have permission to access this page.', 'post-views-counter' ) );

		if ( ! check_ajax_referer( 'pvc-dashboard-chart', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		// get period
		$period = isset( $_POST['period'] ) ? sanitize_text_field( $_POST['period'] ) : 'this_month';

		// get post types
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];
		
		if ( $period !== 'this_month' ) {
			$date = explode( '|', $period, 2 );
			$months = strtotime( (string) $date[1] . '-' . (string) $date[0] . '-13' );
		} else
			$months = current_time( 'timestamp', false );

		// get date chunks
		$date = explode( ' ', date( "m Y t F", $months ) );

		// get stats
		$query_args = [
			'post_type'			=> $post_types,
			'posts_per_page'	=> 10,
			'paged'				=> false,
			'suppress_filters'	=> false,
			'no_found_rows'		=> true,
			'views_query'		=> [
				'year'	=> $date[1],
				'month'	=> $date[0],
			]
		];

		$posts = pvc_get_most_viewed_posts( $query_args );

		$data = [
			'months'	=> $this->generate_months( $months ),
			'html'		=> '',
		];

		$html = '<table id="pvc-most-viewed-table" class="pvc-table pvc-table-hover">';
		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th scope="col">#</th>';
		$html .= '<th scope="col">' . __( 'Post', 'post-views-counter' ) . '</th>';
		$html .= '<th scope="col">' . __( 'Post Views', 'post-views-counter' ) . '</th>';
		$html .= '</tr>';
		$html .= '</thead>';
		$html .= '<tbody>';

		if ( $posts ) {
			foreach ( $posts as $index => $post ) {
				setup_postdata( $post );

				$html .= '<tr>';
				$html .= '<th scope="col">' . ( $index + 1 ) . '</th>';

				if ( current_user_can( 'edit_post', $post->ID ) )
					$html .= '<td><a href="' . get_edit_post_link( $post->ID ) . '">' . get_the_title( $post ) . '</a></td>';
				else
					$html .= '<td>' . get_the_title( $post ). '</td>';

				$html .= '<td>' . number_format_i18n( $post->post_views ) . '</td>';
				$html .= '</tr>';
			}
		} else {
			$html .= '<tr class="no-posts">';
			$html .= '<td colspan="3">' . __( 'No most viewed posts found', 'post-views-counter' ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody>';
		$html .= '</table>';

		$data['html'] = $html;

		echo json_encode( $data );

		exit;
	}
	
	/**
	 * Generate months.
	 *
	 * @param int $timestamp
	 * @return string
	 */
	public function generate_months( $timestamp ) {
		$dates = [
			explode( ' ', date( "m F Y", strtotime( "-1 months", $timestamp ) ) ),
			explode( ' ', date( "m F Y", $timestamp ) ),
			explode( ' ', date( "m F Y", strtotime( "+1 months", $timestamp ) ) )
		];

		$current = date( "Ym", current_time( 'timestamp', false ) );

		if ( (int) $current <= (int) ( $dates[1][2] . $dates[1][0] ) )
			$next = '<span class="next">' . $dates[2][1] . ' ' . $dates[2][2] . ' ›</span>';
		else
			$next = '<a class="next" href="#" data-date="' . ( $dates[2][0] . '|' . $dates[2][2] ) . '">' . $dates[2][1] . ' ' . $dates[2][2] . ' ›</a>';

		$dates = [
			'prev'		=> '<a class="prev" href="#" data-date="' . ( $dates[0][0] . '|' . $dates[0][2] ) . '">‹ ' . $dates[0][1] . ' ' . $dates[0][2] . '</a>',
			'current'	=> '<span class="current">' . $dates[1][1] . ' ' . $dates[1][2] . '</span>',
			'next'		=> $next
		];

		return $dates['prev'] . $dates['current'] . $dates['next'];
	}

	/**
	 * Dashboard widget chart user post types.
	 *
	 * @return void
	 */
	public function update_dashboard_user_options() {
		if ( ! check_ajax_referer( 'pvc-dashboard-user-options', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		// get allowed post types
		$allowed_post_types = Post_Views_Counter()->options['general']['post_types_count'];

		// simulate total views as post type
		$allowed_post_types[] = '_pvc_total_views';

		// get allowed menu items
		$allowed_menu_items = [ 'post-views', 'most-viewed' ];

		// valid data?
		if ( isset( $_POST['nonce'], $_POST['options'] ) && ! empty( $_POST['options'] ) ) {
			// get options
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
			if ( ! empty( $update['post_type'] ) && in_array( $update['post_type'], $allowed_post_types, true ) ) {
				if ( isset( $update['hidden'] ) && $update['hidden'] === 'true' ) {
					if ( ! in_array( $update['post_type'], $user_options['post_types'], true ) )
						$user_options['post_types'][] = $update['post_type'];
				} else {
					if ( ( $key = array_search( $update['post_type'], $user_options['post_types'] ) ) !== false )
						unset( $user_options['post_types'][$key] );
				}
			}

			// hide menu item?
			$user_options['menu_items'] = [];

			if ( ! empty( $update['menu_items'] ) && is_array( $update['menu_items'] ) ) {
				$update['menu_items'] = map_deep( $update['menu_items'], 'sanitize_text_field' );

				foreach ( $update['menu_items'] as $menu_item => $hidden ) {
					if ( in_array( $menu_item, $allowed_menu_items ) && $hidden === 'true' )
						$user_options['menu_items'][] = $menu_item;					
				}
			}

			// update userdata
			update_user_meta( $user_id, 'pvc_dashboard', $user_options );
		}

		exit;
	}

	/**
	 * Get user dashboard data.
	 *
	 * @param int $user_id
	 * @param string $data_type
	 * @return array
	 */
	public function get_dashboard_user_options( $user_id, $data_type ) {
		$user_options = get_user_meta( $user_id, 'pvc_dashboard', true );

		if ( ! is_array( $user_options ) || empty( $user_options ) )
			$user_options = [];

		if ( ! array_key_exists( $data_type, $user_options ) || ! is_array( $user_options[$data_type] ) )
			$user_options[$data_type] = [];

		return $user_options[$data_type];
	}

	/**
	 * Convert hex to rgb color.
	 *
	 * @param string $color
	 * @return bool
	 */
	public function hex2rgb( $color ) {
		if ( $color[0] == '#' )
			$color = substr( $color, 1 );

		if ( strlen( $color ) == 6 )
			list( $r, $g, $b ) = [ $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] ];
		elseif ( strlen( $color ) == 3 )
			list( $r, $g, $b ) = [ $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] ];
		else
			return false;

		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );

		return [ 'r' => $r, 'g' => $g, 'b' => $b ];
	}
}
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

	public function __construct() {
		// actions
		add_action( 'wp_dashboard_setup', array( $this, 'wp_dashboard_setup' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_styles' ) );
		add_action( 'wp_ajax_pvc_dashboard_chart', array( $this, 'dashboard_widget_chart' ) );
		add_action( 'wp_ajax_pvc_dashboard_chart_user_post_types', array( $this, 'dashboard_widget_chart_user_post_types' ) );
	}

	/**
	 * Initialize widget.
	 */
	public function wp_dashboard_setup() {
		// filter user_can_see_stats
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) ) {
			return;
		}

		// add dashboard widget
		wp_add_dashboard_widget( 'pvc_dashboard', __( 'Post Views', 'post-views-counter' ), array( $this, 'dashboard_widget' ) );
	}

	/**
	 * Render dashboard widget.
	 *
	 * @return mixed
	 */
	public function dashboard_widget() {
		?>
		<div id="pvc_dashboard_container">
			<canvas id="pvc_chart" height="175"></canvas>
			<div class="pvc_months">

			<?php echo $this->generate_months( current_time( 'timestamp', false ) ); ?>

			</div>
		</div>
		<?php
	}

	/**
	 * Generate months.
	 *
	 * @param string $timestamp
	 * @return string
	 */
	public function generate_months( $timestamp ) {
		$dates = array(
			explode( ' ', date( "m F Y", strtotime( "-1 months", $timestamp ) ) ),
			explode( ' ', date( "m F Y", $timestamp ) ),
			explode( ' ', date( "m F Y", strtotime( "+1 months", $timestamp ) ) )
		);

		$current = date( "Ym", current_time( 'timestamp', false ) );

		if ( (int) $current <= (int) ( $dates[1][2] . $dates[1][0] ) )
			$next = '<span class="next">' . $dates[2][1] . ' ' . $dates[2][2] . ' ›</span>';
		else
			$next = '<a class="next" href="#" data-date="' . ( $dates[2][0] . '|' . $dates[2][2] ) . '">' . $dates[2][1] . ' ' . $dates[2][2] . ' ›</a>';

		$dates = array(
			'prev'		=> '<a class="prev" href="#" data-date="' . ( $dates[0][0] . '|' . $dates[0][2] ) . '">‹ ' . $dates[0][1] . ' ' . $dates[0][2] . '</a>',
			'current'	=> '<span class="current">' . $dates[1][1] . ' ' . $dates[1][2] . '</span>',
			'next'		=> $next
		);

		return $dates['prev'] . $dates['current'] . $dates['next'];
	}

	/**
	 * Dashboard widget chart user post types.
	 * 
	 * @return void
	 */
	public function dashboard_widget_chart_user_post_types() {
		if ( ! check_ajax_referer( 'dashboard-chart-user-post-types', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		// get post types
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		// simulate total views as post type
		$post_types[] = '_pvc_total_views';

		// valid data?
		if ( isset( $_POST['nonce'], $_POST['hidden'], $_POST['post_type'] ) && ( in_array( $_POST['post_type'], $post_types, true ) ) ) {
			// get user ID
			$user_id = get_current_user_id();

			// get user dashboard data
			$userdata = get_user_meta( $user_id, 'pvc_dashboard', true );

			// empty userdata?
			if ( ! is_array( $userdata ) || empty( $userdata ) )
				$userdata = array();

			// empty post types?
			if ( ! array_key_exists( 'post_types', $userdata ) || ! is_array( $userdata['post_types'] ) )
				$userdata['post_types'] = array();

			// hide post type?
			if ( $_POST['hidden'] === 'true' ) {
				if ( ! in_array( $_POST['post_type'], $userdata['post_types'], true ) )
					$userdata['post_types'][] = $_POST['post_type'];
			} else {
				if ( ( $key = array_search( $_POST['post_type'], $userdata['post_types'] ) ) !== false )
					unset( $userdata['post_types'][$key] );
			}

			// update userdata
			update_user_meta( $user_id, 'pvc_dashboard', $userdata );
		}

		exit;
	}

	/**
	 * Dashboard widget chart data function.
	 * 
	 * @global $_wp_admin_css_colors
	 * @return void
	 */
	public function dashboard_widget_chart() {
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) )
			wp_die( _( 'You do not have permission to access this page.', 'post-views-counter' ) );

		if ( ! check_ajax_referer( 'dashboard-chart', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		// get period
		$period = isset( $_POST['period'] ) ? esc_attr( $_POST['period'] ) : 'this_month';

		// get post types
		$post_types = Post_Views_Counter()->options['general']['post_types_count'];

		// get stats
		$query_args = array(
			'post_type'			 => $post_types,
			'posts_per_page'	 => -1,
			'paged'				 => false,
			'orderby'			 => 'post_views',
			'suppress_filters'	 => false,
			'no_found_rows'		 => true
		);

		// $now = getdate( current_time( 'timestamp', get_option( 'gmt_offset' ) ) );
		$now = getdate( current_time( 'timestamp', get_option( 'gmt_offset' ) ) - 2592000 );

		// get admin color scheme
		global $_wp_admin_css_colors;

		$admin_color = get_user_option( 'admin_color' );
		$colors = $_wp_admin_css_colors[$admin_color]->colors;
		$color = $this->hex2rgb( $colors[2] );

		// set chart labels
		switch ( $period ) {
			case 'this_week':
				$data = array(
					'text' => array(
						'xAxes'	 => date_i18n( 'F Y' ),
						'yAxes'	 => __( 'Post Views', 'post-views-counter' )
					)
				);

				for ( $day = 0; $day <= 6; $day ++ ) {
					$date = strtotime( $now['mday'] . '-' . $now['mon'] . '-' . $now['year'] . ' + ' . $day . ' days - ' . $now['wday'] . ' days' );
					$query = new WP_Query( wp_parse_args( $query_args, array( 'views_query' => array( 'year' => date( 'Y', $date ), 'month' => date( 'n', $date ), 'day' => date( 'd', $date ) ) ) ) );

					$data['data']['labels'][] = date_i18n( 'j', $date );
					$data['data']['datasets'][$type_name]['label'] = __( 'Post Views', 'post-views-counter' );
					$data['data']['datasets'][0]['data'][] = $query->total_views;
				}
				break;

			case 'this_year':
				$data = array(
					'text'		=> array(
						'xAxes'	=> __( 'Year', 'post-views-counter' ) . date( ' Y' ),
						'yAxes'	=> __( 'Post Views', 'post-views-counter' ),
					),
					'design'	=> array(
						'fill'					=> true,
						'backgroundColor'		=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 0.2)',
						'borderColor'			=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'borderWidth'			=> 1.2,
						'borderDash'			=> array(),
						'pointBorderColor'		=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'pointBackgroundColor'	=> 'rgba(255, 255, 255, 1)',
						'pointBorderWidth'		=> 1.2
					)
				);

				$data['data']['datasets'][0]['label'] = __( 'Total Views', 'post-views-counter' );
				$data['data']['datasets'][0]['post_type'] = '_pvc_total_views';

				// reindex post types
				$post_types = array_combine( range( 1, count( $post_types ) ), array_values( $post_types ) );

				$post_type_data = array();

				foreach ( $post_types as $id => $post_type ) {
					$post_type_obj = get_post_type_object( $post_type );

					$data['data']['datasets'][$id]['label'] = $post_type_obj->labels->name;
					$data['data']['datasets'][$id]['post_type'] = $post_type_obj->name;
					$data['data']['datasets'][$id]['data'] = array();

					// get month views
					$post_type_data[$id] = array_values(
						pvc_get_views(
							array(
								'fields'		 => 'date=>views',
								'post_type'		 => $post_type,
								'views_query'	 => array(
									'year'	 => date( 'Y' ),
									'month'	 => '',
									'week'	 => '',
									'day'	 => ''
								)
							)
						)
					);
				}

				$sum = array();

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
				$userdata = $this->get_dashboard_user_data( get_current_user_id(), 'post_types' );

				if ( $period !== 'this_month' ) {
					$date = explode( '|', $period, 2 );
					$months = strtotime( (string) $date[1] . '-' . (string) $date[0] . '-13' );
				} else
					$months = current_time( 'timestamp', false );

				// get date chunks
				$date = explode( ' ', date( "m Y t F", $months ) );

				$data = array(
					'months'	=> $this->generate_months( $months ),
					'text'		=> array(
						'xAxes'	=> $date[3] . ' ' . $date[1],
						'yAxes'	=> __( 'Post Views', 'post-views-counter' ),
					),
					'design'	=> array(
						'fill'					=> true,
						'backgroundColor'		=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 0.2)',
						'borderColor'			=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'borderWidth'			=> 1.2,
						'borderDash'			=> array(),
						'pointBorderColor'		=> 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'pointBackgroundColor'	=> 'rgba(255, 255, 255, 1)',
						'pointBorderWidth'		=> 1.2
					)
				);

				$data['data']['datasets'][0]['label'] = __( 'Total Views', 'post-views-counter' );
				$data['data']['datasets'][0]['post_type'] = '_pvc_total_views';
				$data['data']['datasets'][0]['hidden'] = in_array( '_pvc_total_views', $userdata, true );

				// reindex post types
				$post_types = array_combine( range( 1, count( $post_types ) ), array_values( $post_types ) );

				$post_type_data = array();

				foreach ( $post_types as $id => $post_type ) {
					$post_type_obj = get_post_type_object( $post_type );

					$data['data']['datasets'][$id]['label'] = $post_type_obj->labels->name;
					$data['data']['datasets'][$id]['post_type'] = $post_type_obj->name;
					$data['data']['datasets'][$id]['hidden'] = in_array( $post_type_obj->name, $userdata, true );
					$data['data']['datasets'][$id]['data'] = array();

					// get month views
					$post_type_data[$id] = array_values(
						pvc_get_views(
							array(
								'fields'		 => 'date=>views',
								'post_type'		 => $post_type,
								'views_query'	 => array(
									'year'	 => $date[1],
									'month'	 => $date[0],
									'week'	 => '',
									'day'	 => ''
								)
							)
						)
					);
				}

				$sum = array();

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
	 * Get user dashboard data.
	 *
	 * @param string $data_type
	 * @return array
	 */
	public function get_dashboard_user_data( $user_id, $data_type ) {
		$userdata = get_user_meta( $user_id, 'pvc_dashboard', true );

		if ( ! is_array( $userdata ) || empty( $userdata ) )
			$userdata = array();

		if ( ! array_key_exists( $data_type, $userdata ) || ! is_array( $userdata[$data_type] ) )
			$userdata[$data_type] = array();

		return $userdata[$data_type];
	}

	/**
	 * Enqueue admin scripts and styles.
	 * 
	 * @param string $pagenow
	 */
	public function admin_scripts_styles( $pagenow ) {
		if ( $pagenow != 'index.php' )
			return;

		// filter user_can_see_stats
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) )
			return;

		wp_register_style( 'pvc-admin-dashboard', POST_VIEWS_COUNTER_URL . '/css/admin-dashboard.css' );
		wp_enqueue_style( 'pvc-admin-dashboard' );
		wp_enqueue_style( 'pvc-chart-css', POST_VIEWS_COUNTER_URL . '/assets/chartjs/chart.min.css' );

		wp_register_script( 'pvc-chart', POST_VIEWS_COUNTER_URL . '/assets/chartjs/chart.min.js', array( 'jquery' ), Post_Views_Counter()->defaults['version'], true );
		wp_register_script( 'pvc-admin-dashboard', POST_VIEWS_COUNTER_URL . '/js/admin-dashboard.js', array( 'jquery', 'pvc-chart' ), Post_Views_Counter()->defaults['version'], true );

		wp_enqueue_script( 'pvc-admin-dashboard' );

		wp_localize_script(
			'pvc-admin-dashboard',
			'pvcArgs',
			array(
				'ajaxURL'	 => admin_url( 'admin-ajax.php' ),
				'nonce'		 => wp_create_nonce( 'dashboard-chart' ),
				'nonceUser'	 => wp_create_nonce( 'dashboard-chart-user-post-types' )
			)
		);
	}

	/**
	 * Convert hex to rgb color.
	 * 
	 * @param type $color
	 * @return boolean
	 */
	public function hex2rgb( $color ) {
		if ( $color[0] == '#' ) {
			$color = substr( $color, 1 );
		}
		if ( strlen( $color ) == 6 ) {
			list( $r, $g, $b ) = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) == 3 ) {
			list( $r, $g, $b ) = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return false;
		}
		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );
		return array( 'r' => $r, 'g' => $g, 'b' => $b );
	}

}

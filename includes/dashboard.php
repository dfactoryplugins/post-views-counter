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
		wp_add_dashboard_widget( 'pvc_dashboard', __( 'Post Views', 'post-views-counter' ), array( $this, 'dashboard_widget' ) /* , array( $this, 'dashboard_widget_control' ) */ );
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
		</div>
		<?php
	}

	/**
	 * Dashboard widget settings.
	 *
	 * @return mixed
	 */
	public function dashboard_widget_control() {
		
	}

	/**
	 * Dashboard widget chart data function.
	 * 
	 * @global $_wp_admin_css_colors
	 * @return mixed
	 */
	public function dashboard_widget_chart() {

		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) )
			wp_die( _( 'You do not have permission to access this page.', 'post-views-counter' ) );

		if ( ! check_ajax_referer( 'dashboard-chart', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		// $period = isset( $_REQUEST['period'] ) ? esc_attr( $_REQUEST['period'] ) : 'this_month';

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

		// set range
		$range = 'this_month';

		// $now = getdate( current_time( 'timestamp', get_option( 'gmt_offset' ) ) );
		$now = getdate( current_time( 'timestamp', get_option( 'gmt_offset' ) ) - 2592000 );

		// get admin color scheme
		global $_wp_admin_css_colors;

		$admin_color = get_user_option( 'admin_color' );
		$colors = $_wp_admin_css_colors[$admin_color]->colors;
		$color = $this->hex2rgb( $colors[2] );

		// set chart labels
		switch ( $range ) {
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
					'text'	 => array(
						'xAxes'	 => __( 'Year', 'post-views-counter' ) . date( ' Y' ),
						'yAxes'	 => __( 'Post Views', 'post-views-counter' ),
					),
					'design' => array(
						'fill'					 => true,
						'backgroundColor'		 => 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 0.2)',
						'borderColor'			 => 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'borderWidth'			 => 1.2,
						'borderDash'			 => array(),
						'pointBorderColor'		 => 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'pointBackgroundColor'	 => 'rgba(255, 255, 255, 1)',
						'pointBorderWidth'		 => 1.2
					)
				);

				$data['data']['datasets'][0]['label'] = __( 'Total Views', 'post-views-counter' );

				// get data for specific post types
				$empty_post_type_views = array();

				// reindex post types
				$post_types = array_combine( range( 1, count( $post_types ) ), array_values( $post_types ) );

				$post_type_data = array();

				foreach ( $post_types as $id => $post_type ) {
					$empty_post_type_views[$post_type] = 0;
					$post_type_obj = get_post_type_object( $post_type );

					$data['data']['datasets'][$id]['label'] = $post_type_obj->labels->name;
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
			default :
				$data = array(
					'text'	 => array(
						'xAxes'	 => date_i18n( 'F Y' ),
						'yAxes'	 => __( 'Post Views', 'post-views-counter' ),
					),
					'design' => array(
						'fill'					 => true,
						'backgroundColor'		 => 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 0.2)',
						'borderColor'			 => 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'borderWidth'			 => 1.2,
						'borderDash'			 => array(),
						'pointBorderColor'		 => 'rgba(' . $color['r'] . ',' . $color['g'] . ',' . $color['b'] . ', 1)',
						'pointBackgroundColor'	 => 'rgba(255, 255, 255, 1)',
						'pointBorderWidth'		 => 1.2
					)
				);

				$data['data']['datasets'][0]['label'] = __( 'Total Views', 'post-views-counter' );

				// get data for specific post types
				$empty_post_type_views = array();

				// reindex post types
				$post_types = array_combine( range( 1, count( $post_types ) ), array_values( $post_types ) );

				$post_type_data = array();

				foreach ( $post_types as $id => $post_type ) {
					$empty_post_type_views[$post_type] = 0;
					$post_type_obj = get_post_type_object( $post_type );

					$data['data']['datasets'][$id]['label'] = $post_type_obj->labels->name;
					$data['data']['datasets'][$id]['data'] = array();

					// get month views
					$post_type_data[$id] = array_values(
					pvc_get_views(
					array(
						'fields'		 => 'date=>views',
						'post_type'		 => $post_type,
						'views_query'	 => array(
							'year'	 => date( 'Y' ),
							'month'	 => date( 'm' ),
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
				for ( $i = 1; $i <= date( 't' ); $i ++ ) {
					// generate chart data
					$data['data']['labels'][] = ( $i % 2 === 0 ? '' : $i );
					$data['data']['dates'][] = date_i18n( get_option( 'date_format' ), strtotime( date( 'Y' ) . '-' . date( 'm' ) . '-' . str_pad( $i, 2, '0', STR_PAD_LEFT ) ) );
					$data['data']['datasets'][0]['data'][] = $sum[$i - 1];
				}
				break;
		}

		echo json_encode( $data );

		exit;
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
		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) ) {
			return;
		}

		wp_register_style(
		'pvc-admin-dashboard', POST_VIEWS_COUNTER_URL . '/css/admin-dashboard.css'
		);
		wp_enqueue_style( 'pvc-admin-dashboard' );

		wp_register_script(
		'pvc-admin-dashboard', POST_VIEWS_COUNTER_URL . '/js/admin-dashboard.js', array( 'jquery', 'pvc-chart' ), Post_Views_Counter()->defaults['version'], true
		);

		wp_register_script(
		'pvc-chart', POST_VIEWS_COUNTER_URL . '/js/chart.min.js', array( 'jquery' ), Post_Views_Counter()->defaults['version'], true
		);

		// set ajax args
		$ajax_args = array(
			'ajaxURL'	 => admin_url( 'admin-ajax.php' ),
			'nonce'		 => wp_create_nonce( 'dashboard-chart' )
		);

		wp_enqueue_script( 'pvc-admin-dashboard' );
		// wp_enqueue_script( 'pvc-chart' );

		wp_localize_script(
		'pvc-admin-dashboard', 'pvcArgs', $ajax_args
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

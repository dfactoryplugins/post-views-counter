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
	 * @return mixed
	 */
	public function dashboard_widget_chart() {

		if ( ! apply_filters( 'pvc_user_can_see_stats', current_user_can( 'publish_posts' ) ) )
			wp_die( _( 'You do not have permission to access this page.', 'post-views-counter' ) );

		if ( ! check_ajax_referer( 'dashboard-chart', 'nonce' ) )
			wp_die( __( 'You do not have permission to access this page.', 'post-views-counter' ) );

		$period = isset( $_REQUEST['period'] ) ? esc_attr( $_REQUEST['period'] ) : 'this_month';

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
		$now = getdate( current_time( 'timestamp', get_option( 'gmt_offset' ) ) );

		// set chart labels
		switch ( $range ) {
			case 'this_week' :
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

			case 'this month' :
			default :
				$data = array(
					'text'	 => array(
						'xAxes'	 => date_i18n( 'F Y' ),
						'yAxes'	 => __( 'Post Views', 'post-views-counter' ),
					),
					'design' => array(
						'fill'					 => true,
						'backgroundColor'		 => 'rgba(50, 143, 186, 0.2)',
						'borderColor'			 => 'rgba(50, 143, 186, 1)',
						'borderWidth'			 => 2,
						'borderDash'			 => array(),
						'pointBorderColor'		 => 'rgba(50, 143, 186, 1)',
						'pointBackgroundColor'	 => 'rgba(255, 255, 255, 1)',
						'pointBorderWidth'		 => 1.2
					)
				);

				$data['data']['datasets'][0]['label'] = __( 'Total Views', 'post-views-counter' );

				// get data for specific post types
				$empty_post_type_views = array();

				// reindex post types
				$post_types = array_combine( range( 1, count( $post_types ) ), array_values( $post_types ) );

				foreach ( $post_types as $id => $post_type ) {
					$empty_post_type_views[$post_type] = 0;
					$post_type_obj = get_post_type_object( $post_type );

					$data['data']['datasets'][$id]['label'] = $post_type_obj->labels->name;
					$data['data']['datasets'][$id]['data'] = array();
				}

				// reverse post types
				$rev_post_types = array_flip( $post_types );

				// this month day loop
				for ( $i = 1; $i <= date( 't' ); $i ++ ) {

					if ( $i <= $now['mday'] ) {

						$query = new WP_Query( wp_parse_args( $query_args, array( 'views_query' => array( 'year' => date( 'Y' ), 'month' => date( 'n' ), 'day' => str_pad( $i, 2, '0', STR_PAD_LEFT ) ) ) ) );

						// get data for specific post types
						$post_type_views = $empty_post_type_views;

						if ( ! empty( $query->posts ) ) {
							foreach ( $query->posts as $index => $post ) {
								$post_type_views[$post->post_type] += $post->post_views;
							}
						}
					} else {

						$post_type_views = $empty_post_type_views;

						foreach ( $post_types as $id => $post_type ) {
							$post_type_views[$post_type] = 0;
						}

						$query->total_views = 0;
					}

					// generate chart data
					$data['data']['labels'][] = ( $i % 2 === 0 ? '' : $i );
					$data['data']['dates'][] = date_i18n( get_option( 'date_format' ), strtotime( date( 'Y' ) . '-' . date( 'n' ) . '-' . str_pad( $i, 2, '0', STR_PAD_LEFT ) ) );
					$data['data']['datasets'][0]['data'][] = $query->total_views;

					// generate chart data for specific post types
					foreach ( $post_type_views as $type_name => $type_views ) {
						$data['data']['datasets'][$rev_post_types[$type_name]]['data'][] = $type_views;
					}
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

}

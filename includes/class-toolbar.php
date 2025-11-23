<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Toolbar class.
 *
 * @class Post_Views_Counter_Toolbar
 */
class Post_Views_Counter_Toolbar {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'wp_loaded', [ $this, 'maybe_load_admin_bar_menu' ] );
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

		// break if display is not allowed
		if ( empty( $post_types ) || empty( $post ) || ! in_array( $post->post_type, $post_types, true ) )
			return;

		if ( apply_filters( 'pvc_admin_display_post_views', true ) === false )
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

		// break if display is not allowed
		if ( empty( $post_types ) || empty( $post ) || ! in_array( $post->post_type, $post_types, true ) )
			return;

		if ( apply_filters( 'pvc_admin_display_post_views', true ) === false )
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

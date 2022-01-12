<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Frontend class.
 *
 * @class Post_Views_Counter_Frontend
 */
class Post_Views_Counter_Frontend {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'after_setup_theme', [ $this, 'register_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
		add_action( 'wp', [ $this, 'run' ] );
	}

	/**
	 * Register post-views shortcode function.
	 *
	 * @return void
	 */
	public function register_shortcode() {
		add_shortcode( 'post-views', [ $this, 'post_views_shortcode' ] );
	}

	/**
	 * Post views shortcode function.
	 *
	 * @param array $args
	 * @return string
	 */
	public function post_views_shortcode( $args ) {
		$defaults = [
			'id' => get_the_ID()
		];

		$args = shortcode_atts( $defaults, $args );

		return pvc_post_views( $args['id'], false );
	}

	/**
	 * Set up plugin hooks.
	 *
	 * @return void
	 */
	public function run() {
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
			return;

		$filter = apply_filters( 'pvc_shortcode_filter_hook', Post_Views_Counter()->options['display']['position'] );

		if ( ! empty( $filter ) && in_array( $filter, [ 'before', 'after' ] ) ) {
			// post content
			add_filter( 'the_content', [ $this, 'add_post_views_count' ] );

			// bbpress support
			add_action( 'bbp_template_' . $filter . '_single_topic', [ $this, 'display_bbpress_post_views' ] );
			add_action( 'bbp_template_' . $filter . '_single_forum', [ $this, 'display_bbpress_post_views' ] );
		} else {
			// custom
			if ( $filter !== 'manual' && is_string( $filter ) )
				add_filter( $filter, [ $this, 'add_post_views_count' ] );
		}
	}

	/**
	 * Add post views counter to forum/topic of bbPress.
	 *
	 * @return void
	 */
	public function display_bbpress_post_views() {
		$post_id = get_the_ID();

		// check only for forums and topics
		if ( bbp_is_forum( $post_id ) || bbp_is_topic( $post_id ) )
			echo $this->add_post_views_count( '' );
	}

	/**
	 * Add post views counter to content.
	 *
	 * @param string $content
	 * @return string
	 */
	public function add_post_views_count( $content = '' ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$display = false;

		// page visibility check
		if ( ! empty( $pvc->options['display']['page_types_display'] ) ) {
			foreach ( $pvc->options['display']['page_types_display'] as $page ) {
				switch ( $page ) {
					case 'singular':
						if ( is_singular( $pvc->options['display']['post_types_display'] ) )
							$display = true;
						break;

					case 'archive':
						if ( is_archive() )
							$display = true;
						break;

					case 'search':
						if ( is_search() )
							$display = true;
						break;

					case 'home':
						if ( is_home() || is_front_page() )
							$display = true;
						break;
				}
			}
		}

		// get groups to check it faster
		$groups = $pvc->options['display']['restrict_display']['groups'];

		// whether to display views
		if ( is_user_logged_in() ) {
			// exclude logged in users?
			if ( in_array( 'users', $groups, true ) )
				$display = false;
			// exclude specific roles?
			elseif ( in_array( 'roles', $groups, true ) && $pvc->counter->is_user_role_excluded( get_current_user_id(), $pvc->options['display']['restrict_display']['roles'] ) )
				$display = false;
		// exclude guests?
		} elseif ( in_array( 'guests', $groups, true ) )
			$display = false;

		// we don't want to mess custom loops
		if ( ! in_the_loop() && ! class_exists( 'bbPress' ) )
			$display = false;

		if ( (bool) apply_filters( 'pvc_display_views_count', $display ) === true ) {
			$filter = apply_filters( 'pvc_shortcode_filter_hook', $pvc->options['display']['position'] );

			switch ( $filter ) {
				case 'after':
					$content = $content . do_shortcode( '[post-views]' );
					break;

				case 'before':
					$content = do_shortcode( '[post-views]' ) . $content;
					break;

				case 'manual':
				default:
					break;
			}
		}

		return $content;
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @return void
	 */
	public function wp_enqueue_scripts() {
		// get main instance
		$pvc = Post_Views_Counter();

		// enable styles?
		if ( (bool) apply_filters( 'pvc_enqueue_styles', true ) === true ) {
			// load dashicons
			wp_enqueue_style( 'dashicons' );

			// load style
			wp_enqueue_style( 'post-views-counter-frontend', POST_VIEWS_COUNTER_URL . '/css/frontend.css', [], $pvc->defaults['version'] );
		}

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// whether to count this post type or not
		if ( empty( $post_types ) || ! is_singular( $post_types ) )
			return;

		// get counter mode
		$mode = $pvc->options['general']['counter_mode'];

		// specific counter mode?
		if ( in_array( $mode, [ 'js', 'rest_api' ], true ) ) {
			wp_enqueue_script( 'post-views-counter-frontend', POST_VIEWS_COUNTER_URL . '/js/frontend.js', [ 'jquery' ], $pvc->defaults['version'], true );

			$args = [
				'mode'		=> $mode,
				'postID'	=> get_the_ID(),
				'nonce'		=> ( $mode === 'rest_api' ? wp_create_nonce( 'wp_rest' ) : wp_create_nonce( 'pvc-check-post' ) )
			];

			switch ( $mode ) {
				case 'rest_api':
					$args['requestURL'] = rest_url( 'post-views-counter/view-post/' );
					break;

				case 'js':
				default:
					$args['requestURL'] = admin_url( 'admin-ajax.php' );
					break;
			}

			// make it safe
			$args['requestURL'] = esc_url_raw( $args['requestURL'] );

			wp_localize_script( 'post-views-counter-frontend', 'pvcArgsFrontend', apply_filters( 'pvc_frontend_script_args', $args ) );
		}
	}
}
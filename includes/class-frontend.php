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

	private $script_args = [];

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
			'id'	=> get_the_ID(),
			'type'	=> 'post'
		];

		// main item?
		if ( ! in_the_loop() ) {
			// get current object
			$object = get_queried_object();

			// post?
			if ( is_a( $object, 'WP_Post' ) ) {
				$defaults['id'] = $object->ID;
				$defaults['type'] = 'post';
			// term?
			} elseif ( is_a( $object, 'WP_Term' ) ) {
				$defaults['id'] = $object->term_id;
				$defaults['type'] = 'term';
			// user?
			} elseif ( is_a( $object, 'WP_User' ) ) {
				$defaults['id'] = $object->ID;
				$defaults['type'] = 'user';
			}
		}

		// combine attributes
		$args = shortcode_atts( $defaults, $args );

		// default type?
		if ( $args['type'] === 'post' )
			$views = pvc_post_views( $args['id'], false );
		else
			$views = apply_filters( 'pvc_post_views_shortcode', '', $args );

		return $views;
	}

	/**
	 * Display number of post views.
	 *
	 * @return void
	 */
	public function run() {
		if ( is_admin() && ! wp_doing_ajax() )
			return;

		$filter = apply_filters( 'pvc_shortcode_filter_hook', Post_Views_Counter()->options['display']['position'] );

		// valid filter?
		if ( ! empty( $filter ) && in_array( $filter, [ 'before', 'after' ] ) ) {
			// post content
			add_filter( 'the_content', [ $this, 'add_post_views_count' ] );

			// bbpress support
			add_action( 'bbp_template_' . $filter . '_single_topic', [ $this, 'display_bbpress_post_views' ] );
			add_action( 'bbp_template_' . $filter . '_single_forum', [ $this, 'display_bbpress_post_views' ] );
		// custom
		} elseif ( $filter !== 'manual' && is_string( $filter ) )
			add_filter( $filter, [ $this, 'add_post_views_count' ] );
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

		// post type check
		if ( ! empty( $pvc->options['display']['post_types_display'] ) )
			$display = is_singular( $pvc->options['display']['post_types_display'] );

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
	 * Get frontend script arguments.
	 *
	 * @return array
	 */
	public function get_frontend_script_args() {
		return $this->script_args;
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
			wp_enqueue_style( 'post-views-counter-frontend', POST_VIEWS_COUNTER_URL . '/css/frontend' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', [], $pvc->defaults['version'] );
		}

		// skip special requests
		if ( is_preview() || is_feed() || is_trackback() || is_favicon() || is_customize_preview() )
			return;

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// whether to count this post type or not
		if ( empty( $post_types ) || ! is_singular( $post_types ) )
			return;

		// get current post id
		$post_id = (int) get_the_ID();

		// allow to run check post?
		if ( ! (bool) apply_filters( 'pvc_run_check_post', true, $post_id ) )
			return;

		// get counter mode
		$mode = $pvc->options['general']['counter_mode'];

		// specific counter mode?
		if ( in_array( $mode, [ 'js', 'rest_api' ], true ) ) {
			wp_enqueue_script( 'post-views-counter-frontend', POST_VIEWS_COUNTER_URL . '/js/frontend' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', [], $pvc->defaults['version'], true );

			// prepare args
			$args = [
				'mode'			=> $mode,
				'postID'		=> $post_id,
				'requestURL'	=> '',
				'nonce'			=> '',
				'dataStorage'	=> $pvc->options['general']['data_storage'],
				'multisite'		=> ( is_multisite() ? (int) get_current_blog_id() : false ),
				'path'			=> empty( COOKIEPATH ) || ! is_string( COOKIEPATH ) ? '/' : COOKIEPATH,
				'domain'		=> empty( COOKIE_DOMAIN ) || ! is_string( COOKIE_DOMAIN ) ? '' : COOKIE_DOMAIN
			];

			switch ( $mode ) {
				// rest api
				case 'rest_api':
					$args['requestURL'] = rest_url( 'post-views-counter/view-post/' . $args['postID'] );
					$args['nonce'] = wp_create_nonce( 'wp_rest' );
					break;

				// javascript
				case 'js':
				default:
					$args['requestURL'] = admin_url( 'admin-ajax.php' );
					$args['nonce'] = wp_create_nonce( 'pvc-check-post' );
			}

			// make it safe
			$args['requestURL'] = esc_url_raw( $args['requestURL'] );

			// set script args
			$this->script_args = apply_filters( 'pvc_frontend_script_args', $args, 'standard' );

			wp_add_inline_script( 'post-views-counter-frontend', 'var pvcArgsFrontend = ' . wp_json_encode( $this->script_args ) . ";\n", 'before' );
		}
	}
}

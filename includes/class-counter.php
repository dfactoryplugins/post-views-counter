<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Counter class.
 *
 * @class Post_Views_Counter_Counter
 */
class Post_Views_Counter_Counter {

	private $storage = [];
	private $storage_type = 'cookies';
	/* COUNT_POST_AS_AUTHOR_VIEW | removed property
	private $storage_modified = false;
	*/
	private $queue = [];
	private $queue_mode = false;
	private $db_insert_values = '';
	private $cookie = [
		'exists'		 => false,
		'visited_posts'	 => [],
		'expiration'	 => 0
	];

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'plugins_loaded', [ $this, 'check_cookie' ], 1 );
		add_action( 'init', [ $this, 'init_counter' ] );
		add_action( 'deleted_post', [ $this, 'delete_post_views' ] );
	}

	/**
	 * Get storage data.
	 *
	 * @return array
	 */
	public function get_storage() {
		return $this->storage;
	}

	/**
	 * Set storage data. Used only for additional authors counting.
	 *
	 * @return bool
	 */
	/* COUNT_POST_AS_AUTHOR_VIEW | removed function
	public function set_storage( $data, $class ) {
		if ( ! is_a( $class, 'Post_Views_Counter_Pro_Counter' ) )
			return false;

		if ( ! $class->is_main_storage_allowed() )
			return false;

		// is it active content type?
		if ( ! $class->is_content_type_active( 'user', 'posts' ) )
			return false;

		if ( $this->storage_type === 'cookies' )
			$this->storage = $data;
		else
			$this->storage['user'] = $data;

		$this->storage_modified = true;

		return true;
	}
	*/

	/**
	 * Get storage type.
	 *
	 * @return array
	 */
	public function get_storage_type() {
		return $this->storage_type;
	}

	/**
	 * Set storage type. Used only for fast ajax requests.
	 *
	 * @param string $storage_type
	 * @return array
	 */
	public function set_storage_type( $storage_type, $class ) {
		// allow only from pro counter class
		if ( ! is_a( $class, 'Post_Views_Counter_Pro_Counter' ) )
			return false;

		// allow only fast ajax requests
		if ( ! ( defined( 'SHORTINIT' ) && SHORTINIT ) )
			return false;

		// check post data
		if ( ! isset( $_POST['action'], $_POST['content'], $_POST['type'], $_POST['subtype'], $_POST['storage_type'], $_POST['storage_data'], $_POST['pvcp_nonce'] ) )
			return false;

		// verify nonce
		if ( ! wp_verify_nonce( $_POST['pvcp_nonce'], 'pvcp-check-post' ) )
			return false;

		// allow only valid storage type
		if ( in_array( $storage_type, [ 'cookies', 'cookieless' ], true ) ) {
			$this->storage_type = $storage_type;

			return true;
		}

		return false;
	}

	/**
	 * Add Post ID to queue.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function add_to_queue( $post_id ) {
		$this->queue[] = (int) $post_id;
	}

	/**
	 * Run manual pvc_view_post queue.
	 *
	 * @return void
	 */
	public function queue_count() {
		// check conditions
		if ( ! isset( $_POST['action'], $_POST['ids'], $_POST['pvc_nonce'] ) || ! wp_verify_nonce( $_POST['pvc_nonce'], 'pvc-view-posts' ) || $_POST['ids'] === '' || ! is_string( $_POST['ids'] ) )
			exit;

		// get post ids
		$ids = explode( ',', $_POST['ids'] );

		$counted = [];

		if ( ! empty( $ids ) ) {
			$ids = array_filter( array_map( 'intval', $ids ) );

			if ( ! empty( $ids ) ) {
				// turn on queue mode
				$this->queue_mode = true;

				foreach ( $ids as $id ) {
					$counted[$id] = ! ( $this->check_post( $id ) === null );
				}

				// turn off queue mode
				$this->queue_mode = false;
			}
		}

		echo wp_json_encode(
			[
				'post_ids'	=> $ids,
				'counted'	=> $counted
			]
		);

		exit;
	}

	/**
	 * Print JavaScript with queue in the footer.
	 *
	 * @return void
	 */
	public function print_queue_count() {
		// any ids to "view"?
		if ( ! empty( $this->queue ) ) {
			echo "
			<script>
				( function( window, document, undefined ) {
					document.addEventListener( 'DOMContentLoaded', function() {
						let pvcLoadManualCounter = function( url, counter ) {
							let pvcScriptTag = document.createElement( 'script' );

							// append script
							document.body.appendChild( pvcScriptTag );

							// set attributes
							pvcScriptTag.onload = counter;
							pvcScriptTag.onreadystatechange = counter;
							pvcScriptTag.src = url;
						};

						let pvcExecuteManualCounter = function() {
							let pvcManualCounterArgs = {
								url: '" . esc_url( admin_url( 'admin-ajax.php' ) ) . "',
								nonce: '" . wp_create_nonce( 'pvc-view-posts' ) . "',
								ids: '" . implode( ',', $this->queue ) . "'
							};

							// main javascript file was loaded?
							if ( typeof PostViewsCounter !== 'undefined' && PostViewsCounter.promise !== null ) {
								PostViewsCounter.promise.then( function() {
									PostViewsCounterManual.init( pvcManualCounterArgs );
								} );
							// PostViewsCounter is undefined or promise is null
							} else {
								PostViewsCounterManual.init( pvcManualCounterArgs );
							}
						}

						pvcLoadManualCounter( '" . POST_VIEWS_COUNTER_URL . "/js/counter" . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . ".js', pvcExecuteManualCounter );
					}, false );
				} )( window, document );
			</script>";
		}
	}

	/**
	 * Initialize counter.
	 *
	 * @return void
	 */
	public function init_counter() {
		// admin?
		if ( is_admin() && ! wp_doing_ajax() )
			return;

		// get main instance
		$pvc = Post_Views_Counter();

		// actions
		add_action( 'wp_ajax_pvc-view-posts', [ $this, 'queue_count' ] );
		add_action( 'wp_ajax_nopriv_pvc-view-posts', [ $this, 'queue_count' ] );
		add_action( 'wp_print_footer_scripts', [ $this, 'print_queue_count' ], 11 );

		// php counter
		if ( $pvc->options['general']['counter_mode'] === 'php' )
			add_action( 'wp', [ $this, 'check_post_php' ] );
		// javascript (ajax) counter
		elseif ( $pvc->options['general']['counter_mode'] === 'js' ) {
			add_action( 'wp_ajax_pvc-check-post', [ $this, 'check_post_js' ] );
			add_action( 'wp_ajax_nopriv_pvc-check-post', [ $this, 'check_post_js' ] );
		}

		// rest api
		add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
	}

	/**
	 * Check whether to count visit.
	 *
	 * @param int $post_id
	 * @param array $content_data
	 * @return void|int
	 */
	public function check_post( $post_id = 0, $content_data = [] ) {
		// force check cookie in short init mode
		if ( defined( 'SHORTINIT' ) && SHORTINIT )
			$this->check_cookie();

		// get post id
		$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );

		// empty id?
		if ( empty( $post_id ) )
			return;

		// get main instance
		$pvc = Post_Views_Counter();

		// get user id, from current user or static var in rest api request
		$user_id = get_current_user_id();

		// get user ip address
		$user_ip = $this->get_user_ip();

		// before visit action
		do_action( 'pvc_before_check_visit', $post_id, $user_id, $user_ip, 'post', $content_data );

		// check all conditions to count visit
		add_filter( 'pvc_count_conditions_met', [ $this, 'check_conditions' ], 10, 6 );

		// check conditions - excluded ips, excluded groups
		$conditions_met = apply_filters( 'pvc_count_conditions_met', true, $post_id, $user_id, $user_ip, 'post', $content_data );

		// conditions failed?
		if ( ! $conditions_met )
			return;

		// do not count visit by default
		$count_visit = false;

		// cookieless data storage?
		if ( $pvc->options['general']['data_storage'] === 'cookieless' && $this->storage_type === 'cookieless' ) {
			$count_visit = $this->save_data_storage( $post_id, 'post', $content_data );
		} elseif ( $pvc->options['general']['data_storage'] === 'cookies' && $this->storage_type === 'cookies' ) {
			// php counter mode?
			if ( $pvc->options['general']['counter_mode'] === 'php' ) {
				if ( $this->cookie['exists'] ) {
					// update cookie
					$count_visit = $this->save_cookie( $post_id, $this->cookie );
				} else {
					// set new cookie
					$count_visit = $this->save_cookie( $post_id );
				}
			} else
				$count_visit = $this->save_cookie_storage( $post_id, $content_data );
		}

		// filter visit counting
		$count_visit = (bool) apply_filters( 'pvc_count_visit', $count_visit, $post_id, $user_id, $user_ip, 'post', $content_data );

		// count visit
		if ( $count_visit ) {
			// before count visit action
			do_action( 'pvc_before_count_visit', $post_id, $user_id, $user_ip, 'post', $content_data );

			return $this->count_visit( $post_id );
		}
	}

	/**
	 * Check whether counting conditions are met.
	 *
	 * @param bool $allow_counting
	 * @param int $post_id
	 * @param int $user_id
	 * @param string $user_ip
	 * @param string $content_type
	 * @param array $content_data
	 * @return bool
	 */
	public function check_conditions( $allow_counting, $post_id, $user_id, $user_ip, $content_type, $content_data ) {
		// already failed?
		if ( ! $allow_counting )
			return false;

		// get main instance
		$pvc = Post_Views_Counter();

		// get ips
		$ips = $pvc->options['general']['exclude_ips'];

		// whether to count this ip
		if ( ! empty( $ips ) && filter_var( preg_replace( '/[^0-9a-fA-F:., ]/', '', $user_ip ), FILTER_VALIDATE_IP ) ) {
			// check ips
			foreach ( $ips as $ip ) {
				if ( strpos( $ip, '*' ) !== false ) {
					if ( $this->ipv4_in_range( $user_ip, $ip ) )
						return false;
				} else {
					if ( $user_ip === $ip )
						return false;
				}
			}
		}

		// get groups to check them faster
		$groups = $pvc->options['general']['exclude']['groups'];

		// whether to count this user
		if ( ! empty( $user_id ) ) {
			// exclude logged in users?
			if ( in_array( 'users', $groups, true ) )
				return false;
			// exclude specific roles?
			elseif ( in_array( 'roles', $groups, true ) && $this->is_user_role_excluded( $user_id, $pvc->options['general']['exclude']['roles'] ) )
				return false;
		// exclude guests?
		} elseif ( in_array( 'guests', $groups, true ) )
			return false;

		// whether to count robots
		if ( in_array( 'robots', $groups, true ) && $pvc->crawler->is_crawler() )
			return false;

		return $allow_counting;
	}

	/**
	 * Check whether real home page is displayed.
	 *
	 * @param object $object
	 * @return bool
	 */
	public function is_homepage( $object ) {
		$is_homepage = false;

		// get show on front option
		$show_on_front = get_option( 'show_on_front' );

		if ( $show_on_front === 'posts' )
			$is_homepage = is_home() && is_front_page();
		else {
			// home page
			$homepage = (int) get_option( 'page_on_front' );

			// posts page
			$postspage = (int) get_option( 'page_for_posts' );

			// both pages are set
			if ( $homepage && $postspage )
				$is_homepage = is_front_page();
			// only home page is set
			elseif ( $homepage && ! $postspage )
				$is_homepage = is_front_page();
			// only posts page is set
			elseif( ! $homepage && $postspage )
				$is_homepage = is_home() && ( empty( $object ) || get_queried_object_id() === 0 );
		}

		return $is_homepage;
	}

	/**
	 * Check whether posts page (archive) is displayed.
	 *
	 * @param object $object
	 * @return bool
	 */
	public function is_posts_page( $object ) {
		// get show on front option
		$show_on_front = get_option( 'show_on_front' );

		// get page for posts option
		$page_for_posts = (int) get_option( 'page_for_posts' );

		// check page
		$result = ( $show_on_front === 'page' && ! empty( $object ) && is_home() && is_a( $object, 'WP_Post' ) && (int) $object->ID === $page_for_posts );

		return apply_filters( 'pvc_is_posts_page', $result, $object );
	}

	/**
	 * Check whether to count visit via PHP request.
	 *
	 * @return void
	 */
	public function check_post_php() {
		// do not count admin entries
		if ( is_admin() && ! wp_doing_ajax() )
			return;

		// skip special requests
		if ( is_preview() || is_feed() || is_trackback() || is_favicon() || is_customize_preview() )
			return;

		// get main instance
		$pvc = Post_Views_Counter();

		// do we use php as counter?
		if ( $pvc->options['general']['counter_mode'] !== 'php' )
			return;

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// whether to count this post type
		if ( empty( $post_types ) || ! is_singular( $post_types ) )
			return;

		// get current post id
		$post_id = (int) get_the_ID();

		// allow to run check post?
		if ( ! (bool) apply_filters( 'pvc_run_check_post', true, $post_id ) )
			return;

		$this->check_post( $post_id );
	}

	/**
	 * Check whether to count visit via JavaScript (AJAX) request.
	 *
	 * @return void
	 */
	public function check_post_js() {
		// check conditions
		if ( ! isset( $_POST['action'], $_POST['id'], $_POST['storage_type'], $_POST['storage_data'], $_POST['pvc_nonce'] ) || ! wp_verify_nonce( $_POST['pvc_nonce'], 'pvc-check-post' ) )
			exit;

		// get post id
		$post_id = (int) $_POST['id'];

		if ( $post_id <= 0 )
			exit;

		// get main instance
		$pvc = Post_Views_Counter();

		// do we use javascript as counter?
		if ( $pvc->options['general']['counter_mode'] !== 'js' )
			exit;

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// check if post exists
		$post = get_post( $post_id );

		// whether to count this post type or not
		if ( empty( $post_types ) || empty( $post ) || ! in_array( $post->post_type, $post_types, true ) )
			exit;

		// get storage type
		$storage_type = sanitize_key( $_POST['storage_type'] );

		// invalid storage type?
		if ( ! in_array( $storage_type, [ 'cookies', 'cookieless' ], true ) )
			exit;

		// set storage type
		$this->storage_type = $storage_type;

		// cookieless data storage?
		if ( $storage_type === 'cookieless' && $pvc->options['general']['data_storage'] === 'cookieless' ) {
			// sanitize storage data
			$storage_data = $this->sanitize_storage_data( $_POST['storage_data'] );
		// cookies?
		} elseif ( $storage_type === 'cookies' && $pvc->options['general']['data_storage'] === 'cookies' ) {
			// sanitize cookies data
			$storage_data = $this->sanitize_cookies_data( $_POST['storage_data'] );
		} else
			$storage_data = [];

		echo wp_json_encode(
			[
				'post_id'	=> $post_id,
				'counted'	=> ! ( $this->check_post( $post_id, $storage_data ) === null ),
				'storage'	=> $this->storage,
				'type'		=> 'post'
			]
		);

		exit;
	}

	/**
	 * Check whether to count visit via REST API request.
	 *
	 * @param object $request
	 * @return int|WP_Error
	 */
	public function check_post_rest_api( $request ) {
		// get main instance
		$pvc = Post_Views_Counter();

		// get post id (already sanitized)
		$post_id = $request->get_param( 'id' );

		// do we use REST API as counter?
		if ( $pvc->options['general']['counter_mode'] !== 'rest_api' )
			return new WP_Error( 'pvc_rest_api_disabled', __( 'REST API method is disabled.', 'post-views-counter' ), [ 'status' => 404 ] );

//TODO get current user id in direct api endpoint calls
		// check if post exists
		$post = get_post( $post_id );

		if ( ! $post )
			return new WP_Error( 'pvc_post_invalid_id', __( 'Invalid post ID.', 'post-views-counter' ), [ 'status' => 404 ] );

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// whether to count this post type
		if ( empty( $post_types ) || ! in_array( $post->post_type, $post_types, true ) )
			return new WP_Error( 'pvc_post_type_excluded', __( 'Post type excluded.', 'post-views-counter' ), [ 'status' => 404 ] );

		// get storage type
		$storage_type = sanitize_key( $request->get_param( 'storage_type' ) );

		// invalid storage type?
		if ( ! in_array( $storage_type, [ 'cookies', 'cookieless' ], true ) )
			return new WP_Error( 'pvc_invalid_storage_type', __( 'Invalid storage type.', 'post-views-counter' ), [ 'status' => 404 ] );

		// set storage type
		$this->storage_type = $storage_type;

		// cookieless data storage?
		if ( $storage_type === 'cookieless' && $pvc->options['general']['data_storage'] === 'cookieless' ) {
			// sanitize storage data
			$storage_data = $this->sanitize_storage_data( $request->get_param( 'storage_data' ) );
		// cookies?
		} elseif ( $storage_type === 'cookies' && $pvc->options['general']['data_storage'] === 'cookies' ) {
			// sanitize cookies data
			$storage_data = $this->sanitize_cookies_data( $request->get_param( 'storage_data' ) );
		} else
			$storage_data = [];

		return [
			'post_id'	=> $post_id,
			'counted'	=> ! ( $this->check_post( $post_id, $storage_data ) === null ),
			'storage'	=> $this->storage,
			'type'		=> 'post'
		];
	}

	/**
	 * Initialize cookie session.
	 *
	 * @param array $cookie Use this data instead of real $_COOKIE
	 * @return void
	 */
	public function check_cookie( $cookie = [] ) {
		// do not run in admin except for ajax requests
		if ( is_admin() && ! wp_doing_ajax() )
			return;

		if ( empty( $cookie ) || ! is_array( $cookie ) ) {
			// assign cookie name
			$cookie_name = 'pvc_visits' . ( is_multisite() ? '_' . get_current_blog_id() : '' );

			// is cookie set?
			if ( isset( $_COOKIE[$cookie_name] ) && ! empty( $_COOKIE[$cookie_name] ) )
				$cookie = $_COOKIE[$cookie_name];
		}

		// cookie data?
		if ( $cookie && is_array( $cookie ) ) {
			$visited_posts = $expirations = [];

			foreach ( $cookie as $content ) {
				// is cookie valid?
				if ( preg_match( '/^(([0-9]+b[0-9]+a?)+)$/', $content ) === 1 ) {
					// get single id with expiration
					$expiration_ids = explode( 'a', $content );

					// check every expiration => id pair
					foreach ( $expiration_ids as $pair ) {
						$pair = explode( 'b', $pair );
						$expirations[] = (int) $pair[0];
						$visited_posts[(int) $pair[1]] = (int) $pair[0];
					}
				}
			}

			// update cookie
			$this->cookie = [
				'exists'		 => true,
				'visited_posts'	 => $visited_posts,
				'expiration'	 => empty( $expirations ) ? 0 : max( $expirations )
			];
		}
	}

	/**
	 * Sanitize storage data.
	 *
	 * @param string $storage_data
	 * @return array
	 */
	public function sanitize_storage_data( $storage_data ) {
		try {
			// strip slashes
			$storage_data = stripslashes( $storage_data );

			// decode storage data
			$storage_data = json_decode( $storage_data, true, 2 );
		} finally {
			// valid data?
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $storage_data ) && ! empty( $storage_data ) ) {
				$content_data = [];

				foreach ( $storage_data as $content_id => $content_expiration ) {
					$content_data[(int) $content_id] = (int) $content_expiration;
				}

				return array_unique( $content_data, SORT_NUMERIC );
			} else
				return [];
		}
	}

	/**
	 * Sanitize cookies.
	 *
	 * @param string $storage_data
	 * @return array
	 */
	public function sanitize_cookies_data( $storage_data ) {
		$content_data = $expirations = [];

		// is cookie valid?
		if ( preg_match( '/^(([0-9]+b[0-9]+a?)+)$/', $storage_data ) === 1 ) {
			// get single id with expiration
			$expiration_ids = explode( 'a', $storage_data );

			// check every expiration => id pair
			foreach ( $expiration_ids as $pair ) {
				$pair = explode( 'b', $pair );
				$expirations[] = (int) $pair[0];
				$content_data[(int) $pair[1]] = (int) $pair[0];
			}
		}

		return [
			'visited'		=> array_unique( $content_data, SORT_NUMERIC ),
			'expiration'	=> empty( $expirations ) ? 0 : max( $expirations )
		];
	}

	/**
	 * Save data storage.
	 *
	 * @param int $content
	 * @param string $content_type
	 * @param array $content_data
	 * @return bool
	 */
	private function save_data_storage( $content, $content_type, $content_data ) {
		// get base instance
		$pvc = Post_Views_Counter();

		// set default flag
		$count_visit = true;

		// get expiration
		$expiration = $this->get_timestamp( $pvc->options['general']['time_between_counts']['type'], $pvc->options['general']['time_between_counts']['number'] );

		// is this a new cookie?
		if ( empty( $content_data ) ) {
			$storage = [
				$content => $expiration
			];
		} else {
			// get current gmt time
			$current_time = current_time( 'timestamp', true );

			// post already viewed but not expired?
			if ( in_array( $content, array_keys( $content_data ), true ) && $current_time < $content_data[$content] )
				$count_visit = false;

			// create copy for better foreach performance
			$content_data_tmp = $content_data;

			// check whether viewed id has expired - no need to keep it anymore
			foreach ( $content_data_tmp as $content_id => $content_expiration ) {
				if ( $current_time > $content_expiration )
					unset( $content_data[$content_id] );
			}

			// add new id or change expiration date if id already exists
			if ( $count_visit )
				$content_data[$content] = $expiration;

			$storage = $content_data;
		}

		$this->storage[$content_type] = $storage;

		return $count_visit;
	}

	/**
	 * Save cookie storage.
	 *
	 * @param int $content
	 * @param array $content_data
	 * @return bool
	 */
	private function save_cookie_storage( $content, $content_data ) {
		// early return?
//TODO check this filter in js
		// if ( apply_filters( 'pvc_maybe_set_cookie', true, $content, $content_type, $content_data ) !== true )
			// return;

		// get base instance
		$pvc = Post_Views_Counter();

		// set default flag
		$count_visit = true;

		// get expiration
		$expiration = $this->get_timestamp( $pvc->options['general']['time_between_counts']['type'], $pvc->options['general']['time_between_counts']['number'] );

		// assign cookie name
		$cookie_name = 'pvc_visits' . ( is_multisite() ? '_' . get_current_blog_id() : '' );

		$cookies_data = [
			'name'		=> [],
			'value'		=> [],
			'expiry'	=> []
		];

		// is this a new cookie?
		if ( empty( $content_data['visited'] ) ) {
			$cookies_data['name'][] = $cookie_name . '[0]';
			$cookies_data['value'][] = $expiration . 'b' . $content;
			$cookies_data['expiry'][] = $expiration;
		} else {
			// get current gmt time
			$current_time = current_time( 'timestamp', true );

			if ( in_array( $content, array_keys( $content_data['visited'] ), true ) && $current_time < $content_data['visited'][$content] ) {
				$count_visit = false;
			} else {
				// add new id or change expiration date if id already exists
				$content_data['visited'][$content] = $expiration;
			}

			// create copy for better foreach performance
			$visited_expirations = $content_data['visited'];

			// check whether viewed id has expired - no need to keep it in cookie (less size)
			foreach ( $visited_expirations as $content_id => $content_expiration ) {
				if ( $current_time > $content_expiration )
					unset( $content_data['visited'][$content_id] );
			}

			// set new last expiration date if needed
			$content_data['expiration'] = empty( $content_data['visited'] ) ? 0 : max( $content_data['visited'] );

			$cookies = $imploded = [];

			// create pairs
			foreach ( $content_data['visited'] as $id => $exp ) {
				$imploded[] = $exp . 'b' . $id;
			}

			// split cookie into chunks (3980 bytes to make sure it is safe for every browser)
			$chunks = str_split( implode( 'a', $imploded ), 3980 );

			// more then one chunk?
			if ( count( $chunks ) > 1 ) {
				$last_id = '';

				foreach ( $chunks as $chunk_id => $chunk ) {
					// new chunk
					$chunk_c = $last_id . $chunk;

					// is it full-length chunk?
					if ( strlen( $chunk ) === 3980 ) {
						// get last part
						$last_part = strrchr( $chunk_c, 'a' );

						// get last id
						$last_id = substr( $last_part, 1 );

						// add new full-lenght chunk
						$cookies[$chunk_id] = substr( $chunk_c, 0, strlen( $chunk_c ) - strlen( $last_part ) );
					} else {
						// add last chunk
						$cookies[$chunk_id] = $chunk_c;
					}
				}
			} else {
				// only one chunk
				$cookies[] = $chunks[0];
			}

			foreach ( $cookies as $key => $value ) {
				$cookies_data['name'][] = $cookie_name . '[' . $key . ']';
				$cookies_data['value'][] = $value;
				$cookies_data['expiry'][] = $content_data['expiration'];
			}
		}

		/* COUNT_POST_AS_AUTHOR_VIEW | removed additional data
		if ( $this->storage_modified && ! empty( $this->storage ) ) {
			foreach ( $this->storage as $key => $value ) {
				foreach ( $value as $subkey => $subvalue ) {
					$cookies_data[$key][] = $subvalue;
				}
			}
		}
		*/

		$this->storage = $cookies_data;

		return $count_visit;
	}

	/**
	 * Save cookie function.
	 *
	 * @param int $id
	 * @param array $cookie
	 * @return bool
	 */
	private function save_cookie( $id, $cookie = [] ) {
		// early return?
		if ( apply_filters( 'pvc_maybe_set_cookie', true, $id, 'post', $cookie ) !== true )
			return;

		// get main instance
		$pvc = Post_Views_Counter();

		// set default flag
		$count_visit = true;

		// get expiration
		$expiration = $this->get_timestamp( $pvc->options['general']['time_between_counts']['type'], $pvc->options['general']['time_between_counts']['number'] );

		// assign cookie name
		$cookie_name = 'pvc_visits' . ( is_multisite() ? '_' . get_current_blog_id() : '' );

		// check whether php version is at least 7.3
		$php_at_least_73 = version_compare( phpversion(), '7.3', '>=' );

		// is this a new cookie?
		if ( empty( $cookie ) ) {
			if ( $php_at_least_73 ) {
				// set cookie
				setcookie(
					$cookie_name . '[0]',
					$expiration . 'b' . $id,
					[
						'expires'	=> $expiration,
						'path'		=> COOKIEPATH,
						'domain'	=> COOKIE_DOMAIN,
						'secure'	=> is_ssl(),
						'httponly'	=> false,
						'samesite'	=> 'LAX'
					]
				);
			} else {
				// set cookie
				setcookie( $cookie_name . '[0]', $expiration . 'b' . $id, $expiration, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
			}

			if ( $this->queue_mode )
				$this->check_cookie( [ 0 => $expiration . 'b' . $id ] );
		} else {
			// get current gmt time
			$current_time = current_time( 'timestamp', true );

			// post already viewed but not expired?
			if ( in_array( $id, array_keys( $cookie['visited_posts'] ), true ) && $current_time < $cookie['visited_posts'][$id] )
				$count_visit = false;
			else {
				// add new id or change expiration date if id already exists
				$cookie['visited_posts'][$id] = $expiration;
			}

			// create copy for better foreach performance
			$visited_posts_expirations = $cookie['visited_posts'];

			// check whether viewed id has expired - no need to keep it in cookie (less size)
			foreach ( $visited_posts_expirations as $post_id => $post_expiration ) {
				if ( $current_time > $post_expiration )
					unset( $cookie['visited_posts'][$post_id] );
			}

			// set new last expiration date if needed
			$cookie['expiration'] = empty( $cookie['visited_posts'] ) ? 0 : max( $cookie['visited_posts'] );

			$cookies = $imploded = [];

			// create pairs
			foreach ( $cookie['visited_posts'] as $id => $exp ) {
				$imploded[] = $exp . 'b' . $id;
			}

			// split cookie into chunks (3980 bytes to make sure it is safe for every browser)
			$chunks = str_split( implode( 'a', $imploded ), 3980 );

			// more then one chunk?
			if ( count( $chunks ) > 1 ) {
				$last_id = '';

				foreach ( $chunks as $chunk_id => $chunk ) {
					// new chunk
					$chunk_c = $last_id . $chunk;

					// is it full-length chunk?
					if ( strlen( $chunk ) === 3980 ) {
						// get last part
						$last_part = strrchr( $chunk_c, 'a' );

						// get last id
						$last_id = substr( $last_part, 1 );

						// add new full-lenght chunk
						$cookies[$chunk_id] = substr( $chunk_c, 0, strlen( $chunk_c ) - strlen( $last_part ) );
					} else {
						// add last chunk
						$cookies[$chunk_id] = $chunk_c;
					}
				}
			} else {
				// only one chunk
				$cookies[] = $chunks[0];
			}

			foreach ( $cookies as $key => $value ) {
				if ( $php_at_least_73 ) {
					// set cookie
					setcookie(
						$cookie_name . '[' . $key . ']',
						$value,
						[
							'expires'	=> $cookie['expiration'],
							'path'		=> COOKIEPATH,
							'domain'	=> COOKIE_DOMAIN,
							'secure'	=> is_ssl(),
							'httponly'	=> false,
							'samesite'	=> 'LAX'
						]
					);
				} else {
					// set cookie
					setcookie( $cookie_name . '[' . $key . ']', $value, $cookie['expiration'], COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
				}
			}

			if ( $this->queue_mode )
				$this->check_cookie( $cookies );
		}

		return $count_visit;
	}

	/**
	 * Count visit.
	 *
	 * @param int $id
	 * @return int
	 */
	private function count_visit( $id ) {
		// increment amount
		$increment_amount = (int) apply_filters( 'pvc_views_increment_amount', 1, $id, 'post' );

		if ( $increment_amount < 1 )
			$increment_amount = 1;

		// get day, week, month and year
		$date = explode( '-', date( 'W-d-m-Y-o', current_time( 'timestamp', true ) ) );

		foreach ( [
			0 => $date[3] . $date[2] . $date[1],	// day like 20140324
			1 => $date[4] . $date[0],				// week like 201439
			2 => $date[3] . $date[2],				// month like 201405
			3 => $date[3],							// year like 2014
			4 => 'total'							// total views
		] as $type => $period ) {
//TODO investigate queueing these queries on the 'shutdown' hook instead of running them instantly?
			// hit the database directly
			$this->db_insert( $id, $type, $period, $increment_amount );
		}

		do_action( 'pvc_after_count_visit', $id, 'post' );

		return $id;
	}

	/**
	 * Remove post views from database when post is deleted.
	 *
	 * @global object $wpdb
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function delete_post_views( $post_id ) {
		global $wpdb;

		$data = [
			'where'		=> [ 'id' => $post_id ],
			'format'	=> [ '%d' ]
		];

		$data = apply_filters( 'pvc_delete_post_views_where_clause', $data, $post_id );

		$wpdb->delete( $wpdb->prefix . 'post_views', $data['where'], $data['format'] );
	}

	/**
	 * Get timestamp convertion.
	 *
	 * @param string $type
	 * @param int $number
	 * @param bool $timestamp
	 * @return int
	 */
	public function get_timestamp( $type, $number, $timestamp = true ) {
		$converter = [
			'minutes'	=> MINUTE_IN_SECONDS,
			'hours'		=> HOUR_IN_SECONDS,
			'days'		=> DAY_IN_SECONDS,
			'weeks'		=> WEEK_IN_SECONDS,
			'months'	=> MONTH_IN_SECONDS,
			'years'		=> YEAR_IN_SECONDS
		];

		return (int) ( ( $timestamp ? current_time( 'timestamp', true ) : 0 ) + $number * $converter[$type] );
	}

	/**
	 * Check if object cache is in use.
	 *
	 * @param bool $only_interval
	 * @return bool
	 */
	public function using_object_cache( $only_interval = false ) {
		$using = wp_using_ext_object_cache();

		// is object cache active?
		if ( $using ) {
			// get main instance
			$pvc = Post_Views_Counter();

			// check object cache
			if ( ! $only_interval && ! $pvc->options['general']['object_cache'] )
				$using = false;

			// check interval
			if ( $pvc->options['general']['flush_interval']['number'] <= 0 )
				$using = false;
		}

		return $using;
	}

	/**
	 * Flush views data stored in the persistent object cache into
	 * our custom table and clear the object cache keys when done.
	 *
	 * @return bool
	 */
	public function flush_cache_to_db() {
		// get keys
		$key_names = wp_cache_get( 'cached_key_names', 'pvc' );

		if ( ! $key_names )
			$key_names = [];
		else {
			// create an array out of a string that's stored in the cache
			$key_names = explode( '|', $key_names );
		}

		// any data?
		if ( ! empty( $key_names ) ) {
			foreach ( $key_names as $key_name ) {
				// get values stored within the key name itself
				list( $id, $type, $period ) = explode( '.', $key_name );

				// get the cached count value
				$count = wp_cache_get( $key_name, 'pvc' );

				// store cached value in the database
				$this->db_prepare_insert( $id, $type, $period, $count );

				// clear the cache key we just flushed
				wp_cache_delete( $key_name, 'pvc' );
			}

			// flush values to database
			$this->db_commit_insert();

			// delete the key holding the list
			wp_cache_delete( 'cached_key_names', 'pvc' );
		}

		// remove last flush
		wp_cache_delete( 'last-flush', 'pvc' );

		return true;
	}

	/**
	 * Insert or update views count.
	 *
	 * @global object $wpdb
	 *
	 * @param int $id
	 * @param int $type
	 * @param string $period
	 * @param int $count
	 * @return int|bool
	 */
	private function db_insert( $id, $type, $period, $count ) {
		// check whether skip single query
		$skip_single_query = (bool) apply_filters( 'pvc_skip_single_query', false, $id, $type, $period, $count, 'post' );

		// skip query?
		if ( $skip_single_query )
			return false;

		global $wpdb;

		return $wpdb->query( $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count) VALUES (%d, %d, %s, %d) ON DUPLICATE KEY UPDATE count = count + %d", $id, $type, $period, $count, $count ) );
	}

	/**
	 * Prepare bulk insert or update views count.
	 *
	 * @param int $id
	 * @param int $type
	 * @param string $period
	 * @param int $count
	 * @return void
	 */
	private function db_prepare_insert( $id, $type, $period, $count = 1 ) {
		// cast count
		$count = (int) $count;

		if ( ! $count )
			$count = 1;

		// any queries?
		if ( ! empty( $this->db_insert_values ) )
			$this->db_insert_values .= ', ';

		// append insert queries
		$this->db_insert_values .= sprintf( '(%d, %d, "%s", %d)', $id, $type, $period, $count );

		if ( strlen( $this->db_insert_values ) > 25000 )
			$this->db_commit_insert();
	}

	/**
	 * Insert accumulated values to database.
	 *
	 * @global object $wpdb
	 *
	 * @return int|bool
	 */
	private function db_commit_insert() {
		if ( empty( $this->db_insert_values ) )
			return false;

		global $wpdb;

		$result = $wpdb->query(
			"INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count)
			VALUES " . $this->db_insert_values . "
			ON DUPLICATE KEY UPDATE count = count + VALUES(count)"
		);

		$this->db_insert_values = '';

		return $result;
	}

	/**
	 * Check whether user has excluded roles.
	 *
	 * @param int $user_id
	 * @param string $option
	 * @return bool
	 */
	public function is_user_role_excluded( $user_id, $option = [] ) {
		// get user by ID
		$user = get_user_by( 'id', $user_id );

		// no user?
		if ( empty( $user ) )
			return false;

		// get user roles
		$roles = (array) $user->roles;

		// any roles?
		if ( ! empty( $roles ) ) {
			foreach ( $roles as $role ) {
				if ( in_array( $role, $option, true ) )
					return true;
			}
		}

		return false;
	}

	/**
	 * Check if IPv4 is in range.
	 *
	 * @param string $ip IP address
	 * @param string $range IP range
	 * @return bool
	 */
	public function ipv4_in_range( $ip, $range ) {
		$start = str_replace( '*', '0', $range );
		$end = str_replace( '*', '255', $range );
		$ip = (float) sprintf( "%u", ip2long( $ip ) );

		return ( $ip >= (float) sprintf( "%u", ip2long( $start ) ) && $ip <= (float) sprintf( "%u", ip2long( $end ) ) );
	}

	/**
	 * Get user real IP address.
	 *
	 * @return string
	 */
	public function get_user_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';

		foreach ( [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ] as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( explode( ',', $_SERVER[$key] ) as $ip ) {
					// trim for safety measures
					$ip = trim( $ip );

					// attempt to validate IP
					if ( $this->validate_user_ip( $ip ) )
						continue;
				}
			}
		}

		return (string) $ip;
	}

	/**
	 * Ensure an IP address is both a valid IP and does not fall within a private network range.
	 *
	 * @param $ip string IP address
	 * @return bool
	 */
	public function validate_user_ip( $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false )
			return false;

		return true;
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @return void
	 */
	public function rest_api_init() {
		// view post route
		register_rest_route(
			'post-views-counter',
			'/view-post/(?P<id>\d+)|/view-post/',
			[
				'methods'				 => [ 'POST' ],
				'callback'				 => [ $this, 'check_post_rest_api' ],
				'permission_callback'	 => [ $this, 'view_post_permissions_check' ],
				'args'					 => apply_filters( 'pvc_rest_api_view_post_args', [
					'id'			=> [
						'default'			 => 0,
						'sanitize_callback'	 => 'absint'
					],
					'storage_type'	=> [
						'default'			 => 'cookies'
					],
					'storage_data'	=> [
						'default'			 => ''
					]
				] )
			]
		);

		// get views route
		register_rest_route(
			'post-views-counter',
			'/get-post-views/(?P<id>(\d+,?)+)',
			[
				'methods'				 => [ 'GET', 'POST' ],
				'callback'				 => [ $this, 'get_post_views_rest_api' ],
				'permission_callback'	 => [ $this, 'get_post_views_permissions_check' ],
				'args'					 => apply_filters( 'pvc_rest_api_get_post_views_args', [
					'id' => [
						'default'			=> 0,
						'sanitize_callback'	=> [ $this, 'validate_rest_api_data' ]
					]
				] )
			]
		);
	}

	/**
	 * Get post views via REST API request.
	 *
	 * @param object $request
	 * @return int
	 */
	public function get_post_views_rest_api( $request ) {
		return pvc_get_post_views( $request->get_param( 'id' ) );
	}

	/**
	 * Check if a given request has access to get views.
	 *
	 * @param object $request
	 * @return bool
	 */
	public function get_post_views_permissions_check( $request ) {
		return (bool) apply_filters( 'pvc_rest_api_get_post_views_check', true, $request );
	}
	
	/**
	 * Check if a given request has access to view post.
	 *
	 * @param object $request
	 * @return bool
	 */
	public function view_post_permissions_check( $request ) {
		return (bool) apply_filters( 'pvc_rest_api_view_post_check', true, $request );
	}

	/**
	 * Validate REST API incoming data.
	 *
	 * @param int|array $data
	 * @return int|array
	 */
	public function validate_rest_api_data( $data ) {
		// POST array?
		if ( is_array( $data ) )
			$data = array_unique( array_filter( array_map( 'absint', $data ) ), SORT_NUMERIC );
		// multiple comma-separated values?
		elseif ( strpos( $data, ',' ) !== false ) {
			$data = explode( ',', $data );

			if ( is_array( $data ) && ! empty( $data ) )
				$data = array_unique( array_filter( array_map( 'absint', $data ) ), SORT_NUMERIC );
			else
				$data = [];
		// single value?
		} else
			$data = absint( $data );

		return $data;
	}
}

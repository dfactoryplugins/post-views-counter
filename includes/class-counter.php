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

	const GROUP = 'pvc';
	const NAME_ALLKEYS = 'cached_key_names';
	const CACHE_KEY_SEPARATOR = '.';
	const MAX_INSERT_STRING_LENGTH = 25000;

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
		if ( isset( $_POST['action'], $_POST['ids'], $_POST['pvc_nonce'] ) && $_POST['action'] === 'pvc-view-posts' && wp_verify_nonce( $_POST['pvc_nonce'], 'pvc-view-posts' ) !== false && $_POST['ids'] !== '' && is_string( $_POST['ids'] ) ) {
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

			echo json_encode(
				[
					'post_ids'	=> $ids,
					'counted'	=> $counted
				]
			);
		}

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
		// rest api counter
		} elseif ( $pvc->options['general']['counter_mode'] === 'rest_api' )
			add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
	}

	/**
	 * Check whether to count visit.
	 *
	 * @param int $id
	 * @return void|int
	 */
	public function check_post( $id = 0 ) {
		// short init?
		if ( defined( 'SHORTINIT' ) && SHORTINIT )
			$this->check_cookie();

		// get post id
		$id = (int) ( empty( $id ) ? get_the_ID() : $id );

		// empty id?
		if ( empty( $id ) )
			return;

		// get user id, from current user or static var in rest api request
		$user_id = get_current_user_id();

		// get user IP address
		$user_ip = $this->get_user_ip();

		do_action( 'pvc_before_check_visit', $id, $user_id, $user_ip );

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
						return;
				} else {
					if ( $user_ip === $ip )
						return;
				}
			}
		}

		// strict counts?
		if ( $pvc->options['general']['strict_counts'] ) {
			// get IP cached visits
			$ip_cache = get_transient( 'post_views_counter_ip_cache' );

			if ( ! $ip_cache )
				$ip_cache = [];

			// get user IP address
			$user_ip = $this->encrypt_ip( $user_ip );

			// visit exists in transient?
			if ( isset( $ip_cache[$id][$user_ip] ) ) {
				// get current time
				$current_time = current_time( 'timestamp', true );

				if ( $current_time < $ip_cache[$id][$user_ip] + $this->get_timestamp( $pvc->options['general']['time_between_counts']['type'], $pvc->options['general']['time_between_counts']['number'], false ) )
					return;
			}
		}

		// get groups to check them faster
		$groups = $pvc->options['general']['exclude']['groups'];

		// whether to count this user
		if ( ! empty( $user_id ) ) {
			// exclude logged in users?
			if ( in_array( 'users', $groups, true ) )
				return;
			// exclude specific roles?
			elseif ( in_array( 'roles', $groups, true ) && $this->is_user_role_excluded( $user_id, $pvc->options['general']['exclude']['roles'] ) )
				return;
		// exclude guests?
		} elseif ( in_array( 'guests', $groups, true ) )
			return;

		// whether to count robots
		if ( in_array( 'robots', $groups, true ) && $pvc->crawler_detect->is_crawler() )
			return;

		// cookie already existed?
		if ( $this->cookie['exists'] ) {
			// get current time if needed
			if ( ! isset( $current_time ) )
				$current_time = current_time( 'timestamp', true );

			// post already viewed but not expired?
			if ( in_array( $id, array_keys( $this->cookie['visited_posts'] ), true ) && $current_time < $this->cookie['visited_posts'][$id] ) {
				// update cookie but do not count visit
				$this->save_cookie( $id, $this->cookie, false );

				return;
			// update cookie
			} else
				$this->save_cookie( $id, $this->cookie );
		} else {
			// set new cookie
			$this->save_cookie( $id );
		}

		$count_visit = (bool) apply_filters( 'pvc_count_visit', true, $id );

		// count visit
		if ( $count_visit ) {
			// strict counts?
			if ( $pvc->options['general']['strict_counts'] )
				$this->save_ip( $id );

			return $this->count_visit( $id );
		}
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

		$this->check_post( get_the_ID() );
	}

	/**
	 * Check whether to count visit via JavaScript (AJAX) request.
	 *
	 * @return void
	 */
	public function check_post_js() {
		if ( isset( $_POST['action'], $_POST['id'], $_POST['pvc_nonce'] ) && $_POST['action'] === 'pvc-check-post' && ( $post_id = (int) $_POST['id'] ) > 0 && wp_verify_nonce( $_POST['pvc_nonce'], 'pvc-check-post' ) !== false ) {
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

			echo json_encode(
				[
					'post_id'	=> $post_id,
					'counted'	=> ! ( $this->check_post( $post_id ) === null )
				]
			);
		}

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

		// @todo: get current user id in direct api endpoint calls
		// check if post exists
		$post = get_post( $post_id );

		if ( ! $post )
			return new WP_Error( 'pvc_post_invalid_id', __( 'Invalid post ID.', 'post-views-counter' ), [ 'status' => 404 ] );

		// get countable post types
		$post_types = $pvc->options['general']['post_types_count'];

		// whether to count this post type
		if ( empty( $post_types ) || ! in_array( $post->post_type, $post_types, true ) )
			return new WP_Error( 'pvc_post_type_excluded', __( 'Post type excluded.', 'post-views-counter' ), [ 'status' => 404 ] );

		return [
			'post_id'	=> $post_id,
			'counted'	=> ! ( $this->check_post( $post_id ) === null )
		];
	}

	/**
	 * Initialize cookie session.
	 *
	 * @param array $cookie Use this cookie instead of $_COOKIE
	 * @return void
	 */
	public function check_cookie( $cookie = [] ) {
		// do not run in admin except for ajax requests
		if ( is_admin() && ! wp_doing_ajax() )
			return;

		if ( empty( $cookie ) ) {
			// assign cookie name
			$cookie_name = 'pvc_visits' . ( is_multisite() ? '_' . get_current_blog_id() : '' );

			// is cookie set?
			if ( isset( $_COOKIE[$cookie_name] ) && ! empty( $_COOKIE[$cookie_name] ) )
				$cookie = $_COOKIE[$cookie_name];
		}

		// cookie data?
		if ( $cookie ) {
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
	 * Save cookie function.
	 *
	 * @param int $id
	 * @param array $cookie
	 * @param bool $expired
	 * @return void
	 */
	private function save_cookie( $id, $cookie = [], $expired = true ) {
		$set_cookie = apply_filters( 'pvc_maybe_set_cookie', true );

		if ( $set_cookie !== true )
			return;

		// get main instance
		$pvc = Post_Views_Counter();

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
						'secure'	=> isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off',
						'httponly'	=> true,
						'samesite'	=> 'LAX'
					]
				);
			} else {
				// set cookie
				setcookie( $cookie_name . '[0]', $expiration . 'b' . $id, $expiration, COOKIEPATH, COOKIE_DOMAIN, ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ), true );
			}

			if ( $this->queue_mode )
				$this->check_cookie( [ 0 => $expiration . 'b' . $id ] );
		} else {
			if ( $expired ) {
				// add new id or change expiration date if id already exists
				$cookie['visited_posts'][$id] = $expiration;
			}

			// create copy for better foreach performance
			$visited_posts_expirations = $cookie['visited_posts'];

			// get current gmt time
			$time = current_time( 'timestamp', true );

			// check whether viewed id has expired - no need to keep it in cookie (less size)
			foreach ( $visited_posts_expirations as $post_id => $post_expiration ) {
				if ( $time > $post_expiration )
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
							'secure'	=> isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off',
							'httponly'	=> true,
							'samesite'	=> 'LAX'
						]
					);
				} else {
					// set cookie
					setcookie( $cookie_name . '[' . $key . ']', $value, $cookie['expiration'], COOKIEPATH, COOKIE_DOMAIN, ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ), true );
				}
			}

			if ( $this->queue_mode )
				$this->check_cookie( $cookies );
		}
	}

	/**
	 * Save user IP address.
	 *
	 * @param int $id
	 * @return int|void
	 */
	private function save_ip( $id ) {
		$set_cookie = apply_filters( 'pvc_maybe_set_cookie', true );

		if ( $set_cookie !== true )
			return $id;

		// get IP cached visits
		$ip_cache = get_transient( 'post_views_counter_ip_cache' );

		if ( ! $ip_cache )
			$ip_cache = [];

		// get user IP address
		$user_ip = $this->encrypt_ip( $this->get_user_ip() );

		// get current time
		$current_time = current_time( 'timestamp', true );

		// visit exists in transient?
		if ( isset( $ip_cache[$id][$user_ip] ) ) {
			// get main instance
			$pvc = Post_Views_Counter();

			if ( $current_time > $ip_cache[$id][$user_ip] + $this->get_timestamp( $pvc->options['general']['time_between_counts']['type'], $pvc->options['general']['time_between_counts']['number'], false ) )
				$ip_cache[$id][$user_ip] = $current_time;
			else
				return;
		} else
			$ip_cache[$id][$user_ip] = $current_time;

		// keep it light, only 10 records per post and maximum 100 post records (max. 1000 ip entries)
		// also, the data gets deleted after a week if there's no activity during this time
		if ( count( $ip_cache[$id] ) > 10 )
			$ip_cache[$id] = array_slice( $ip_cache[$id], -10, 10, true );

		if ( count( $ip_cache ) > 100 )
			$ip_cache = array_slice( $ip_cache, -100, 100, true );

		set_transient( 'post_views_counter_ip_cache', $ip_cache, WEEK_IN_SECONDS );
	}

	/**
	 * Count visit.
	 *
	 * @param int $id
	 * @return int
	 */
	private function count_visit( $id ) {
		$cache_key_names = [];
		$using_object_cache = $this->using_object_cache();
		$increment_amount = (int) apply_filters( 'pvc_views_increment_amount', 1, $id );

		// get day, week, month and year
		$date = explode( '-', date( 'W-d-m-Y-o', current_time( 'timestamp', true ) ) );

		foreach ( [
			0	 => $date[3] . $date[2] . $date[1], // day like 20140324
			1	 => $date[4] . $date[0], // week like 201439
			2	 => $date[3] . $date[2], // month like 201405
			3	 => $date[3], // year like 2014
			4	 => 'total'	   // total views
		] as $type => $period ) {
			if ( $using_object_cache ) {
				$cache_key = $id . self::CACHE_KEY_SEPARATOR . $type . self::CACHE_KEY_SEPARATOR . $period;
				wp_cache_add( $cache_key, 0, self::GROUP );
				wp_cache_incr( $cache_key, $increment_amount, self::GROUP );
				$cache_key_names[] = $cache_key;
			} else {
				// hit the database directly
				// @TODO: investigate queueing these queries on the 'shutdown' hook instead of running them instantly?
				$this->db_insert( $id, $type, $period, $increment_amount );
			}
		}

		// update the list of cache keys to be flushed
		if ( $using_object_cache && ! empty( $cache_key_names ) )
			$this->update_cached_keys_list_if_needed( $cache_key_names );

		do_action( 'pvc_after_count_visit', $id );

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

		$wpdb->delete( $wpdb->prefix . 'post_views', [ 'id' => $post_id ], [ '%d' ] );
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
			'minutes'	=> 60,
			'hours'		=> 3600,
			'days'		=> 86400,
			'weeks'		=> 604800,
			'months'	=> 2592000,
			'years'		=> 946080000
		];

		return (int) ( ( $timestamp ? current_time( 'timestamp', true ) : 0 ) + $number * $converter[$type] );
	}

	/**
	 * Check if object cache is in use.
	 *
	 * @param bool $using
	 * @return bool|null
	 */
	public function using_object_cache( $using = null ) {
		$using = wp_using_ext_object_cache( $using );

		if ( $using ) {
			// check if explicitly disabled by flush_interval setting/option <= 0
			$flush_interval_number = Post_Views_Counter()->options['general']['flush_interval']['number'];
			$using = ( $flush_interval_number <= 0 ) ? false : true;
		}

		return $using;
	}

	/**
	 * Update the single cache key which holds a list of all the cache keys
	 * that need to be flushed to the database.
	 *
	 * The value of that special cache key is a giant string containing key names separated with the `|` character.
	 * Each such key name then consists of 3 elements: $id, $type, $period (separated by a `.` character).
	 * Examples:
	 * 62053.0.20150327|62053.1.201513|62053.2.201503|62053.3.2015|62053.4.total|62180.0.20150327|62180.1.201513|62180.2.201503|62180.3.2015|62180.4.total
	 * A single key is `62053.0.20150327` and that key's data is: $id = 62053, $type = 0, $period = 20150327
	 *
	 * This data format proved more efficient (avoids the (un)serialization overhead completely + duplicates filtering is a string search now)
	 *
	 * @param array $key_names
	 * @return void
	 */
	private function update_cached_keys_list_if_needed( $key_names = [] ) {
		$existing_list = wp_cache_get( self::NAME_ALLKEYS, self::GROUP );

		if ( ! $existing_list )
			$existing_list = '';

		$list_modified = false;

		// modify the list contents if/when needed
		if ( empty( $existing_list ) ) {
			// the simpler case of an empty initial list where we just
			// transform the specified key names into a string
			$existing_list = implode( '|', $key_names );
			$list_modified = true;
		} else {
			// search each specified key name and append it if it's not found
			foreach ( $key_names as $key_name ) {
				if ( false === strpos( $existing_list, $key_name ) ) {
					$existing_list .= '|' . $key_name;
					$list_modified = true;
				}
			}
		}

		// save modified list back in cache
		if ( $list_modified )
			wp_cache_set( self::NAME_ALLKEYS, $existing_list, self::GROUP );
	}

	/**
	 * Flush views data stored in the persistent object cache into
	 * our custom table and clear the object cache keys when done.
	 *
	 * @return bool
	 */
	public function flush_cache_to_db() {
		$key_names = wp_cache_get( self::NAME_ALLKEYS, self::GROUP );

		if ( ! $key_names )
			$key_names = [];
		else {
			// create an array out of a string that's stored in the cache
			$key_names = explode( '|', $key_names );
		}

		foreach ( $key_names as $key_name ) {
			// get values stored within the key name itself
			list( $id, $type, $period ) = explode( self::CACHE_KEY_SEPARATOR, $key_name );

			// get the cached count value
			$count = wp_cache_get( $key_name, self::GROUP );

			// store cached value in the db
			$this->db_prepare_insert( $id, $type, $period, $count );

			// clear the cache key we just flushed
			wp_cache_delete( $key_name, self::GROUP );
		}

		// actually flush values to db (if any left)
		$this->db_commit_insert();

		// remember last flush to db time
		wp_cache_set( 'last-flush', time(), self::GROUP );

		// delete the key holding the list itself after we've successfully flushed it
		if ( ! empty( $key_names ) )
			wp_cache_delete( self::NAME_ALLKEYS, self::GROUP );

		return true;
	}

	/**
	 * Insert or update views count.
	 *
	 * @global object $wpdb
	 *
	 * @param int $id
	 * @param string $type
	 * @param string $period
	 * @param int $count
	 * @return int|bool
	 */
	private function db_insert( $id, $type, $period, $count = 1 ) {
		global $wpdb;

		$count = (int) $count;

		if ( ! $count )
			$count = 1;

		return $wpdb->query( $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count) VALUES (%d, %d, %s, %d) ON DUPLICATE KEY UPDATE count = count + %d", $id, $type, $period, $count, $count ) );
	}

	/**
	 * Prepare bulk insert or update views count.
	 *
	 * @param int $id
	 * @param string $type
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

		if ( strlen( $this->db_insert_values ) > self::MAX_INSERT_STRING_LENGTH )
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
	 * Encrypt user IP.
	 *
	 * @param string $ip
	 * @return string
	 */
	public function encrypt_ip( $ip ) {
		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : false;
		$auth_iv = defined( 'NONCE_KEY' ) ? NONCE_KEY : false;
		$cipher = 'AES-256-CBC';
		$php_71x = version_compare( phpversion(), '7.1.0', '>=' ) && version_compare( phpversion(), '7.2.0', '<' );

		// openssl encryption
		if ( $auth_key && $auth_iv && function_exists( 'openssl_encrypt' ) && in_array( $cipher, array_map( 'strtoupper', openssl_get_cipher_methods() ) ) )
			$encrypted_ip = base64_encode( openssl_encrypt( $ip, $cipher, $auth_key, 0, mb_strimwidth( $auth_iv, 0, openssl_cipher_iv_length( $cipher ), '', 'UTF-8' ) ) );
		// mcrypt encryption
		elseif ( $auth_key && $auth_iv && ! $php_71x && function_exists( 'mcrypt_encrypt' ) && function_exists( 'mcrypt_get_key_size' ) && function_exists( 'mcrypt_get_iv_size' ) && defined( 'MCRYPT_BLOWFISH' ) ) {
			// get max key size of the mcrypt mode
			$max_key_size = mcrypt_get_key_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );
			$max_iv_size = mcrypt_get_iv_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );

			$encrypt_key = mb_strimwidth( $auth_key, 0, $max_key_size );
			$encrypt_iv = mb_strimwidth( $auth_iv, 0, $max_iv_size );

			$encrypted_ip = base64_encode( mcrypt_encrypt( MCRYPT_BLOWFISH, $encrypt_key, $ip, MCRYPT_MODE_CBC, $encrypt_iv ) );
		// simple encryption
		} elseif ( function_exists( 'gzdeflate' ) )
			$encrypted_ip = base64_encode( convert_uuencode( gzdeflate( $ip ) ) );
		// no encryption
		else
			$encrypted_ip = base64_encode( convert_uuencode( $ip ) );

		return $encrypted_ip;
	}

	/**
	 * Decrypt user IP.
	 *
	 * @param string $encrypted_ip
	 * @return string
	 */
	public function decrypt_ip( $encrypted_ip ) {
		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : false;
		$auth_iv = defined( 'NONCE_KEY' ) ? NONCE_KEY : false;
		$cipher = 'AES-256-CBC';
		$php_71x = version_compare( phpversion(), '7.1.0', '>=' ) && version_compare( phpversion(), '7.2.0', '<' );

		// openssl decryption
		if ( $auth_key && $auth_iv && function_exists( 'openssl_encrypt' ) && in_array( $cipher, array_map( 'strtoupper', openssl_get_cipher_methods() ) ) )
			$ip = openssl_decrypt( base64_decode( $encrypted_ip ), $cipher, $auth_key, 0, mb_strimwidth( $auth_iv, 0, openssl_cipher_iv_length( $cipher ), '', 'UTF-8' ) );
		// mcrypt decryption
		elseif ( $auth_key && $auth_iv && ! $php_71x && function_exists( 'mcrypt_decrypt' ) && function_exists( 'mcrypt_get_key_size' ) && function_exists( 'mcrypt_get_iv_size' ) && defined( 'MCRYPT_BLOWFISH' ) ) {
			// get max key size of the mcrypt mode
			$max_key_size = mcrypt_get_key_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );
			$max_iv_size = mcrypt_get_iv_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );

			$encrypt_key = mb_strimwidth( $auth_key, 0, $max_key_size );
			$encrypt_iv = mb_strimwidth( $auth_iv, 0, $max_iv_size );

			$ip = rtrim( mcrypt_decrypt( MCRYPT_BLOWFISH, $encrypt_key, base64_decode( $encrypted_ip ), MCRYPT_MODE_CBC, $encrypt_iv ), "\0" );
		// simple decryption
		} elseif ( function_exists( 'gzinflate' ) )
			$ip = gzinflate( convert_uudecode( base64_decode( $encrypted_ip ) ) );
		// no decryption
		else
			$ip = convert_uudecode( base64_decode( $encrypted_ip ) );

		return $ip;
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
				'methods'				 => [ 'GET', 'POST' ],
				'callback'				 => [ $this, 'check_post_rest_api' ],
				'permission_callback'	 => [ $this, 'post_view_permissions_check' ],
				'args'					 => [
					'id' => [
						'default'			 => 0,
						'sanitize_callback'	 => 'absint'
					]
				]
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
				'args'					 => [
					'id' => [
						'default'			=> 0,
						'sanitize_callback'	=> [ $this, 'validate_rest_api_data' ]
					]
				]
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
	 * Check if a given request has access to view post.
	 *
	 * @param object $request
	 * @return bool
	 */
	public function post_view_permissions_check( $request ) {
		return (bool) apply_filters( 'pvc_rest_api_post_views_check', true, $request );
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
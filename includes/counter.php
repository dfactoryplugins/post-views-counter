<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

new Post_Views_Counter_Counter();

class Post_Views_Counter_Counter {

	const GROUP = 'pvc';
	const NAME_ALLKEYS = 'cached_key_names';
	const CACHE_KEY_SEPARATOR = '.';

	private $cookie = array(
		'exists'		 => false,
		'visited_posts'	 => array(),
		'expiration'	 => 0
	);

	public function __construct() {
		// set instance
		Post_Views_Counter()->add_instance( 'counter', $this );

		// actions
		add_action( 'plugins_loaded', array( &$this, 'check_cookie' ), 1 );
		add_action( 'wp', array( &$this, 'check_post' ) );
		add_action( 'deleted_post', array( &$this, 'delete_post_views' ) );
		add_action( 'wp_ajax_pvc-check-post', array( &$this, 'check_post_ajax' ) );
		add_action( 'wp_ajax_nopriv_pvc-check-post', array( &$this, 'check_post_ajax' ) );
	}

	/**
	 * Remove post views from database when post is deleted.
	 */
	public function delete_post_views( $post_id ) {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'post_views', array( 'id' => $post_id ), array( '%d' ) );
	}

	/**
	 * Check whether user has excluded roles.
	 */
	public function is_user_roles_excluded( $option ) {
		$user = wp_get_current_user();

		if ( empty( $user ) )
			return false;

		$roles = (array) $user->roles;

		if ( ! empty( $roles ) ) {
			foreach ( $roles as $role ) {
				if ( in_array( $role, $option, true ) )
					return true;
			}
		}

		return false;
	}

	/**
	 * Get timestamp convertion.
	 */
	public function get_timestamp( $type, $number, $timestamp = true ) {
		$converter = array(
			'minutes'	 => 60,
			'hours'		 => 3600,
			'days'		 => 86400,
			'weeks'		 => 604800,
			'months'	 => 2592000,
			'years'		 => 946080000
		);

		return ($timestamp ? current_time( 'timestamp', true ) : 0) + $number * $converter[$type];
	}

	/**
	 * Check whether to count visit via AJAX request.
	 */
	public function check_post_ajax() {
		if ( isset( $_POST['action'], $_POST['post_id'], $_POST['pvc_nonce'], $_POST['post_type'] ) && $_POST['action'] === 'pvc-check-post' && ($post_id = (int) $_POST['post_id']) > 0 && wp_verify_nonce( $_POST['pvc_nonce'], 'pvc-check-post' ) !== false && Post_Views_Counter()->get_attribute( 'options', 'general', 'counter_mode' ) === 'js' ) {
			// get countable post types
			$post_types = Post_Views_Counter()->get_attribute( 'options', 'general', 'post_types_count' );

			// get post type
			$post_type = get_post_type( $post_id );

			// whether to count this post type or not
			if ( empty( $post_types ) || empty( $post_type ) || $post_type !== $_POST['post_type'] || ! in_array( $post_type, $post_types, true ) )
				exit;

			// get excluded ips
			$excluded_ips = Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude_ips' );

			// whether to count this ip or not
			if ( ! empty( $excluded_ips ) && filter_var( preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) && in_array( $_SERVER['REMOTE_ADDR'], $excluded_ips, true ) )
				exit;

			// get groups to check them faster
			$groups = Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude', 'groups' );

			// whether to count this user
			if ( is_user_logged_in() ) {
				// exclude logged in users?
				if ( in_array( 'users', $groups, true ) )
					exit;
				// exclude specific roles?
				elseif ( in_array( 'roles', $groups, true ) && $this->is_user_roles_excluded( Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude', 'roles' ) ) )
					exit;
			}
			// exclude guests?
			elseif ( in_array( 'guests', $groups, true ) )
				exit;

			// whether to count robots
			if ( $this->is_robot() )
				exit;

			// cookie already existed?
			if ( $this->cookie['exists'] ) {
				// post already viewed but not expired?
				if ( in_array( $post_id, array_keys( $this->cookie['visited_posts'] ), true ) && current_time( 'timestamp', true ) < $this->cookie['visited_posts'][$post_id] ) {
					// updates cookie but do not count visit
					$this->save_cookie( $post_id, $this->cookie, false );

					exit;
				} else
				// updates cookie
					$this->save_cookie( $post_id, $this->cookie );
			} else {
				// set new cookie
				$this->save_cookie( $post_id );
			}

			// count visit
			$this->count_visit( $post_id );
		}

		exit;
	}

	/**
	 * Check whether to count visit.
	 */
	public function check_post() {
		// do not count admin entries
		if ( is_admin() )
			return;

		// do we use PHP as counter?
		if ( Post_Views_Counter()->get_attribute( 'options', 'general', 'counter_mode' ) === 'php' ) {
			$post_types = Post_Views_Counter()->get_attribute( 'options', 'general', 'post_types_count' );

			// whether to count this post type
			if ( empty( $post_types ) || ! is_singular( $post_types ) )
				return;

			$ips = Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude_ips' );

			// whether to count this ip
			if ( ! empty( $ips ) && filter_var( preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) && in_array( $_SERVER['REMOTE_ADDR'], $ips, true ) )
				return;

			// get groups to check them faster
			$groups = Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude', 'groups' );

			// whether to count this user
			if ( is_user_logged_in() ) {
				// exclude logged in users?
				if ( in_array( 'users', $groups, true ) )
					return;
				// exclude specific roles?
				elseif ( in_array( 'roles', $groups, true ) && $this->is_user_roles_excluded( Post_Views_Counter()->get_attribute( 'options', 'general', 'exclude', 'roles' ) ) )
					return;
			}
			// exclude guests?
			elseif ( in_array( 'guests', $groups, true ) )
				return;

			// whether to count robots
			if ( $this->is_robot() )
				return;

			// get post id
			$id = get_the_ID();

			// cookie already existed?
			if ( $this->cookie['exists'] ) {
				// post already viewed but not expired?
				if ( in_array( $id, array_keys( $this->cookie['visited_posts'] ), true ) && current_time( 'timestamp', true ) < $this->cookie['visited_posts'][$id] ) {
					// update cookie but do not count visit
					$this->save_cookie( $id, $this->cookie, false );

					return;
				} else
				// update cookie
					$this->save_cookie( $id, $this->cookie );
			} else
			// set new cookie
				$this->save_cookie( $id );

			// count visit
			$this->count_visit( $id );
		}
	}

	/**
	 * Initialize cookie session.
	 */
	public function check_cookie() {
		// do not run in admin except for ajax requests
		if ( is_admin() && ! (defined( 'DOING_AJAX' ) && DOING_AJAX) )
			return;

		// is cookie set?
		if ( isset( $_COOKIE['pvc_visits'] ) && ! empty( $_COOKIE['pvc_visits'] ) ) {
			$visited_posts = $expirations = array();

			foreach ( $_COOKIE['pvc_visits'] as $content ) {
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

			$this->cookie = array(
				'exists'		 => true,
				'visited_posts'	 => $visited_posts,
				'expiration'	 => max( $expirations )
			);
		}
	}

	/**
	 * Save cookie function.
	 */
	private function save_cookie( $id, $cookie = array(), $expired = true ) {
		$expiration = $this->get_timestamp( Post_Views_Counter()->get_attribute( 'options', 'general', 'time_between_counts', 'type' ), Post_Views_Counter()->get_attribute( 'options', 'general', 'time_between_counts', 'number' ) );

		// is this a new cookie?
		if ( empty( $cookie ) ) {
			// set cookie
			setcookie( 'pvc_visits[0]', $expiration . 'b' . $id, $expiration, COOKIEPATH, COOKIE_DOMAIN, (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ? true : false ), true );
		} else {
			if ( $expired ) {
				// add new id or chang expiration date if id already exists
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
			$cookie['expiration'] = max( $cookie['visited_posts'] );

			$cookies = $imploded = array();

			// create pairs
			foreach ( $cookie['visited_posts'] as $id => $exp ) {
				$imploded[] = $exp . 'b' . $id;
			}

			// split cookie into chunks (4000 bytes to make sure it is safe for every browser)
			$chunks = str_split( implode( 'a', $imploded ), 4000 );

			// more then one chunk?
			if ( count( $chunks ) > 1 ) {
				$last_id = '';

				foreach ( $chunks as $chunk_id => $chunk ) {
					// new chunk
					$chunk_c = $last_id . $chunk;

					// is it full-length chunk?
					if ( strlen( $chunk ) === 4000 ) {
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
				// set cookie
				setcookie( 'pvc_visits[' . $key . ']', $value, $cookie['expiration'], COOKIEPATH, COOKIE_DOMAIN, (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ? true : false ), true );
			}
		}
	}

	/**
	 * Check if object cache is in use.
	 */
	public function using_object_cache( $using = null ) {
		$using = wp_using_ext_object_cache( $using );

		if ( $using ) {
			// check if explicitly disabled by flush_interval setting/option <= 0
			$flush_interval_number = Post_Views_Counter()->get_attribute( 'options', 'general', 'flush_interval', 'number' );
			$using = ( $flush_interval_number <= 0 ) ? false : true;
		}

		return $using;
	}

	/**
	 * Count visit function.
	 */
	private function count_visit( $id ) {
		global $wpdb;

		$cache_key_names = array();
		$using_object_cache = $this->using_object_cache();

		// get day, week, month and year
		$date = explode( '-', date( 'W-d-m-Y', current_time( 'timestamp' ) ) );

		foreach ( array(
		0	 => $date[3] . $date[2] . $date[1], // day like 20140324
		1	 => $date[3] . $date[0], // week like 201439
		2	 => $date[3] . $date[2], // month like 201405
		3	 => $date[3], // year like 2014
		4	 => 'total'	  // total views
		) as $type => $period ) {
			if ( $using_object_cache ) {
				$cache_key = $id . self::CACHE_KEY_SEPARATOR . $type . self::CACHE_KEY_SEPARATOR . $period;
				wp_cache_add( $cache_key, 0, self::GROUP );
				wp_cache_incr( $cache_key, 1, self::GROUP );
				$cache_key_names[] = $cache_key;
			} else {
				// hit the db directly
				// @TODO: investigate queueing these queries on the 'shutdown' hook instead instead of running them instantly?
				$this->db_insert( $id, $type, $period, 1 );
			}
		}

		// update the list of cache keys to be flushed
		if ( $using_object_cache && ! empty( $cache_key_names ) ) {
			$this->update_cached_keys_list_if_needed( $cache_key_names );
		}

		do_action( 'pvc_after_count_visit', $id );

		return true;
	}

	/**
	 * Update the single cache key which holds a list of all the cache keys
	 * that need to be flushed to the db.
	 *
	 * The value of that special cache key is a giant string containing key names separated with the `|` character.
	 * Each such key name then consists of 3 elements: $id, $type, $period (separated by a `.` character).
	 * Examples:
	 * 62053.0.20150327|62053.1.201513|62053.2.201503|62053.3.2015|62053.4.total|62180.0.20150327|62180.1.201513|62180.2.201503|62180.3.2015|62180.4.total
	 * A single key is `62053.0.20150327` and that key's data is: $id = 62053, $type = 0, $period = 20150327
	 *
	 * This data format proved more efficient (avoids the (un)serialization overhead completely + duplicates filtering is a string search now)
	 */
	private function update_cached_keys_list_if_needed( $key_names = array() ) {
		$existing_list = wp_cache_get( self::NAME_ALLKEYS, self::GROUP );
		if ( ! $existing_list ) {
			$existing_list = '';
		}

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
		if ( $list_modified ) {
			wp_cache_set( self::NAME_ALLKEYS, $existing_list, self::GROUP );
		}
	}

	/**
	 * Flush views data stored in the persistent object cache into
	 * our custom table and clear the object cache keys when done
	 */
	public function flush_cache_to_db() {
		global $wpdb;

		$key_names = wp_cache_get( self::NAME_ALLKEYS, self::GROUP );

		if ( ! $key_names ) {
			$key_names = array();
		} else {
			// create an array out of a string that's stored in the cache
			$key_names = explode( '|', $key_names );
		}

		foreach ( $key_names as $key_name ) {
			// get values stored within the key name itself
			list( $id, $type, $period ) = explode( self::CACHE_KEY_SEPARATOR, $key_name );
			// get the cached count value
			$count = wp_cache_get( $key_name, self::GROUP );

			// store cached value in the db
			$this->db_insert( $id, $type, $period, $count );

			// clear the cache key we just flushed
			wp_cache_delete( $key_name, self::GROUP );
		}

		// delete the key holding the list itself after we've successfully flushed it
		if ( ! empty( $key_names ) ) {
			wp_cache_delete( self::NAME_ALLKEYS, self::GROUP );
		}

		return true;
	}

	/*
	 * Insert or update views count.
	 */
	private function db_insert( $id, $type, $period, $count = 1 ) {
		global $wpdb;

		$count = (int) $count;

		if ( ! $count ) {
			$count = 1;
		}

		return $wpdb->query(
				$wpdb->prepare( "
				INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count)
				VALUES (%d, %d, %s, %d)
				ON DUPLICATE KEY UPDATE count = count + %d", $id, $type, $period, $count, $count
				)
		);
	}

	/**
	 * Check whether visitor is a bot.
	 */
	private function is_robot() {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) || (isset( $_SERVER['HTTP_USER_AGENT'] ) && trim( $_SERVER['HTTP_USER_AGENT'] ) === '') )
			return false;

		$robots = array(
			'bot', 'b0t', 'Acme.Spider', 'Ahoy! The Homepage Finder', 'Alkaline', 'Anthill', 'Walhello appie', 'Arachnophilia', 'Arale', 'Araneo', 'ArchitextSpider', 'Aretha', 'ARIADNE', 'arks', 'AskJeeves', 'ASpider (Associative Spider)', 'ATN Worldwide', 'AURESYS', 'BackRub', 'Bay Spider', 'Big Brother', 'Bjaaland', 'BlackWidow', 'Die Blinde Kuh', 'Bloodhound', 'BSpider', 'CACTVS Chemistry Spider', 'Calif', 'Cassandra', 'Digimarc Marcspider/CGI', 'ChristCrawler.com', 'churl', 'cIeNcIaFiCcIoN.nEt', 'CMC/0.01', 'Collective', 'Combine System', 'Web Core / Roots', 'Cusco', 'CyberSpyder Link Test', 'CydralSpider', 'Desert Realm Spider', 'DeWeb(c) Katalog/Index', 'DienstSpider', 'Digger', 'Direct Hit Grabber', 'DownLoad Express', 'DWCP (Dridus\' Web Cataloging Project)', 'e-collector', 'EbiNess', 'Emacs-w3 Search Engine', 'ananzi', 'esculapio', 'Esther', 'Evliya Celebi', 'FastCrawler', 'Felix IDE', 'Wild Ferret Web Hopper #1, #2, #3', 'FetchRover', 'fido', 'KIT-Fireball', 'Fish search', 'Fouineur', 'Freecrawl', 'FunnelWeb', 'gammaSpider, FocusedCrawler', 'gazz', 'GCreep', 'GetURL', 'Golem', 'Grapnel/0.01 Experiment', 'Griffon', 'Gromit', 'Northern Light Gulliver', 'Harvest', 'havIndex', 'HI (HTML Index) Search', 'Hometown Spider Pro', 'ht://Dig', 'HTMLgobble', 'Hyper-Decontextualizer', 'IBM_Planetwide', 'Popular Iconoclast', 'Ingrid', 'Imagelock', 'IncyWincy', 'Informant', 'Infoseek Sidewinder', 'InfoSpiders', 'Inspector Web', 'IntelliAgent', 'Iron33', 'Israeli-search', 'JavaBee', 'JCrawler', 'Jeeves', 'JumpStation', 'image.kapsi.net', 'Katipo', 'KDD-Explorer', 'Kilroy', 'LabelGrabber', 'larbin', 'legs', 'Link Validator', 'LinkScan', 'LinkWalker', 'Lockon', 'logo.gif Crawler', 'Lycos', 'Mac WWWWorm', 'Magpie', 'marvin/infoseek', 'Mattie', 'MediaFox', 'MerzScope', 'NEC-MeshExplorer', 'MindCrawler', 'mnoGoSearch search engine software', 'moget', 'MOMspider', 'Monster', 'Motor', 'Muncher', 'Muninn', 'Muscat Ferret', 'Mwd.Search', 'Internet Shinchakubin', 'NDSpider', 'Nederland.zoek', 'NetCarta WebMap Engine', 'NetMechanic', 'NetScoop', 'newscan-online', 'NHSE Web Forager', 'Nomad', 'nzexplorer', 'ObjectsSearch', 'Occam', 'HKU WWW Octopus', 'OntoSpider', 'Openfind data gatherer', 'Orb Search', 'Pack Rat', 'PageBoy', 'ParaSite', 'Patric', 'pegasus', 'The Peregrinator', 'PerlCrawler 1.0', 'Phantom', 'PhpDig', 'PiltdownMan', 'Pioneer', 'html_analyzer', 'Portal Juice Spider', 'PGP Key Agent', 'PlumtreeWebAccessor', 'Poppi', 'PortalB Spider', 'GetterroboPlus Puu', 'Raven Search', 'RBSE Spider', 'RoadHouse Crawling System', 'ComputingSite Robi/1.0', 'RoboCrawl Spider', 'RoboFox', 'Robozilla', 'RuLeS', 'Scooter', 'Sleek', 'Search.Aus-AU.COM', 'SearchProcess', 'Senrigan', 'SG-Scout', 'ShagSeeker', 'Shai\'Hulud', 'Sift', 'Site Valet', 'SiteTech-Rover', 'Skymob.com', 'SLCrawler', 'Inktomi Slurp', 'Smart Spider', 'Snooper', 'Spanner', 'Speedy Spider', 'spider_monkey', 'Spiderline Crawler', 'SpiderMan', 'SpiderView(tm)', 'Site Searcher', 'Suke', 'suntek search engine', 'Sven', 'Sygol', 'TACH Black Widow', 'Tarantula', 'tarspider', 'Templeton', 'TeomaTechnologies', 'TITAN', 'TitIn', 'TLSpider', 'UCSD Crawl', 'UdmSearch', 'URL Check', 'URL Spider Pro', 'Valkyrie', 'Verticrawl', 'Victoria', 'vision-search', 'Voyager', 'W3M2', 'WallPaper (alias crawlpaper)', 'the World Wide Web Wanderer', 'w@pSpider by wap4.com', 'WebBandit Web Spider', 'WebCatcher', 'WebCopy', 'webfetcher', 'Webinator', 'weblayers', 'WebLinker', 'WebMirror', 'The Web Moose', 'WebQuest', 'Digimarc MarcSpider', 'WebReaper', 'webs', 'Websnarf', 'WebSpider', 'WebVac', 'webwalk', 'WebWalker', 'WebWatch', 'Wget', 'whatUseek Winona', 'Wired Digital', 'Weblog Monitor', 'w3mir', 'WebStolperer', 'The Web Wombat', 'The World Wide Web Worm', 'WWWC Ver 0.2.5', 'WebZinger', 'XGET'
		);

		foreach ( $robots as $robot ) {
			if ( stripos( $_SERVER['HTTP_USER_AGENT'], $robot ) !== false )
				return true;
		}

		return false;
	}

}

<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Query class.
 * 
 * @class Post_Views_Counter_Query
 */
class Post_Views_Counter_Query {

	public function __construct() {
		// actions
		add_action( 'pre_get_posts', array( $this, 'extend_pre_query' ), 1 );

		// filters
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
		add_filter( 'posts_groupby', array( $this, 'posts_groupby' ), 10, 2 );
		add_filter( 'posts_orderby', array( $this, 'posts_orderby' ), 10, 2 );
		add_filter( 'posts_fields', array( $this, 'posts_fields' ), 10, 2 );
		add_filter( 'the_posts', array( $this, 'the_posts' ), 10, 2 );
	}

	/**
	 * Register views_query var.
	 *
	 * @param array $query_vars
	 * @return array
	 */
	public function query_vars( $query_vars ) {
		$query_vars[] = 'views_query';

		return $query_vars;
	}

	/**
	 * Extend query with post_views orderby parameter.
	 *
	 * @param object $query
	 */
	public function extend_pre_query( $query ) {
		if ( isset( $query->query_vars['orderby'] ) && $query->query_vars['orderby'] === 'post_views' )
			$query->pvc_orderby = true;
	}

	/**
	 * Modify the db query to use post_views parameter.
	 * 
	 * @global object $wpdb
	 * @param string $join
	 * @param object $query
	 * @return string
	 */
	public function posts_join( $join, $query ) {
		$sql = '';
		$query_chunks = array();

		// views query?
		if ( ! empty( $query->query['views_query'] ) ) {
			if ( isset( $query->query['views_query']['inclusive'] ) )
				$query->query['views_query']['inclusive'] = (bool) $query->query['views_query']['inclusive'];
			else
				$query->query['views_query']['inclusive'] = true;

			// check after and before dates
			foreach ( array( 'after' => '>', 'before' => '<' ) as $date => $type ) {
				$year_ = null;
				$month_ = null;
				$week_ = null;
				$day_ = null;

				// check views query date
				if ( ! empty( $query->query['views_query'][$date] ) ) {
					// is it a date array?
					if ( is_array( $query->query['views_query'][$date] ) ) {
						// check views query $date date year
						if ( ! empty( $query->query['views_query'][$date]['year'] ) )
							$year_ = str_pad( (int) $query->query['views_query'][$date]['year'], 4, 0, STR_PAD_LEFT );

						// check views query date month
						if ( ! empty( $query->query['views_query'][$date]['month'] ) )
							$month_ = str_pad( (int) $query->query['views_query'][$date]['month'], 2, 0, STR_PAD_LEFT );

						// check views query date week
						if ( ! empty( $query->query['views_query'][$date]['week'] ) )
							$week_ = str_pad( (int) $query->query['views_query'][$date]['week'], 2, 0, STR_PAD_LEFT );

						// check views query date day
						if ( ! empty( $query->query['views_query'][$date]['day'] ) )
							$day_ = str_pad( (int) $query->query['views_query'][$date]['day'], 2, 0, STR_PAD_LEFT );
					// is it a date string?
					} elseif ( is_string( $query->query['views_query'][$date] ) ) {
						$time_ = strtotime( $query->query['views_query'][$date] );

						// valid datetime?
						if ( $time_ !== false ) {
							// week does not exists here, string dates are always treated as year + month + day
							list( $day_, $month_, $year_ ) = explode( ' ', date( "d m Y", $time_ ) );
						}
					}

					// valid date?
					if ( ! ( $year_ === null && $month_ === null && $week_ === null && $day_ === null ) ) {
						$query_chunks[] = array(
							'year'	=> $year_,
							'month'	=> $month_,
							'day'	=> $day_,
							'week'	=> $week_,
							'type'	=> $type . ( $query->query['views_query']['inclusive'] ? '=' : '' )
						);
					}
				}
			}

			// any after, before query chunks?
			if ( ! empty( $query_chunks ) ) {
				$valid_dates = true;

				// check only if both dates are in query
				if ( count( $query_chunks ) === 2 ) {
					// before and after dates should be the same
					foreach ( array( 'year', 'month', 'day', 'week' ) as $date_type ) {
						if ( ! ( ( $query_chunks[0][$date_type] !== null && $query_chunks[1][$date_type] !== null ) || ( $query_chunks[0][$date_type] === null && $query_chunks[1][$date_type] === null ) ) )
							$valid_dates = false;
					}
				}

				// after and before dates should be 
				if ( $valid_dates ) {
					foreach ( $query_chunks as $chunk ) {
						// year
						if ( isset( $chunk['year'] ) ) {
							// year, week
							if ( isset( $chunk['week'] ) )
								$sql .= " AND pvc.type = 1 AND pvc.period " . $chunk['type'] . " '" . $chunk['year'] . $chunk['week'] . "'";
							// year, month
							elseif ( isset( $chunk['month'] ) ) {
								// year, month, day
								if ( isset( $chunk['day'] ) )
									$sql .= " AND pvc.type = 0 AND pvc.period " . $chunk['type'] . " '" . $chunk['year'] . $chunk['month'] . $chunk['day'] . "'";
								// year, month
								else
									$sql .= " AND pvc.type = 2 AND pvc.period " . $chunk['type'] . " '" . $chunk['year'] . $chunk['month'] . "'";
							// year
							} else
								$sql .= " AND pvc.type = 3 AND pvc.period " . $chunk['type'] . " '" . $chunk['year'] . "'";
						// month
						} elseif ( isset( $chunk['month'] ) ) {
							// month, day
							if ( isset( $chunk['day'] ) )
								$sql .= " AND pvc.type = 0 AND RIGHT( pvc.period, 4 ) " . $chunk['type'] . " '" . $chunk['month'] . $chunk['day'] . "'";
							// month
							else
								$sql .= " AND pvc.type = 2 AND RIGHT( pvc.period, 2 ) " . $chunk['type'] . " '" . $chunk['month'] . "'";
						// week
						} elseif ( isset( $chunk['week'] ) )
							$sql .= " AND pvc.type = 1 AND RIGHT( pvc.period, 2 ) " . $chunk['type'] . " '" . $chunk['week'] . "'";
						// day
						elseif ( isset( $chunk['day'] ) )
							$sql .= " AND pvc.type = 0 AND RIGHT( pvc.period, 2 ) " . $chunk['type'] . " '" . $chunk['day'] . "'";
					}
				}
			}

			// standard query
			if ( $sql === '' ) {
				// check year
				if ( isset( $query->query['views_query']['year'] ) )
					$year = (int) $query->query['views_query']['year'];

				// check month
				if ( isset( $query->query['views_query']['month'] ) )
					$month = (int) $query->query['views_query']['month'];

				// check week
				if ( isset( $query->query['views_query']['week'] ) )
					$week = (int) $query->query['views_query']['week'];

				// check day
				if ( isset( $query->query['views_query']['day'] ) )
					$day = (int) $query->query['views_query']['day'];

				// year
				if ( isset( $year ) ) {
					// year, week
					if ( isset( $week ) && $this->is_valid_date( 'yw', $year, 0, 0, $week ) )
						$sql = " AND pvc.type = 1 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . str_pad( $week, 2, 0, STR_PAD_LEFT ) . "'";
					// year, month
					elseif ( isset( $month ) ) {
						// year, month, day
						if ( isset( $day ) && $this->is_valid_date( 'ymd', $year, $month, $day ) )
							$sql = " AND pvc.type = 0 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . str_pad( $month, 2, 0, STR_PAD_LEFT ) . str_pad( $day, 2, 0, STR_PAD_LEFT ) . "'";
						// year, month
						elseif ( $this->is_valid_date( 'ym', $year, $month ) )
							$sql = " AND pvc.type = 2 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . str_pad( $month, 2, 0, STR_PAD_LEFT ) . "'";
					// year
					} elseif ( $this->is_valid_date( 'y', $year ) )
						$sql = " AND pvc.type = 3 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . "'";
				// month
				} elseif ( isset( $month ) ) {
					// month, day
					if ( isset( $day ) && $this->is_valid_date( 'md', 0, $month, $day ) ) {
						$sql = " AND pvc.type = 0 AND RIGHT( pvc.period, 4 ) = '" . str_pad( $month, 2, 0, STR_PAD_LEFT ) . str_pad( $day, 2, 0, STR_PAD_LEFT ) . "'";
					// month
					} elseif ( $this->is_valid_date( 'm', 0, $month ) )
						$sql = " AND pvc.type = 2 AND RIGHT( pvc.period, 2 ) = '" . str_pad( $month, 2, 0, STR_PAD_LEFT ) . "'";
				// week
				} elseif ( isset( $week ) && $this->is_valid_date( 'w', 0, 0, 0, $week ) )
					$sql = " AND pvc.type = 1 AND RIGHT( pvc.period, 2 ) = '" . str_pad( $week, 2, 0, STR_PAD_LEFT ) . "'";
				// day
				elseif ( isset( $day ) && $this->is_valid_date( 'd', 0, 0, $day ) )
					$sql = " AND pvc.type = 0 AND RIGHT( pvc.period, 2 ) = '" . str_pad( $day, 2, 0, STR_PAD_LEFT ) . "'";
			}

			if ( $sql !== '' )
				$query->pvc_query = true;
		}

		// is it sorted by post views?
		if ( ( $sql === '' && isset( $query->pvc_orderby ) && $query->pvc_orderby ) || apply_filters( 'pvc_extend_post_object', false, $query ) === true )
			$sql = ' AND pvc.type = 4';

		// add date range
		if ( $sql !== '' ) {
			global $wpdb;

			$join .= " LEFT JOIN " . $wpdb->prefix . "post_views pvc ON pvc.id = " . $wpdb->prefix . "posts.ID" . $sql;
		}

		return $join;
	}

	/**
	 * Group posts using the post ID.
	 * 
	 * @global object $wpdb
	 * @param string $groupby
	 * @param object $query
	 * @return string
	 */
	public function posts_groupby( $groupby, $query ) {
		// is it sorted by post views or views_query is used?
		if ( ( isset( $query->pvc_orderby ) && $query->pvc_orderby ) || ( isset( $query->pvc_query ) && $query->pvc_query ) || apply_filters( 'pvc_extend_post_object', false, $query ) === true ) {
			global $pagenow;

			// needed only for sorting
			if ( $pagenow === 'upload.php' || $pagenow === 'edit.php' )
				$query->query['views_query']['hide_empty'] = false;

			global $wpdb;

			$groupby = trim( $groupby );

			if ( strpos( $groupby, $wpdb->prefix . 'posts.ID' ) === false )
				$groupby = ( $groupby !== '' ? $groupby . ', ' : '') . $wpdb->prefix . 'posts.ID';

			if ( ! isset( $query->query['views_query']['hide_empty'] ) || $query->query['views_query']['hide_empty'] === true )
				$groupby .= ' HAVING post_views > 0';
		}

		return $groupby;
	}

	/**
	 * Order posts by post views.
	 * 
	 * @global object $wpdb
	 * @param string $orderby
	 * @param object $query
	 * @return string
	 */
	public function posts_orderby( $orderby, $query ) {
		// is it sorted by post views?
		if ( ( isset( $query->pvc_orderby ) && $query->pvc_orderby ) ) {
			global $wpdb;

			$order = $query->get( 'order' );
			$orderby = ( ! isset( $query->query['views_query']['hide_empty'] ) || $query->query['views_query']['hide_empty'] === true ? 'post_views' : 'pvc.count' ) . ' ' . $order . ', ' . $wpdb->prefix . 'posts.ID ' . $order;
		}

		return $orderby;
	}

	/**
	 * Return post views in queried post objects.
	 * 
	 * @param string $fields
	 * @param object $query
	 * @return string
	 */
	public function posts_fields( $fields, $query ) {
		if ( ( ! isset( $query->query['fields'] ) || $query->query['fields'] === '' ) && ( ( isset( $query->pvc_orderby ) && $query->pvc_orderby ) || ( isset( $query->pvc_query ) && $query->pvc_query ) || apply_filters( 'pvc_extend_post_object', false, $query ) === true ) )
			$fields = $fields . ', SUM( IFNULL( pvc.count, 0 ) ) AS post_views';

		return $fields;
	}

	/**
	 * Extend query object with total post views.
	 *
	 * @param array $posts
	 * @param object $query
	 * @return array
	 */
	public function the_posts( $posts, $query ) {
		if ( ( isset( $query->pvc_orderby ) && $query->pvc_orderby ) || ( isset( $query->pvc_query ) && $query->pvc_query ) || apply_filters( 'pvc_extend_post_object', false, $query ) === true ) {
			$sum = 0;

			// any posts found?
			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					if ( ! empty( $post->post_views ) )
						$sum += (int) $post->post_views;
				}
			}

			// pass total views
			$query->total_views = $sum;
		}

		return $posts;
	}

	/**
	 * Validate date helper function.
	 *
	 * @param string $type
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 * @param int $week
	 * @return bool
	 */
	private function is_valid_date( $type, $year = 0, $month = 0, $day = 0, $week = 0 ) {
		switch ( $type ) {
			case 'y':
				$bool = ( $year >= 1 && $year <= 32767 );
				break;

			case 'yw':
				$bool = ( $year >= 1 && $year <= 32767 && $week >= 0 && $week <= 53 );
				break;

			case 'ym':
				$bool = ( $year >= 1 && $year <= 32767 && $month >= 1 && $month <= 12 );
				break;

			case 'ymd':
				$bool = checkdate( $month, $day, $year );
				break;

			case 'm':
				$bool = ( $month >= 1 && $month <= 12 );
				break;

			case 'md':
				$bool = ( $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 );
				break;

			case 'w':
				$bool = ( $week >= 0 && $week <= 53 );
				break;

			case 'd':
				$bool = ( $day >= 1 && $day <= 31 );
				break;
		}

		return $bool;
	}

}

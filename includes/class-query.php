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

	private $join_sql = '';

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'pre_get_posts', [ $this, 'extend_pre_query' ], 1 );

		// filters
		add_filter( 'query_vars', [ $this, 'query_vars' ] );
		add_filter( 'posts_join', [ $this, 'posts_join' ], 100, 2 );
		add_filter( 'posts_groupby', [ $this, 'posts_groupby' ], 10, 2 );
		add_filter( 'posts_orderby', [ $this, 'posts_orderby' ], 10, 2 );
		add_filter( 'posts_distinct', [ $this, 'posts_distinct' ], 10, 2 );
		add_filter( 'posts_fields', [ $this, 'posts_fields' ], 10, 2 );
		add_filter( 'the_posts', [ $this, 'the_posts' ], 10, 2 );
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
	 * @return void
	 */
	public function extend_pre_query( $query ) {
		if ( isset( $query->query_vars['orderby'] ) && $query->query_vars['orderby'] === 'post_views' )
			$query->pvc_orderby = true;
	}

	/**
	 * Modify the database query to use post_views parameter.
	 *
	 * @global object $wpdb
	 *
	 * @param string $join
	 * @param object $query
	 * @return string
	 */
	public function posts_join( $join, $query ) {
		$sql = '';
		$query_chunks = [];

		// views query?
		if ( ! empty( $query->query['views_query'] ) ) {
			if ( isset( $query->query['views_query']['inclusive'] ) )
				$query->query['views_query']['inclusive'] = (bool) $query->query['views_query']['inclusive'];
			else
				$query->query['views_query']['inclusive'] = true;

			// check after and before dates
			foreach ( [ 'after' => '>', 'before' => '<' ] as $date => $type ) {
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
						$query_chunks[] = [
							'year'	=> $year_,
							'month'	=> $month_,
							'day'	=> $day_,
							'week'	=> $week_,
							'type'	=> $type . ( $query->query['views_query']['inclusive'] ? '=' : '' )
						];
					}
				}
			}

			// any after, before query chunks?
			if ( ! empty( $query_chunks ) ) {
				$valid_dates = true;

				// check only if both dates are in query
				if ( count( $query_chunks ) === 2 ) {
					// before and after dates should be the same
					foreach ( [ 'year', 'month', 'day', 'week' ] as $date_type ) {
						if ( ! ( ( $query_chunks[0][$date_type] !== null && $query_chunks[1][$date_type] !== null ) || ( $query_chunks[0][$date_type] === null && $query_chunks[1][$date_type] === null ) ) )
							$valid_dates = false;
					}
				}

				// after and before dates should be both valid
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
					if ( isset( $week ) && $this->is_date_valid( 'yw', $year, 0, 0, $week ) )
						$sql = " AND pvc.type = 1 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . str_pad( $week, 2, 0, STR_PAD_LEFT ) . "'";
					// year, month
					elseif ( isset( $month ) ) {
						// year, month, day
						if ( isset( $day ) && $this->is_date_valid( 'ymd', $year, $month, $day ) )
							$sql = " AND pvc.type = 0 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . str_pad( $month, 2, 0, STR_PAD_LEFT ) . str_pad( $day, 2, 0, STR_PAD_LEFT ) . "'";
						// year, month
						elseif ( $this->is_date_valid( 'ym', $year, $month ) )
							$sql = " AND pvc.type = 2 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . str_pad( $month, 2, 0, STR_PAD_LEFT ) . "'";
					// year
					} elseif ( $this->is_date_valid( 'y', $year ) )
						$sql = " AND pvc.type = 3 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . "'";
				// month
				} elseif ( isset( $month ) ) {
					// month, day
					if ( isset( $day ) && $this->is_date_valid( 'md', 0, $month, $day ) ) {
						$sql = " AND pvc.type = 0 AND RIGHT( pvc.period, 4 ) = '" . str_pad( $month, 2, 0, STR_PAD_LEFT ) . str_pad( $day, 2, 0, STR_PAD_LEFT ) . "'";
					// month
					} elseif ( $this->is_date_valid( 'm', 0, $month ) )
						$sql = " AND pvc.type = 2 AND RIGHT( pvc.period, 2 ) = '" . str_pad( $month, 2, 0, STR_PAD_LEFT ) . "'";
				// week
				} elseif ( isset( $week ) && $this->is_date_valid( 'w', 0, 0, 0, $week ) )
					$sql = " AND pvc.type = 1 AND RIGHT( pvc.period, 2 ) = '" . str_pad( $week, 2, 0, STR_PAD_LEFT ) . "'";
				// day
				elseif ( isset( $day ) && $this->is_date_valid( 'd', 0, 0, $day ) )
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

			$this->join_sql = $join;
		}

		return $join;
	}

	/**
	 * Group posts using the post ID.
	 *
	 * @global object $wpdb
	 * @global string $pagenow
	 *
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
			$groupby_aliases = [];
			$groupby_values = [];
			$groupby_sql = '';
			$groupby_set = false;

			// standard group by
			if ( strpos( $groupby, $wpdb->prefix . 'posts.ID' ) === false )
				$groupby_aliases[] = $wpdb->prefix . 'posts.ID';
			else
				$groupby_set = true;

			// tax query group by
			$groupby_aliases[] = $this->get_groupby_meta_aliases( $query );

			// meta query group by
			if ( $this->join_sql ) {
				$groupby_aliases[] = $this->get_groupby_tax_aliases( $query, $this->join_sql );

				// clear join to avoid possible issues
				$this->join_sql = '';
			}

			// any group by aliases?
			if ( ! empty( $groupby_aliases ) ) {
				foreach ( $groupby_aliases as $alias ) {
					if ( is_array( $alias ) ) {
						$groupby_values = array_merge( $groupby_values, $alias );
					} else
						$groupby_values[] = $alias;
				}
			}

			// any group by values?
			if ( ! empty( $groupby_values ) ) {
				$groupby = ( $groupby !== '' ? $groupby . ', ' : '' ) . implode( ', ', $groupby_values );

				// set group by flag
				$groupby_set = true;
			}

			if ( $groupby_set )
				$query->pvc_groupby = true;

			// hide empty?
			if ( ! isset( $query->query['views_query']['hide_empty'] ) || $query->query['views_query']['hide_empty'] === true )
				$groupby .= ' HAVING post_views > 0';
		}

		return $groupby;
	}

	/**
	 * Order posts by post views.
	 *
	 * @global object $wpdb
	 *
	 * @param string $orderby
	 * @param object $query
	 * @return string
	 */
	public function posts_orderby( $orderby, $query ) {
		// is it sorted by post views?
		if ( ( isset( $query->pvc_orderby ) && $query->pvc_orderby ) ) {
			global $wpdb;

			$order = $query->get( 'order' );
			$orderby = 'post_views ' . $order . ', ' . $wpdb->prefix . 'posts.ID ' . $order;
		}

		return $orderby;
	}

	/**
	 * Add DISTINCT clause.
	 *
	 * @param string $distinct
	 * @param object $query
	 * @return string
	 */
	public function posts_distinct( $distinct, $query ) {
		if ( ( ( isset( $query->pvc_groupby ) && $query->pvc_groupby ) || ( isset( $query->pvc_orderby ) && $query->pvc_orderby ) || ( isset( $query->pvc_query ) && $query->pvc_query ) || apply_filters( 'pvc_extend_post_object', false, $query ) === true ) && ( strpos( $distinct, 'DISTINCT' ) === false ) )
			$distinct = $distinct . ' DISTINCT ';

		return $distinct;
	}

	/**
	 * Return post views in queried post objects.
	 *
	 * @param string $fields
	 * @param object $query
	 * @return string
	 */
	public function posts_fields( $fields, $query ) {
		if ( ( ! isset( $query->query['fields'] ) || $query->query['fields'] === '' || $query->query['fields'] === 'all' ) && ( ( isset( $query->pvc_orderby ) && $query->pvc_orderby ) || ( isset( $query->pvc_query ) && $query->pvc_query ) || apply_filters( 'pvc_extend_post_object', false, $query ) === true ) )
			$fields = $fields . ', SUM( COALESCE( pvc.count, 0 ) ) AS post_views';

		return $fields;
	}

	/**
	 * Get tax table aliases from query.
	 *
	 * @global object $wpdb
	 *
	 * @param object $query
	 * @param string $join_sql
	 * @return array
	 */
	private function get_groupby_tax_aliases( $query, $join_sql ) {
		global $wpdb;

		$groupby = [];

		// trim join sql
		$join_sql = trim( $join_sql );

		// any join sql? valid query with tax query?
		if ( $join_sql !== '' && is_a( $query, 'WP_Query' ) && ! empty( $query->tax_query ) && is_a( $query->tax_query, 'WP_Tax_Query' ) ) {
			// unfortunately there is no way to get table_aliases by native function
			// tax query does not have get_clauses either like meta query does
			// we have to find aliases the hard way
			$chunks = explode( 'JOIN', $join_sql );

			// any join clauses?
			if ( ! empty( $chunks ) ) {
				$aliases = [];

				foreach ( $chunks as $chunk ) {
					// standard join
					if ( strpos( $chunk, $wpdb->prefix . 'term_relationships ON' ) !== false )
						$aliases[] = $wpdb->prefix . 'term_relationships';
					// alias join
					elseif ( strpos( $chunk, $wpdb->prefix . 'term_relationships AS' ) !== false && preg_match( '/' . $wpdb->prefix . 'term_relationships AS ([a-z0-9]+) ON/i', $chunk, $matches ) === 1 )
						$aliases[] = $matches[1];
				}

				// any aliases?
				if ( ! empty( $aliases ) ) {
					foreach ( array_unique( $aliases ) as $alias ) {
						$groupby[] = $alias . '.term_taxonomy_id';
					}
				}
			}
		}

		return $groupby;
	}

	/**
	 * Get meta table aliases from query.
	 *
	 * @param object $query
	 * @return array
	 */
	private function get_groupby_meta_aliases( $query ) {
		$groupby = [];

		// valid query with meta query?
		if ( is_a( $query, 'WP_Query' ) && ! empty( $query->meta_query ) && is_a( $query->meta_query, 'WP_Meta_Query' ) ) {
			// get meta clauses, we can't use table_aliases here since it's protected value
			$clauses = $query->meta_query->get_clauses();

			// any meta clauses?
			if ( ! empty( $clauses ) ) {
				$aliases = [];

				foreach ( $clauses as $clause ) {
					$aliases[] = $clause['alias'];
				}

				// any aliases?
				if ( ! empty( $aliases ) ) {
					foreach ( array_unique( $aliases ) as $alias ) {
						$groupby[] = $alias . '.meta_id';
					}
				}
			}
		}

		return $groupby;
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
	 * Check whether date is valid.
	 *
	 * @param string $type
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 * @param int $week
	 * @return bool
	 */
	private function is_date_valid( $type, $year = 0, $month = 0, $day = 0, $week = 0 ) {
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

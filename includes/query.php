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

		if ( ! empty( $query->query['views_query'] ) ) {
			if ( isset( $query->query['views_query']['year'] ) )
				$year = (int) $query->query['views_query']['year'];

			if ( isset( $query->query['views_query']['month'] ) )
				$month = (int) $query->query['views_query']['month'];

			if ( isset( $query->query['views_query']['week'] ) )
				$week = (int) $query->query['views_query']['week'];

			if ( isset( $query->query['views_query']['day'] ) )
				$day = (int) $query->query['views_query']['day'];

			// year
			if ( isset( $year ) ) {
				// year, week
				if ( isset( $week ) && $this->is_valid_date( 'yw', $year, 0, 0, $week ) ) {
					$sql = " AND pvc.type = 1 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . str_pad( $week, 2, 0, STR_PAD_LEFT ) . "'";
					// year, month
				} elseif ( isset( $month ) ) {
					// year, month, day
					if ( isset( $day ) && $this->is_valid_date( 'ymd', $year, $month, $day ) ) {
						$sql = " AND pvc.type = 0 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . str_pad( $month, 2, 0, STR_PAD_LEFT ) . str_pad( $day, 2, 0, STR_PAD_LEFT ) . "'";
						// year, month
					} elseif ( $this->is_valid_date( 'ym', $year, $month ) ) {
						$sql = " AND pvc.type = 2 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . str_pad( $month, 2, 0, STR_PAD_LEFT ) . "'";
					}
					// year
				} elseif ( $this->is_valid_date( 'y', $year ) ) {
					$sql = " AND pvc.type = 3 AND pvc.period = '" . str_pad( $year, 4, 0, STR_PAD_LEFT ) . "'";
				}
				// month
			} elseif ( isset( $month ) ) {
				// month, day
				if ( isset( $day ) && $this->is_valid_date( 'md', 0, $month, $day ) ) {
					$sql = " AND pvc.type = 0 AND RIGHT( pvc.period, 4 ) = '" . str_pad( $month, 2, 0, STR_PAD_LEFT ) . str_pad( $day, 2, 0, STR_PAD_LEFT ) . "'";
					// month
				} elseif ( $this->is_valid_date( 'm', 0, $month ) ) {
					$sql = " AND pvc.type = 2 AND RIGHT( pvc.period, 2 ) = '" . str_pad( $month, 2, 0, STR_PAD_LEFT ) . "'";
				}
				// week
			} elseif ( isset( $week ) && $this->is_valid_date( 'w', 0, 0, 0, $week ) ) {
				$sql = " AND pvc.type = 1 AND RIGHT( pvc.period, 2 ) = '" . str_pad( $week, 2, 0, STR_PAD_LEFT ) . "'";
				// day
			} elseif ( isset( $day ) && $this->is_valid_date( 'd', 0, 0, $day ) ) {
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

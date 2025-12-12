<?php
/**
 * Post Views Counter pluggable template functions
 *
 * Override any of those functions by copying it to your theme or replace it via plugin
 *
 * @author Digital Factory
 * @package Post Views Counter
 * @since 1.0.0
 */
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Get post views for a post or array of posts.
 *
 * @global object $wpdb
 *
 * @param int|array $post_id
 * @param string $period
 *
 * @return int
 */
if ( ! function_exists( 'pvc_get_post_views' ) ) {
	function pvc_get_post_views( $post_id = 0, $period = 'total' ) {
		global $wpdb;

		// sanitize period
		$period = sanitize_key( $period );

		if ( empty( $post_id ) )
			$post_id = get_the_ID();

		if ( is_array( $post_id ) ) {
			$numbers = array_filter( array_unique( array_map( 'intval', $post_id ) ) );
			$post_id = implode( ',', $numbers );
		} else if ( $post_id === 'all' ) {
			$numbers = [];
		} else {
			$post_id = (int) $post_id;
			$numbers = [ $post_id ];
		}

		// set where clause
		$where = [ 'type' => 'type = 4' ];

		// update where clause
		$where = apply_filters( 'pvc_get_post_views_period_where', $where, $period, $post_id );

		// updated where clause
		$_where = [];

		// sanitize where clause
		foreach ( $where as $index => $value ) {
			if ( $index === 'type' || $index === 'content' )
				$_where[$index] = preg_replace( '/[^0-9]/', '', $value );
			elseif ( $index === 'period' ) {
				$values = preg_match_all( '/\d+/', $value, $matches );

				// any values?
				if ( $values !== false && $values > 0 )
					$_where['period'] = $matches[0];
			}
		}

		// get current number of ids
		$ids_count = count( $numbers );

		$where_clause = '';
		
		// add post ids if needed
		if ( $ids_count ) {
			$where_clause .= " WHERE id IN (" . implode( ',', array_fill( 0, $ids_count, '%d' ) ) . ") AND";
		} else {
			$where_clause .= " WHERE";
		}

		// validate where clause
		foreach( $_where as $index => $value ) {
			if ( $index === 'type' ) {
				$where_clause .= ' type = %d';
				$numbers[] = (int) $value;
			} elseif ( $index === 'content' ) {
				$where_clause .= ' AND content = %d';
				$numbers[] = (int) $value;
			} elseif ( $index === 'period' ) {
				$nop = count( $_where['period'] );

				if ( $nop === 1 ) {
					$where_clause .= ' AND CAST( period AS SIGNED ) = %d';
					$numbers[] = (int) $_where['period'][0];
				} elseif ( $nop === 2 ) {
					$where_clause .= ' AND CAST( period AS SIGNED ) <= %d AND CAST( period AS SIGNED ) >= %d';
					$numbers[] = (int) $_where['period'][0];
					$numbers[] = (int) $_where['period'][1];
				}
			}
		}

		// prepare query
		$query = $wpdb->prepare( "SELECT SUM(count) AS views FROM " . $wpdb->prefix . "post_views" . $where_clause, $numbers );

		// calculate query hash
		$query_hash = md5( $query );

		// get cached data
		$post_views = wp_cache_get( $query_hash, 'pvc-get_post_views' );

		// cached data not found?
		if ( $post_views === false ) {
			// get post views
			$post_views = (int) $wpdb->get_var( $query );

			// set the cache expiration, 5 minutes by default
			$expire = absint( apply_filters( 'pvc_object_cache_expire', 300 ) );

			// add cached post views
			wp_cache_add( $query_hash, $post_views, 'pvc-get_post_views', $expire );
		}

		return (int) apply_filters( 'pvc_get_post_views', $post_views, $post_id, $period );
	}
}

/**
 * Get views query.
 *
 * @global object $wpdb
 *
 * @param array $args
 *
 * @return int|array
 */
if ( ! function_exists( 'pvc_get_views' ) ) {
	function pvc_get_views( $args = [] ) {
		global $wpdb;

		$range = [];
		$defaults = [
			'fields'		=> 'views',
			'post_id'		=> '',
			'post_type'		=> '',
			'views_query'	=> [
				'year'		=> '',
				'month'		=> '',
				'week'		=> '',
				'day'		=> '',
				'after'		=> '',	// string or array
				'before'	=> '',	// string or array
				'inclusive'	=> true
			]
		];

		// merge default options with new arguments
		$args = array_merge( $defaults, $args );

		// check views query
		if ( ! is_array( $args['views_query'] ) )
			$args['views_query'] = $defaults['views_query'];

		// merge views query too
		$args['views_query'] = array_merge( $defaults['views_query'], $args['views_query'] );

		// filter arguments
		$args = apply_filters( 'pvc_get_views_args', $args );

		// check post types
		if ( is_string( $args['post_type'] ) )
			$args['post_type'] = [ $args['post_type'] ];
		elseif ( ! is_array( $args['post_type'] ) )
			$args['post_type'] = [];

		// get number of post types
		$post_types_count = count( $args['post_type'] );

		// check post ids
		if ( is_array( $args['post_id'] ) && ! empty( $args['post_id'] ) )
			$args['post_id'] = array_filter( array_unique( array_map( 'intval', $args['post_id'] ) ) );
		elseif ( is_string( $args['post_id'] ) || is_numeric( $args['post_id'] ) ) {
			$post_id = (int) $args['post_id'];

			if ( $post_id === 0 )
				$args['post_id'] = [];
			else
				$args['post_id'] = [ $post_id ];
		} else
			$args['post_id'] = [];

		// get number of post ids
		$post_ids_count = count( $args['post_id'] );

		// placeholder for empty query data
		$query_data = [ 1 ];

		// set query data
		if ( $post_ids_count === 0 && $post_types_count === 0 )
			$query_data = [ 1 ];
		elseif ( $post_ids_count === 0 )
			$query_data = array_merge( $query_data, array_values( $args['post_type'] ) );
		elseif ( $post_types_count === 0 )
			$query_data = array_merge( $query_data, array_values( $args['post_id'] ) );
		else
			$query_data = array_merge( $query_data, array_values( $args['post_id'] ), array_values( $args['post_type'] ) );
		
		// set where clause
		$where = apply_filters( 'pvc_get_views_period_where', [], $args );

		// check fields
		if ( ! in_array( $args['fields'], [ 'views', 'date=>views' ], true ) )
			$args['fields'] = $defaults['fields'];

		$query_chunks = [];
		$views_query = '';

		// views query after/before parameters work only when fields == views
		if ( $args['fields'] === 'views' ) {
			// check views query inclusive
			if ( ! isset( $args['views_query']['inclusive'] ) )
				$args['views_query']['inclusive'] = $defaults['views_query']['inclusive'];
			else
				$args['views_query']['inclusive'] = (bool) $args['views_query']['inclusive'];

			// check after and before dates
			foreach ( [ 'after' => '>', 'before' => '<' ] as $date => $type ) {
				$year_ = null;
				$month_ = null;
				$week_ = null;
				$day_ = null;

				// check views query date
				if ( ! empty( $args['views_query'][$date] ) ) {
					// is it a date array?
					if ( is_array( $args['views_query'][$date] ) ) {
						// check views query $date date year
						if ( ! empty( $args['views_query'][$date]['year'] ) )
							$year_ = str_pad( (int) $args['views_query'][$date]['year'], 4, 0, STR_PAD_LEFT );

						// check views query date month
						if ( ! empty( $args['views_query'][$date]['month'] ) )
							$month_ = str_pad( (int) $args['views_query'][$date]['month'], 2, 0, STR_PAD_LEFT );

						// check views query date week
						if ( ! empty( $args['views_query'][$date]['week'] ) )
							$week_ = str_pad( (int) $args['views_query'][$date]['week'], 2, 0, STR_PAD_LEFT );

						// check views query date day
						if ( ! empty( $args['views_query'][$date]['day'] ) )
							$day_ = str_pad( (int) $args['views_query'][$date]['day'], 2, 0, STR_PAD_LEFT );
					// is it a date string?
					} elseif ( is_string( $args['views_query'][$date] ) ) {
						$time_ = strtotime( $args['views_query'][$date] );

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
							'type'	=> $type . ( $args['views_query']['inclusive'] ? '=' : '' )
						];
					}
				}
			}

			if ( ! empty( $query_chunks ) ) {
				$valid_dates = true;

				// after and before?
				if ( count( $query_chunks ) === 2 ) {
					// before and after dates should be the same
					foreach ( [ 'year', 'month', 'day', 'week' ] as $date_type ) {
						if ( ! ( ( $query_chunks[0][$date_type] !== null && $query_chunks[1][$date_type] !== null ) || ( $query_chunks[0][$date_type] === null && $query_chunks[1][$date_type] === null ) ) )
							$valid_dates = false;
					}
				}

				if ( $valid_dates ) {
					foreach ( $query_chunks as $chunk ) {
						// year
						if ( isset( $chunk['year'] ) ) {
							// year, week
							if ( isset( $chunk['week'] ) ) {
								$where['type'] = 'pvc.type = 1';
								$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) " . $chunk['type'] . " " . (int) ( $chunk['year'] . $chunk['week'] );
							}
							// year, month
							elseif ( isset( $chunk['month'] ) ) {
								// year, month, day
								if ( isset( $chunk['day'] ) ) {
									$where['type'] = 'pvc.type = 0';
									$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) " . $chunk['type'] . " " . (int) ( $chunk['year'] . $chunk['month'] . $chunk['day'] );
								}
								// year, month
								else {
									$where['type'] = 'pvc.type = 2';
									$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) " . $chunk['type'] . " " . (int) ( $chunk['year'] . $chunk['month'] );
								}
							// year
							} else {
								$where['type'] = 'pvc.type = 3';
								$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) " . $chunk['type'] . " " . (int) ( $chunk['year'] );
							}
						// month
						} elseif ( isset( $chunk['month'] ) ) {
							// month, day
							if ( isset( $chunk['day'] ) ) {
								$where['type'] = 'pvc.type = 0';
								$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( RIGHT( pvc.period, 4 ) AS SIGNED ) " . $chunk['type'] . " " . (int) ( $chunk['month'] . $chunk['day'] );
							// month
							} else {
								$where['type'] = 'pvc.type = 2';
								$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( RIGHT( pvc.period, 2 ) AS SIGNED ) " . $chunk['type'] . " " . (int) ( $chunk['month'] );
							}
						// week
						} elseif ( isset( $chunk['week'] ) ) {
								$where['type'] = 'pvc.type = 1';
								$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( RIGHT( pvc.period, 2 ) AS SIGNED ) " . $chunk['type'] . " " . (int) ( $chunk['week'] );
						}
						// day
						elseif ( isset( $chunk['day'] ) ) {
							$where['type'] = 'pvc.type = 0';
							$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( RIGHT( pvc.period, 2 ) AS SIGNED ) " . $chunk['type'] . " " . (int) ( $chunk['day'] );
						}
					}
				}
			}
		}

		$special_views_query = ( $views_query !== '' );

		if ( $args['fields'] === 'date=>views' || $views_query === '' ) {
			// check views query year
			if ( ! empty( $args['views_query']['year'] ) )
				$year = str_pad( (int) $args['views_query']['year'], 4, 0, STR_PAD_LEFT );

			// check views query month
			if ( ! empty( $args['views_query']['month'] ) )
				$month = str_pad( (int) $args['views_query']['month'], 2, 0, STR_PAD_LEFT );

			// check views query week
			if ( ! empty( $args['views_query']['week'] ) )
				$week = str_pad( (int) $args['views_query']['week'], 2, 0, STR_PAD_LEFT );

			// check views query day
			if ( ! empty( $args['views_query']['day'] ) )
				$day = str_pad( (int) $args['views_query']['day'], 2, 0, STR_PAD_LEFT );

			// year
			if ( isset( $year ) ) {
				// year, week
				if ( isset( $week ) ) {
					if ( $args['fields'] === 'date=>views' ) {
						// create date based on week number
						$date = new DateTime( $year . 'W' . $week );

						// get monday
						$monday = $date->format( 'd' );

						// get month of monday
						$monday_month = $date->format( 'm' );

						// prepare range
						for( $i = 1; $i <= 6; $i++ ) {
							$range[(string) ( $date->format( 'Y' ) . $date->format( 'm' ) . $date->format( 'd' ) )] = 0;

							$date->modify( '+1days' );
						}

						$range[(string) ( $date->format( 'Y' ) . $date->format( 'm' ) . $date->format( 'd' ) )] = 0;

						// get month of sunday
						$sunday_month = $date->format( 'm' );

						$where['type'] = 'pvc.type = 0';
						$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) >= " . (int) ( $year . $monday_month . $monday ) . " AND CAST( pvc.period AS SIGNED ) <= " . (int) ( $date->format( 'Y' ) . $sunday_month . $date->format( 'd' ) );
					} else {
						$where['type'] = 'pvc.type = 1';
						$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) = " . (int) ( $year . $week );
					}
				// year, month
				} elseif ( isset( $month ) ) {
					// year, month, day
					if ( isset( $day ) ) {
						if ( $args['fields'] === 'date=>views' )
							// prepare range
							$range[(string) ( $year . $month . $day )] = 0;

						$where['type'] = 'pvc.type = 0';
						$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) = " . (int) ( $year . $month . $day );
					// year, month
					} else {
						if ( $args['fields'] === 'date=>views' ) {
							// create date
							$date = new DateTime( $year . '-' . $month . '-01' );

							// get last day
							$last = $date->format( 't' );

							// prepare range
							for( $i = 1; $i <= $last; $i++ ) {
								$range[(string) ( $year . $month . str_pad( $i, 2, 0, STR_PAD_LEFT ) )] = 0;
							}

							$where['type'] = 'pvc.type = 0';
							$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) >= " . (int) ( $year . $month ) . "01 AND CAST( pvc.period AS SIGNED ) <= " . (int) ( $year . $month . $last );
						} else {
							$where['type'] = 'pvc.type = 2';
							$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) = " . (int) ( $year . $month );
						}
					}
				// year
				} else {
					if ( $args['fields'] === 'date=>views' ) {
						// prepare range
						for( $i = 1; $i <= 12; $i++ ) {
							$range[(string) ( $year . str_pad( $i, 2, 0, STR_PAD_LEFT ) )] = 0;
						}

						// create date
						$date = new DateTime( $year . '-12-01' );

						$where['type'] = 'pvc.type = 2';
						$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) >= " . (int) ( $year ) . "01 AND CAST( pvc.period AS SIGNED ) <= " . (int) ( $year ) . "12";
					} else {
						$where['type'] = 'pvc.type = 3';
						$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( pvc.period AS SIGNED ) = " . (int) ( $year );
					}
				}
			// month
			} elseif ( isset( $month ) ) {
				// month, day
				if ( isset( $day ) ) {
					$where['type'] = 'pvc.type = 0';
					$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( RIGHT( pvc.period, 4 ) AS SIGNED ) = " . (int) ( $month . $day );
				// month
				} else {
					$where['type'] = 'pvc.type = 2';
					$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( RIGHT( pvc.period, 2 ) AS SIGNED ) = " . (int) ( $month );
				}
			// week
			} elseif ( isset( $week ) ) {
				$where['type'] = 'pvc.type = 1';
				$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( RIGHT( pvc.period, 2 ) AS SIGNED ) = " . (int) ( $week );
			// day
			} elseif ( isset( $day ) ) {
				$where['type'] = 'pvc.type = 0';
				$views_query .= ' AND ' . implode( ' AND ', $where ) . " AND CAST( RIGHT( pvc.period, 2 ) AS SIGNED ) = " . (int) ( $day );
			}
		}

		// update where clause
		$where['type'] = [ 'pvc.type = 4' ];

		$query = $wpdb->prepare(
			"SELECT " . ( $args['fields'] === 'date=>views' ? 'pvc.period, ' : '' ) . "SUM( COALESCE( pvc.count, 0 ) ) AS post_views
			FROM " . $wpdb->prefix . "posts wpp
			LEFT JOIN " . $wpdb->prefix . "post_views pvc ON pvc.id = wpp.ID AND 1 = %d" . ( $views_query !== '' ? ' ' . $views_query : ' AND ' . implode( ' AND ', $where ) ) . ( ! empty( $args['post_id'] ) ? ' AND pvc.id IN (' . implode( ',', array_fill( 0, $post_ids_count, '%d' ) ) . ')' : '' ) . "
			" . ( ! empty( $args['post_type'] ) ? 'WHERE wpp.post_type IN (' . implode( ',', array_fill( 0, $post_types_count, '%s' ) ) . ')' : '' ) . "
			" . ( $views_query !== '' && $special_views_query === false ? 'GROUP BY pvc.period HAVING post_views > 0' : '' ),
			$query_data
		);

		$query = apply_filters( 'pvc_get_views_query_sql', $query, $args, $views_query );

		// get cached data
		$post_views = wp_cache_get( md5( $query ), 'pvc-get_views' );

		// cached data not found?
		if ( $post_views === false ) {
			if ( $args['fields'] === 'date=>views' && ! empty( $range ) ) {
				$results = $wpdb->get_results( $query );

				if ( ! empty( $results ) ) {
					foreach ( $results as $row ) {
						$range[$row->period] = (int) $row->post_views;
					}
				}

				$post_views = $range;
			} else
				$post_views = (int) $wpdb->get_var( $query );

			// set the cache expiration, 5 minutes by default
			$expire = absint( apply_filters( 'pvc_object_cache_expire', 300 ) );

			wp_cache_add( md5( $query ), $post_views, 'pvc-get_views', $expire );
		}

		return apply_filters( 'pvc_get_views', $post_views );
	}
}

/**
 * Display post views for a given post.
 *
 * @param int $post_id
 * @param bool $display
 *
 * @return string|void
 */
if ( ! function_exists( 'pvc_post_views' ) ) {
	function pvc_post_views( $post_id = 0, $display = true, $period = '' ) {
		// get all data
		$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );

		// get display options
		$options = Post_Views_Counter()->options['display'];

		// get post views
		$views = pvc_get_post_views( $post_id, $period !== '' ? $period : $options['display_period'] );

		// use number format?
		$views = $options['use_format'] ? number_format_i18n( $views ) : $views;

		// container class
		$class = apply_filters( 'pvc_post_views_class', 'post-views content-post post-' . $post_id . ' entry-meta', $post_id );

		// dynamic loading?
		$class .= $options['dynamic_loading'] === true ? ' load-dynamic' : ' load-static';

		// prepare display
		$label = apply_filters( 'pvc_post_views_label', ( function_exists( 'icl_t' ) ? icl_t( 'Post Views Counter', 'Post Views Label', $options['label'] ) : $options['label'] ), $post_id );

		// add dashicons class if needed
		$icon_class = strpos( $options['icon_class'], 'dashicons' ) === false ? $options['icon_class'] : 'dashicons ' . $options['icon_class'];

		// prepare icon output
		$icon = apply_filters( 'pvc_post_views_icon', '<span class="post-views-icon ' . esc_attr( $icon_class ) . '"></span> ', $post_id );

		// final views
		$views = apply_filters( 'pvc_post_views_number_format', $views, $post_id );

		$html = apply_filters(
			'pvc_post_views_html',
			'<div class="' . esc_attr( $class ) . '">
				' . ( $options['display_style']['icon'] ? $icon : '' )
				. ( $options['display_style']['text'] ? '<span class="post-views-label">' . esc_html( $label ) . '</span> ' : '' )
				. '<span class="post-views-count">' . $views . '</span>
			</div>',
			$post_id,
			$views,
			$label,
			$icon
		);

		if ( $display )
			echo $html;
		else
			return $html;
	}
}

/**
 * Get most viewed posts.
 *
 * @param array $args
 *
 * @return array
 */
if ( ! function_exists( 'pvc_get_most_viewed_posts' ) ) {
	function pvc_get_most_viewed_posts( $args = [] ) {
		$args = array_merge(
			[
				'posts_per_page'	=> 10,
				'order'				=> 'desc',
				'post_type'			=> [ 'post' ],
				'post_status'		=> [ 'publish' ],
				'fields'			=> 'all',
				'period'			=> 'total'
			],
			$args
		);

		if ( ( is_array( $args['post_type'] ) && in_array( 'attachment', $args['post_type'], true ) ) || ( is_string( $args['post_type'] ) && $args['post_type'] === 'attachment' ) )
			$args['post_status'][] = 'inherit';

		$args = apply_filters( 'pvc_get_most_viewed_posts_args', $args );

		// force to use filters
		$args['suppress_filters'] = false;

		// force to use post views as order
		$args['orderby'] = 'post_views';

		return apply_filters( 'pvc_get_most_viewed_posts', get_posts( $args ), $args );
	}
}

/**
 * Display a list of most viewed posts.
 *
 * @param array $args
 * @param bool $display
 *
 * @return void|string
 */
if ( ! function_exists( 'pvc_most_viewed_posts' ) ) {
	function pvc_most_viewed_posts( $args = [], $display = true ) {
		$defaults = [
			'number_of_posts'		=> 5,
			'post_type'				=> [ 'post' ],
			'order'					=> 'desc',
			'thumbnail_size'		=> 'thumbnail',
			'list_type'				=> 'unordered',
			'show_post_views'		=> true,
			'show_post_thumbnail'	=> false,
			'show_post_author'		=> false,
			'show_post_excerpt'		=> false,
			'no_posts_message'		=> __( 'No most viewed posts found.', 'post-views-counter' ),
			'item_before'			=> '',
			'item_after'			=> '',
			'period'				=> 'total'
		];

		$args = apply_filters( 'pvc_most_viewed_posts_args', wp_parse_args( $args, $defaults ) );

		// get periods
		$periods = apply_filters( 'pvc_display_period_options', [ 'total' => __( 'Total Views', 'post-views-counter' ) ] );

		// sanitize arguments
		$args['show_post_views'] = (bool) $args['show_post_views'];
		$args['show_post_thumbnail'] = (bool) $args['show_post_thumbnail'];
		$args['show_post_author'] = (bool) $args['show_post_author'];
		$args['show_post_excerpt'] = (bool) $args['show_post_excerpt'];
		$args['period'] = isset( $args['period'] ) && array_key_exists( $args['period'], $periods ) ? $args['period'] : $defaults['period'];
		$args['post_type'] = isset( $args['post_type'] ) ? $args['post_type'] : $defaults['post_type'];

		// no post types?
		if ( empty( $args['post_type'] ) )
			$html = $args['no_posts_message'];
		else {
			// get posts
			$posts = pvc_get_most_viewed_posts( [
				'posts_per_page'	=> isset( $args['number_of_posts'] ) ? (int) $args['number_of_posts'] : $defaults['number_of_posts'],
				'order'				=> isset( $args['order'] ) ? $args['order'] : $defaults['order'],
				'post_type'			=> $args['post_type'],
				'period'			=> $args['period']
			] );

			if ( ! empty( $posts ) ) {
				$html = ( $args['list_type'] === 'unordered' ? '<ul>' : '<ol>' );

				foreach ( $posts as $post ) {
					setup_postdata( $post );

					$html .= '<li>';
					$html .= apply_filters( 'pvc_most_viewed_posts_item_before', $args['item_before'], $post );

					if ( $args['show_post_thumbnail'] && has_post_thumbnail( $post->ID ) ) {
						$html .= '<span class="post-thumbnail">' . get_the_post_thumbnail( $post->ID, $args['thumbnail_size'] ) . '</span>';
					}

					$html .= '<a class="post-title" href="' . get_permalink( $post->ID ) . '">' . get_the_title( $post->ID ) . '</a>' . ( $args['show_post_author'] ? ' <span class="author">(' . get_the_author_meta( 'display_name', $post->post_author ) . ')</span> ' : '' ) . ( $args['show_post_views'] ? ' <span class="count">(' . number_format_i18n( (int) ( property_exists( $post, 'post_views' ) ? $post->post_views : pvc_get_post_views( $post->ID, $args['period'] ) ) ) . ')</span>' : '' );

					if ( $args['show_post_excerpt'] ) {
						$excerpt = '';

						if ( empty( $post->post_excerpt ) )
							$text = $post->post_content;
						else
							$text = $post->post_excerpt;

						if ( ! empty( $text ) )
							$excerpt = wp_trim_words( str_replace( ']]>', ']]&gt;', strip_shortcodes( $text ) ), apply_filters( 'excerpt_length', 55 ), apply_filters( 'excerpt_more', ' ' . '[&hellip;]' ) );

						if ( ! empty( $excerpt ) )
							$html .= '<div class="post-excerpt">' . esc_html( $excerpt ) . '</div>';
					}

					$html .= apply_filters( 'pvc_most_viewed_posts_item_after', $args['item_after'], $post );
					$html .= '</li>';
				}

				wp_reset_postdata();

				$html .= ( $args['list_type'] === 'unordered' ? '</ul>' : '</ol>' );
			} else
				$html = $args['no_posts_message'];
		}

		$html = apply_filters( 'pvc_most_viewed_posts_html', $html, $args );

		if ( $display )
			echo $html;
		else
			return $html;
	}
}

/**
 * Update total number of post views for a post.
 *
 * @global object $wpdb
 *
 * @param int $post_id
 * @param int $post_views
 *
 * @return bool|int
 */
function pvc_update_post_views( $post_id = 0, $post_views = 0 ) {
	global $wpdb;

	// cast post ID
	$post_id = (int) $post_id;

	// get post
	$post = get_post( $post_id );

	// check if post exists
	if ( empty( $post ) )
		return false;

	// cast number of views
	$post_views = (int) $post_views;
	$post_views = $post_views < 0 ? 0 : $post_views;

	// change post views?
	$post_views = apply_filters( 'pvc_update_post_views_count', $post_views, $post_id );

	// insert or update database post views count
	$wpdb->query( $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count) VALUES (%d, %d, %s, %d) ON DUPLICATE KEY UPDATE count = %d", $post_id, 4, 'total', $post_views, $post_views ) );

	// query fails only if it returns false
	return apply_filters( 'pvc_update_post_views', $post_id );
}

/**
 * View post manually function.
 *
 * By default this function has limitations. It works properly only between
 * wp_loaded (minimum priority 10) and wp_head (maximum priority 6) actions and
 * it can handle only one function execution per site request.
 *
 * To bypass these limitations there is a $bypass_content argument. It requires
 * JavaScript or REST API as counter mode but it extends the ability to use
 * pvc_view_post up to wp_print_footer_scripts (maximum priority 10) action. It
 * also bypass one function execution limitation to allow multiple function
 * calls during one site request. This also includes the correct saving of
 * cookies.
 *
 * @since 1.2.0
 *
 * @param int $post_id
 * @param bool $bypass_content
 *
 * @return bool
 */
function pvc_view_post( $post_id = 0, $bypass_content = false ) {
	// no post id?
	if ( empty( $post_id ) ) {
		// get current id
		$post_id = get_the_ID();
	} else {
		// cast post id
		$post_id = (int) $post_id;
	}

	// get post
	$post = get_post( $post_id );

	// invalid post?
	if ( ! is_a( $post, 'WP_Post' ) )
		return false;

	// get main instance
	$pvc = Post_Views_Counter();

	if ( $bypass_content )
		$pvc->counter->add_to_queue( $post_id );
	else
		$pvc->counter->check_post( $post_id );

	return true;
}

/**
 * Convert string to date.
 *
 * @param string $period
 * @return DateTiem object
 */
if ( ! function_exists( 'pvc_period2date' ) ) {
	function pvc_period2date( $period ) {
		$datetime = false;
		
		// check requested period by string length
		$length = is_string( $period ) ? strlen( $period ) : 0;

		if ( $length ) {
			switch ( $length ) {
				// day
				case 8:
					$datetime = date_create_from_format( 'Ymd' , $period );
					break;

				// week
				case 7:
					// get year and week from string
					$period_year = (int) substr( $period, 0, -3 );
					$period_week = (int) substr( $period, 4, -1 );

					$datetime = new DateTime();
					$datetime->setISODate( $period_year, $period_week );
					break;

				// month
				case 6:
					$datetime = date_create_from_format( 'Ym' , $period );
					break;
				// year
				case 4:
					$datetime = date_create_from_format( 'Y' , $period );
					break;

				default:
					$datetime = new DateTime();
			}
		}
		
		return apply_filters( 'pvc_period2date', $datetime, $period );
	}
}

/**
* Convert period to timestamp.
*
* @param string $period
* @return int
*/
if ( ! function_exists( 'pvc_period2timestamp' ) ) {
	function pvc_period2timestamp( $period ) {
		$period = preg_replace( '/[^a-z0-9_|]/', '', $period );
		
		// default time
		$timestamp = current_time( 'timestamp', false );
		
		// whitelisted period?
		if ( in_array( $period, [ 'this_week', 'this_year', 'this_month' ], true ) ) {
			$timestamp = current_time( 'timestamp', false );
		// backward compatibility
		} else if ( preg_match( '/^([0-9]{2}\|[0-9]{4})$/', $period ) === 1 ) {
			// month|year
			$date = explode( '|', $period, 2 );

			// get timestamp
			$timestamp = strtotime( (string) $date[1] . '-' . (string) $date[0] . '-13' );
		} else {
			// convert string to DateTime()
			$d = pvc_period2date( $period );
			
			if ( $d ) {
				$timestamp = $d->getTimestamp();
			}
		}

		return $timestamp;
	}
}
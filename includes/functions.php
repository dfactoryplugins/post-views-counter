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
 * @global $wpdb
 * @param int|array $post_id
 * @return int
 */
if ( ! function_exists( 'pvc_get_post_views' ) ) {

	function pvc_get_post_views( $post_id = 0 ) {
		if ( empty( $post_id ) )
			$post_id = get_the_ID();

		if ( is_array( $post_id ) )
			$post_id = implode( ',', array_map( 'intval', $post_id ) );
		else
			$post_id = (int) $post_id;

		global $wpdb;

		$query = "SELECT SUM(count) AS views
		FROM " . $wpdb->prefix . "post_views
		WHERE id IN (" . $post_id . ") AND type = 4";

		// get cached data
		$post_views = wp_cache_get( md5( $query ), 'pvc-get_post_views' );

		// cached data not found?
		if ( $post_views === false ) {
			$post_views = (int) $wpdb->get_var( $query );
			
			// set the cache expiration, 5 minutes by default
			$expire = absint( apply_filters( 'pvc_object_cache_expire', 5 * 60 ) );

			wp_cache_add( md5( $query ), $post_views, 'pvc-get_post_views', $expire );
		}

		return apply_filters( 'pvc_get_post_views', $post_views, $post_id );
	}

}

/**
 * Get views query.
 * 
 * @global $wpdb
 * @param array $args
 * @return int|array
 */
if ( ! function_exists( 'pvc_get_views' ) ) {

	function pvc_get_views( $args = array() ) {
		$range = array();
		$defaults = array(
			'fields'		=> 'views',
			'post_id'		=> '',
			'post_type'		=> '',
			'views_query'	=> array(
				'year'		=> '',
				'month'		=> '',
				'week'		=> '',
				'day'		=> '',
				'after'		=> '',	// string or array
				'before'	=> '',	// string or array
				'inclusive'	=> true
			)
		);

		// merge default options with new arguments
		$args = array_merge( $defaults, $args );

		// check views query
		if ( ! is_array( $args['views_query'] ) )
			$args['views_query'] = $defaults['views_query'];

		// merge views query too
		$args['views_query'] = array_merge( $defaults['views_query'], $args['views_query'] );

		$args = apply_filters( 'pvc_get_views_args', $args );

		// check post types
		if ( is_array( $args['post_type'] ) && ! empty( $args['post_type'] ) ) {
			$post_types = array();

			foreach( $args['post_type'] as $post_type ) {
				$post_types[] = "'" . $post_type . "'";
			}

			$args['post_type'] = implode( ', ', $post_types );
		} elseif ( ! is_string( $args['post_type'] ) )
			$args['post_type'] = $defaults['post_type'];
		else
			$args['post_type'] = "'" . $args['post_type'] . "'";

		// check post ids
		if ( is_array( $args['post_id'] ) && ! empty( $args['post_id'] ) )
			$args['post_id'] = implode( ', ', array_unique( array_map( 'intval', $args['post_id'] ) ) );
		else
			$args['post_id'] = (int) $args['post_id'];

		// check fields
		if ( ! in_array( $args['fields'], array( 'views', 'date=>views' ), true ) )
			$args['fields'] = $defaults['fields'];

		$query_chunks = array();
		$views_query = '';

		// views query after/before parameters work only when fields == views
		if ( $args['fields'] === 'views' ) {
			// check views query inclusive
			if ( ! isset( $args['views_query']['inclusive'] ) ) {
				$args['views_query']['inclusive'] = $defaults['views_query']['inclusive'];
			} else
				$args['views_query']['inclusive'] = (bool) $args['views_query']['inclusive'];

			// check after and before dates
			foreach ( array( 'after' => '>', 'before' => '<' ) as $date => $type ) {
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
						$query_chunks[] = array(
							'year'	=> $year_,
							'month'	=> $month_,
							'day'	=> $day_,
							'week'	=> $week_,
							'type'	=> $type . ( $args['views_query']['inclusive'] ? '=' : '' )
						);
					}
				}
			}

			if ( ! empty( $query_chunks ) ) {
				$valid_dates = true;

				// after and before?
				if ( count( $query_chunks ) === 2 ) {
					// before and after dates should be the same
					foreach ( array( 'year', 'month', 'day', 'week' ) as $date_type ) {
						if ( ! ( ( $query_chunks[0][$date_type] !== null && $query_chunks[1][$date_type] !== null ) || ( $query_chunks[0][$date_type] === null && $query_chunks[1][$date_type] === null ) ) )
							$valid_dates = false;
					}
				}

				if ( $valid_dates ) {
					foreach ( $query_chunks as $chunk ) {
						// year
						if ( isset( $chunk['year'] ) ) {
							// year, week
							if ( isset( $chunk['week'] ) )
								$views_query .= " AND pvc.type = 1 AND pvc.period " . $chunk['type'] . " '" . $chunk['year'] . $chunk['week'] . "'";
							// year, month
							elseif ( isset( $chunk['month'] ) ) {
								// year, month, day
								if ( isset( $chunk['day'] ) )
									$views_query .= " AND pvc.type = 0 AND pvc.period " . $chunk['type'] . " '" . $chunk['year'] . $chunk['month'] . $chunk['day'] . "'";
								// year, month
								else
									$views_query .= " AND pvc.type = 2 AND pvc.period " . $chunk['type'] . " '" . $chunk['year'] . $chunk['month'] . "'";
							// year
							} else
								$views_query .= " AND pvc.type = 3 AND pvc.period " . $chunk['type'] . " '" . $chunk['year'] . "'";
						// month
						} elseif ( isset( $chunk['month'] ) ) {
							// month, day
							if ( isset( $chunk['day'] ) ) {
								$views_query .= " AND pvc.type = 0 AND RIGHT( pvc.period, 4 ) " . $chunk['type'] . " '" . $chunk['month'] . $chunk['day'] . "'";
							// month
							} else
								$views_query .= " AND pvc.type = 2 AND RIGHT( pvc.period, 2 ) " . $chunk['type'] . " '" . $chunk['month'] . "'";
						// week
						} elseif ( isset( $chunk['week'] ) )
							$views_query .= " AND pvc.type = 1 AND RIGHT( pvc.period, 2 ) " . $chunk['type'] . " '" . $chunk['week'] . "'";
						// day
						elseif ( isset( $chunk['day'] ) )
							$views_query .= " AND pvc.type = 0 AND RIGHT( pvc.period, 2 ) " . $chunk['type'] . " '" . $chunk['day'] . "'";
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

						$views_query = " AND pvc.type = 0 AND pvc.period >= '" . $year . $monday_month . $monday . "' AND pvc.period <= '" . $date->format( 'Y' ) . $sunday_month . $date->format( 'd' ) . "'";
					} else
						$views_query = " AND pvc.type = 1 AND pvc.period = '" . $year . $week . "'";
				// year, month
				} elseif ( isset( $month ) ) {
					// year, month, day
					if ( isset( $day ) ) {
						if ( $args['fields'] === 'date=>views' )
							// prepare range
							$range[(string) ( $year . $month . $day )] = 0;

						$views_query = " AND pvc.type = 0 AND pvc.period = '" . $year . $month . $day . "'";
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

							$views_query = " AND pvc.type = 0 AND pvc.period >= '" . $year . $month . "01' AND pvc.period <= '" . $year . $month . $last . "'";
						} else
							$views_query = " AND pvc.type = 2 AND pvc.period = '" . $year . $month . "'";
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

						$views_query = " AND pvc.type = 2 AND pvc.period >= '" . $year . "01' AND pvc.period <= '" . $year . "12'";
					} else
						$views_query = " AND pvc.type = 3 AND pvc.period = '" . $year . "'";
				}
			// month
			} elseif ( isset( $month ) ) {
				// month, day
				if ( isset( $day ) ) {
					$views_query = " AND pvc.type = 0 AND RIGHT( pvc.period, 4 ) = '" . $month . $day . "'";
				// month
				} else {
					$views_query = " AND pvc.type = 2 AND RIGHT( pvc.period, 2 ) = '" . $month . "'";
				}
			// week
			} elseif ( isset( $week ) ) {
				$views_query = " AND pvc.type = 1 AND RIGHT( pvc.period, 2 ) = '" . $week . "'";
			// day
			} elseif ( isset( $day ) ) {
				$views_query = " AND pvc.type = 0 AND RIGHT( pvc.period, 2 ) = '" . $day . "'";
			}
		}

		global $wpdb;

		$query = "SELECT " . ( $args['fields'] === 'date=>views' ? 'pvc.period, ' : '' ) . "SUM( IFNULL( pvc.count, 0 ) ) AS post_views
		FROM " . $wpdb->prefix . "posts wpp
		LEFT JOIN " . $wpdb->prefix . "post_views pvc ON pvc.id = wpp.ID" . ( $views_query !== '' ? ' ' . $views_query : ' AND pvc.type = 4' ) . ( ! empty( $args['post_id'] ) ? ' AND pvc.id IN (' . $args['post_id'] . ')' : '' ) . "
		" . ( $args['post_type'] !== '' ? "WHERE wpp.post_type IN (" . $args['post_type'] . ")" : '' ) . "
		" . ( $views_query !== '' && $special_views_query === false ? 'GROUP BY pvc.period' : '' ) . "
		HAVING post_views > 0";

		// get cached data
		$post_views = wp_cache_get( md5( $query ), 'pvc-get_views' );

		// cached data not found?
		if ( $post_views === false ) {
			if ( $args['fields'] === 'date=>views' && ! empty( $range ) ) {
				$results = $wpdb->get_results( $query );

				if ( ! empty( $results ) ) {
					foreach( $results as $row ) {
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
 * @param  int|array $post_id
 * @param bool $display
 * @return mixed
 */
if ( ! function_exists( 'pvc_post_views' ) ) {

	function pvc_post_views( $post_id = 0, $echo = true ) {
		// get all data
		$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );
		$options = Post_Views_Counter()->options['display'];
		$views = pvc_get_post_views( $post_id );

		// prepare display
		$label = apply_filters( 'pvc_post_views_label', ( function_exists( 'icl_t' ) ? icl_t( 'Post Views Counter', 'Post Views Label', $options['label'] ) : $options['label'] ), $post_id );

		// get icon class
		$icon_class = ( $options['icon_class'] !== '' ? esc_attr( $options['icon_class'] ) : '' );

		// add dashicons class if needed
		$icon_class = strpos( $icon_class, 'dashicons' ) === 0 ? 'dashicons ' . $icon_class : $icon_class;

		// prepare icon output
		$icon = apply_filters( 'pvc_post_views_icon', '<span class="post-views-icon ' . $icon_class . '"></span>', $post_id );

		$html = apply_filters(
			'pvc_post_views_html',
			'<div class="post-views post-' . $post_id . ' entry-meta">
				' . ( $options['display_style']['icon'] && $icon_class !== '' ? $icon : '' ) . '
				' . ( $options['display_style']['text'] && $label !== '' ? '<span class="post-views-label">' . $label . ' </span>' : '' ) . '
				<span class="post-views-count">' . number_format_i18n( $views ) . '</span>
			</div>',
			$post_id,
			$views,
			$label,
			$icon
		);

		if ( $echo )
			echo $html;
		else
			return $html;
	}

}

/**
 * Get most viewed posts.
 * 
 * @param array $args
 * @return array
 */
if ( ! function_exists( 'pvc_get_most_viewed_posts' ) ) {

	function pvc_get_most_viewed_posts( $args = array() ) {
		$args = array_merge(
			array(
				'posts_per_page' => 10,
				'order'			 => 'desc',
				'post_type'		 => 'post'
			), $args
		);

		$args = apply_filters( 'pvc_get_most_viewed_posts_args', $args );

		// force to use filters
		$args['suppress_filters'] = false;

		// force to use post views as order
		$args['orderby'] = 'post_views';

		// force to get all fields
		$args['fields'] = '';

		return apply_filters( 'pvc_get_most_viewed_posts', get_posts( $args ), $args );
	}

}

/**
 * Display a list of most viewed posts.
 * 
 * @param array $post_id
 * @param bool $display
 * @return mixed
 */
if ( ! function_exists( 'pvc_most_viewed_posts' ) ) {

	function pvc_most_viewed_posts( $args = array(), $display = true ) {
		$defaults = array(
			'number_of_posts'		=> 5,
			'post_type'				=> array( 'post' ),
			'order'					=> 'desc',
			'thumbnail_size'		=> 'thumbnail',
			'list_type'				=> 'unordered',
			'show_post_views'		=> true,
			'show_post_thumbnail'	=> false,
			'show_post_author'		=> false,
			'show_post_excerpt'		=> false,
			'no_posts_message'		=> __( 'No Posts', 'post-views-counter' ),
			'item_before'			=> '',
			'item_after'			=> ''
		);

		$args = apply_filters( 'pvc_most_viewed_posts_args', wp_parse_args( $args, $defaults ) );

		$args['show_post_views'] = (bool) $args['show_post_views'];
		$args['show_post_thumbnail'] = (bool) $args['show_post_thumbnail'];
		$args['show_post_author'] = (bool) $args['show_post_author'];
		$args['show_post_excerpt'] = (bool) $args['show_post_excerpt'];

		$posts = pvc_get_most_viewed_posts(
			array(
				'posts_per_page' => ( isset( $args['number_of_posts'] ) ? (int) $args['number_of_posts'] : $defaults['number_of_posts'] ),
				'order'			 => ( isset( $args['order'] ) ? $args['order'] : $defaults['order'] ),
				'post_type'		 => ( isset( $args['post_type'] ) ? $args['post_type'] : $defaults['post_type'] )
			)
		);

		if ( ! empty( $posts ) ) {
			$html = ( $args['list_type'] === 'unordered' ? '<ul>' : '<ol>' );

			foreach ( $posts as $post ) {
				setup_postdata( $post );

			$html .= '
			<li>';
				
				$html .= apply_filters( 'pvc_most_viewed_posts_item_before', $args['item_before'], $post );

				if ( $args['show_post_thumbnail'] && has_post_thumbnail( $post->ID ) ) {
					$html .= '
					<span class="post-thumbnail">
					' . get_the_post_thumbnail( $post->ID, $args['thumbnail_size'] ) . '
					</span>';
				}

				$html .= '
					<a class="post-title" href="' . get_permalink( $post->ID ) . '">' . get_the_title( $post->ID ) . '</a>' . ( $args['show_post_author'] ? ' <span class="author">(' . get_the_author_meta( 'display_name', $post->post_author ) . ')</span> ' : '' ) . ( $args['show_post_views'] ? ' <span class="count">(' . number_format_i18n( pvc_get_post_views( $post->ID ) ) . ')</span>' : '' );

				$excerpt = '';

				if ( $args['show_post_excerpt'] ) {
					if ( empty( $post->post_excerpt ) )
						$text = $post->post_content;
					else
						$text = $post->post_excerpt;

					if ( ! empty( $text ) )
						$excerpt = wp_trim_words( str_replace( ']]>', ']]&gt;', strip_shortcodes( $text ) ), apply_filters( 'excerpt_length', 55 ), apply_filters( 'excerpt_more', ' ' . '[&hellip;]' ) );
				}

				if ( ! empty( $excerpt ) )
					$html .= '
				
				<div class="post-excerpt">' . esc_html( $excerpt ) . '</div>';
				
				$html .= apply_filters( 'pvc_most_viewed_posts_item_after', $args['item_after'], $post );

				$html .= '
			</li>';
			}

			wp_reset_postdata();

			$html .= ( $args['list_type'] === 'unordered' ? '</ul>' : '</ol>' );
		} else
			$html = $args['no_posts_message'];

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
 * @global $wpdb
 * @param int $post_id Post ID
 * @param int $post_views Number of post views
 * @return true|string True on success, error string otherwise
 */
function pvc_update_post_views( $post_id = 0, $post_views = 0 ) {
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

	global $wpdb;

	// chnage post views?
	$post_views = apply_filters( 'pvc_update_post_views_count', $post_views, $post_id );

	// insert or update db post views count
	$wpdb->query( $wpdb->prepare( "INSERT INTO " . $wpdb->prefix . "post_views (id, type, period, count) VALUES (%d, %d, %s, %d) ON DUPLICATE KEY UPDATE count = %d", $post_id, 4, 'total', $post_views, $post_views ) );

	// query fails only if it returns false
	return apply_filters( 'pvc_update_post_views', $post_id );
}

/**
 * View post manually function.
 * 
 * @since 1.2.0
 * @param int $post_id
 * @return bool
 */
function pvc_view_post( $post_id = 0 ) {
	$post_id = (int) ( empty( $post_id ) ? get_the_ID() : $post_id );

	if ( ! $post_id )
		return false;

	Post_Views_Counter()->counter->check_post( $post_id );

	return true;
}

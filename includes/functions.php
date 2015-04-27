<?php
/**
 * Post Views Counter pluggable template functions
 *
 * Override any of those functions by copying it to your theme or replace it via plugin
 *
 * @author 	Digital Factory
 * @package Post Views Counter
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Get post views for a post or array of posts.
 * 
 * @param	int|array $post_id
 * @return	int
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

		$post_views = (int) $wpdb->get_var( "
			SELECT SUM(count) AS views
			FROM " . $wpdb->prefix . "post_views
			WHERE id IN (" . $post_id . ") AND type = 4"
		);

		return apply_filters( 'pvc_get_post_views', $post_views, $post_id );
	}

}

/**
 * Display post views for a given post.
 * 
 * @param	int|array $post_id
 * @param	bool $display
 * @return	mixed
 */
if ( ! function_exists( 'pvc_post_views' ) ) {

	function pvc_post_views( $post_id = 0, $display = true ) {
		// get all data
		$post_id = (int) (empty( $post_id ) ? get_the_ID() : $post_id);
		$options = Post_Views_Counter()->get_attribute( 'options', 'display' );
		$views = pvc_get_post_views( $post_id );

		// prepares display
		$label = apply_filters( 'pvc_post_views_label', (function_exists( 'icl_t' ) ? icl_t( 'Post Views Counter', 'Post Views Label', $options['label'] ) : $options['label'] ), $post_id );
		$icon_class = ($options['icon_class'] !== '' ? ' ' . esc_attr( $options['icon_class'] ) : '');
		$icon = apply_filters( 'pvc_post_views_icon', '<span class="post-views-icon dashicons ' . $icon_class . '"></span>', $post_id );

		$html = apply_filters(
			'pvc_post_views_html', '<div class="post-views post-' . $post_id . ' entry-meta">
				' . ($options['display_style']['icon'] && $icon_class !== '' ? $icon : '') . '
				' . ($options['display_style']['text'] ? '<span class="post-views-label">' . $label . ' </span>' : '') . '
				<span class="post-views-count">' . number_format_i18n( $views ) . '</span>
			</div>', $post_id, $views, $label, $icon
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
 * @param	array $args
 * @return	array
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

		// forces to use filters and post views as order
		$args['suppress_filters'] = false;
		$args['orderby'] = 'post_views';

		return get_posts( $args );
	}

}

/**
 * Display a list of most viewed posts.
 * 
 * @param	array $post_id
 * @param	bool $display
 * @return	mixed
 */
if ( ! function_exists( 'pvc_most_viewed_posts' ) ) {

	function pvc_most_viewed_posts( $args = array(), $display = true ) {
		$defaults = array(
			'number_of_posts'		 => 5,
			'post_types'			 => array( 'post' ),
			'order'					 => 'desc',
			'thumbnail_size'		 => 'thumbnail',
			'show_post_views'		 => true,
			'show_post_thumbnail'	 => false,
			'show_post_excerpt'		 => false,
			'no_posts_message'		 => __( 'No Posts', 'post-views-counter' )
		);

		$args = apply_filters( 'pvc_most_viewed_posts_args', wp_parse_args( $args, $defaults ) );

		$args['show_post_views'] = (bool) $args['show_post_views'];
		$args['show_post_thumbnail'] = (bool) $args['show_post_thumbnail'];
		$args['show_post_excerpt'] = (bool) $args['show_post_excerpt'];

		$posts = pvc_get_most_viewed_posts(
			array(
				'posts_per_page' => (isset( $args['number_of_posts'] ) ? (int) $args['number_of_posts'] : $defaults['number_of_posts']),
				'order'			 => (isset( $args['order'] ) ? $args['order'] : $defaults['order']),
				'post_type'		 => (isset( $args['post_types'] ) ? $args['post_types'] : $defaults['post_types'])
			)
		);

		if ( ! empty( $posts ) ) {
			$html = '
			<ul>';

			foreach ( $posts as $post ) {
				setup_postdata( $post );

				$html .= '
				<li>';

				if ( $args['show_post_thumbnail'] && has_post_thumbnail( $post->ID ) ) {
					$html .= '
					<span class="post-thumbnail">
						' . get_the_post_thumbnail( $post->ID, $args['thumbnail_size'] ) . '
					</span>';
				}

				$html .= '
					<a class="post-title" href="' . get_permalink( $post->ID ) . '">' . get_the_title( $post->ID ) . '</a>' . ($args['show_post_views'] ? ' <span class="count">(' . number_format_i18n( pvc_get_post_views( $post->ID ) ) . ')</span>' : '');

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

				$html .= '
				</li>';
			}

			wp_reset_postdata();

			$html .= '
			</ul>';
		} else
			$html = $args['no_posts_message'];

		$html = apply_filters( 'pvc_most_viewed_posts_html', $html, $args );

		if ( $display )
			echo $html;
		else
			return $html;
	}

}

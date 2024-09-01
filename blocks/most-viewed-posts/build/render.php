<?php
// map block attributes
$args = [
	'number_of_posts'		=> max( 1, (int) $attributes['numberOfPosts'] ),
	'post_type'				=> $attributes['postTypes'],
	'order'					=> $attributes['order'],
	'thumbnail_size'		=> $attributes['thumbnailSize'],
	'list_type'				=> $attributes['listType'],
	'show_post_views'		=> $attributes['displayPostViews'],
	'show_post_thumbnail'	=> $attributes['displayPostThumbnail'],
	'show_post_author'		=> $attributes['displayPostAuthor'],
	'show_post_excerpt'		=> $attributes['displayPostExcerpt'],
	'no_posts_message'		=> $attributes['noPostsMessage']
];

echo '
<div ' . get_block_wrapper_attributes() . '>
	<h2 class="widget-title">' . esc_html( $attributes['title'] ) . '</h2>';

pvc_most_viewed_posts( $args = [], true );
	
echo '
</div>';
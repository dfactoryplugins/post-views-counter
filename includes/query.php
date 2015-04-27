<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

new Post_Views_Counter_Query();

class Post_Views_Counter_Query {

	public function __construct() {
		// actions
		add_action( 'pre_get_posts', array( &$this, 'extend_pre_query' ), 9 );

		// filters
		add_filter( 'posts_join', array( &$this, 'posts_join' ), 10, 2 );
		add_filter( 'posts_groupby', array( &$this, 'posts_groupby' ), 10, 2 );
		add_filter( 'posts_orderby', array( &$this, 'posts_orderby' ), 10, 2 );
	}

	/**
	 * Extend query with post_views orderby parameter.
	 */
	public function extend_pre_query( $query ) {
		if ( isset( $query->query_vars['orderby'] ) && $query->query_vars['orderby'] === 'post_views' )
			$query->order_by_post_views = true;
	}

	/**
	 * Modify the db query to use post_views parameter.
	 */
	public function posts_join( $join, $query ) {
		global $wpdb;

		// is it sorted by post views?
		if ( isset( $query->order_by_post_views ) && $query->order_by_post_views )
			$join .= " LEFT JOIN " . $wpdb->prefix . "post_views pvc ON pvc.id = " . $wpdb->prefix . "posts.ID AND pvc.type = 4";

		return $join;
	}

	/**
	 * Group posts using the post ID.
	 */
	public function posts_groupby( $groupby, $query ) {
		global $wpdb;

		// is it sorted by post views?
		if ( isset( $query->order_by_post_views ) && $query->order_by_post_views )
			$groupby = (trim( $groupby ) !== '' ? $groupby . ', ' : '') . $wpdb->prefix . 'posts.ID';

		return $groupby;
	}

	/**
	 * Order posts by post views.
	 */
	public function posts_orderby( $orderby, $query ) {
		global $wpdb;

		// is it sorted by post views?
		if ( isset( $query->order_by_post_views ) && $query->order_by_post_views ) {
			$order = $query->get( 'order' );
			$orderby = 'pvc.count ' . $order . ', ' . $wpdb->prefix . 'posts.ID ' . $order;
		}

		return $orderby;
	}

}

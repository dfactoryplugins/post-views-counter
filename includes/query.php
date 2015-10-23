<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

new Post_Views_Counter_Query();

class Post_Views_Counter_Query {

	public function __construct() {
		// actions
		add_action( 'pre_get_posts', array( $this, 'extend_pre_query' ), 9 );

		// filters
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
		add_filter( 'posts_groupby', array( $this, 'posts_groupby' ), 10, 2 );
		add_filter( 'posts_orderby', array( $this, 'posts_orderby' ), 10, 2 );
		add_filter( 'posts_fields', array( $this, 'posts_fields' ), 10, 2 );
	}

	/**
	 * Extend query with post_views orderby parameter.
	 * 
	 * @param object $query
	 */
	public function extend_pre_query( $query ) {
		if ( isset( $query->query_vars['orderby'] ) && $query->query_vars['orderby'] === 'post_views' )
			$query->order_by_post_views = true;
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
		// is it sorted by post views?
		if ( ( isset( $query->order_by_post_views ) && $query->order_by_post_views ) || apply_filters( 'pvc_extend_post_object', false, $query ) === true ) {
			global $wpdb;

			$join .= " LEFT JOIN " . $wpdb->prefix . "post_views pvc ON pvc.id = " . $wpdb->prefix . "posts.ID AND pvc.type = 4";
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
		// is it sorted by post views?
		if ( ( isset( $query->order_by_post_views ) && $query->order_by_post_views ) || apply_filters( 'pvc_extend_post_object', false, $query ) === true ) {
			global $wpdb;

			$groupby = trim( $groupby );

			if ( strpos( $groupby, $wpdb->prefix . 'posts.ID' ) === false )
				$groupby = ( $groupby !== '' ? $groupby . ', ' : '') . $wpdb->prefix . 'posts.ID';
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
		if ( isset( $query->order_by_post_views ) && $query->order_by_post_views ) {
			global $wpdb;

			$order = $query->get( 'order' );
			$orderby = 'pvc.count ' . $order . ', ' . $wpdb->prefix . 'posts.ID ' . $order;
		}

		return $orderby;
	}

	/**
	 * Return post views in queried post objects.
	 * 
	 * @global object $wpdb
	 * @param string $fields
	 * @param object $query
	 * @return string
	 */
	public function posts_fields( $fields, $query ) {
		// is it sorted by post views?
		if ( ( isset( $query->order_by_post_views ) && $query->order_by_post_views ) || apply_filters( 'pvc_extend_post_object', false, $query ) === true ) {
			global $wpdb;

			$fields = $wpdb->prefix . 'posts.ID, IFNULL( pvc.count, 0 ) AS post_views';
		}

		return $fields;
	}
}
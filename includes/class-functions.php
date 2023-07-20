<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Functions class.
 *
 * @class Post_Views_Counter_Functions
 */
class Post_Views_Counter_Functions {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {}

	/**
	 * Get post types available for counting.
	 *
	 * @return array
	 */
	public function get_post_types() {
		$post_types = [];

		// get public post types
		foreach ( get_post_types( [ 'public' => true ], 'objects', 'and' ) as $key => $post_type ) {
			$post_types[$key] = $post_type->labels->name;
		}

		// remove bbPress replies
		if ( class_exists( 'bbPress' ) && isset( $post_types['reply'] ) )
			unset( $post_types['reply'] );

		// filter post types
		$post_types = apply_filters( 'pvc_available_post_types', $post_types );

		// sort post types alphabetically
		asort( $post_types, SORT_STRING );

		return $post_types;
	}

	/**
	 * Get all user roles.
	 *
	 * @global object $wp_roles
	 *
	 * @return array
	 */
	public function get_user_roles() {
		global $wp_roles;

		$roles = [];

		foreach ( apply_filters( 'editable_roles', $wp_roles->roles ) as $role => $details ) {
			$roles[$role] = translate_user_role( $details['name'] );
		}

		// sort user roles alphabetically
		asort( $roles, SORT_STRING );

		return $roles;
	}

	/**
	 * Get taxonomies available for counting.
	 *
	 * @param bool $mode
	 * @return array
	 */
	public function get_taxonomies( $mode = 'labels' ) {
		// get public taxonomies
		$taxonomies = get_taxonomies(
			[
				'public' => true
			],
			$mode === 'keys' ? 'names' : 'objects',
			'and'
		);

		// only keys
		if ( $mode === 'keys' )
			$_taxonomies = array_keys( $taxonomies );
		// objects
		elseif ( $mode === 'objects' )
			$_taxonomies = $taxonomies;
		// labels
		else {
			$_taxonomies = [];

			// prepare taxonomy labels
			foreach ( $taxonomies as $name => $taxonomy ) {
				$_taxonomies[$name] = $taxonomy->label;
			}
		}

		return $_taxonomies;
	}

	/**
	 * Get number of columns in post_views table.
	 *
	 * @global object $wpdb
	 *
	 * @return int
	 */
	public function get_number_of_columns() {
		global $wpdb;

		// get number of columns
		return (int) $wpdb->get_var( "SELECT COUNT(*) AS result FROM information_schema.columns WHERE table_schema = '" . $wpdb->dbname . "' AND table_name = '" . $wpdb->prefix . "post_views'" );
	}
}

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

		// built in public post types
		foreach ( get_post_types( [ '_builtin' => true, 'public' => true ], 'objects', 'and' ) as $key => $post_type ) {
			$post_types[$key] = $post_type->labels->name;
		}

		// public custom post types
		foreach ( get_post_types( [ '_builtin' => false, 'public' => true ], 'objects', 'and' ) as $key => $post_type ) {
			$post_types[$key] = $post_type->labels->name;
		}

		// remove bbPress replies
		if ( class_exists( 'bbPress' ) && isset( $post_types['reply'] ) )
			unset( $post_types['reply'] );

		// filter post types
		$post_types = apply_filters( 'pvc_available_post_types', $post_types );

		// sort post types alphabetically with their keys
		asort( $post_types, SORT_STRING );

		return $post_types;
	}

	/**
	 * Get all user roles.
	 *
	 * @global object $wp_roles
	 * @return array
	 */
	public function get_user_roles() {
		global $wp_roles;

		$roles = [];

		foreach ( apply_filters( 'editable_roles', $wp_roles->roles ) as $role => $details ) {
			$roles[$role] = translate_user_role( $details['name'] );
		}

		// sort user roles alphabetically with their keys
		asort( $roles, SORT_STRING );

		return $roles;
	}
}
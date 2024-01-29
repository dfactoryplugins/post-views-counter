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
	 * Get color scheme.
	 *
	 * @global array $_wp_admin_css_colors
	 *
	 * @return string
	 */
	public function get_current_scheme_color( $default_color = '' ) {
		// get color scheme global
		global $_wp_admin_css_colors;

		// set default color;
		$color = '#2271b1';

		if ( ! empty( $_wp_admin_css_colors ) ) {
			// get current admin color scheme name
			$current_color_scheme = get_user_option( 'admin_color' );

			if ( empty( $current_color_scheme ) )
				$current_color_scheme = 'fresh';

			$wp_scheme_colors = [
				'coffee'	=> 2,
				'ectoplasm'	=> 2,
				'ocean'		=> 2,
				'sunrise'	=> 2,
				'midnight'	=> 3,
				'blue'		=> 3,
				'modern'	=> 1,
				'light'		=> 1,
				'fresh'		=> 2
			];

			// one of default wp schemes?
			if ( array_key_exists( $current_color_scheme, $wp_scheme_colors ) ) {
				$color_number = $wp_scheme_colors[$current_color_scheme];

				// color exists?
				if ( isset( $_wp_admin_css_colors[$current_color_scheme] ) && property_exists( $_wp_admin_css_colors[$current_color_scheme], 'colors' ) && isset( $_wp_admin_css_colors[$current_color_scheme]->colors[$color_number] ) )
					$color = $_wp_admin_css_colors[$current_color_scheme]->colors[$color_number];
			}
		}

		return $color;
	}

	/**
	 * Convert HEX to RGB color.
	 *
	 * @param string $color
	 * @return bool|array
	 */
	public function hex2rgb( $color ) {
		if ( ! is_string( $color ) )
			return false;

		// with hash?
		if ( $color[0] === '#' )
			$color = substr( $color, 1 );

		if ( sanitize_hex_color_no_hash( $color ) !== $color )
			return false;

		// 6 hex digits?
		if ( strlen( $color ) === 6 )
			list( $r, $g, $b ) = [ $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] ];
		// 3 hex digits?
		elseif ( strlen( $color ) === 3 )
			list( $r, $g, $b ) = [ $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] ];
		else
			return false;

		return [ 'r' => hexdec( $r ), 'g' => hexdec( $g ), 'b' => hexdec( $b ) ];
	}

	/**
	 * Get default color.
	 *
	 * @return array
	 */
	public function get_colors() {
		// get current color scheme
		$color = $this->get_current_scheme_color();

		// convert it to rgb
		$color = $this->hex2rgb( $color );

		// invalid color?
		if ( $color === false ) {
			// set default color
			$color = [ 'r' => 34, 'g' => 113, 'b' => 177 ];
		}

		return $color;
	}
}

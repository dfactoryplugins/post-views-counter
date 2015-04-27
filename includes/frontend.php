<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

new Post_Views_Counter_Frontend();

class Post_Views_Counter_Frontend {

	public function __construct() {
		// actions
		add_action( 'wp_loaded', array( &$this, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'frontend_scripts_styles' ) );

		// filters
		add_filter( 'the_content', array( &$this, 'add_post_views_count' ) );
		add_filter( 'the_excerpt', array( &$this, 'remove_post_views_count' ) );
	}

	/**
	 * Register post-views shortcode function.
	 */
	public function register_shortcode() {
		add_shortcode( 'post-views', array( &$this, 'post_views_shortcode' ) );
	}

	/**
	 * Post views shortcode function.
	 */
	public function post_views_shortcode( $args ) {
		$defaults = array(
			'id' => get_the_ID()
		);

		$args = shortcode_atts( $defaults, $args );

		return pvc_post_views( $args['id'], false );
	}

	/**
	 * Add post views counter to content.
	 */
	public function add_post_views_count( $content ) {
		if ( is_singular() && in_array( get_post_type(), Post_Views_Counter()->get_attribute( 'options', 'display', 'post_types_display' ), true ) ) {

			// get groups to check it faster
			$groups = Post_Views_Counter()->get_attribute( 'options', 'display', 'restrict_display', 'groups' );

			// whether to display views
			if ( is_user_logged_in() ) {
				// exclude logged in users?
				if ( in_array( 'users', $groups, true ) )
					return $content;
				// exclude specific roles?
				elseif ( in_array( 'roles', $groups, true ) && Post_Views_Counter()->get_instance( 'counter' )->is_user_roles_excluded( Post_Views_Counter()->get_attribute( 'options', 'display', 'restrict_display', 'roles' ) ) )
					return $content;
			}
			// exclude guests?
			elseif ( in_array( 'guests', $groups, true ) )
				return $content;

			switch ( Post_Views_Counter()->get_attribute( 'options', 'display', 'position' ) ) {
				case 'after':
					return $content . '[post-views]';

				case 'before':
					return '[post-views]' . $content;

				default:
				case 'manual':
					return $content;
			}
		}

		return $content;
	}

	/**
	 * Remove post views shortcode from excerpt.
	 */
	public function remove_post_views_count( $excerpt ) {
		remove_shortcode( 'post-views' );
		$excerpt = preg_replace( '/\[post-views[^\]]*\]/', '', $excerpt );
		return $excerpt;
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function frontend_scripts_styles() {
		$post_types = Post_Views_Counter()->get_attribute( 'options', 'display', 'post_types_display' );

		// load dashicons
		wp_enqueue_style( 'dashicons' );

		wp_register_style(
			'post-views-counter-frontend', POST_VIEWS_COUNTER_URL . '/css/frontend.css'
		);

		wp_enqueue_style( 'post-views-counter-frontend' );

		if ( Post_Views_Counter()->get_attribute( 'options', 'general', 'counter_mode' ) === 'js' ) {
			$post_types = Post_Views_Counter()->get_attribute( 'options', 'general', 'post_types_count' );

			// whether to count this post type or not
			if ( empty( $post_types ) || ! is_singular( $post_types ) )
				return;

			wp_register_script(
				'post-views-counter-frontend', POST_VIEWS_COUNTER_URL . '/js/frontend.js', array( 'jquery' )
			);

			wp_enqueue_script( 'post-views-counter-frontend' );

			wp_localize_script(
				'post-views-counter-frontend', 'pvcArgsFrontend', array(
				'ajaxURL'	 => admin_url( 'admin-ajax.php' ),
				'postID'	 => get_the_ID(),
				'nonce'		 => wp_create_nonce( 'pvc-check-post' ),
				'postType'	 => get_post_type()
				)
			);
		}
	}

}

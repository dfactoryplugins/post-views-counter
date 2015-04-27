<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

new Post_Views_Counter_Update();

class Post_Views_Counter_Update {

	public function __construct() {
		// actions
		add_action( 'init', array( &$this, 'check_update' ) );
	}

	/**
	 * Check if there's a db update required
	 */
	public function check_update() {
		if ( ! current_user_can( 'manage_options' ) )
			return;

		// get current database version
		$current_db_version = get_option( 'post_views_counter_version', '1.0.0' );

		// new version?
		if ( version_compare( $current_db_version, Post_Views_Counter()->get_attribute( 'defaults', 'version' ), '<' ) ) {
			// update plugin version
			update_option( 'post_views_counter_version', Post_Views_Counter()->get_attribute( 'defaults', 'version' ) );
		}
	}

}

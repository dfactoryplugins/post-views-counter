<?php
// mimic the actuall admin-ajax
define( 'DOING_AJAX', true );

if ( ! isset( $_POST['action'] ) )
	die( '-1' );

// hide errors if any
ini_set( 'html_errors', 0 );

// we need only basics
define( 'SHORTINIT', true );

// get wp-load.php location
$path = explode( 'wp-content', __FILE__ );

if ( is_file( reset( $path ) . 'wp-load.php' ) ) {
	require_once( reset( $path ) . 'wp-load.php' );
} else {
	die( '-1' );
}

// typical headers
header( 'Content-Type: text/html' );
send_nosniff_header();

// disable caching
header( 'Cache-Control: no-cache' );
header( 'Pragma: no-cache' );

// include only the files and function we need
require_once( ABSPATH . 'wp-config.php' );

require_once( ABSPATH . WPINC . '/post.php' );
require_once( ABSPATH . WPINC . '/formatting.php' );
require_once( ABSPATH . WPINC . '/capabilities.php' );
require_once( ABSPATH . WPINC . '/query.php' );
require_once( ABSPATH . WPINC . '/taxonomy.php' );
require_once( ABSPATH . WPINC . '/meta.php' );
require_once( ABSPATH . WPINC . '/functions.php' );
require_once( ABSPATH . WPINC . '/link-template.php' );
require_once( ABSPATH . WPINC . '/class-wp-post.php' );
require_once( ABSPATH . WPINC . '/kses.php' );
require_once( ABSPATH . WPINC . '/rest-api.php' );
// required for wp user
require_once( ABSPATH . WPINC . '/user.php' );
require_once( ABSPATH . WPINC . '/vars.php' );
require_once( ABSPATH . WPINC . '/class-wp-session-tokens.php' );
require_once( ABSPATH . WPINC . '/class-wp-user-meta-session-tokens.php' );
require_once( ABSPATH . WPINC . '/class-wp-roles.php' );
require_once( ABSPATH . WPINC . '/class-wp-role.php' );
require_once( ABSPATH . WPINC . '/class-wp-user.php' );
require_once( ABSPATH . WPINC . '/pluggable.php' );

// get constants
wp_plugin_directory_constants();
wp_cookie_constants();
wp_ssl_constants();

// include Post Views Counter core
require_once( WP_PLUGIN_DIR . '/post-views-counter/post-views-counter.php' );

// if PVC_SHORTINIT_INC is defined in wp-config.php load theme/pvc/includes.php file
// this allows to perform custom actions, for example hook into pvc_after_count_visit
if ( defined( 'PVC_SHORTINIT_INC' ) && PVC_SHORTINIT_INC ) {
	require_once( ABSPATH . WPINC . '/plugin.php' );
	require_once( ABSPATH . WPINC . '/theme.php' );
	
	// get the current theme path
	$theme_path = get_theme_file_path();
	// load custom pvc includes file
	$pvc_file_path = $theme_path . DIRECTORY_SEPARATOR . 'pvc' . DIRECTORY_SEPARATOR . 'includes.php';
	
	if ( is_file( $pvc_file_path ) ) {
		require_once( $pvc_file_path );
	}
}

$action = esc_attr( trim( $_POST['action'] ) );

// a bit of security
$allowed_actions = array(
	'pvc-check-post'
);

if ( in_array( $action, $allowed_actions ) ) {
	do_action( 'pvc_ajax_' . $action );
} else {
	die( '-1' );
} 
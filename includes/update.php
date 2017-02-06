<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Update class.
 * 
 * @class Post_Views_Counter_Update
 */
class Post_Views_Counter_Update {

	public function __construct() {
		// actions
		add_action( 'init', array( $this, 'check_update' ) );
	}

	/**
	 * Check if there's a db update required
	 */
	public function check_update() {
		if ( ! current_user_can( 'manage_options' ) )
			return;

		// get current database version
		$current_db_version = get_option( 'post_views_counter_version', '1.0.0' );

		// update 1.2.4+
		if ( version_compare( $current_db_version, '1.2.4', '<=' ) ) {
			$general = Post_Views_Counter()->options['general'];

			if ( $general['reset_counts']['number'] > 0 ) {
				// unsupported data reset in minutes/hours
				if ( in_array( $general['reset_counts']['type'], array( 'minutes', 'hours' ), true ) ) {
					// set type to date
					$general['reset_counts']['type'] = 'days';

					// new number of days
					if ( $general['reset_counts']['type'] === 'minutes' )
						$general['reset_counts']['number'] = $general['reset_counts']['number'] * 60;
					else
						$general['reset_counts']['number'] = $general['reset_counts']['number'] * 3600;

					// how many days?
					$general['reset_counts']['number'] = (int) round( ceil( $general['reset_counts']['number'] / 86400 ) );

					// force cron to update
					$general['cron_run'] = true;
					$general['cron_update'] = true;

					// update settings
					update_option( 'post_views_counter_settings_general', $general );

					// update general options
					Post_Views_Counter()->options['general'] = $general;
				}

				// update cron job for all users
				Post_Views_Counter()->cron->check_cron();
			}
		}

		if ( isset( $_POST['post_view_counter_update'], $_POST['post_view_counter_number'] ) ) {
			if ( $_POST['post_view_counter_number'] === 'update_1' ) {
				$this->update_1();

				// update plugin version
				update_option( 'post_views_counter_version', Post_Views_Counter()->defaults['version'], false );
			}
		}

		$update_1_html = '
		<form action="" method="post">
			<input type="hidden" name="post_view_counter_number" value="update_1"/>
			<p>' . __( '<strong>Post Views Counter</strong> - this version requires a database update. Make sure to back up your database first.', 'post-views-counter' ) . '</p>
			<p><input type="submit" class="button button-primary" name="post_view_counter_update" value="' . __( 'Run the Update', 'post-views-counter' ) . '"/></p>
		</form>';

		// get current database version
		$current_db_version = get_option( 'post_views_counter_version', '1.0.0' );

		// new version?
		if ( version_compare( $current_db_version, Post_Views_Counter()->defaults['version'], '<' ) ) {
			// is update 1 required?
			if ( version_compare( $current_db_version, '1.2.4', '<=' ) )
				Post_Views_Counter()->add_notice( $update_1_html, 'notice notice-info' );
			else
				// update plugin version
				update_option( 'post_views_counter_version', Post_Views_Counter()->defaults['version'], false );
		}
	}

	/**
	 * Database update for 1.2.4 and below.
	 */
	public function update_1() {
		global $wpdb;

		// get index
		$old_index = $wpdb->query( "SHOW INDEX FROM `" . $wpdb->prefix . "post_views` WHERE Key_name = 'id_period'" );

		// check whether index already exists
		if ( $old_index > 0 ) {
			// drop unwanted index which prevented saving views with indentical weeks and months
			$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "post_views` DROP INDEX id_period" );
		}

		// get index
		$new_index = $wpdb->query( "SHOW INDEX FROM `" . $wpdb->prefix . "post_views` WHERE Key_name = 'id_type_period_count'" );

		// check whether index already exists
		if ( $new_index === 0 ) {
			// create new index for better performance of SQL queries
			$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'post_views` ADD UNIQUE INDEX `id_type_period_count` (`id`, `type`, `period`, `count`) USING BTREE' );
		}

		Post_Views_Counter()->add_notice( __( 'Thank you! Datebase was succesfully updated.', 'post-views-counter' ), 'updated', true );
	}
}
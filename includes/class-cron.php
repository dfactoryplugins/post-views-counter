<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Cron class.
 *
 * @class Post_Views_Counter_Cron
 */
class Post_Views_Counter_Cron {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'init', [ $this, 'check_cron' ] );
		add_action( 'pvc_reset_counts', [ $this, 'reset_counts' ] );

		// filters
		add_filter( 'cron_schedules', [ $this, 'cron_time_intervals' ] );
	}

	/**
	 * Reset daily counts.
	 *
	 * @global object $wpdb
	 *
	 * @return void
	 */
	public function reset_counts() {
		global $wpdb;

		$counter = [
			'days'		=> 1,
			'weeks'		=> 7,
			'months'	=> 30,
			'years'		=> 365
		];

		// get main instance
		$pvc = Post_Views_Counter();

		// default where clause
		$where = [ 'type = 0', 'CAST( period AS SIGNED ) < CAST( ' . date( 'Ymd', strtotime( '-' . ( (int) ( $counter[$pvc->options['general']['reset_counts']['type']] * $pvc->options['general']['reset_counts']['number'] ) ) . ' days' ) ) . ' AS SIGNED)' ];

		// update where clause
		$where = apply_filters( 'pvc_reset_counts_where_clause', $where );

		// delete views
		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'post_views WHERE ' . implode( ' AND ', $where ) );
	}

	/**
	 * Add new cron interval from settings.
	 *
	 * @param array $schedules
	 * @return array
	 */
	public function cron_time_intervals( $schedules ) {
		// get main instance
		$pvc = Post_Views_Counter();

		$schedules['post_views_counter_interval'] = [
			'interval'	=> DAY_IN_SECONDS,
			'display'	=> __( 'Post Views Counter reset daily counts interval', 'post-views-counter' )
		];

		return $schedules;
	}

	/**
	 * Check whether WP Cron needs to add new task.
	 *
	 * @return void
	 */
	public function check_cron() {
		// only for backend
		if ( ! is_admin() )
			return;

		// get main instance
		$pvc = Post_Views_Counter();

		// set wp cron task
		if ( $pvc->options['general']['cron_run'] ) {
			// not set or need to be updated?
			if ( ! wp_next_scheduled( 'pvc_reset_counts' ) || $pvc->options['general']['cron_update'] ) {
				// task is added but need to be updated
				if ( $pvc->options['general']['cron_update'] ) {
					// remove old schedule
					wp_clear_scheduled_hook( 'pvc_reset_counts' );

					// set update to false
					$general = $pvc->options['general'];
					$general['cron_update'] = false;

					// update settings
					update_option( 'post_views_counter_settings_general', $general );
				}

				// set schedule
				wp_schedule_event( current_time( 'timestamp', true ) + DAY_IN_SECONDS, 'post_views_counter_interval', 'pvc_reset_counts' );
			}
		} else {
			// remove schedule
			wp_clear_scheduled_hook( 'pvc_reset_counts' );

			remove_action( 'pvc_reset_counts', [ $this, 'reset_counts' ] );
		}
	}
}

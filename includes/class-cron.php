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
		add_action( 'pvc_flush_cached_counts', [ $this, 'flush_cached_counts' ] );

		// filters
		add_filter( 'cron_schedules', [ $this, 'cron_time_intervals' ] );
	}

	/**
	 * Reset daily counts.
	 *
	 * @global $wpdb
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

		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'post_views WHERE type = 0 AND CAST( period AS SIGNED ) < CAST( ' . date( 'Ymd', strtotime( '-' . ( (int) ( $counter[$pvc->options['general']['reset_counts']['type']] * $pvc->options['general']['reset_counts']['number'] ) ) . ' days' ) ) . ' AS SIGNED)' );
	}

	/**
	 * Call Post_Views_Counter_Counter::flush_cache_to_db().
	 * This is (un)scheduled on plugin activation/deactivation.
	 *
	 * @return void
	 */
	public function flush_cached_counts() {
		// get counter class
		$counter = Post_Views_Counter()->counter;

		// caching?
		if ( $counter && $counter->using_object_cache() )
			$counter->flush_cache_to_db();
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
			'interval'	=> 86400,
			'display'	=> __( 'Post Views Counter reset daily counts interval', 'post-views-counter' )
		];

		$schedules['post_views_counter_flush_interval'] = [
			'interval'	=> $pvc->counter->get_timestamp( $pvc->options['general']['flush_interval']['type'], $pvc->options['general']['flush_interval']['number'], false ),
			'display'	=> __( 'Post Views Counter cache flush interval', 'post-views-counter' )
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
				wp_schedule_event( current_time( 'timestamp', true ) + 86400, 'post_views_counter_interval', 'pvc_reset_counts' );
			}
		} else {
			// remove schedule
			wp_clear_scheduled_hook( 'pvc_reset_counts' );
			remove_action( 'pvc_reset_counts', [ $this, 'reset_counts' ] );
		}
	}
}
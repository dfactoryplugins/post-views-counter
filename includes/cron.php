<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

new Post_Views_Counter_Cron();

class Post_Views_Counter_Cron {

	public function __construct() {
		// set instance
		Post_Views_Counter()->add_instance( 'cron', $this );

		// actions
		add_action( 'init', array( &$this, 'check_cron' ) );
		add_action( 'pvc_reset_counts', array( &$this, 'reset_counts' ) );
		add_action( 'pvc_flush_cached_counts', array( &$this, 'flush_cached_counts' ) );

		// filters
		add_filter( 'cron_schedules', array( &$this, 'cron_time_intervals' ) );
	}

	/**
	 * Reset daily counts.
	 */
	public function reset_counts() {
		global $wpdb;

		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'post_views WHERE type = 0' );
	}

	/**
	 * Call Post_Views_Counter_Counter::flush_cache_to_db().
	 * This is (un)scheduled on plugin activation/deactivation.
	 */
	public function flush_cached_counts() {
		$counter = Post_Views_Counter()->get_instance( 'counter' );

		if ( $counter && $counter->using_object_cache() ) {
			$counter->flush_cache_to_db();
		}
	}

	/**
	 * Add new cron interval from settings.
	 */
	public function cron_time_intervals( $schedules ) {
		$schedules['post_views_counter_interval'] = array(
			'interval'	 => Post_Views_Counter()->get_instance( 'counter' )->get_timestamp( Post_Views_Counter()->get_attribute( 'options', 'general', 'reset_counts', 'type' ), Post_Views_Counter()->get_attribute( 'options', 'general', 'reset_counts', 'number' ), false ),
			'display'	 => __( 'Post Views Counter reset daily counts interval', 'post-views-counter' )
		);

		$schedules['post_views_counter_flush_interval'] = array(
			'interval'	 => Post_Views_Counter()->get_instance( 'counter' )->get_timestamp( Post_Views_Counter()->get_attribute( 'options', 'general', 'flush_interval', 'type' ), Post_Views_Counter()->get_attribute( 'options', 'general', 'flush_interval', 'number' ), false ),
			'display'	 => __( 'Post Views Counter cache flush interval', 'post-views-counter' )
		);

		return $schedules;
	}

	/**
	 * Check whether WP Cron needs to add new task.
	 */
	public function check_cron() {
		if ( ! is_admin() )
			return;

		// set wp cron task
		if ( Post_Views_Counter()->get_attribute( 'options', 'general', 'cron_run' ) ) {

			// not set or need to be updated?
			if ( ! wp_next_scheduled( 'pvc_reset_counts' ) || Post_Views_Counter()->get_attribute( 'options', 'general', 'cron_update' ) ) {

				// task is added but need to be updated
				if ( Post_Views_Counter()->get_attribute( 'options', 'general', 'cron_update' ) ) {
					// remove old schedule
					wp_clear_scheduled_hook( 'pvc_reset_counts' );

					// set update to false
					$general = Post_Views_Counter()->get_attribute( 'options', 'general' );
					$general['cron_update'] = false;

					// update settings
					update_option( 'post_views_counter_settings_general', $general );
				}

				// set schedule
				wp_schedule_event( Post_Views_Counter()->get_instance( 'counter' )->get_timestamp( Post_Views_Counter()->get_attribute( 'options', 'general', 'reset_counts', 'type' ), Post_Views_Counter()->get_attribute( 'options', 'general', 'reset_counts', 'number' ) ), 'post_views_counter_interval', 'pvc_reset_counts' );
			}
		} else {
			// remove schedule
			wp_clear_scheduled_hook( 'pvc_reset_counts' );
			remove_action( 'pvc_reset_counts', array( &$this, 'reset_counts' ) );
		}
	}

}

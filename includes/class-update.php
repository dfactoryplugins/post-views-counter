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

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'admin_init', [ $this, 'check_update' ] );
	}

	/**
	 * Check whether update is required.
	 *
	 * @return void
	 */
	public function check_update() {
		if ( ! current_user_can( 'manage_options' ) )
			return;

		// get main instance
		$pvc = Post_Views_Counter();

		// get current database version
		$current_db_version = get_option( 'post_views_counter_version', '1.0.0' );

		// update 1.2.4+
		if ( version_compare( $current_db_version, '1.2.4', '<=' ) ) {
			// get general options
			$general = $pvc->options['general'];

			if ( $general['reset_counts']['number'] > 0 ) {
				// unsupported data reset in minutes/hours
				if ( in_array( $general['reset_counts']['type'], [ 'minutes', 'hours' ], true ) ) {
					// set type to date
					$general['reset_counts']['type'] = 'days';

					// new number of days
					if ( $general['reset_counts']['type'] === 'minutes' )
						$general['reset_counts']['number'] = $general['reset_counts']['number'] * MINUTE_IN_SECONDS;
					else
						$general['reset_counts']['number'] = $general['reset_counts']['number'] * HOUR_IN_SECONDS;

					// how many days?
					$general['reset_counts']['number'] = (int) round( ceil( $general['reset_counts']['number'] / DAY_IN_SECONDS ) );

					// force cron to update
					$general['cron_run'] = true;
					$general['cron_update'] = true;

					// update settings
					update_option( 'post_views_counter_settings_general', $general );

					// update general options
					$pvc->options['general'] = $general;
				}

				// update cron job for all users
				$pvc->cron->check_cron();
			}
		}

		// update 1.3.13+
		if ( version_compare( $current_db_version, '1.3.13', '<=' ) ) {
			// get general options
			$general = $pvc->options['general'];

			// disable strict counts
			$general['strict_counts'] = false;

			// get default other options
			$other_options = $pvc->defaults['other'];

			// set current options
			$other_options['deactivation_delete'] = isset( $general['deactivation_delete'] ) ? (bool) $general['deactivation_delete'] : false;

			// add other options
			add_option( 'post_views_counter_settings_other', $other_options, null, false );

			// update other options
			$pvc->options['other'] = $other_options;

			// remove old setting
			unset( $general['deactivation_delete'] );

			// flush cache enabled?
			if ( $general['flush_interval']['number'] > 0 ) {
				if ( $pvc->counter->using_object_cache( true ) ) {
					// flush data from cache
					$pvc->counter->flush_cache_to_db();
				}

				// unschedule cron event
				wp_clear_scheduled_hook( 'pvc_flush_cached_counts' );

				// disable cache
				$general['flush_interval'] = [
					'number'	=> 0,
					'type'		=> 'minutes'
				];
			}

			// update general options
			$pvc->options['general'] = $general;

			// update general options
			update_option( 'post_views_counter_settings_general', $general );
		}

		// update 1.5.2+
		if ( version_compare( $current_db_version, '1.5.2', '<=' ) ) {
			// get options
			$general = $pvc->options['general'];
			$display = $pvc->options['display'];

			// copy values
			$display['post_views_column'] = $general['post_views_column'];
			$display['restrict_edit_views'] = $general['restrict_edit_views'];

			// remove old values
			unset( $general['post_views_column'] );
			unset( $general['restrict_edit_views'] );

			// update settings
			update_option( 'post_views_counter_settings_general', $general );
			update_option( 'post_views_counter_settings_display', $display );

			// update options
			$pvc->options['general'] = $general;
			$pvc->options['display'] = $display;
		}

		// update 1.6.0+ - migrate import settings to provider format
		if ( version_compare( $current_db_version, '1.6.0', '<' ) ) {
			// get other options
			$other = $pvc->options['other'];

			// check if migration is needed
			if ( ! isset( $other['import_provider_settings'] ) && isset( $other['import_meta_key'] ) ) {
				$old_meta_key = $other['import_meta_key'];

				// create new provider settings structure
				$other['import_provider_settings'] = [
					'provider' => 'custom_meta_key',
					'strategy' => 'merge',
					'custom_meta_key' => [
						'meta_key' => $old_meta_key
					]
				];

				// update settings
				update_option( 'post_views_counter_settings_other', $other );

				// update options
				$pvc->options['other'] = $other;
			}
		}

		// move menu position setting to display tab
		$this->migrate_menu_position_option();

		if ( isset( $_POST['post_view_counter_update'], $_POST['post_view_counter_number'] ) ) {
			if ( $_POST['post_view_counter_number'] === 'update_1' ) {
				$this->update_1();

				// update plugin version
				update_option( 'post_views_counter_version', $pvc->defaults['version'], false );
			}
		}

		// get current database version
		$current_db_version = get_option( 'post_views_counter_version', '1.0.0' );

		// new version?
		if ( version_compare( $current_db_version, $pvc->defaults['version'], '<' ) ) {
			// is update 1 required?
			if ( version_compare( $current_db_version, '1.2.4', '<=' ) ) {
				$update_1_html = '
				<form action="" method="post">
					<input type="hidden" name="post_view_counter_number" value="update_1"/>
					<p>' . __( '<strong>Post Views Counter</strong> - this version requires a database update. Make sure to back up your database first.', 'post-views-counter' ) . '</p>
					<p><input type="submit" class="button button-primary" name="post_view_counter_update" value="' . __( 'Run the Update', 'post-views-counter' ) . '"/></p>
				</form>';

				$pvc->add_notice( $update_1_html, 'notice notice-info', false );
			} else
				// update plugin version
				update_option( 'post_views_counter_version', $pvc->defaults['version'], false );
		}

		// ensure integrations option exists
		if ( ! get_option( 'post_views_counter_settings_integrations' ) ) {
			add_option( 'post_views_counter_settings_integrations', $pvc->defaults['integrations'], null, false );
		}
	}

	/**
	 * Database update for 1.2.4 and below.
	 *
	 * @global object $wpdb
	 *
	 * @return void
	 */
	public function update_1() {
		global $wpdb;

		// get index
		$old_index = $wpdb->query( "SHOW INDEX FROM `" . $wpdb->prefix . "post_views` WHERE Key_name = 'id_period'" );

		// check whether index already exists
		if ( $old_index > 0 ) {
			// drop unwanted index which prevented saving views with identical weeks and months
			$wpdb->query( "ALTER TABLE `" . $wpdb->prefix . "post_views` DROP INDEX id_period" );
		}

		// get index
		$new_index = $wpdb->query( "SHOW INDEX FROM `" . $wpdb->prefix . "post_views` WHERE Key_name = 'id_type_period_count'" );

		// check whether index already exists
		if ( $new_index === 0 ) {
			// create new index for better performance of sql queries
			$wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'post_views` ADD UNIQUE INDEX `id_type_period_count` (`id`, `type`, `period`, `count`) USING BTREE' );
		}

		Post_Views_Counter()->add_notice( __( 'Thank you! Datebase was successfully updated.', 'post-views-counter' ), 'updated', true );
	}

	/**
	 * Move menu position setting from "Other" to "Display" settings.
	 *
	 * @return void
	 */
	private function migrate_menu_position_option() {
		$pvc = Post_Views_Counter();

		// prefer legacy value if present, otherwise fall back to current display option
		$menu_position = isset( $pvc->options['other']['menu_position'] ) && in_array( $pvc->options['other']['menu_position'], [ 'top', 'sub' ], true )
			? $pvc->options['other']['menu_position']
			: ( isset( $pvc->options['display']['menu_position'] ) && in_array( $pvc->options['display']['menu_position'], [ 'top', 'sub' ], true )
				? $pvc->options['display']['menu_position']
				: 'top' );

		$display_options = get_option( 'post_views_counter_settings_display', [] );

		if ( ! isset( $display_options['menu_position'] ) || ! in_array( $display_options['menu_position'], [ 'top', 'sub' ], true ) ) {
			$display_options['menu_position'] = $menu_position;
			update_option( 'post_views_counter_settings_display', $display_options );
		}

		$other_options = get_option( 'post_views_counter_settings_other', [] );

		if ( ! is_array( $other_options ) )
			$other_options = [];

		if ( ! isset( $other_options['menu_position'] ) || ! in_array( $other_options['menu_position'], [ 'top', 'sub' ], true ) ) {
			$other_options['menu_position'] = $display_options['menu_position'];
			update_option( 'post_views_counter_settings_other', $other_options );
		}

		$pvc->options['other']['menu_position'] = $other_options['menu_position'];
		$pvc->options['display']['menu_position'] = $display_options['menu_position'];
	}
}

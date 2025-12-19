<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Settings_Reports class.
 *
 * @class Post_Views_Counter_Settings_Reports
 */
class Post_Views_Counter_Settings_Reports {

	private $pvc;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->pvc = Post_Views_Counter();
	}

	/**
	 * Get sections for reports tab.
	 *
	 * @return array
	 */
	public function get_sections() {
		return [
			'post_views_counter_reports_settings'	=> [
				'tab'		=> 'reports',
				'callback'  => [ $this, 'section_reports_placeholder' ]
			]
		];
	}

	/**
	 * Get fields for reports tab.
	 *
	 * @return array
	 */
	public function get_fields() {
		return [];
	}

	/**
	 * Reports page placeholder.
	 */
	public function section_reports_placeholder() {
		echo '
		<form action="#">
			<div id="pvc-reports-placeholder">
				<img id="pvc-reports-bg" src="' . esc_url( POST_VIEWS_COUNTER_URL ) . '/css/page-reports.png" alt="Post Views Counter - Reports" />
				<div id="pvc-reports-upgrade">
					<div id="pvc-reports-modal">
						<h2>' . esc_html__( 'Display Reports and Export Views to CSV/XML', 'post-views-counter' ) . '</h2>
						<p>' . esc_html__( 'View detailed stats about the popularity of your content.', 'post-views-counter' ) . '</p>
						<p>' . esc_html__( 'Generate views reports in any date range you need.', 'post-views-counter' ) . '</p>
						<p>' . esc_html__( 'Export, download and share your website views data.', 'post-views-counter' ) . '</p>
						<p><a href="https://postviewscounter.com/upgrade/?utm_source=post-views-counter-lite&utm_medium=button&utm_campaign=upgrade-to-pro" class="button button-secondary button-hero pvc-button" target="_blank">' . esc_html__( 'Upgrade to Pro', 'post-views-counter' ) . '</a></p>
					</div>
				</div>
			</div>
		</form>';
	}
}
<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Post_Views_Counter_Integrations class.
 *
 * Handles loading and management of integrations.
 *
 * @class Post_Views_Counter_Integrations
 */
class Post_Views_Counter_Integrations {

	/**
	 * Get all integrations with their definitions and effective status.
	 *
	 * @return array
	 */
	public static function get_integrations() {
		// get main instance
		$pvc = Post_Views_Counter();

		// get saved statuses
		$saved_statuses = isset( $pvc->options['integrations']['integrations'] ) ? $pvc->options['integrations']['integrations'] : [];

		// base integrations
		$integrations = self::get_base_integrations();

		// allow filtering
		$integrations = apply_filters( 'pvc_integrations', $integrations );

		// compute effective status for each
		foreach ( $integrations as $slug => &$integration ) {
			$pro_active = ! ( isset( $integration['pro'] ) && $integration['pro'] ) || class_exists( 'Post_Views_Counter_Pro' );
			$available = self::is_integration_available( $integration );
			$saved_status = isset( $saved_statuses[$slug] ) ? (bool) $saved_statuses[$slug] : null;

			// default status: saved if set, otherwise true
			$default_status = $saved_status === null ? true : $saved_status;

			// effective status: available and enabled_check result
			$enabled_check = isset( $integration['enabled_check'] ) && is_callable( $integration['enabled_check'] )
				? $integration['enabled_check']
				: function( $default_status, $integration, $slug, $saved_status ) { return $default_status; };

			$integration['pro_active'] = $pro_active;
			$integration['status'] = $available && $pro_active && call_user_func( $enabled_check, $default_status, $integration, $slug, $saved_status );
			$integration['availability'] = $available;
		}

		return $integrations;
	}

	/**
	 * Check if a specific integration is effectively enabled.
	 *
	 * @param string $slug
	 * @return bool
	 */
	public static function is_integration_enabled( $slug ) {
		$integrations = self::get_integrations();
		return isset( $integrations[$slug]['status'] ) ? $integrations[$slug]['status'] : false;
	}

	/**
	 * Get the status of a specific integration.
	 *
	 * @param string $slug
	 * @return bool
	 */
	public static function get_integration_status( $slug ) {
		return self::is_integration_enabled( $slug );
	}

	/**
	 * Check if an integration is available based on its availability_check.
	 *
	 * @param array $integration
	 * @return bool
	 */
	private static function is_integration_available( $integration ) {
		if ( ! isset( $integration['availability_check'] ) )
			return false;

		if ( is_callable( $integration['availability_check'] ) )
			return call_user_func( $integration['availability_check'] );

		return false;
	}

	/**
	 * Get base integrations definitions.
	 *
	 * @return array
	 */
	public static function get_base_integrations() {
		return [
			'gutenberg' => [
				'name' => 'Gutenberg',
				'description' => __( 'Integrate with WordPress block editor to order posts by views in Query Loop and Latest Posts blocks.', 'post-views-counter' ),
				'menu_order' => 5,
				'pro' => false,
				'availability_check' => function() { return function_exists( 'register_block_type' ); },
				'enabled_check' => function( $default_status, $integration, $slug, $saved_status ) { return $default_status; },
				'items' => [
					[
						'name' => __( 'Query Loop Block', 'post-views-counter' ),
						'description' => __( 'Enables ordering posts by view count in the core Query Loop block.', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Latest Posts Block', 'post-views-counter' ),
						'description' => __( 'Enables ordering posts by view count in the core Latest Posts block.', 'post-views-counter' ),
						'status' => true
					]
				]
			],
			'amp' => [
				'name' => 'AMP',
				'description' => __( 'Integrate with AMP plugin to handle post views on AMP pages.', 'post-views-counter' ),
				'menu_order' => 20,
				'pro' => true,
				'availability_check' => function() { return function_exists( 'amp_is_request' ); },
				'enabled_check' => function( $default_status, $integration, $slug, $saved_status ) { return $default_status; },
				'items' => [
					[
						'name' => __( 'AMP Support', 'post-views-counter' ),
						'description' => __( 'Tracks and displays views on AMP-enabled pages.', 'post-views-counter' ),
						'status' => true
					]
				]
			],
			'beaver-builder' => [
				'name' => 'Beaver Builder',
				'description' => __( 'Integrate with Beaver Builder to order module posts by views.', 'post-views-counter' ),
				'menu_order' => 20,
				'pro' => true,
				'availability_check' => function() { return class_exists( 'FLBuilder' ); },
				'enabled_check' => function( $default_status, $integration, $slug, $saved_status ) { return $default_status; },
				'items' => [
					[
						'name' => __( 'Post Grid Module', 'post-views-counter' ),
						'description' => __( 'Orders posts by views in the Beaver Builder Post Grid module.', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Post Carousel Module', 'post-views-counter' ),
						'description' => __( 'Orders posts by views in the Beaver Builder Post Carousel module.', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Post Slider Module', 'post-views-counter' ),
						'description' => __( 'Orders posts by views in the Beaver Builder Post Slider module.', 'post-views-counter' ),
						'status' => true
					]
				]
			],
			'divi' => [
				'name' => 'Divi',
				'description' => __( 'Integrate with Divi Theme to order module posts by views when the module uses the "orderby-post-views" CSS class.', 'post-views-counter' ),
				'menu_order' => 20,
				'pro' => true,
				'availability_check' => function() { return defined( 'ET_CORE_VERSION' ); },
				'enabled_check' => function( $default_status, $integration, $slug, $saved_status ) { return $default_status; },
				'items' => [
					[
						'name' => __( 'Blog Module', 'post-views-counter' ),
						'description' => __( 'Orders posts by views in the Divi Blog module.', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Post Slider Module', 'post-views-counter' ),
						'description' => __( 'Orders posts by views in the Divi Post Slider module.', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Portfolio Module', 'post-views-counter' ),
						'description' => __( 'Orders posts by views in the Divi Portfolio module.', 'post-views-counter' ),
						'status' => true
					]
				]
			],
			'elementor-pro' => [
				'name' => 'Elementor Pro',
				'description' => __( 'Integrate with Elementor Pro to order posts by views when a widget query uses the "post_views" Query ID.', 'post-views-counter' ),
				'menu_order' => 20,
				'pro' => true,
				'availability_check' => function() { return defined( 'ELEMENTOR_PRO_VERSION' ); },
				'enabled_check' => function( $default_status, $integration, $slug, $saved_status ) { return $default_status; },
				'items' => [
					[
						'name' => __( 'Posts Widget', 'post-views-counter' ),
						'description' => __( 'Orders posts by views in the Elementor Pro Posts widget when its Query ID is set to "post_views".', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Portfolio Widget', 'post-views-counter' ),
						'description' => __( 'Orders items by views in the Elementor Pro Portfolio widget when its Query ID is set to "post_views".', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Loop Grid / Loop Carousel', 'post-views-counter' ),
						'description' => __( 'Orders items by views in the Elementor Pro Loop Grid and Loop Carousel widgets when the Query ID is set to "post_views".', 'post-views-counter' ),
						'status' => true
					]
				]
			],
			'generateblocks' => [
				'name' => 'GenerateBlocks',
				'description' => __( 'Integrate with GenerateBlocks to order Query block results by views.', 'post-views-counter' ),
				'menu_order' => 20,
				'pro' => true,
				'availability_check' => function() { return defined( 'GENERATEBLOCKS_VERSION' ); },
				'enabled_check' => function( $default_status, $integration, $slug, $saved_status ) { return $default_status; },
				'items' => [
					[
						'name' => __( 'Dynamic Content', 'post-views-counter' ),
						'description' => __( 'Adds post_views ordering to the GenerateBlocks Query block.', 'post-views-counter' ),
						'status' => true
					]
				]
			],
			'jet-engine' => [
				'name' => 'JetEngine',
				'description' => __( 'Integrate with JetEngine plugin to display post views in custom listings and queries.', 'post-views-counter' ),
				'menu_order' => 20,
				'pro' => true,
				'availability_check' => function() { return class_exists( 'Jet_Engine' ); },
				'enabled_check' => function( $default_status, $integration, $slug, $saved_status ) { return $default_status; },
				'items' => [
					[
						'name' => __( 'Query Builder', 'post-views-counter' ),
						'description' => __( 'Enables ordering posts by view count in Query Builder posts queries.', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Listing Grid', 'post-views-counter' ),
						'description' => __( 'Enables ordering posts by view count in Listing Grid, Maps Listing and Calendar widgets.', 'post-views-counter' ),
						'status' => true
					]
				]
			],
			'polylang' => [
				'name' => 'Polylang',
				'description' => __( 'Integrate with Polylang to filter reports, exports, and dashboard widgets by language.', 'post-views-counter' ),
				'menu_order' => 20,
				'pro' => true,
				'availability_check' => function() { return function_exists( 'pll_languages_list' ) || class_exists( 'Polylang' ); },
				'enabled_check' => function( $default_status, $integration, $slug, $saved_status ) { return $default_status; },
				'items' => [
					[
						'name' => __( 'Reports Language Filters', 'post-views-counter' ),
						'description' => __( 'Adds language filtering and a language column to Reports tables, charts, and exports.', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Dashboard Widgets', 'post-views-counter' ),
						'description' => __( 'Filters dashboard widgets (site, post, and term views) by the selected language.', 'post-views-counter' ),
						'status' => true
					]
				]
			],
			'wpml' => [
				'name' => 'WPML',
				'description' => __( 'Integrate with WPML to filter reports, exports, and dashboard widgets by language.', 'post-views-counter' ),
				'menu_order' => 20,
				'pro' => true,
				'availability_check' => function() { return defined( 'WPML_PLUGIN_FILE' ) || class_exists( 'SitePress' ); },
				'enabled_check' => function( $default_status, $integration, $slug, $saved_status ) { return $default_status; },
				'items' => [
					[
						'name' => __( 'Reports Language Filters', 'post-views-counter' ),
						'description' => __( 'Adds language filtering and a language column to Reports tables, charts, and exports.', 'post-views-counter' ),
						'status' => true
					],
					[
						'name' => __( 'Dashboard Widgets', 'post-views-counter' ),
						'description' => __( 'Filters dashboard widgets (site, post, and term views) by the selected language.', 'post-views-counter' ),
						'status' => true
					]
				]
			]
		];
	}
}

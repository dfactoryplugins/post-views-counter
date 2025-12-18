( function( $ ) {

	// ready event
	$( function() {
		let ip_boxes = $( '#post_views_counter_general_exclude_ips_setting' ).find( '.ip-box' ).length;

		$( '#post_views_counter_general_exclude_ips_setting .ip-box:first' ).find( '.remove-exclude-ip' ).hide();

		// ask whether to reset options to defaults
		$( document ).on( 'click', '.reset_pvc_settings', function() {
			const result = confirm( pvcArgsSettings.resetToDefaults );

			if ( result && $( this ).hasClass( 'reset_post_views_counter_settings_other' ) )
				$( 'input[data-pvc-menu="submenu"]' ).after( $( 'input[data-pvc-menu="topmenu"]' ) );

			return result;
		} );

		// ask whether to reset views
		$( document ).on( 'click', 'input[name="post_views_counter_reset_views"]', function() {
			return confirm( pvcArgsSettings.resetViews );
		} );

		// ask whether to import views
		$( document ).on( 'click', 'input[name="post_views_counter_import_views"]', function() {
			return confirm( pvcArgsSettings.importViews );
		} );

		// remove ip box
		$( document ).on( 'click', '.remove-exclude-ip', function( e ) {
			e.preventDefault();

			ip_boxes--;

			const parent = $( this ).parent();

			// remove ip box
			parent.slideUp( 'fast', function() {
				$( this ).remove();
			} );
		} );

		// add ip box
		$( document ).on( 'click', '.add-exclude-ip', function() {
			ip_boxes++;

			const parent = $( this ).parents( '#post_views_counter_general_exclude_ips_setting' );
				const new_ip_box = parent.find( '.ip-box:last' ).clone().hide();

			// clear value
			new_ip_box.find( 'input' ).val( '' );

			if ( ip_boxes > 1 )
				new_ip_box.find( '.remove-exclude-ip' ).show();

			// add and display new ip box
			parent.find( '.ip-box:last' ).after( new_ip_box ).next().slideDown( 'fast' );
		} );

		// add current ip
		$( document ).on( 'click', '.add-current-ip', function() {
			// fill input with user's current ip
			$( this ).parents( '#post_views_counter_general_exclude_ips_setting' ).find( '.ip-box' ).last().find( 'input' ).val( $( this ).attr( 'data-rel' ) );
		} );

		// toggle user roles
		$( '#pvc_exclude-roles, #pvc_restrict_display-roles' ).on( 'change', function() {
			if ( $( this ).is( ':checked' ) )
				$( '.pvc_user_roles' ).slideDown( 'fast' );
			else
				$( '.pvc_user_roles' ).slideUp( 'fast' );
		} );

		// menu position referer update
		$( 'input[name="post_views_counter_settings_display[menu_position]"], input[name="post_views_counter_settings_other[menu_position]"]' ).on( 'change', function() {
			if ( $( this ).val() === 'top' )
				$( 'input[data-pvc-menu="submenu"]' ).after( $( 'input[data-pvc-menu="topmenu"]' ) );
			else
				$( 'input[data-pvc-menu="submenu"]' ).before( $( 'input[data-pvc-menu="topmenu"]' ) );
		} );

		// import provider switching
		$( 'input[name="pvc_import_provider"]' ).on( 'change', function() {
			const selectedProvider = $( this ).val();
				const $current = $( '.pvc-provider-content:visible' );
				const $target = $( '.pvc-provider-' + selectedProvider );

			if ( ! $target.length || $target.is( ':visible' ) )
				return;

			$current.stop( true, true ).slideUp( 'fast', function() {
				$target.stop( true, true ).slideDown( 'fast' );
			} );
		} );

		// import strategy description switching
		( function() {
			const $container = $( '.pvc-import-strategy-details' );
				const $strategyRadios = $( 'input[name="pvc_import_strategy"]' );

			if ( ! $container.length || ! $strategyRadios.length )
				return;

			const showStrategy = function( slug ) {
				const $target = $container.find( '.pvc-strategy-' + slug );

				if ( ! $target.length )
					return;

				const $current = $container.find( '.pvc-strategy-content:visible' );

				if ( $target.is( ':visible' ) )
					return;

				$current.stop( true, true ).slideUp( 'fast', function() {
					$target.stop( true, true ).slideDown( 'fast' );
				} );
			};

			$strategyRadios.on( 'change', function() {
				showStrategy( $( this ).val() );
			} );

			showStrategy( $strategyRadios.filter( ':checked' ).val() );
		} )();
	} );

} )( jQuery );

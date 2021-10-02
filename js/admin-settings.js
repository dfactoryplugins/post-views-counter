( function( $ ) {

	// ready event
	$( function() {
		var ip_boxes = $( '#post_views_counter_general_exclude_ips_setting' ).find( '.ip-box' ).length;

		$( '#post_views_counter_general_exclude_ips_setting .ip-box:first' ).find( '.remove-exclude-ip' ).hide();

		// ask whether to reset options to defaults
		$( document ).on( 'click', '.reset_pvc_settings', function() {
			return confirm( pvcArgsSettings.resetToDefaults );
		} );

		// ask whether to reset views
		$( document ).on( 'click', 'input[name="post_views_counter_reset_views"]', function() {
			return confirm( pvcArgsSettings.resetViews );
		} );

		// remove ip box
		$( document ).on( 'click', '.remove-exclude-ip', function( e ) {
			e.preventDefault();

			ip_boxes--;

			var parent = $( this ).parent();

			// remove ip box
			parent.slideUp( 'fast', function() {
				$( this ).remove();
			} );
		} );

		// add ip box
		$( document ).on( 'click', '.add-exclude-ip', function() {
			ip_boxes++;

			var parent = $( this ).parents( '#post_views_counter_general_exclude_ips_setting' ),
				new_ip_box = parent.find( '.ip-box:last' ).clone().hide();

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
	} );

} )( jQuery );
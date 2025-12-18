/**
 * Post Views Counter Admin Settings JavaScript
 *
 * Manages the settings page interactions for Post Views Counter plugin.
 * Handles IP exclusion lists, reset confirmations, and import/export operations.
 */

(( $ ) => {
	$( () => {
		const $ipSetting = $( '#post_views_counter_general_exclude_ips_setting' );
		let ipBoxes = $ipSetting.find( '.ip-box' ).length;

		$ipSetting.find( '.ip-box:first' ).find( '.remove-exclude-ip' ).hide();

		$( document ).on( 'click', '.reset_pvc_settings', function() {
			const result = confirm( pvcArgsSettings.resetToDefaults );

			if ( result && $( this ).hasClass( 'reset_post_views_counter_settings_other' ) ) {
				$( 'input[data-pvc-menu="submenu"]' ).after( $( 'input[data-pvc-menu="topmenu"]' ) );
			}

			return result;
		} );

		$( document ).on( 'click', 'input[name="post_views_counter_reset_views"]', () => confirm( pvcArgsSettings.resetViews ) );

		$( document ).on( 'click', 'input[name="post_views_counter_import_views"]', () => confirm( pvcArgsSettings.importViews ) );

		$( document ).on( 'click', '.remove-exclude-ip', function( event ) {
			event.preventDefault();

			ipBoxes--;

			const parent = $( this ).parent();

			parent.slideUp( 'fast', function() {
				$( this ).remove();
			} );
		} );

		$( document ).on( 'click', '.add-exclude-ip', function() {
			ipBoxes++;

			const parent = $( this ).parents( '#post_views_counter_general_exclude_ips_setting' );
			const newIpBox = parent.find( '.ip-box:last' ).clone().hide();

			newIpBox.find( 'input' ).val( '' );

			if ( ipBoxes > 1 ) {
				newIpBox.find( '.remove-exclude-ip' ).show();
			}

			parent.find( '.ip-box:last' ).after( newIpBox ).next().slideDown( 'fast' );
		} );

		$( document ).on( 'click', '.add-current-ip', function() {
			$( this )
				.parents( '#post_views_counter_general_exclude_ips_setting' )
				.find( '.ip-box' )
				.last()
				.find( 'input' )
				.val( $( this ).attr( 'data-rel' ) );
		} );

		$( '#pvc_exclude-roles, #pvc_restrict_display-roles' ).on( 'change', function() {
			if ( $( this ).is( ':checked' ) ) {
				$( '.pvc_user_roles' ).slideDown( 'fast' );
			} else {
				$( '.pvc_user_roles' ).slideUp( 'fast' );
			}
		} );

		$( 'input[name="post_views_counter_settings_display[menu_position]"], input[name="post_views_counter_settings_other[menu_position]"]' ).on( 'change', function() {
			if ( $( this ).val() === 'top' ) {
				$( 'input[data-pvc-menu="submenu"]' ).after( $( 'input[data-pvc-menu="topmenu"]' ) );
			} else {
				$( 'input[data-pvc-menu="submenu"]' ).before( $( 'input[data-pvc-menu="topmenu"]' ) );
			}
		} );

		$( 'input[name="pvc_import_provider"]' ).on( 'change', function() {
			const selectedProvider = $( this ).val();
			const $current = $( '.pvc-provider-content:visible' );
			const $target = $( `.pvc-provider-${ selectedProvider }` );

			if ( ! $target.length || $target.is( ':visible' ) ) {
				return;
			}

			$current.stop( true, true ).slideUp( 'fast', function() {
				$target.stop( true, true ).slideDown( 'fast' );
			} );
		} );

		( () => {
			const $container = $( '.pvc-import-strategy-details' );
			const $strategyRadios = $( 'input[name="pvc_import_strategy"]' );

			if ( ! $container.length || ! $strategyRadios.length ) {
				return;
			}

			const showStrategy = ( slug ) => {
				const $target = $container.find( `.pvc-strategy-${ slug }` );

				if ( ! $target.length ) {
					return;
				}

				const $current = $container.find( '.pvc-strategy-content:visible' );

				if ( $target.is( ':visible' ) ) {
					return;
				}

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
})( jQuery );

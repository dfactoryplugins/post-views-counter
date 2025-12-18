/**
 * Post Views Counter Admin Widgets JavaScript
 *
 * Handles widget configuration in the WordPress admin.
 * Manages thumbnail display options for post views widgets.
 */

(( $ ) => {
	$( () => {
		$( document ).on( 'change', '.pvc-show-post-thumbnail', ( event ) => {
			$( event.currentTarget ).closest( '.widget-content' ).find( '.pvc-post-thumbnail-size' ).fadeToggle( 300 );
		} );
	} );
})( jQuery );

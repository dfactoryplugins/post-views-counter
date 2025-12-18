( function( $ ) {

	// ready event
	$( function() {
		$( document ).on( 'change', '.pvc-show-post-thumbnail', function() {
			$( this ).closest( '.widget-content' ).find( '.pvc-post-thumbnail-size' ).fadeToggle( 300 );
		} );
	} );

} )( jQuery );
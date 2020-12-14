( function( $ ) {

	// ready event
	$( function() {
		// post views input
		$( '#post-views .edit-post-views' ).on( 'click', function() {
			if ( $( '#post-views-input-container' ).is( ":hidden" ) ) {
				$( '#post-views-input-container' ).slideDown( 'fast' );
				$( this ).hide();
			}

			return false;
		} );

		// save post views
		$( '#post-views .save-post-views' ).on( 'click', function() {
			var views = ( $( '#post-views-display b' ).text() ).trim();

			$( '#post-views-input-container' ).slideUp( 'fast' );
			$( '#post-views .edit-post-views' ).show();

			views = parseInt( $( '#post-views-input' ).val() );
			// reassign value as integer
			$( '#post-views-input' ).val( views );

			$( '#post-views-display b' ).text( views );

			return false;
		} );

		// cancel post views
		$( '#post-views .cancel-post-views' ).on( 'click', function() {
			var views = ( $( '#post-views-display b' ).text() ).trim();

			$( '#post-views-input-container' ).slideUp( 'fast' );
			$( '#post-views .edit-post-views' ).show();

			views = parseInt( $( '#post-views-current' ).val() );

			$( '#post-views-display b' ).text( views );
			$( '#post-views-input' ).val( views );

			return false;
		} );
	} );

} )( jQuery );
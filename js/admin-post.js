( function ( $ ) {

    $( document ).ready( function () {

	// post views input
	$( '#post-views .edit-post-views' ).click( function () {
	    if ( $( '#post-views-input-container' ).is( ":hidden" ) ) {
		$( '#post-views-input-container' ).slideDown( 'fast' );
		$( this ).hide();
	    }
	    return false;
	} );

	// save post views
	$( '#post-views .save-post-views' ).click( function () {

	    var views = $.trim( $( '#post-views-display b' ).text() );

	    $( '#post-views-input-container' ).slideUp( 'fast' );
	    $( '#post-views .edit-post-views' ).show();

	    views = parseInt( $( '#post-views-input' ).val() );
	    // reassign value as integer
	    $( '#post-views-input' ).val( views );

	    $( '#post-views-display b' ).text( views );

	    return false;
	} );

	// cancel post views
	$( '#post-views .cancel-post-views' ).click( function () {

	    var views = $.trim( $( '#post-views-display b' ).text() );

	    $( '#post-views-input-container' ).slideUp( 'fast' );
	    $( '#post-views .edit-post-views' ).show();

	    views = parseInt( $( '#post-views-current' ).val() );

	    $( '#post-views-display b' ).text( views );
	    $( '#post-views-input' ).val( views );

	    return false;
	} );

    } );

} )( jQuery );
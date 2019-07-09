( function( $ ) {

	// we create a copy of the WP inline edit post function
	var $wp_inline_edit = inlineEditPost.edit;

	// and then we overwrite the function with our own code
	inlineEditPost.edit = function( id ) {
		console.log( 'edit' );
		// call the original WP edit function
		// we don't want to leave WordPress hanging
		$wp_inline_edit.apply( this, arguments );

		// get the post ID
		var $post_id = 0;

		if ( typeof ( id ) == 'object' )
			$post_id = parseInt( this.getId( id ) );

		if ( $post_id > 0 ) {
			// define the edit row
			var $edit_row = $( '#edit-' + $post_id ),
				$post_row = $( '#post-' + $post_id );

			// get the data
			var $post_views = $( '.column-post_views', $post_row ).text();

			// populate the data
			$( ':input[name="post_views"]', $edit_row ).val( $post_views );
			$( ':input[name="current_post_views"]', $edit_row ).val( $post_views );
		}

		return false;
	};

	$( document ).on( 'click', '#bulk_edit', function() {
		console.log( 'bulk edit' );
		// define the bulk edit row
		var $bulk_row = $( '#bulk-edit' );

		// get the selected post ids that are being edited
		var $post_ids = new Array();

		$bulk_row.find( '#bulk-titles' ).children().each( function() {
			$post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
		} );

		// get the data
		var $post_views = $bulk_row.find( 'input[name="post_views"]' ).val();

		// save the data
		$.ajax( {
			url: ajaxurl, // this is a variable that WordPress has already defined for us
			type: 'post',
			async: false,
			cache: false,
			data: {
				action: 'save_bulk_post_views', // this is the name of our WP AJAX function that we'll set up next
				post_ids: $post_ids, // and these are the 2 parameters we're passing to our function
				post_views: $post_views,
				current_post_views: $post_views,
			}
		} );
	} );

} )( jQuery );
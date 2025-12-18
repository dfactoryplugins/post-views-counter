/**
 * Post Views Counter Admin Quick Edit JavaScript
 *
 * Extends WordPress quick edit functionality to include post views editing.
 * Handles both individual post quick edit and bulk edit operations.
 */

( function( $ ) {

	// ready event
	$( function() {
		// we create a copy of the WP inline edit post function
		const wpInlineEdit = inlineEditPost.edit;

		/**
		 * Override WordPress's inline edit function to include post views editing.
		 *
		 * @param {number|string|Object} id - The post ID or element identifier
		 */
		// and then we overwrite the function with our own code
		inlineEditPost.edit = function( id ) {
			// call the original WP edit function, we don't want to leave WordPress hanging
			wpInlineEdit.apply( this, arguments );

			// get the post ID
			let postId = 0;

			if ( typeof ( id ) === 'object' )
				postId = parseInt( this.getId( id ) );

			if ( postId > 0 ) {
				// define the edit row
				const editRow = $( '#edit-' + postId );
				const postRow = $( '#post-' + postId );

				// get the data
				const postViews = $( '.column-post_views', postRow ).text();

				// populate the data
				$( ':input[name="post_views"]', editRow ).val( postViews );
				$( ':input[name="current_post_views"]', editRow ).val( postViews );
			}

			return false;
		};

		$( document ).on( 'click', '#bulk_edit', function() {
			// define the bulk edit row
			const bulkRow = $( '#bulk-edit' );

			// get the selected post ids that are being edited
			const postIds = [];

			// at least wp 5.9?
			if ( pvcArgsQuickEdit.wpVersion59 ) {
				bulkRow.find( '#bulk-titles-list' ).children( '.ntdelitem' ).each( function() {
					postIds.push( $( this ).find( 'button' ).attr( 'id' ).replace( /[^0-9]/i, '' ) );
				} );
			} else {
				bulkRow.find( '#bulk-titles' ).children().each( function() {
					postIds.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
				} );
			}

			// get the data
			const postViews = bulkRow.find( 'input[name="post_views"]' ).val();

			// save the data
			$.ajax( {
				url: ajaxurl, // this is a variable that WordPress has already defined for us
				type: 'post',
				async: false,
				cache: false,
				data: {
					action: 'save_bulk_post_views', // this is the name of our WP AJAX function that we'll set up next
					post_ids: postIds, // and these are the 2 parameters we're passing to our function
					post_views: postViews,
					current_post_views: postViews,
					nonce: pvcArgsQuickEdit.nonce
				}
			} );
		} );
	} );

} )( jQuery );
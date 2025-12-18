/**
 * Post Views Counter Admin Quick Edit JavaScript
 *
 * Extends WordPress quick edit functionality to include post views editing.
 * Handles both individual post quick edit and bulk edit operations.
 */

(( $ ) => {
	$( () => {
		const wpInlineEdit = inlineEditPost.edit;

		/**
		 * Override WordPress's inline edit function to include post views editing.
		 *
		 * @param {number|string|Object} id - The post ID or element identifier
		 */
		inlineEditPost.edit = function( id ) {
			wpInlineEdit.apply( this, arguments );

			let postId = 0;

			if ( typeof id === 'object' ) {
				postId = parseInt( this.getId( id ), 10 );
			}

			if ( postId > 0 ) {
				const editRow = $( `#edit-${ postId }` );
				const postRow = $( `#post-${ postId }` );
				const postViews = $( '.column-post_views', postRow ).text();

				$( ':input[name="post_views"]', editRow ).val( postViews );
				$( ':input[name="current_post_views"]', editRow ).val( postViews );
			}

			return false;
		};

		$( document ).on( 'click', '#bulk_edit', () => {
			const bulkRow = $( '#bulk-edit' );
			const postIds = [];

			if ( pvcArgsQuickEdit.wpVersion59 ) {
				bulkRow.find( '#bulk-titles-list' ).children( '.ntdelitem' ).each( function() {
					postIds.push( $( this ).find( 'button' ).attr( 'id' ).replace( /[^0-9]/i, '' ) );
				} );
			} else {
				bulkRow.find( '#bulk-titles' ).children().each( function() {
					postIds.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
				} );
			}

			const postViews = bulkRow.find( 'input[name="post_views"]' ).val();

			$.ajax( {
				url: ajaxurl,
				type: 'post',
				async: false,
				cache: false,
				data: {
					action: 'save_bulk_post_views',
					post_ids: postIds,
					post_views: postViews,
					current_post_views: postViews,
					nonce: pvcArgsQuickEdit.nonce,
				},
			} );
		} );
	} );
})( jQuery );

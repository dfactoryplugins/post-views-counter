/**
 * Post Views Counter Admin Post JavaScript
 *
 * Handles the inline post views editing functionality in the WordPress admin.
 * Provides UI for manually setting view counts on individual posts.
 */

(( $ ) => {
	$( () => {
		const $inputContainer = $( '#post-views-input-container' );
		const $postViewsDisplay = $( '#post-views-display b' );
		const $postViewsInput = $( '#post-views-input' );
		const $editTrigger = $( '#post-views .edit-post-views' );

		$editTrigger.on( 'click', ( event ) => {
			if ( $inputContainer.is( ':hidden' ) ) {
				$inputContainer.slideDown( 'fast' );
				$( event.currentTarget ).hide();
			}

			return false;
		} );

		$( '#post-views .save-post-views' ).on( 'click', () => {
			let views = $postViewsDisplay.text().trim();

			$inputContainer.slideUp( 'fast' );
			$editTrigger.show();

			views = parseInt( $postViewsInput.val(), 10 );
			$postViewsInput.val( views );
			$postViewsDisplay.text( views );

			return false;
		} );

		$( '#post-views .cancel-post-views' ).on( 'click', () => {
			let views = $postViewsDisplay.text().trim();

			$inputContainer.slideUp( 'fast' );
			$editTrigger.show();

			views = parseInt( $( '#post-views-current' ).val(), 10 );

			$postViewsDisplay.text( views );
			$postViewsInput.val( views );

			return false;
		} );
	} );
})( jQuery );

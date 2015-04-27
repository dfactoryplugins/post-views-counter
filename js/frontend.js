( function ( $ ) {

	$( document ).ready( function () {

		$.post( pvcArgsFrontend.ajaxURL, {
			action: 'pvc-check-post',
			pvc_nonce: pvcArgsFrontend.nonce,
			post_id: pvcArgsFrontend.postID,
			post_type: pvcArgsFrontend.postType
		} );

	} );

} )( jQuery );
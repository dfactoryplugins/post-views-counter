( function ( $ ) {

	$( document ).ready( function () {

		// rest api request
		if ( pvcArgsFrontend.mode == 'rest_api' ) {

			var request = {
				id: pvcArgsFrontend.postID
			};

			$.ajax( {
				url: pvcArgsFrontend.requestURL + '?id=' + pvcArgsFrontend.postID,
				type: 'post',
				async: true,
				cache: false,
				data: request,
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', pvcArgsFrontend.nonce );
				}
			} );

			// admin ajax or fast ajax request
		} else {

			var request = {
				action: 'pvc-check-post',
				pvc_nonce: pvcArgsFrontend.nonce,
				id: pvcArgsFrontend.postID
			};

			$.ajax( {
				url: pvcArgsFrontend.requestURL,
				type: 'post',
				async: true,
				cache: false,
				data: request
			} ).done( function( data ) {
			    if ( typeof data.post_id == 'undefined' || typeof data.count == 'undefined' ){
			        return;
                }
			    var $counter_container = $('.pvc-post-'+data.post_id+'-current');

			    if($counter_container.length){
                    $counter_container.html(data.count);
                }
            });

		}

	} );

} )( jQuery );
document.addEventListener( 'DOMContentLoaded', function() {
	PostViewsCounter = {
		/**
		 * Initialize counter.
		 *
		 * @param {object} args
		 *
		 * @return {void}
		 */
		init: function( args ) {
			// rest api request
			if ( args.mode === 'rest_api' ) {
				let options = {
					id: args.postID
				};
				let headers = {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
					'X-WP-Nonce': args.nonce
				};

				// request
				this.request( args.requestURL+ '?id=' + args.postID, options, 'POST', headers );
			// admin ajax request
			} else {
				let options = {
					action:	'pvc-check-post',
					pvc_nonce: args.nonce,
					id: args.postID
				};
				let headers = {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
				};

				// request
				this.request( args.requestURL, options, 'POST', headers );
			}
		},

		/**
		 * Handle fetch request.
		 *
		 * @param {string} url
		 * @param {object} params
		 * @param {string} method
		 * @param {object} headers
		 *
		 * @return {object}
		 */
		request: function( url, params, method, headers ) {
			let options = {
				method: method,
				mode: 'cors',
				cache: 'no-cache',
				credentials: 'same-origin',
				headers: headers,
				body: this.prepareData( params )
			};

			const _this = this;

			return fetch( url, options ).then( function( response ) {
				// invalid response?
				if ( ! response.ok )
					throw Error( response.statusText );

				return response.json();
			} ).then( function( response ) {
				try {
					if ( typeof response === 'object' && response !== null )
						_this.triggerEvent( 'pvcCheckPost', response );
				} catch( error ) {
					console.log( 'Invalid JSON data' );
					console.log( error );
				}
			} ).catch( function( error ) {
				console.log( 'Invalid response' );
				console.log( error );
			} );
		},

		/**
		 * Prepare the data to be sent with the request.
		 *
		 * @param {object} data
		 *
		 * @return {string}
		 */
		prepareData: function( data ) {
			return Object.keys( data ).map( function( el ) {
				// add extra "data" array
				return encodeURIComponent( el ) + '=' + encodeURIComponent( data[el] )
			} ).join( '&' ).replace( /%20/g, '+' );
		},

		/**
		 * Prepare the data to be sent with the request.
		 *
		 * @param {string} eventName
		 * @param {object} data
		 *
		 * @return {void}
		 */
		triggerEvent: function( eventName, data ) {
			const newEvent = new CustomEvent( eventName, {
				bubbles: true,
				detail: data
			} );

			// trigger event
			document.dispatchEvent( newEvent );
		}
	}

	PostViewsCounter.init( pvcArgsFrontend );
} );
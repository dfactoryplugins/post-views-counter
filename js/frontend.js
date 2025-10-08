var initPostViewsCounter = function() {
	PostViewsCounter = {
		promise: null,
		args: {},

		/**
		 * Initialize counter.
		 *
		 * @param {Object} args
		 * @return {void}
		 */
		init: function( args ) {
			this.args = args;

			// default parameters
			var params = {};

			// data storage
			params.storage_type = 'cookies';
			params.storage_data = this.readCookieData( 'pvc_visits' + ( args.multisite !== false ? '_' + parseInt( args.multisite ) : '' ) );

			// rest api request
			if ( args.mode === 'rest_api' ) {
				this.promise = this.request( args.requestURL, params, 'POST', {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
					'X-WP-Nonce': args.nonce
				} );
			// ajax request
			} else {
				params.action = 'pvc-check-post';
				params.pvc_nonce = args.nonce;
				params.id = args.postID;

				this.promise = this.request( args.requestURL, params, 'POST', {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
				} );
			}
		},

		/**
		 * Handle fetch request.
		 *
		 * @param {string} url
		 * @param {Object} params
		 * @param {string} method
		 * @param {Object} headers
		 * @return {Promise}
		 */
		request: function( url, params, method, headers ) {
			var options = {
				method: method,
				mode: 'cors',
				cache: 'no-cache',
				credentials: 'same-origin',
				headers: headers,
				body: this.prepareRequestData( params )
			};

			var _this = this;
			
			return fetch( url, options ).then( function( response ) {
				// invalid response?
				if ( ! response.ok )
					throw Error( response.statusText );

				return response.json();
			} ).then( function( response ) {
				try {
					if ( typeof response === 'object' && response !== null ) {
						if ( 'success' in response && response.success === false ) {
							console.log( 'PVC: Request error.' );
							console.log( response.data );
						} else {
							_this.saveCookieData( response.storage );

							_this.triggerEvent( 'pvcCheckPost', response );
						}
					} else {
						console.log( 'PVC: Invalid object.' );
						console.log( response );
					}
				} catch( error ) {
					console.log( 'PVC: Invalid JSON data.' );
					console.log( error );
				}
			} ).catch( function( error ) {
				console.log( 'PVC: Invalid response.' );
				console.log( error );
			} );
		},

		/**
		 * Prepare the data to be sent with the request.
		 *
		 * @param {Object} data
		 * @return {string}
		 */
		prepareRequestData: function( data ) {
			return Object.keys( data ).map( function( el ) {
				// add extra "data" array
				return encodeURIComponent( el ) + '=' + encodeURIComponent( data[el] );
			} ).join( '&' ).replace( /%20/g, '+' );
		},

		/**
		 * Trigger a custom DOM event.
		 *
		 * @param {string} eventName
		 * @param {Object} data
		 * @return {void}
		 */
		triggerEvent: function( eventName, data ) {
			var newEvent = new CustomEvent( eventName, {
				bubbles: true,
				detail: data
			} );

			// trigger event
			document.dispatchEvent( newEvent );
		},

		/**
		 * Save cookies.
		 *
		 * @param {Object} data
		 * @return {void}
		 */
		saveCookieData: function( data ) {
			// empty storage? nothing to save
			if ( ! data.hasOwnProperty( 'name' ) )
				return;

			var cookieSecure = '';

			// ssl?
			if ( document.location.protocol === 'https:' )
				cookieSecure = ';secure';

			for ( var i = 0; i < data.name.length; i++ ) {
				var cookieDate = new Date();
				var expiration = parseInt( data.expiry[i] );

				// valid expiration timestamp?
				if ( expiration )
					expiration = expiration * 1000;
				// add default 24 hours
				else
					expiration = cookieDate.getTime() + 86400000;

				// set cookie date expiry
				cookieDate.setTime( expiration );

				// set cookie
				document.cookie = data.name[i] + '=' + data.value[i] + ';expires=' + cookieDate.toUTCString() + ';path=/' + ( this.args.path === '/' ? '' : this.args.path ) + ';domain=' + this.args.domain + cookieSecure + ';SameSite=Lax';
			}
		},

		/**
		 * Read cookies.
		 *
		 * @param {string} name
		 * @return {string}
		 */
		readCookieData: function( name ) {
			var cookies = [];

			document.cookie.split( ';' ).forEach( function( el ) {
				var parts = el.split( '=' );
				var key = parts[0];
				var value = parts[1];
				var trimmedKey = key.trim();
				var regex = new RegExp( name + '\\[\\d+\\]' );

				// look all cookies starts with name parameter
				if ( regex.test( trimmedKey ) )
					cookies.push( value );
			} );

			return cookies.join( 'a' );
		}
	}

	PostViewsCounter.init( pvcArgsFrontend );
}

document.addEventListener( 'DOMContentLoaded', initPostViewsCounter );
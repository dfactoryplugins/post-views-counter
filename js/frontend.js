var initPostViewsCounter = function() {
	PostViewsCounter = {
		promise: null,
		args: {},

		/**
		 * Initialize counter.
		 *
		 * @param {object} args
		 *
		 * @return {void}
		 */
		init: function( args ) {
			this.args = args;

			// default parameters
			let params = {};

			// set cookie/storage name
			let name = 'pvc_visits' + ( args.multisite !== false ? '_' + parseInt( args.multisite ) : '' );

			// cookieless data storage?
			if ( args.dataStorage === 'cookieless' && this.isLocalStorageAvailable() ) {
				params.storage_type = 'cookieless';
				params.storage_data = this.readStorageData( name );

				/* COUNT_POST_AS_AUTHOR_VIEW | removed parameter from request
				if ( 'countAuthor' in args && args.countAuthor === true ) {
					params.storage_data_author = this.readStorageData( 'pvc_visits_user' + ( args.multisite !== false ? '_' + parseInt( args.multisite ) : '' ) );
				}
				*/
			} else {
				params.storage_type = 'cookies';
				params.storage_data = this.readCookieData( name );

				/* COUNT_POST_AS_AUTHOR_VIEW | removed parameter from request
				if ( 'countAuthor' in args && args.countAuthor === true ) {
					params.storage_data_author = this.readCookieData( 'pvc_visits_user' + ( args.multisite !== false ? '_' + parseInt( args.multisite ) : '' ) );
				}
				*/
			}

			// rest api request
			if ( args.mode === 'rest_api' ) {
				this.promise = this.request( args.requestURL, params, 'POST', {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
					'X-WP-Nonce': args.nonce
				}, name );
			// admin ajax request
			} else {
				params.action = 'pvc-check-post';
				params.pvc_nonce = args.nonce;
				params.id = args.postID;

				this.promise = this.request( args.requestURL, params, 'POST', {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
				}, name );
			}
		},

		/**
		 * Handle fetch request.
		 *
		 * @param {string} url
		 * @param {object} params
		 * @param {string} method
		 * @param {object} headers
		 * @param {string} name
		 *
		 * @return {object}
		 */
		request: function( url, params, method, headers, name = '' ) {
			let options = {
				method: method,
				mode: 'cors',
				cache: 'no-cache',
				credentials: 'same-origin',
				headers: headers,
				body: this.prepareRequestData( params )
			};

			const _this = this;

			return fetch( url, options ).then( function( response ) {
				// invalid response?
				if ( ! response.ok )
					throw Error( response.statusText );

				return response.json();
			} ).then( function( response ) {
				try {
					if ( typeof response === 'object' && response !== null ) {
						if ( _this.args.dataStorage === 'cookieless' )
							_this.saveStorageData.call( _this, name, response.storage, response.type );
						else
							_this.saveCookieData( name, response.storage );

						_this.triggerEvent( 'pvcCheckPost', response );
					}
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
		prepareRequestData: function( data ) {
			return Object.keys( data ).map( function( el ) {
				// add extra "data" array
				return encodeURIComponent( el ) + '=' + encodeURIComponent( data[el] );
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
		},

		/**
		 * Save localStorage data.
		 *
		 * @param {string} name
		 * @param {object} data
		 * @param {string} type
		 *
		 * @return {void}
		 */
		saveStorageData: function( name, data, type ) {
			/* COUNT_POST_AS_AUTHOR_VIEW | removed setting localStorage user data
			if ( 'countAuthor' in this.args && this.args.countAuthor === true && 'user' in data )
				window.localStorage.setItem( 'pvc_visits_user' + ( this.args.multisite !== false ? '_' + parseInt( this.args.multisite ) : '' ), JSON.stringify( data['user'] ) );
			*/

			window.localStorage.setItem( name, JSON.stringify( data[type] ) );
		},

		/**
		 * Read localStorage data.
		 *
		 * @param {string} name
		 *
		 * @return {string}
		 */
		readStorageData: function( name ) {
			let data = null;

			// get data
			data = window.localStorage.getItem( name );

			// no data?
			if ( data === null )
				data = '';

			return data;
		},

		/**
		 * Save cookies.
		 *
		 * @param {string} name
		 * @param {object} data
		 *
		 * @return {void}
		 */
		saveCookieData: function( name, data ) {
			var cookieSecure = '';

			// ssl?
			if ( document.location.protocol === 'https:' )
				cookieSecure = ';secure';

			for ( let i = 0; i < data.name.length; i++ ) {
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
		 *
		 * @return {string}
		 */
		readCookieData: function( name ) {
			var cookies = [];

			document.cookie.split( ';' ).forEach( function( el ) {
				var [key, value] = el.split( '=' );
				var trimmedKey = key.trim();
				var regex = new RegExp( name + '\\[\\d+\\]' );

				// look all cookies starts with name parameter
				if ( regex.test( trimmedKey ) )
					cookies.push( value );
			} );

			return cookies.join( 'a' );
		},

		/**
		 * Check whether localStorage is available.
		 *
		 * @return {bool}
		 */
		isLocalStorageAvailable: function() {
			var storage;

			try {
				storage = window['localStorage'];

				storage.setItem( '__pvcStorageTest', 0 );
				storage.removeItem( '__pvcStorageTest' );

				return true;
			} catch( e ) {
				return e instanceof DOMException && ( e.code === 22 || e.code === 1014 || e.name === 'QuotaExceededError' || e.name === 'NS_ERROR_DOM_QUOTA_REACHED' ) && ( storage && storage.length !== 0 );
			}
		}
	}

	PostViewsCounter.init( pvcArgsFrontend );
}

document.addEventListener( 'DOMContentLoaded', initPostViewsCounter );
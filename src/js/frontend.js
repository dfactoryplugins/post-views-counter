/**
 * Post Views Counter Frontend JavaScript
 *
 * Handles the frontend view counting functionality for Post Views Counter plugin.
 * Manages AJAX/REST API requests to track post views and cookie storage.
 */

/**
 * Initialize the Post Views Counter on page load.
 *
 * Sets up the PostViewsCounter object and triggers the view counting request
 * when the DOM is ready.
 *
 * @return {void}
 */
window.PostViewsCounter = {
	promise: null,
	args: {},

	/**
	 * Initialize counter.
	 *
	 * @param {Object} args
	 * @return {void}
	 */
	init( args ) {
		this.args = args;

		const params = {
			storage_type: 'cookies',
			storage_data: this.readCookieData( `pvc_visits${ args.multisite !== false ? `_${ parseInt( args.multisite, 10 ) }` : '' }` ),
		};

		if ( args.mode === 'rest_api' ) {
			this.promise = this.request( args.requestURL, params, 'POST', {
				'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
				'X-WP-Nonce': args.nonce,
			} );
		} else {
			params.action = 'pvc-check-post';
			params.pvc_nonce = args.nonce;
			params.id = args.postID;

			this.promise = this.request( args.requestURL, params, 'POST', {
				'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
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
	request( url, params, method, headers ) {
		const options = {
			method,
			mode: 'cors',
			cache: 'no-cache',
			credentials: 'same-origin',
			headers,
			body: this.prepareRequestData( params ),
		};

		return fetch( url, options )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw Error( response.statusText );
				}

				return response.json();
			} )
			.then( ( response ) => {
				try {
					if ( typeof response === 'object' && response !== null ) {
						if ( 'success' in response && response.success === false ) {
							console.log( 'PVC: Request error.' );
							console.log( response.data );
						} else {
							this.saveCookieData( response.storage );
							this.triggerEvent( 'pvcCheckPost', response );
						}
					} else {
						console.log( 'PVC: Invalid object.' );
						console.log( response );
					}
				} catch ( error ) {
					console.log( 'PVC: Invalid JSON data.' );
					console.log( error );
				}
			} )
			.catch( ( error ) => {
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
	prepareRequestData( data ) {
		return Object.keys( data ).map( ( key ) => `${ encodeURIComponent( key ) }=${ encodeURIComponent( data[key] ) }` ).join( '&' ).replace( /%20/g, '+' );
	},

	/**
	 * Trigger a custom DOM event.
	 *
	 * @param {string} eventName
	 * @param {Object} data
	 * @return {void}
	 */
	triggerEvent( eventName, data ) {
		const newEvent = new CustomEvent( eventName, {
			bubbles: true,
			detail: data,
		} );

		document.dispatchEvent( newEvent );
	},

	/**
	 * Save cookies.
	 *
	 * @param {Object} data
	 * @return {void}
	 */
	saveCookieData( data ) {
		if ( ! data || ! Object.prototype.hasOwnProperty.call( data, 'name' ) ) {
			return;
		}

		let cookieSecure = '';

		if ( document.location.protocol === 'https:' ) {
			cookieSecure = ';secure';
		}

		for ( let i = 0; i < data.name.length; i++ ) {
			const cookieDate = new Date();
			let expiration = parseInt( data.expiry[i], 10 );

			if ( expiration ) {
				expiration *= 1000;
			} else {
				expiration = cookieDate.getTime() + 86400000;
			}

			cookieDate.setTime( expiration );

			document.cookie = `${ data.name[i] }=${ data.value[i] };expires=${ cookieDate.toUTCString() };path=/${ this.args.path === '/' ? '' : this.args.path };domain=${ this.args.domain }${ cookieSecure };SameSite=Lax`;
		}
	},

	/**
	 * Read cookies.
	 *
	 * @param {string} name
	 * @return {string}
	 */
	readCookieData( name ) {
		const cookies = [];

		document.cookie.split( ';' ).forEach( ( el ) => {
			const [key, value] = el.split( '=' );
			const trimmedKey = key.trim();
			const regex = new RegExp( `${ name }\\[\\d+\\]` );

			if ( regex.test( trimmedKey ) ) {
				cookies.push( value );
			}
		} );

		return cookies.join( 'a' );
	},
};

const initPostViewsCounter = () => window.PostViewsCounter.init( pvcArgsFrontend );

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initPostViewsCounter );
} else {
	initPostViewsCounter();
}

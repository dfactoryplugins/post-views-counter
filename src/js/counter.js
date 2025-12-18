/**
 * Post Views Counter Manual JavaScript
 *
 * Provides manual view counting functionality for Post Views Counter plugin.
 * Allows triggering view updates for specific posts via AJAX requests.
 */

window.PostViewsCounterManual = {
	/**
	 * Initialize counter.
	 *
	 * @param {object} args
	 *
	 * @return {Promise}
	 */
	init( args ) {
		const params = {
			action: 'pvc-view-posts',
			pvc_nonce: args.nonce,
			ids: args.ids,
		};

		const requestBody = Object.keys( params ).map( ( key ) => `${ encodeURIComponent( key ) }=${ encodeURIComponent( params[key] ) }` ).join( '&' ).replace( /%20/g, '+' );

		return fetch( args.url, {
			method: 'POST',
			mode: 'cors',
			cache: 'no-cache',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
			},
			body: requestBody,
		} ).then( ( response ) => {
			if ( ! response.ok ) {
				throw Error( response.statusText );
			}

			return response.json();
		} ).then( ( response ) => {
			try {
				if ( typeof response === 'object' && response !== null ) {
					this.triggerEvent( 'pvcCheckPost', response );
				}
			} catch ( error ) {
				console.log( 'Invalid JSON data' );
				console.log( error );
			}
		} ).catch( ( error ) => {
			console.log( 'Invalid response' );
			console.log( error );
		} );
	},

	/**
	 * Trigger custom event with provided data.
	 *
	 * @param {string} eventName
	 * @param {object} data
	 *
	 * @return {void}
	 */
	triggerEvent( eventName, data ) {
		const newEvent = new CustomEvent( eventName, {
			bubbles: true,
			detail: data,
		} );

		document.dispatchEvent( newEvent );
	},
};

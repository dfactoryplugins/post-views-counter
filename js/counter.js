PostViewsCounterManual = {
	/**
	 * Initialize counter.
	 *
	 * @param {object} args
	 *
	 * @return {void}
	 */
	init: function( args ) {
		let params = {
			action:	'pvc-view-posts',
			pvc_nonce: args.nonce,
			ids: args.ids
		};

		let newParams = Object.keys( params ).map( function( el ) {
			// add extra "data" array
			return encodeURIComponent( el ) + '=' + encodeURIComponent( params[el] );
		} ).join( '&' ).replace( /%20/g, '+' );

		const _this = this;

		return fetch( args.url, {
			method: 'POST',
			mode: 'cors',
			cache: 'no-cache',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
			},
			body: newParams
		} ).then( function( response ) {
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
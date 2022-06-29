document.addEventListener( 'DOMContentLoaded', function() {

	const request = ( url, params = {}, method = 'GET', headers = {} ) => {
		let options = {
			method,
			mode: 'cors',
			cache: 'no-cache',
			credentials: 'same-origin',
			headers: headers
		};

		if ( 'GET' === method ) {
			url += '?' + ( new URLSearchParams( params ) ).toString();
		} else {
			options.body = new URLSearchParams( params );
		}

		return fetch( url, options ).then( function( response ) {
			if ( !response.ok ) {
				throw Error( response.statusText );
			}
			return response.json();
		} );
	}

	const get = ( url, params, headers ) => request( url, params, 'GET', headers );
	const post = ( url, params, headers ) => request( url, params, 'POST', headers );

	// rest api request
	if ( pvcArgsFrontend.mode === 'rest_api' ) {
		let url = pvcArgsFrontend.requestURL + '?id=' + pvcArgsFrontend.postID + '&_wpnonce=' + pvcArgsFrontend.nonce;
		let params = {};
		let headers = {
			'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
		};

		post( url, params, headers )
			.then( function( response ) {

				let data = null;

				if ( typeof response === 'object' )
					data = response;

				// trigger pvcCheckPost event
				if ( data !== null ) {
					const event = new CustomEvent( 'pvcCheckPost', {
						bubbles: true,
						detail: data
					} );

					document.dispatchEvent( event );
				}

				console.log( data );

				return data;
			} ).catch( function( error ) {
				// display error in the console
				console.log( error );
			} ).finally( function() {
				// do sth
			} );

		// admin ajax request
	} else {

		let url = pvcArgsFrontend.requestURL;
		let params = {
			action: 'pvc-check-post',
			pvc_nonce: pvcArgsFrontend.nonce,
			id: pvcArgsFrontend.postID
		};
		let headers = {
			'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
		};

		post( url, params, headers )
			.then( function( response ) {

				let data = null;

				if ( typeof response === 'object' )
					data = response;

				// trigger pvcCheckPost event
				if ( data !== null ) {
					const event = new CustomEvent( 'pvcCheckPost', {
						bubbles: true,
						detail: data
					} );

					document.dispatchEvent( event );
				}

				// console.log( data );

				return data;
			} ).catch( function( error ) {
				// display error in the console
				console.log( error );
			} ).finally( function() {
				// do sth
			} );
	}
} );
/**
 * Post Views Counter Admin Dashboard JavaScript
 *
 * Handles the admin dashboard widgets and AJAX functionality for Post Views Counter.
 * Manages collapsible widgets, data updates, and user preferences.
 */

(( $ ) => {
	window.addEventListener( 'load', () => {
		window.pvcUpdatePostViewsWidget();
		window.pvcUpdatePostMostViewedWidget();
	} );

	$( () => {
		$( '.pvc-accordion-header' ).on( 'click', ( event ) => {
			const $item = $( event.currentTarget ).closest( '.pvc-accordion-item' );
			$item.toggleClass( 'pvc-collapsed' );

			const items = $( '#pvc-dashboard-accordion' ).find( '.pvc-accordion-item' );
			const menuItems = {};

			if ( items.length > 0 ) {
				$( items ).each( ( index, item ) => {
					let itemName = $( item ).attr( 'id' );

					itemName = itemName.replace( 'pvc-', '' );

					menuItems[itemName] = $( item ).hasClass( 'pvc-collapsed' );
				} );
			}

			window.pvcUpdateUserOptions( { menu_items: menuItems } );
		} );
	} );

	const pvcAjaxQueue = $( {} );

	$.pvcAjaxQueue = ( ajaxOpts ) => {
		let jqXHR;
		const dfd = $.Deferred();
		const promise = dfd.promise();

		const doRequest = ( next ) => {
			jqXHR = $.ajax( ajaxOpts );
			jqXHR.done( dfd.resolve )
				.fail( dfd.reject )
				.then( next, next );
		};

		pvcAjaxQueue.queue( doRequest );

		promise.abort = ( statusText ) => {
			if ( jqXHR ) {
				return jqXHR.abort( statusText );
			}

			const queue = pvcAjaxQueue.queue();
			const index = $.inArray( doRequest, queue );

			if ( index > -1 ) {
				queue.splice( index, 1 );
			}

			dfd.rejectWith( ajaxOpts.context || ajaxOpts, [promise, statusText, ''] );
			return promise;
		};

		return promise;
	};

	window.pvcUpdateUserOptions = ( options ) => {
		$.pvcAjaxQueue( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_user_options',
				nonce: pvcArgs.nonceUser,
				options,
			},
			success: () => {},
		} );
	};

	window.pvcUpdateConfig = ( config, args ) => {
		config.data = args.data;

		config.options.plugins.tooltip = {
			callbacks: {
				title( tooltip ) {
					return args.data.dates[tooltip[0].dataIndex];
				},
			},
		};

		$.each( config.data.datasets, ( i, dataset ) => {
			dataset.fill = args.design.fill;
			dataset.tension = 0.4;
			dataset.borderColor = args.design.borderColor;
			dataset.backgroundColor = args.design.backgroundColor;
			dataset.borderWidth = args.design.borderWidth;
			dataset.borderDash = args.design.borderDash;
			dataset.pointBorderColor = args.design.pointBorderColor;
			dataset.pointBackgroundColor = args.design.pointBackgroundColor;
			dataset.pointBorderWidth = args.design.pointBorderWidth;
		} );

		return config;
	};

	const pvcGetPostMostViewedData = ( init, period, container ) => {
		$( container ).addClass( 'loading' ).find( '.spinner' ).addClass( 'is-active' );

		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_post_most_viewed',
				nonce: pvcArgs.nonce,
				period,
				lang: pvcArgs.lang ? pvcArgs.lang : '',
			},
			success( response ) {
				$( container ).removeClass( 'loading' );
				$( container ).find( '.spinner' ).removeClass( 'is-active' );

				if ( ! init ) {
					pvcBindDateEvents( response.dates, container );
				}

				$( container ).find( '#pvc-post-most-viewed-content' ).html( response.html );

				pvcTriggerEvent( 'pvc-dashboard-widget-loaded', response );
			},
		} );
	};

	const pvcGetPostViewsData = ( init, period, container ) => {
		$( container ).addClass( 'loading' ).find( '.spinner' ).addClass( 'is-active' );

		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_post_views_chart',
				nonce: pvcArgs.nonce,
				period,
				lang: pvcArgs.lang ? pvcArgs.lang : '',
			},
			success( response ) {
				$( container ).removeClass( 'loading' );
				$( container ).find( '.spinner' ).removeClass( 'is-active' );

				if ( init ) {
					let config = {
						type: 'line',
						options: {
							maintainAspectRatio: false,
							responsive: true,
							plugins: {
								legend: {
									display: true,
									position: 'bottom',
									align: 'center',
									fullSize: true,
									onHover( e ) {
										e.native.target.style.cursor = 'pointer';
									},
									onLeave( e ) {
										e.native.target.style.cursor = 'default';
									},
									onClick( e, element, legend ) {
										const index = element.datasetIndex;
										const ci = legend.chart;
										const meta = ci.getDatasetMeta( index );

										meta.hidden = ci.isDatasetVisible( index ) ? true : false;

										ci.update();

										window.pvcUpdateUserOptions( {
											post_type: ci.data.datasets[index].post_type,
											hidden: meta.hidden,
										} );
									},
									labels: {
										boxWidth: 8,
										boxHeight: 8,
										font: {
											size: 13,
											weight: 'normal',
											family: "'-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Oxygen-Sans', 'Ubuntu', 'Cantarell', 'Helvetica Neue', 'sans-serif'",
										},
										padding: 10,
										usePointStyle: false,
										textAlign: 'center',
									},
								},
							},
							scales: {
								x: {
									display: true,
									title: {
										display: false,
									},
								},
								y: {
									display: true,
									grace: 0,
									beginAtZero: true,
									title: {
										display: false,
									},
									ticks: {
										precision: 0,
										maxTicksLimit: 12,
									},
								},
							},
							hover: {
								mode: 'label',
							},
						},
					};

					config = window.pvcUpdateConfig( config, response );

					window.postViewsChart = new Chart( document.getElementById( 'pvc-post-views-chart' ).getContext( '2d' ), config );
				} else {
					pvcBindDateEvents( response.dates, container );

					window.postViewsChart.config = window.pvcUpdateConfig( window.postViewsChart.config, response );
					window.postViewsChart.update();
				}

				pvcTriggerEvent( 'pvc-dashboard-widget-loaded', response );
			},
		} );
	};

	window.pvcUpdatePostViewsWidget = ( period = '' ) => {
		const container = $( '#pvc-post-views' ).find( '.pvc-dashboard-container' );

		if ( $( container ).length > 0 ) {
			pvcBindDateEvents( false, container );
			pvcGetPostViewsData( true, period, container );
		}
	};

	window.pvcUpdatePostMostViewedWidget = ( period = '' ) => {
		const container = $( '#pvc-post-most-viewed' ).find( '.pvc-dashboard-container' );

		if ( $( container ).length > 0 ) {
			pvcBindDateEvents( false, container );
			pvcGetPostMostViewedData( true, period, container );
		}
	};

	const pvcBindDateEvents = ( newDates, container ) => {
		const dates = $( container ).find( '.pvc-date-nav' );
		const nav = dates[0];

		if ( ! nav ) {
			return;
		}

		if ( newDates !== false ) {
			nav.innerHTML = newDates;
		}

		const prev = nav.getElementsByClassName( 'prev' )[0];
		const next = nav.getElementsByClassName( 'next' )[0];
		const id = $( container ).closest( '.pvc-accordion-item' ).attr( 'id' );

		if ( id === 'pvc-post-most-viewed' ) {
			prev.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				pvcLoadPostMostViewedData( e.target.dataset.date );
			} );
		} else if ( id === 'pvc-post-views' ) {
			prev.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				pvcLoadPostViewsData( e.target.dataset.date );
			} );
		}

		if ( next.tagName === 'A' ) {
			if ( id === 'pvc-post-most-viewed' ) {
				next.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					pvcLoadPostMostViewedData( e.target.dataset.date );
				} );
			} else if ( id === 'pvc-post-views' ) {
				next.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					pvcLoadPostViewsData( e.target.dataset.date );
				} );
			}
		}
	};

	const pvcLoadPostViewsData = ( period = '' ) => {
		const container = $( '#pvc-post-views' ).find( '.pvc-dashboard-container' );

		pvcGetPostViewsData( false, period, container );
	};

	const pvcLoadPostMostViewedData = ( period = '' ) => {
		const container = $( '#pvc-post-most-viewed' ).find( '.pvc-dashboard-container' );

		pvcGetPostMostViewedData( false, period, container );
	};

	const pvcTriggerEvent = ( name, response ) => {
		const remove = ['dates', 'html', 'design'];

		remove.forEach( ( prop ) => {
			delete response[prop];
		} );

		const event = new CustomEvent( name, {
			detail: response,
		} );

		window.dispatchEvent( event );
	};
})( jQuery );

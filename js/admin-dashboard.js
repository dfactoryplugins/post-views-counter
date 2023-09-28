( function( $ ) {

	/**
	 * Load initial data.
	 */
	window.addEventListener( 'load', function() {
		updatePostViewsWidget( 'this_month' );
		updatePostMostViewedWidget( 'this_month' );
	} );

	/**
	 * Ready event.
	 */
	$( function() {
		// toggle collapse items
		$( '.pvc-accordion-header' ).on( 'click', function( e ) {
			$( this ).closest( '.pvc-accordion-item' ).toggleClass( 'pvc-collapsed' );

			var items = $( '#pvc-dashboard-accordion' ).find( '.pvc-accordion-item' );
			var menuItems = {};

			if ( items.length > 0 ) {
				$( items ).each( function( index, item ) {
					var itemName = $( item ).attr( 'id' );

					itemName = itemName.replace( 'pvc-', '' );

					menuItems[itemName] = $( item ).hasClass( 'pvc-collapsed' );
				} );
			}

			// update user options
			pvcUpdateUserOptions( { menu_items: menuItems } );
		} );
	} );

	/**
	 * Update user options.
	 */
	pvcUpdateUserOptions = function( options ) {
		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_user_options',
				nonce: pvcArgs.nonceUser,
				options: options
			},
			success: function() {}
		} );
	}

	/**
	 * Update configuration.
	 */
	pvcUpdateConfig = function( config, args ) {
		// update datasets
		config.data = args.data;

		// update tooltips with new dates
		config.options.plugins.tooltip = {
			callbacks: {
				title: function( tooltip ) {
					return args.data.dates[tooltip[0].dataIndex];
				}
			}
		};

		// update colors
		$.each( config.data.datasets, function( i, dataset ) {
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
	}

	/**
	 * Get post most viewed data.
	 */
	function getPostMostViewedData( init, period, container ) {
		$( container ).addClass( 'loading' ).find( '.spinner' ).addClass( 'is-active' );

		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_post_most_viewed',
				nonce: pvcArgs.nonce,
				period: period
			},
			success: function( response ) {
				// remove loader
				$( container ).removeClass( 'loading' );
				$( container ).find( '.spinner' ).removeClass( 'is-active' );

				// next call?
				if ( ! init )
					bindMonthEvents( response.months, container );

				$( container ).find( '#pvc-post-most-viewed-content' ).html( response.html );
			}
		} );
	}

	/**
	 * Get post views data.
	 */
	function getPostViewsData( init, period, container ) {
		$( container ).addClass( 'loading' ).find( '.spinner' ).addClass( 'is-active' );

		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_post_views_chart',
				nonce: pvcArgs.nonce,
				period: period
			},
			success: function( response ) {
				// remove loader
				$( container ).removeClass( 'loading' );
				$( container ).find( '.spinner' ).removeClass( 'is-active' );

				// first call?
				if ( init ) {
					var config = {
						type: 'line',
						options: {
							maintainAspectRatio: true,
							responsive: true,
							plugins: {
								legend: {
									display: true,
									position: 'bottom',
									align: 'center',
									fullSize: true,
									onHover: function( e ) {
										e.native.target.style.cursor = 'pointer';
									},
									onLeave: function( e ) {
										e.native.target.style.cursor = 'default';
									},
									onClick: function( e, element, legend ) {
										var index = element.datasetIndex;
										var ci = legend.chart;
										var meta = ci.getDatasetMeta( index );

										// set new hidden value
										if ( ci.isDatasetVisible( index ) )
											meta.hidden = true;
										else
											meta.hidden = false;

										// rerender the chart
										ci.update();

										// update user options
										pvcUpdateUserOptions( {
											post_type: ci.data.datasets[index].post_type,
											hidden: meta.hidden
										} );
									},
									labels: {
										boxWidth: 8,
										boxHeight: 8,
										font: {
											size: 13,
											weight: 'normal',
											family: "'-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Oxygen-Sans', 'Ubuntu', 'Cantarell', 'Helvetica Neue', 'sans-serif'"
										},
										padding: 10,
										usePointStyle: false,
										textAlign: 'center'
									}
								}
							},
							scales: {
								x: {
									display: true,
									title: {
										display: false
									}
								},
								y: {
									display: true,
									grace: 0,
									beginAtZero: true,
									title: {
										display: false
									},
									ticks: {
										precision: 0,
										maxTicksLimit: 12
									}
								}
							},
							hover: {
								mode: 'label'
							}
						}
					};

					config = pvcUpdateConfig( config, response );

					window.pvcPostViewsChart = new Chart( document.getElementById( 'pvc-post-views-chart' ).getContext( '2d' ), config );
				} else {
					bindMonthEvents( response.months, container );

					window.pvcPostViewsChart.config = pvcUpdateConfig( window.pvcPostViewsChart.config, response );
					window.pvcPostViewsChart.update();
				}
			}
		} );
	}

	/**
	 * Update post views widget.
	 */
	function updatePostViewsWidget( period ) {
		var container = $( '#pvc-post-views' ).find( '.pvc-dashboard-container' );

		if ( $( container ).length > 0 ) {
			bindMonthEvents( false, container );

			getPostViewsData( true, period, container );
		}
	}

	/**
	 * Update post most viewed widget.
	 */
	function updatePostMostViewedWidget( period ) {
		var container = $( '#pvc-post-most-viewed' ).find( '.pvc-dashboard-container' );

		if ( $( container ).length > 0 ) {
			bindMonthEvents( false, container );

			getPostMostViewedData( true, period, container );
		}
	}

	/**
	 * Bind month events.
	 */
	function bindMonthEvents( newMonths, container ) {
		var months = $( container ).find( '.pvc-months' );

		// replace months?
		if ( newMonths !== false )
			months[0].innerHTML = newMonths;

		var prev = months[0].getElementsByClassName( 'prev' )[0];
		var next = months[0].getElementsByClassName( 'next' )[0];
		var id = $( container ).closest( '.pvc-accordion-item' ).attr( 'id' );

		if ( id === 'pvc-post-most-viewed' )
			prev.addEventListener( 'click', loadPostMostViewedData );
		else if ( id === 'pvc-post-views' )
			prev.addEventListener( 'click', loadPostViewsData );

		// skip span
		if ( next.tagName === 'A' ) {
			if ( id === 'pvc-post-most-viewed' )
				next.addEventListener( 'click', loadPostMostViewedData );
			else if ( id === 'pvc-post-views' )
				next.addEventListener( 'click', loadPostViewsData );
		}
	}

	/**
	 * Load post views data.
	 */
	function loadPostViewsData( e ) {
		e.preventDefault();

		var container = $( '#pvc-post-views' ).find( '.pvc-dashboard-container' );

		getPostViewsData( false, e.target.dataset.date, container );
	}

	/**
	 * Load post most viewed data.
	 */
	function loadPostMostViewedData( e ) {
		e.preventDefault();

		var container = $( '#pvc-post-most-viewed' ).find( '.pvc-dashboard-container' );

		getPostMostViewedData( false, e.target.dataset.date, container );
	}

} )( jQuery );
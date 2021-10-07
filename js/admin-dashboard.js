( function ( $ ) {

	window.onload = function () {
		pvcUpdateChart( 'this_month' );
		pvcUpdateMostViewed( 'this_month' );
	};
	
	// ready event
	$( function() {
		$( '.pvc-accordion-header' ).on( 'click', function( e ) {
			$( this ).closest( '.pvc-accordion-item' ).toggleClass( 'pvc-collapsed' );
			
			var items = $( '#pvc-dashboard-accordion' ).find( '.pvc-accordion-item' ),
				menuItems = {};

			if ( items.length > 0 ) {
				$( items ).each( function( index, item ) {
					var itemName = $( item ).prop( 'id' );
					itemName = itemName.replace( 'pvc-', '' );
					
					menuItems[itemName] = $( item ).hasClass( 'pvc-collapsed' );
				} );
			}
			
			// update user options
			var userOptions = {
				menu_items: menuItems
			};

			pvcUpdateUserOptions( userOptions );
		} );
	} );
	
	function pvcGetViewedData( init, period, container ) {
		$( container ).addClass( 'loading' ).find( '.spinner' ).addClass( 'is-active' );
		
		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_most_viewed',
				nonce: pvcArgs.nonce,
				period: period
			},
			success: function ( response ) {		
				// remove loader
				$( container ).removeClass( 'loading' );
				$( container ).find( '.spinner' ).removeClass( 'is-active' );

				// first call?
				if ( init ) {
					$( container ).find( '#pvc-viewed' ).html( response.html );
				} else {
					pvcBindMonthEvents( response.months, container );
					
					$( container ).find( '#pvc-viewed' ).html( response.html );
				}
			}
		} );
	}

	function pvcGetChartData( init, period, container ) {
		$( container ).addClass( 'loading' ).find( '.spinner' ).addClass( 'is-active' );
		
		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_chart',
				nonce: pvcArgs.nonce,
				period: period
			},
			success: function ( response ) {		
				// remove loader
				$( container ).removeClass( 'loading' );
				$( container ).find( '.spinner' ).removeClass( 'is-active' );
					
				// first call?
				if ( init ) {
					var config = {
						type: 'line',
						options: {
							responsive: true,
							legend: {
								display: true,
								position: 'bottom',
								onClick: function( e, element ) {
									var index = element.datasetIndex,
										ci = this.chart,
										meta = ci.getDatasetMeta( index );

									// set new hidden value
									meta.hidden = ( meta.hidden === null ? ! ci.data.datasets[index].hidden : null );

									// rerender the chart
									ci.update();
									
									// update user options
									var userOptions = {
										post_type: ci.data.datasets[index].post_type,
										hidden: meta.hidden === null ? false : meta.hidden
									}
									
									pvcUpdateUserOptions( userOptions );
								},
								labels: {
									boxWidth: 0,
									fontSize: 12,
									padding: 10,
									usePointStyle: false
								}
							},
							scales: {
								xAxes: [ {
									display: true,
									scaleLabel: {
										display: false,
										labelString: response.text.xAxes
									}
								} ],
								yAxes: [ {
									display: true,
									scaleLabel: {
										display: false,
										labelString: response.text.yAxes
									},
									ticks: {
										precision: 0,
										beginAtZero: true,
										maxTicksLimit: 12
									}
								} ]
							},
							hover: {
								mode: 'label'
							}
						}
					};

					config = pvcUpdateConfig( config, response );

					window.chartPVC = new Chart( document.getElementById( 'pvc-chart' ).getContext( '2d' ), config );
				} else {
					pvcBindMonthEvents( response.months, container );

					window.chartPVC.config = pvcUpdateConfig( window.chartPVC.config, response );
					window.chartPVC.update();
				}
			}
		} );
	}

	function pvcUpdateUserOptions( options ) {
		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_user_options',
				nonce: pvcArgs.nonceUser,
				options: options
			},
			success: function ( ) {}
		} );
	}

	function pvcUpdateConfig( config, args ) {
		// update datasets
		config.data = args.data;

		// update tooltips with new dates
		config.options.tooltips = {
			callbacks: {
				title: function ( tooltip ) {
					return args.data.dates[tooltip[0].index];
				}
			}
		};

		// update labels
		config.options.scales.xAxes[0].scaleLabel.labelString = args.text.xAxes;
		config.options.scales.yAxes[0].scaleLabel.labelString = args.text.yAxes;

		// update colors
		$.each( config.data.datasets, function ( i, dataset ) {
			dataset.fill = args.design.fill;
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

	function pvcUpdateChart( period ) {
		var container = $( '#pvc-post-views' ).find( '.pvc-dashboard-container' );

		if ( $( container ).length > 0 ) {
			pvcBindMonthEvents( false, container );

			pvcGetChartData( true, period, container );
		}
	}
	
	function pvcUpdateMostViewed( period ) {
		var container = $( '#pvc-most-viewed' ).find( '.pvc-dashboard-container' );

		if ( $( container ).length > 0 ) {
			pvcBindMonthEvents( false, container );

			pvcGetViewedData( true, period, container );
		}
	}

	function pvcBindMonthEvents( newMonths, container ) {
		var months = $( container ).find( '.pvc-months' );

		// replace months?
		if ( newMonths !== false )
			months[0].innerHTML = newMonths;

		var prev = months[0].getElementsByClassName( 'prev' );
		var next = months[0].getElementsByClassName( 'next' );

		if ( $( container ).closest( '.pvc-accordion-item' ).attr( 'id' ) === 'pvc-most-viewed' ) {
			prev[0].addEventListener( 'click', pvcLoadMostViewedData );
		} else {
			prev[0].addEventListener( 'click', pvcLoadChartData );
		}

		// skip span
		if ( next[0].tagName === 'A' ) {
			if ( $( container ).closest( '.pvc-accordion-item' ).attr( 'id' ) === 'pvc-most-viewed' ) {
				next[0].addEventListener( 'click', pvcLoadMostViewedData );
			} else {
				next[0].addEventListener( 'click', pvcLoadChartData );
			}
		}
	}

	function pvcLoadChartData( e ) {
		e.preventDefault();
		
		var container = $( '#pvc-post-views' ).find( '.pvc-dashboard-container' );

		pvcGetChartData( false, e.target.dataset.date, container );
	}
	
	function pvcLoadMostViewedData( e ) {
		e.preventDefault();
		
		var container = $( '#pvc-most-viewed' ).find( '.pvc-dashboard-container' );

		pvcGetViewedData( false, e.target.dataset.date, container );
	}

} )( jQuery );
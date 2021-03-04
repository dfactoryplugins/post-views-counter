( function ( $ ) {

	window.onload = function () {
		pvcUpdateChart( 'this_month' );
		pvcUpdateMostViewed( 'this_month' );
	};
	
	function pvcGetViewedData( init, period, container ) {
		$( container ).addClass( 'loading' ).find( '.spinner' ).addClass( 'is-active' );
		
		console.log( container );
		
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

									pvcUpdateChartPostTypes( ci.data.datasets[index].post_type, meta.hidden === null ? false : meta.hidden );
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

	function pvcUpdateChartPostTypes( post_type, hidden ) {
		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_chart_user_post_types',
				nonce: pvcArgs.nonceUser,
				post_type: post_type,
				hidden: hidden
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
		var container = document.getElementById( 'pvc-post-views' );

		if ( $( container ).length > 0 ) {
			pvcBindMonthEvents( false, container );

			pvcGetChartData( true, period, container );
		}
	}
	
	function pvcUpdateMostViewed( period ) {
		var container = document.getElementById( 'pvc-most-viewed' );

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

		if ( $( container ).attr( 'id' ) === 'pvc-most-viewed' ) {
			prev[0].addEventListener( 'click', pvcLoadMostViewedData );
		} else {
			prev[0].addEventListener( 'click', pvcLoadChartData );
		}

		// skip span
		if ( next[0].tagName === 'A' ) {
			if ( $( container ).attr( 'id' ) === 'pvc-most-viewed' ) {
				next[0].addEventListener( 'click', pvcLoadMostViewedData );
			} else {
				next[0].addEventListener( 'click', pvcLoadChartData );
			}
		}
	}

	function pvcLoadChartData( e ) {
		e.preventDefault();
		
		var container = document.getElementById( 'pvc-post-views' );

		pvcGetChartData( false, e.target.dataset.date, container );
	}
	
	function pvcLoadMostViewedData( e ) {
		e.preventDefault();
		
		var container = document.getElementById( 'pvc-most-viewed' );

		pvcGetViewedData( false, e.target.dataset.date, container );
	}

} )( jQuery );
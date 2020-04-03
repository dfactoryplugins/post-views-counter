( function ( $ ) {

	// set global options
	// Chart.defaults.global.tooltips.titleMarginBottom = 0;
	// Chart.defaults.global.tooltips.footerMarginTop = 4;

	window.onload = function () {
		updateChart( 'this_month' );
	};

	function runAjax( initial, period, container ) {
		$.ajax( {
			url: pvcArgs.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_dashboard_chart',
				nonce: pvcArgs.nonce,
				period: period
			},
			success: function ( args ) {
				// first call?
				if ( initial ) {
					container.removeClass( 'loading' );
					container.find( '.spinner' ).removeClass( 'is-active' );

					var config = {
						type: 'line',
						options: {
							responsive: true,
							legend: {
								display: true,
								position: 'bottom',
								labels: {
									boxWidth: 0,
									fontSize: 14,
									padding: 10,
									usePointStyle: false
								}
							},
							scales: {
								xAxes: [ {
									display: true,
									scaleLabel: {
										display: false,
										labelString: args.text.xAxes,
										fontSize: 14,
										fontFamily: '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif'
									}
								} ],
								yAxes: [ {
									display: true,
									scaleLabel: {
										display: false,
										labelString: args.text.yAxes
									}
								} ]
							},
							hover: {
								mode: 'label'
							}
						}
					};

					config = updateConfig( config, args );

					window.chartPVC = new Chart( document.getElementById( 'pvc_chart' ).getContext( '2d' ), config );
				} else {
					bindMonthEvents( args.months );

					window.chartPVC.config = updateConfig( window.chartPVC.config, args );
					window.chartPVC.update();
				}
			}
		} );
	}

	function updateConfig( config, args ) {
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

	function updateChart( period ) {
		var container = document.getElementById( 'pvc_dashboard_container' );

		if ( $( container ).length > 0 ) {
			bindMonthEvents( false );

			$( container ).addClass( 'loading' ).append( '<span class="spinner is-active"></span>' );

			runAjax( true, period, $( container ) );
		}
	}

	function bindMonthEvents( newMonths ) {
		var months = document.getElementsByClassName( 'pvc_months' );

		// replace months?
		if ( newMonths !== false )
			months[0].innerHTML = newMonths;

		var prev = months[0].getElementsByClassName( 'prev' );
		var next = months[0].getElementsByClassName( 'next' );

		prev[0].addEventListener( 'click', loadChartData );
		next[0].addEventListener( 'click', loadChartData );
	}

	function loadChartData( e ) {
		e.preventDefault();

		runAjax( false, e.target.dataset.date );
	}

} )( jQuery );
( function ( $ ) {

	window.onload = function () {
		updateChart( 'this_month' );
	};

	function ajaxGetChartData( init, period, container ) {
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
				if ( init ) {
					container.removeClass( 'loading' );
					container.find( '.spinner' ).removeClass( 'is-active' );

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

									ajaxUpdateChartPostTypes( ci.data.datasets[index].post_type, meta.hidden === null ? false : meta.hidden );
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
										labelString: args.text.xAxes
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

	function ajaxUpdateChartPostTypes( post_type, hidden ) {
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

			ajaxGetChartData( true, period, $( container ) );
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

		// skip span
		if ( next[0].tagName === 'A' )
			next[0].addEventListener( 'click', loadChartData );
	}

	function loadChartData( e ) {
		e.preventDefault();

		ajaxGetChartData( false, e.target.dataset.date );
	}

} )( jQuery );
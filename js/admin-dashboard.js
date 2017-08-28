( function ( $ ) {

	// set global options
	// Chart.defaults.global.tooltips.titleMarginBottom = 0;
	// Chart.defaults.global.tooltips.footerMarginTop = 4;

	window.onload = function () {
		updateChart( 'this_month' );
	};

	function updateChart( period ) {

		var container = document.getElementById( 'pvc_dashboard_container' );

		if ( $( container ).length > 0 ) {

			$( container ).addClass( 'loading' ).append( '<span class="spinner is-active"></span>' );

			$.ajax( {
				url: pvcArgs.ajaxURL,
				type: 'POST',
				dataType: 'json',
				data: ( {
					action: 'pvc_dashboard_chart',
					nonce: pvcArgs.nonce,
					period: period
				} ),
				success: function ( args ) {
					$( container ).removeClass( 'loading' );
					$( container ).find( '.spinner' ).removeClass( 'is-active' );

					var config = {
						type: 'line',
						data: args.data,
						options: {
							responsive: true,
							legend: {
								display: false,
								position: 'bottom',
							},
							scales: {
								xAxes: [ {
										display: true,
										scaleLabel: {
											display: true,
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
							},
							tooltips: {
								custom: function ( tooltip ) {
									// console.log( tooltip );
								},
								callbacks: {
									title: function ( tooltip ) {
										return args.data.dates[tooltip[0].index];
									}
								}
							}
						}
					};

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

					window.chartPVC = new Chart( document.getElementById( 'pvc_chart' ).getContext( '2d' ), config );
				}
			} );

		}
	}

	function updateLegend() {
		$legendContainer = $( '#legendContainer' );
		$legendContainer.empty();
		$legendContainer.append( window.chartPVC.generateLegend() );
	}

} )( jQuery );
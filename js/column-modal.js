/**
 * Post Views Counter - Column Modal Chart
 */
( function( $ ) {
	'use strict';

	// check if jQuery is available
	if ( typeof $ === 'undefined' )
		return;

	let pvcModalChart = null;
	let currentPostId = null;

	// check if localized data is available
	if ( typeof pvcColumnModal === 'undefined' )
		return;

	/**
	 * Initialize Micromodal
	 */
	function initMicromodal() {
		if ( typeof MicroModal === 'undefined' )
			return false;

		// initialize with basic config
		MicroModal.init( {
			disableScroll: true,
			awaitCloseAnimation: true
		} );

		return true;
	}

	/**
	 * Prepare modal for specific post
	 */
	function prepareModalForPost( postId, postTitle ) {
		if ( ! postId )
			return false;

		currentPostId = postId;

		// set modal title
		$( '#pvc-modal-title' ).text( postTitle );

		// show loading state
		const $container = $( '.pvc-modal-chart-container' );
		$container.addClass( 'loading' );
		$container.find( '.spinner' ).addClass( 'is-active' );

		// clear previous content
		$( '.pvc-modal-views-label' ).text( '' );
		$( '.pvc-modal-count' ).text( '' );
		$( '.pvc-modal-dates' ).html( '' );

		return true;
	}

	function resetModalContent() {
		$( '#pvc-modal-title' ).text( '' );
		$( '.pvc-modal-views-label' ).text( '' );
		$( '.pvc-modal-count' ).text( '' );
		$( '.pvc-modal-dates' ).html( '' );

		const $container = $( '.pvc-modal-chart-container' );
		$container.removeClass( 'loading' );
		$container.find( '.spinner' ).removeClass( 'is-active' );

		// remove any error messages
		$( '.pvc-modal-error' ).remove();
	}

	/**
	 * Load chart data via AJAX
	 */
	function loadChartData( postId, period ) {
		const $container = $( '.pvc-modal-chart-container' );

		// show loading
		$container.addClass( 'loading' );
		$container.find( '.spinner' ).addClass( 'is-active' );

		$.ajax( {
			url: pvcColumnModal.ajaxURL,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'pvc_column_chart',
				nonce: pvcColumnModal.nonce,
				post_id: postId,
				period: period
			},
			success: function( response ) {
				if ( response.success ) {
					renderChart( response.data );
				} else {
					showError( response.data.message || pvcColumnModal.i18n.error );
				}
			},
			error: function( xhr, status, error ) {
				showError( pvcColumnModal.i18n.error );
			},
			complete: function() {
				$container.removeClass( 'loading' );
				$container.find( '.spinner' ).removeClass( 'is-active' );
			}
		} );
	}

	/**
	 * Render chart with data
	 */
	function renderChart( data ) {
		const ctx = document.getElementById( 'pvc-modal-chart' );

		if ( ! ctx )
			return;

		// destroy existing chart
		if ( pvcModalChart ) {
			pvcModalChart.destroy();
			pvcModalChart = null;
		}

		// remove any error messages and show canvas
		$( '.pvc-modal-error' ).remove();
		$( ctx ).show();

		// update stats
		$( '.pvc-modal-views-label' ).text( pvcColumnModal.i18n.summary );
		$( '.pvc-modal-count' ).text( data.total_views.toLocaleString() );

		// update date navigation
		$( '.pvc-modal-dates' ).html( data.dates_html );

		/* debug logging (admins only) - shows views keys returned from AJAX
		if ( typeof console !== 'undefined' && data.debug_views_keys ) {
			console.debug( 'PVC debug: views keys for post ' + data.post_id + ':', data.debug_views_keys );
			console.debug( 'PVC debug: views sample by keys:', data.debug_views );
			console.debug( 'PVC debug: example date_key used by client:', data.debug_date_key_example );
		}

		if ( typeof console !== 'undefined' && data.data ) {
			console.debug( 'PVC debug: chart data object', data.data );
			console.debug( 'PVC debug: dataset length', data.data.datasets && data.data.datasets[0] ? data.data.datasets[0].data.length : 0 );
		}
		*/

		// chart configuration
		const config = {
			type: 'line',
			data: data.data,
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: false
					},
					tooltip: {
						callbacks: {
							title: function( context ) {
								return data.data.dates[context[0].dataIndex];
							},
							label: function( context ) {
								const count = context.parsed.y;
								const viewText = count === 1 ? pvcColumnModal.i18n.view : pvcColumnModal.i18n.views;
								return count.toLocaleString() + ' ' + viewText;
							}
						}
					}
				},
				scales: {
					x: {
						display: true,
						grid: {
							display: false
						}
					},
					y: {
						display: true,
						beginAtZero: true,
						ticks: {
							precision: 0
						},
						grid: {
							color: 'rgba(0, 0, 0, 0.05)'
						}
					}
				}
			}
		};

		// apply design from server
		if ( data.design ) {
			config.data.datasets.forEach( function( dataset ) {
				Object.assign( dataset, data.design );
				dataset.tension = 0.4; // match dashboard curve
			} );
		}

		// create chart
		pvcModalChart = new Chart( ctx.getContext( '2d' ), config );
	}

	/**
	 * Show error message without destroying modal structure
	 */
	function showError( message ) {
		// destroy existing chart
		if ( pvcModalChart ) {
			pvcModalChart.destroy();
			pvcModalChart = null;
		}

		// reset states
		$( '.pvc-modal-summary' ).text( '' );
		$( '.pvc-modal-dates' ).html( '' );

		const $container = $( '.pvc-modal-chart-container' );
		$container.removeClass( 'loading' );
		$container.find( '.spinner' ).removeClass( 'is-active' );

		// remove any existing error message
		$( '.pvc-modal-error' ).remove();

		// inject error message before chart container
		$container.before( '<p class="pvc-modal-error">' + message + '</p>' );

		// hide canvas during error
		$container.find( 'canvas' ).hide();
	}

	/**
	 * Document ready
	 */
	$( function() {
		// check if modal HTML exists
		if ( $( '#pvc-chart-modal' ).length === 0 )
			return;

		// initialize Micromodal
		if ( ! initMicromodal() )
			return;

		// handle click on view chart link
		$( document ).on( 'click', '.pvc-view-chart', function( e ) {
			e.preventDefault();

			const postId = $( this ).data( 'post-id' );
			const postTitle = $( this ).data( 'post-title' );

			if ( ! postId )
				return;

			// prepare modal with post data
			if ( prepareModalForPost( postId, postTitle ) ) {
				// open modal using MicroModal with callbacks
				if ( typeof MicroModal !== 'undefined' ) {
					MicroModal.show( 'pvc-chart-modal', {
						onShow: function( modal ) {
							if ( currentPostId )
								loadChartData( currentPostId, '' );
						},
						onClose: function() {
							// destroy chart when modal closes
							if ( pvcModalChart ) {
								pvcModalChart.destroy();
								pvcModalChart = null;
							}

							currentPostId = null;

							// reset modal content
							resetModalContent();
						},
						disableScroll: true,
						awaitCloseAnimation: true
					} );
				}
			}
		} );

		// handle period navigation
		$( document ).on( 'click', '.pvc-modal-nav-prev, .pvc-modal-nav-next', function( e ) {
			e.preventDefault();

			// check if disabled
			if ( $( this ).hasClass( 'pvc-disabled' ) )
				return;

			const period = $( this ).data( 'period' );

			if ( period && currentPostId ) {
				loadChartData( currentPostId, period );
			}
		} );
	} );

} )( jQuery );

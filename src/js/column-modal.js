/**
 * Post Views Counter - Column Modal Chart JavaScript
 *
 * Handles the modal popup for displaying post views charts in admin columns.
 * Uses Micromodal for modal functionality and Chart.js for data visualization.
 */

(( $ ) => {
	'use strict';

	if ( typeof $ === 'undefined' ) {
		return;
	}

	let pvcModalChart = null;
	let currentPostId = null;

	if ( typeof pvcColumnModal === 'undefined' ) {
		return;
	}

	const initMicromodal = () => {
		if ( typeof MicroModal === 'undefined' ) {
			return false;
		}

		MicroModal.init( {
			disableScroll: true,
			awaitCloseAnimation: true,
		} );

		return true;
	};

	const prepareModalForPost = ( postId, postTitle ) => {
		if ( ! postId ) {
			return false;
		}

		currentPostId = postId;

		$( '#pvc-modal-title' ).text( postTitle );

		const $container = $( '.pvc-modal-chart-container' );
		$container.addClass( 'loading' );
		$container.find( '.spinner' ).addClass( 'is-active' );

		$( '.pvc-modal-views-label' ).text( '' );
		$( '.pvc-modal-count' ).text( '' );
		$( '.pvc-modal-dates' ).html( '' );

		return true;
	};

	const resetModalContent = () => {
		$( '#pvc-modal-title' ).text( '' );
		$( '.pvc-modal-views-label' ).text( '' );
		$( '.pvc-modal-count' ).text( '' );
		$( '.pvc-modal-dates' ).html( '' );

		const $container = $( '.pvc-modal-chart-container' );
		$container.removeClass( 'loading' );
		$container.find( '.spinner' ).removeClass( 'is-active' );

		$( '.pvc-modal-error' ).remove();
	};

	const loadChartData = ( postId, period ) => {
		const $container = $( '.pvc-modal-chart-container' );

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
				period,
			},
			success( response ) {
				if ( response.success ) {
					renderChart( response.data );
				} else {
					showError( response.data.message || pvcColumnModal.i18n.error );
				}
			},
			error() {
				showError( pvcColumnModal.i18n.error );
			},
			complete() {
				$container.removeClass( 'loading' );
				$container.find( '.spinner' ).removeClass( 'is-active' );
			},
		} );
	};

	const renderChart = ( data ) => {
		const ctx = document.getElementById( 'pvc-modal-chart' );

		if ( ! ctx ) {
			return;
		}

		if ( pvcModalChart ) {
			pvcModalChart.destroy();
			pvcModalChart = null;
		}

		$( '.pvc-modal-error' ).remove();
		$( ctx ).show();

		$( '.pvc-modal-views-label' ).text( pvcColumnModal.i18n.summary );
		$( '.pvc-modal-count' ).text( data.total_views.toLocaleString() );

		$( '.pvc-modal-dates' ).html( data.dates_html );

		const config = {
			type: 'line',
			data: data.data,
			options: {
				maintainAspectRatio: false,
				responsive: true,
				plugins: {
					legend: {
						display: false,
					},
					tooltip: {
						callbacks: {
							title( context ) {
								return data.data.dates[context[0].dataIndex];
							},
							label( context ) {
								const count = context.parsed.y;
								const viewText = count === 1 ? pvcColumnModal.i18n.view : pvcColumnModal.i18n.views;
								return `${ count.toLocaleString() } ${ viewText }`;
							},
						},
					},
				},
				scales: {
					x: {
						display: true,
						grid: {
							display: false,
						},
					},
					y: {
						display: true,
						beginAtZero: true,
						ticks: {
							precision: 0,
						},
						grid: {
							color: 'rgba(0, 0, 0, 0.05)',
						},
					},
				},
			},
		};

		if ( data.design ) {
			config.data.datasets.forEach( ( dataset ) => {
				Object.assign( dataset, data.design );
				dataset.tension = 0.4;
			} );
		}

		pvcModalChart = new Chart( ctx.getContext( '2d' ), config );
	};

	const showError = ( message ) => {
		if ( pvcModalChart ) {
			pvcModalChart.destroy();
			pvcModalChart = null;
		}

		$( '.pvc-modal-summary' ).text( '' );
		$( '.pvc-modal-dates' ).html( '' );

		const $container = $( '.pvc-modal-chart-container' );
		$container.removeClass( 'loading' );
		$container.find( '.spinner' ).removeClass( 'is-active' );

		$( '.pvc-modal-error' ).remove();

		$container.before( `<p class="pvc-modal-error">${ message }</p>` );

		$container.find( 'canvas' ).hide();
	};

	$( () => {
		if ( $( '#pvc-chart-modal' ).length === 0 ) {
			return;
		}

		if ( ! initMicromodal() ) {
			return;
		}

		$( document ).on( 'click', '.pvc-view-chart', ( event ) => {
			event.preventDefault();

			const $target = $( event.currentTarget );
			const postId = $target.data( 'post-id' );
			const postTitle = $target.data( 'post-title' );

			if ( ! postId ) {
				return;
			}

			if ( prepareModalForPost( postId, postTitle ) && typeof MicroModal !== 'undefined' ) {
				MicroModal.show( 'pvc-chart-modal', {
					onShow() {
						if ( currentPostId ) {
							loadChartData( currentPostId, '' );
						}
					},
					onClose() {
						if ( pvcModalChart ) {
							pvcModalChart.destroy();
							pvcModalChart = null;
						}

						currentPostId = null;

						resetModalContent();
					},
					disableScroll: true,
					awaitCloseAnimation: true,
				} );
			}
		} );

		$( document ).on( 'click', '.pvc-modal-nav-prev, .pvc-modal-nav-next', ( event ) => {
			event.preventDefault();

			const $button = $( event.currentTarget );

			if ( $button.hasClass( 'pvc-disabled' ) ) {
				return;
			}

			const period = $button.data( 'period' );

			if ( period && currentPostId ) {
				loadChartData( currentPostId, period );
			}
		} );
	} );
})( jQuery );

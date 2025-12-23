/**
 * Post Views Counter - Gutenberg Integration
 *
 * Adds "Post Views" ordering support to Query Loop and Latest Posts blocks.
 *
 * Note: This file uses JSX and React hooks (useEffect), so it requires transpilation
 * via the Vite build system before deployment. Run `npm run build` to compile.
 */

( function( window, wp ) {
	'use strict';

	const { addFilter } = wp.hooks;
	const { createHigherOrderComponent } = wp.compose;
	const { Fragment, useEffect } = wp.element;
	const { InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl } = wp.components;
	const { __ } = wp.i18n;

	// Get configuration
	const config = window.pvcGutenbergIntegration || { enabled: true, defaultIncludeZeroViews: true };

	if ( ! config.enabled ) {
		return;
	}

	// Module-level storage to track intended orderBy per block (persists across unmount/remount)
	const latestPostsIntentMap = new Map();

	// Track if user is actively interacting with the select (to distinguish from programmatic changes)
	let userInteractionPending = false;

	/**
	 * Add pvcIncludeZeroViews attribute to Latest Posts block.
	 *
	 * Note: We cannot add post_views to the orderBy options via block registration
	 * because QueryControls handles its own options internally.
	 * The DOM injection approach in withLatestPostsControls handles the UI.
	 */
	addFilter(
		'blocks.registerBlockType',
		'pvc/latest-posts-add-post-views-orderby',
		function( settings, name ) {
			if ( name !== 'core/latest-posts' ) {
				return settings;
			}

			// Add pvcIncludeZeroViews attribute
			if ( ! settings.attributes ) {
				settings.attributes = {};
			}

			settings.attributes.pvcIncludeZeroViews = {
				type: 'boolean',
				default: config.defaultIncludeZeroViews
			};

			return settings;
		}
	);

	const addOptionIfMissing = function( selectElement, value, label ) {
		if ( ! selectElement ) {
			return;
		}

		const hasOption = Array.from( selectElement.options ).some( function( option ) {
			return option.value === value;
		} );

		if ( hasOption ) {
			return;
		}

		const option = document.createElement( 'option' );
		option.value = value;
		option.text = label;
		selectElement.appendChild( option );
	};

	const getInspectorRoot = function() {
		return document.querySelector( '.block-editor-block-inspector' )
			|| document.querySelector( '.interface-interface-skeleton__sidebar' )
			|| document.body;
	};

	const findQueryLoopOrderBySelect = function() {
		const root = getInspectorRoot();
		const selects = root.querySelectorAll( 'select' );

		for ( let i = 0; i < selects.length; i++ ) {
			const el = selects[i];
			const hasDateDesc = el.querySelector( 'option[value="date/desc"]' );
			const hasDateAsc = el.querySelector( 'option[value="date/asc"]' );
			const hasTitleAsc = el.querySelector( 'option[value="title/asc"]' );

			if ( hasDateDesc && hasDateAsc && hasTitleAsc ) {
				return el;
			}
		}

		return null;
	};

	/**
	 * Find orderBy select for QueryControls-based blocks (Latest Posts, etc.)
	 *
	 * Note: Modern WordPress (6.x) QueryControls uses combined values like date/desc, title/asc
	 * This is the same format as Query Loop block now, so we identify QueryControls
	 * by the specific options it has (4 options: date/desc, date/asc, title/asc, title/desc)
	 */
	const findLatestPostsOrderBySelect = function() {
		const root = getInspectorRoot();
		const selects = root.querySelectorAll( 'select' );

		// Debug: log available selects
		if ( window.pvcDebug ) {
			console.log( '[PVC] findLatestPostsOrderBySelect - Found selects:', selects.length );
			selects.forEach( function( el, i ) {
				const options = Array.from( el.options ).map( function( o ) { return o.value; } );
				console.log( '[PVC] Select', i, 'options:', options );
			} );
		}

		for ( let i = 0; i < selects.length; i++ ) {
			const el = selects[i];

			// QueryControls in Latest Posts uses: date/desc, date/asc, title/asc, title/desc
			const hasDateDesc = el.querySelector( 'option[value="date/desc"]' );
			const hasDateAsc = el.querySelector( 'option[value="date/asc"]' );
			const hasTitleAsc = el.querySelector( 'option[value="title/asc"]' );
			const hasTitleDesc = el.querySelector( 'option[value="title/desc"]' );

			// QueryControls typically has exactly these 4 options, no menu_order
			const hasMenuOrder = el.querySelector( 'option[value="menu_order/asc"]' ) || el.querySelector( 'option[value="menu_order/desc"]' );

			// Latest Posts QueryControls: has the 4 basic options but NOT menu_order
			// Query Loop has additional options like menu_order
			if ( hasDateDesc && hasDateAsc && hasTitleAsc && hasTitleDesc && ! hasMenuOrder ) {
				if ( window.pvcDebug ) {
					console.log( '[PVC] Found Latest Posts orderBy select (QueryControls format) at index', i );
				}
				return el;
			}
		}

		if ( window.pvcDebug ) {
			console.log( '[PVC] Latest Posts orderBy select NOT found' );
		}

		return null;
	};

	const moveLatestPostsPanel = function( selectElement ) {
		if ( ! selectElement ) {
			return;
		}

		const root = getInspectorRoot();
		const panel = root.querySelector( '.pvc-post-views-settings--latest-posts' );
		if ( ! panel ) {
			return;
		}

		// Latest Posts uses ToolsPanel (components-tools-panel) not PanelBody
		// Find the Sorting and filtering tools panel (contains the orderBy select)
		const sortingToolsPanel = selectElement.closest( '.components-tools-panel' );
		if ( ! sortingToolsPanel ) {
			return;
		}

		// The tools panel might be wrapped in additional divs, find the container that's a sibling of panel__body elements
		let sortingContainer = sortingToolsPanel;
		while ( sortingContainer.parentNode && sortingContainer.parentNode !== root ) {
			const parent = sortingContainer.parentNode;
			// Check if siblings include panel__body elements (like our PVC panel or Advanced)
			const hasPanelBodySiblings = parent.querySelector( ':scope > .components-panel__body' );
			if ( hasPanelBodySiblings ) {
				break;
			}
			sortingContainer = parent;
		}

		// Get the parent that should contain both the sorting section and our panel
		const targetParent = sortingContainer.parentNode;
		if ( ! targetParent ) {
			return;
		}

		// Find the wrapper around our panel
		let panelWrapper = panel;
		while ( panelWrapper.parentNode && panelWrapper.parentNode !== targetParent && panelWrapper.parentNode !== root ) {
			panelWrapper = panelWrapper.parentNode;
		}

		// Check if already positioned correctly
		if ( panelWrapper.previousElementSibling === sortingContainer ) {
			return;
		}

		// Move panel wrapper after the sorting container
		if ( panelWrapper.parentNode === targetParent ) {
			targetParent.insertBefore( panelWrapper, sortingContainer.nextSibling );
		} else {
			targetParent.insertBefore( panel, sortingContainer.nextSibling );
		}
	};

	/**
	 * Add custom controls and post_views orderBy option to Query Loop block inspector.
	 *
	 * Note on Query Loop attributes:
	 * - Query Loop stores settings in a flexible 'query' object attribute (type: object)
	 * - We cannot formally register query.pvcIncludeZeroViews via registerBlockType
	 *   because WordPress doesn't support nested attribute schemas for object types
	 * - Instead, we store/read pvcIncludeZeroViews dynamically within the query object
	 * - This is a standard pattern for Query Loop's extensible query parameters
	 *
	 * Note on orderBy injection:
	 * - Query block doesn't expose an API to add custom orderBy options
	 * - We use DOM manipulation + MutationObserver to inject post_views options
	 * - Query block uses combined values (e.g. date/desc), so we inject post_views/desc and post_views/asc
	 */
	const withQueryLoopControls = createHigherOrderComponent( function( BlockEdit ) {
		return function( props ) {
			const { name, attributes, setAttributes } = props;

			if ( name !== 'core/query' ) {
				return <BlockEdit { ...props } />;
			}

				useEffect( function() {
					if ( ! props.isSelected ) {
						return undefined;
					}

					const injectOrderByOption = function() {
						const selectElement = findQueryLoopOrderBySelect();
						if ( ! selectElement ) {
							return;
						}

						addOptionIfMissing( selectElement, 'post_views/desc', __( 'Most viewed', 'post-views-counter' ) );
						addOptionIfMissing( selectElement, 'post_views/asc', __( 'Least viewed', 'post-views-counter' ) );
					};

					const observerTarget = getInspectorRoot();
					const observer = new MutationObserver( injectOrderByOption );

					injectOrderByOption();
					observer.observe( observerTarget, { childList: true, subtree: true } );

					return function() {
						observer.disconnect();
					};
				}, [ props.clientId, props.isSelected ] );

			// Check if orderBy is set to post_views
			const isPostViewsOrderBy = attributes.query && attributes.query.orderBy === 'post_views';

			// Get current include zero views setting
			const includeZeroViews = attributes.query && typeof attributes.query.pvcIncludeZeroViews !== 'undefined'
				? attributes.query.pvcIncludeZeroViews
				: config.defaultIncludeZeroViews;

			return (
				<Fragment>
					<BlockEdit { ...props } />
					{ isPostViewsOrderBy && (
						<InspectorControls>
							<PanelBody
								title={ __( 'Post Views Settings', 'post-views-counter' ) }
								initialOpen={ true }
								className="pvc-post-views-settings pvc-post-views-settings--query"
							>
									<ToggleControl
										label={ __( 'Include posts with zero views', 'post-views-counter' ) }
										checked={ includeZeroViews }
										onChange={ function( value ) {
											const nextQuery = Object.assign( {}, attributes.query || {} );
											nextQuery.pvcIncludeZeroViews = value;
											setAttributes( { query: nextQuery } );
										} }
										help={ __( 'When enabled, posts with no views will be included in the results.', 'post-views-counter' ) }
									/>
							</PanelBody>
						</InspectorControls>
					) }
				</Fragment>
			);
		};
	}, 'withQueryLoopControls' );

	/**
	 * Add custom controls to Latest Posts block inspector.
	 */
	const withLatestPostsControls = createHigherOrderComponent( function( BlockEdit ) {
		return function( props ) {
			const { name, attributes, setAttributes } = props;

			if ( name !== 'core/latest-posts' ) {
				return <BlockEdit { ...props } />;
			}

			const clientId = props.clientId;

			// Get or initialize the intended orderBy from module-level storage
			const getIntendedOrderBy = function() {
				return latestPostsIntentMap.get( clientId );
			};

			const setIntendedOrderBy = function( value ) {
				latestPostsIntentMap.set( clientId, value );
			};

			// Track user's intent when they select post_views
			useEffect( function() {
				if ( attributes.orderBy === 'post_views' ) {
					setIntendedOrderBy( 'post_views' );
				}
			}, [ attributes.orderBy, clientId ] );

			// Detect and fix unwanted resets (component remounted with date but we intended post_views)
			useEffect( function() {
				const intended = getIntendedOrderBy();
				// Only restore if this wasn't a user action (userInteractionPending would be true)
				if ( intended === 'post_views' && attributes.orderBy === 'date' && ! userInteractionPending ) {
					// Use requestAnimationFrame to ensure DOM is ready
					const rafId = requestAnimationFrame( function() {
						setAttributes( { orderBy: 'post_views' } );
					} );
					return function() {
						cancelAnimationFrame( rafId );
					};
				}
			}, [ attributes.orderBy, clientId, setAttributes ] );

			// Clear intent when user explicitly selects a different option (including date)
			useEffect( function() {
				// If user interacted and selected something other than post_views, clear the intent
				if ( userInteractionPending && attributes.orderBy !== 'post_views' ) {
					latestPostsIntentMap.delete( clientId );
				}
			}, [ attributes.orderBy, clientId ] );

			// Cleanup on unmount - but keep intent for a short time to handle remounts
			useEffect( function() {
				return function() {
					// Don't clear immediately - the block might be remounting
					// Clear after 5 seconds if not accessed again
					const cleanupTimeout = setTimeout( function() {
						// Only clear if the block wasn't remounted (intent would be updated)
						const stillHasIntent = latestPostsIntentMap.get( clientId );
						if ( stillHasIntent && document.querySelector( '[data-block="' + clientId + '"]' ) === null ) {
							latestPostsIntentMap.delete( clientId );
						}
					}, 5000 );
					return function() {
						clearTimeout( cleanupTimeout );
					};
				};
			}, [ clientId ] );

			useEffect( function() {
				if ( ! props.isSelected ) {
					return undefined;
				}

				const injectOrderByOption = function() {
					const selectElement = findLatestPostsOrderBySelect();
					if ( ! selectElement ) {
						return;
					}

					// QueryControls now uses combined format like Query Loop
					addOptionIfMissing( selectElement, 'post_views/desc', __( 'Most viewed', 'post-views-counter' ) );
					addOptionIfMissing( selectElement, 'post_views/asc', __( 'Least viewed', 'post-views-counter' ) );

					// Listen for user interaction to distinguish from programmatic changes
					if ( ! selectElement.dataset.pvcListenerAdded ) {
						selectElement.dataset.pvcListenerAdded = 'true';
						selectElement.addEventListener( 'change', function() {
							userInteractionPending = true;
							// Clear the flag after a short delay (after React processes the change)
							setTimeout( function() {
								userInteractionPending = false;
							}, 100 );
						} );
					}

					// CRITICAL: Sync select's displayed value with current attributes
					// After React re-renders, the select may show wrong value because our options were missing
					if ( attributes.orderBy === 'post_views' ) {
						const expectedValue = 'post_views/' + ( attributes.order || 'desc' );
						if ( selectElement.value !== expectedValue ) {
							selectElement.value = expectedValue;
						}
					}

					// Move panel - may need to retry as React renders it asynchronously
					moveLatestPostsPanel( selectElement );
				};

				const observerTarget = getInspectorRoot();
				const observer = new MutationObserver( injectOrderByOption );

				// Initial injection attempt
				injectOrderByOption();

				// Delayed retry - sometimes the panel takes a moment to render
				const timeoutId = setTimeout( injectOrderByOption, 100 );
				const timeoutId2 = setTimeout( injectOrderByOption, 300 );

				observer.observe( observerTarget, { childList: true, subtree: true } );

				return function() {
					observer.disconnect();
					clearTimeout( timeoutId );
					clearTimeout( timeoutId2 );
				};
			}, [ props.clientId, props.isSelected, attributes.orderBy, attributes.order ] );

			// Check if orderBy is set to post_views
			const isPostViewsOrderBy = attributes.orderBy === 'post_views';

			// Separate effect specifically for panel positioning after it renders
			useEffect( function() {
				if ( ! isPostViewsOrderBy || ! props.isSelected ) {
					return undefined;
				}

				const positionPanel = function() {
					const selectElement = findLatestPostsOrderBySelect();
					if ( selectElement ) {
						moveLatestPostsPanel( selectElement );
					}
				};

				// Panel just rendered, position it after a short delay to ensure DOM is ready
				const timeoutId1 = setTimeout( positionPanel, 50 );
				const timeoutId2 = setTimeout( positionPanel, 150 );
				const timeoutId3 = setTimeout( positionPanel, 300 );
				const timeoutId4 = setTimeout( positionPanel, 500 );
				const timeoutId5 = setTimeout( positionPanel, 1000 );

				// Also use MutationObserver to catch when the panel appears
				const observerTarget = getInspectorRoot();
				const panelObserver = new MutationObserver( function() {
					const panel = observerTarget.querySelector( '.pvc-post-views-settings--latest-posts' );
					if ( panel ) {
						positionPanel();
					}
				} );
				panelObserver.observe( observerTarget, { childList: true, subtree: true } );

				return function() {
					clearTimeout( timeoutId1 );
					clearTimeout( timeoutId2 );
					clearTimeout( timeoutId3 );
					clearTimeout( timeoutId4 );
					clearTimeout( timeoutId5 );
					panelObserver.disconnect();
				};
			}, [ isPostViewsOrderBy, props.isSelected ] );

			// Get current include zero views setting
			const includeZeroViews = typeof attributes.pvcIncludeZeroViews !== 'undefined'
				? attributes.pvcIncludeZeroViews
				: config.defaultIncludeZeroViews;

			return (
				<Fragment>
					<BlockEdit { ...props } />
					{ isPostViewsOrderBy && (
						<InspectorControls>
							<PanelBody
								title={ __( 'Post Views Settings', 'post-views-counter' ) }
								initialOpen={ true }
								className="pvc-post-views-settings pvc-post-views-settings--latest-posts"
							>
								<ToggleControl
									label={ __( 'Include posts with zero views', 'post-views-counter' ) }
									checked={ includeZeroViews }
									onChange={ function( value ) {
										setAttributes( { pvcIncludeZeroViews: value } );
									} }
									help={ __( 'When enabled, posts with no views will be included in the results.', 'post-views-counter' ) }
								/>
							</PanelBody>
						</InspectorControls>
					) }
				</Fragment>
			);
		};
	}, 'withLatestPostsControls' );

	// Register the filters
	addFilter(
		'editor.BlockEdit',
		'pvc/query-loop-controls',
		withQueryLoopControls
	);

	addFilter(
		'editor.BlockEdit',
		'pvc/latest-posts-controls',
		withLatestPostsControls
	);

} )( window, window.wp );

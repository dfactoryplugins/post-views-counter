import { PanelBody, TextControl, CheckboxControl, BaseControl, RadioControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { title, postTypes, numberOfPosts, noPostsMessage, order, listType, displayPostViews, displayPostExcerpt, displayPostAuthor, displayPostThumbnail, thumbnailSize } = attributes;
	// const [ setOption ] = useState( 'a' );
	// const [ isChecked, setChecked ] = useState( true );
// pvcBlockEditorData.imageSizes
// console.log( pvcBlockEditorData.postTypes );
// console.log( attributes );
// console.log( postTypes );
// console.log( displayPostExcerpt );
	const postTypesComponent = (
		Object.keys( pvcBlockEditorData.postTypes ).map( ( key, index ) => (
			postTypes[key] = true
		) ),
		Object.keys( pvcBlockEditorData.postTypes ).map( ( key, index ) => (
			<CheckboxControl
				label={ pvcBlockEditorData.postTypes[key] }
				checked={ postTypes[key] }
				onChange={ ( value ) =>
					setAttributes( { postTypes: [ postTypes[key] ] } )
				}
			/>
		) )
	)

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'post-view-counter' ) }>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Title', 'post-view-counter' ) }
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<BaseControl
						__nextHasNoMarginBottom
						label={ __( 'Post Types', 'post-view-counter' ) }
					>
						{ postTypesComponent }
					</BaseControl>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Number of posts to show', 'post-view-counter' ) }
						value={ numberOfPosts }
						onChange={ ( value ) =>
							setAttributes( { numberOfPosts: Number( value ) } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'No posts message', 'post-view-counter' ) }
						value={ noPostsMessage }
						onChange={ ( value ) =>
							setAttributes( { noPostsMessage: value } )
						}
					/>
					<RadioControl
						label={ __( 'Order', 'post-view-counter' ) }
						selected={ order }
						options={ [
							{ label: __( 'Ascending', 'post-view-counter' ), value: 'asc' },
							{ label: __( 'Descending', 'post-view-counter' ), value: 'desc' }
						] }
						onChange={ ( value ) =>
							setAttributes( { order: value } )
						}
					/>
					<RadioControl
						label={ __( 'Display Style', 'post-view-counter' ) }
						selected={ listType }
						options={ [
							{ label: __( 'Unordered list', 'post-view-counter' ), value: 'unordered' },
							{ label: __( 'Ordered list', 'post-view-counter' ), value: 'ordered' }
						] }
						onChange={ ( value ) =>
							setAttributes( { listType: value } )
						}
					/>
					<BaseControl
						__nextHasNoMarginBottom
						label={ __( 'Display Data', 'post-view-counter' ) }
					>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Post views', 'post-view-counter' ) }
							checked={ displayPostViews }
							onChange={ ( value ) =>
								setAttributes( { displayPostViews: value } )
							}
						/>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Post excerpt', 'post-view-counter' ) }
							checked={ displayPostExcerpt }
							onChange={ ( value ) =>
								setAttributes( { displayPostExcerpt: value } )
							}
						/>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Post author', 'post-view-counter' ) }
							checked={ displayPostAuthor }
							onChange={ ( value ) =>
								setAttributes( { displayPostAuthor: value } )
							}
						/>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Post thumbnail', 'post-view-counter' ) }
							checked={ displayPostThumbnail }
							onChange={ ( value ) =>
								setAttributes( { displayPostThumbnail: value } )
							}
						/>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Thumbnail Size', 'post-view-counter' ) }
							value={ thumbnailSize }
							options={ pvcBlockEditorData.imageSizes }
							onChange={ ( value ) =>
								setAttributes( { thumbnailSize: value } )
							}
						/>
					</BaseControl>
				</PanelBody>
			</InspectorControls>
			<p { ...useBlockProps() }>
				{ __( 'PVC WIDGET', 'post-view-counter' ) }
			</p>
		</>
	);
}
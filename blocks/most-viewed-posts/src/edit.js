import { useState } from 'react';
import { Notice, Spinner, PanelBody, TextControl, CheckboxControl, BaseControl, RadioControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	// attributes
	const { title, postTypes, period, numberOfPosts, noPostsMessage, order, listType, displayPostViews, displayPostExcerpt, displayPostAuthor, displayPostThumbnail, thumbnailSize } = attributes;

	// initialize post types state
	const [checkedState, setCheckedState] = useState( ! postTypes ? pvcBlockEditorData.postTypesKeys : postTypes );

	// spinner
	const spinner = () => {
		return <Spinner	/>;
	}

	const error = ( value ) => {
		return <Notice status="error">{ __( 'Something went wrong. Try again or refresh the page.', 'post-views-counter' ) }</Notice>;
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'post-view-counter' ) }>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Title', 'post-view-counter' ) }
						value={ title }
						onChange={ ( value ) => setAttributes( { title: value } ) }
					/>
					<BaseControl
						__nextHasNoMarginBottom
						label={ __( 'Post types', 'post-view-counter' ) }
					>
						{ (
							Object.keys( pvcBlockEditorData.postTypes ).map( ( postType ) => (
								<CheckboxControl
									key={ postType }
									label={ pvcBlockEditorData.postTypes[postType] }
									checked={ checkedState[postType] }
									onChange={ ( value ) => {
										// clone postTypes, we cant change attribute value directly
										let newValue = {...postTypes}

										// set new value
										newValue[postType] = value

										// set state and attribute
										setCheckedState( ( prevState ) => ( { ...prevState, [postType]: ! prevState[postType] } ) )
										setAttributes( { postTypes: newValue } )
									} }
								/>
							) )
						) }
					</BaseControl>
					<SelectControl
						__nextHasNoMarginBottom
						disabled={ pvcBlockEditorData.periods.length === 1 }
						label={ __( 'Views period', 'post-view-counter-pro' ) }
						value={ period }
						options={ pvcBlockEditorData.periods }
						onChange={ ( value ) => setAttributes( { period: value } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Number of posts to show', 'post-view-counter' ) }
						value={ numberOfPosts }
						onChange={ ( value ) => setAttributes( { numberOfPosts: Number( value ) } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'No posts message', 'post-view-counter' ) }
						value={ noPostsMessage }
						onChange={ ( value ) => setAttributes( { noPostsMessage: value } ) }
					/>
					<RadioControl
						label={ __( 'Order', 'post-view-counter' ) }
						selected={ order }
						options={ [
							{ label: __( 'Ascending', 'post-view-counter' ), value: 'asc' },
							{ label: __( 'Descending', 'post-view-counter' ), value: 'desc' }
						] }
						onChange={ ( value ) => setAttributes( { order: value } ) }
					/>
					<RadioControl
						label={ __( 'Display style', 'post-view-counter' ) }
						selected={ listType }
						options={ [
							{ label: __( 'Unordered list', 'post-view-counter' ), value: 'unordered' },
							{ label: __( 'Ordered list', 'post-view-counter' ), value: 'ordered' }
						] }
						onChange={ ( value ) => setAttributes( { listType: value } ) }
					/>
					<BaseControl
						__nextHasNoMarginBottom
						label={ __( 'Display Data', 'post-view-counter' ) }
					>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Post views', 'post-view-counter' ) }
							checked={ displayPostViews }
							onChange={ ( value ) => setAttributes( { displayPostViews: value } ) }
						/>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Post excerpt', 'post-view-counter' ) }
							checked={ displayPostExcerpt }
							onChange={ ( value ) => setAttributes( { displayPostExcerpt: value } ) }
						/>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Post author', 'post-view-counter' ) }
							checked={ displayPostAuthor }
							onChange={ ( value ) => setAttributes( { displayPostAuthor: value } ) }
						/>
						<CheckboxControl
							__nextHasNoMarginBottom
							label={ __( 'Post thumbnail', 'post-view-counter' ) }
							checked={ displayPostThumbnail }
							onChange={ ( value ) => setAttributes( { displayPostThumbnail: value } ) }
						/>
						{ displayPostThumbnail && <SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Thumbnail size', 'post-view-counter' ) }
							value={ thumbnailSize }
							options={ pvcBlockEditorData.imageSizes }
							onChange={ ( value ) => setAttributes( { thumbnailSize: value } ) }
						/> }
					</BaseControl>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<ServerSideRender
					httpMethod="POST"
					block="post-views-counter/most-viewed-posts"
					attributes={ attributes }
					LoadingResponsePlaceholder={ spinner }
					ErrorResponsePlaceholder={ error }
				/>
			</div>
		</>
	)
}
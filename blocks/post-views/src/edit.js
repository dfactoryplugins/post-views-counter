import { Notice, Spinner, PanelBody, TextControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	// attributes
	const { postID, period } = attributes;

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
				<PanelBody title={ __( 'Settings', 'post-views-counter' ) }>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Post ID', 'post-views-counter' ) }
						value={ postID }
						onChange={ ( value ) => setAttributes( { postID: Number( value ) } ) }
						help={ __( 'Enter 0 to use current visited post.', 'post-views-counter' ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						disabled={ pvcBlockEditorData.periods.length === 1 }
						label={ __( 'Views period', 'post-views-counter' ) }
						value={ period }
						options={ pvcBlockEditorData.periods }
						onChange={ ( value ) => setAttributes( { period: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<ServerSideRender
					httpMethod="POST"
					block="post-views-counter/post-views"
					attributes={ attributes }
					LoadingResponsePlaceholder={ spinner }
					ErrorResponsePlaceholder={ error }
				/>
			</div>
		</>
	)
}
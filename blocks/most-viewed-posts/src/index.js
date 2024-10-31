import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

registerBlockType( "post-views-counter/most-viewed-posts", {
	edit: Edit
} );
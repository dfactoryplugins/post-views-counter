import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';

registerBlockType( "post-views-counter/post-views", {
	edit: Edit
} );
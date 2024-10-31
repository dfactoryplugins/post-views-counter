import { registerBlockType } from '@wordpress/blocks';
import './style.scss';
import Edit from './edit';

registerBlockType( "post-views-counter/post-views", {
	edit: Edit
} );
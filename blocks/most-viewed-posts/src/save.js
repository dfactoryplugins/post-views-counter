import { useBlockProps } from '@wordpress/block-editor';

export default function Save() {
    return (
        <p { ...useBlockProps.save() }>
            { 'ABC123' }
        </p>
    );
}
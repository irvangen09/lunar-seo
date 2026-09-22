/**
 * Media Upload Field.
 *
 * A reusable component for an attachment-ID-typed field (Site Image,
 * and Default Social Image/Twitter Image in the Social section). Uses
 * wp.media directly (not a component from @wordpress/block-editor) so
 * a regular Settings page doesn't pull in an unnecessary block editor
 * dependency.
 *
 * The image preview is fetched via useEntityRecord (REST core-data),
 * not wp.media.attachment(), to stay consistent with the React/REST
 * data-fetching pattern used throughout the Admin app.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEntityRecord } from '@wordpress/core-data';

export default function MediaUploadField( { imageId, onSelect, onRemove, label, help, recommendedSize } ) {
	const { record: attachment } = useEntityRecord( 'postType', 'attachment', imageId || 0 );
	const imageUrl = imageId && attachment ? attachment.source_url : '';

	const openMediaLibrary = () => {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		const frame = window.wp.media( {
			title: label,
			button: { text: __( 'Use this image', 'lunar-seo' ) },
			multiple: false,
			library: { type: 'image' },
		} );

		frame.on( 'select', () => {
			const selection = frame.state().get( 'selection' ).first().toJSON();
			onSelect( selection.id );
		} );

		frame.open();
	};

	return (
		<div className="lunar-media-upload-field">
			<p className="lunar-media-upload-field__label">
				<strong>{ label }</strong>
			</p>

			{ help && <p className="lunar-media-upload-field__help">{ help }</p> }

			<div className="lunar-media-upload-field__preview">
				{ imageUrl ? (
					<img src={ imageUrl } alt="" />
				) : (
					<p>{ __( 'No image selected', 'lunar-seo' ) }</p>
				) }
				{ recommendedSize && (
					<p className="lunar-media-upload-field__recommended">{ recommendedSize }</p>
				) }
			</div>

			<Button variant="secondary" onClick={ openMediaLibrary }>
				{ __( 'Select Image', 'lunar-seo' ) }
			</Button>

			{ imageId > 0 && (
				<Button variant="tertiary" isDestructive onClick={ onRemove }>
					{ __( 'Remove', 'lunar-seo' ) }
				</Button>
			) }
		</div>
	);
}
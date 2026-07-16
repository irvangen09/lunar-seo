/**
 * Media Upload Field.
 *
 * Komponen reusable untuk field bertipe attachment ID (Site Image,
 * dan nanti Default Social Image/Twitter Image di section Social).
 * Menggunakan wp.media secara langsung (bukan komponen dari
 * @wordpress/block-editor) agar tidak menarik dependency block
 * editor yang tidak diperlukan di halaman Settings biasa
 * (ARCHITECTURE.md §20 - hindari dependency yang tidak perlu).
 *
 * Preview gambar diambil via useEntityRecord (REST core-data),
 * bukan wp.media.attachment(), agar konsisten dengan pola data
 * fetching React/REST yang dipakai di seluruh Admin app.
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

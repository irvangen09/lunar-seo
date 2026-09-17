/**
 * Social Panel.
 *
 * Fields for the "Social" section — Open Graph and Twitter Card, each
 * with its own toggle and Default Image. Rendered inside a PanelBody
 * by app.js.
 *
 * onChange here takes (platform, field, fieldValue) — a level deeper
 * than the other panels, since each platform's settings are nested
 * under their own key.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

import MediaUploadField from './media-upload-field';

export default function SocialPanel( { value, onChange } ) {
	const openGraph = value.open_graph || {};
	const twitterCard = value.twitter_card || {};

	return (
		<>
			<h3>{ __( 'Open Graph', 'lunar-seo' ) }</h3>
			<ToggleControl
				label={ __( 'Enable Open Graph', 'lunar-seo' ) }
				help={ __( 'Add Open Graph meta tags to your site.', 'lunar-seo' ) }
				checked={ !! openGraph.enabled }
				onChange={ ( checked ) => onChange( 'open_graph', 'enabled', checked ) }
			/>
			<MediaUploadField
				label={ __( 'Default Social Image', 'lunar-seo' ) }
				help={ __(
					'This image will be used if a post or page does not have a featured image.',
					'lunar-seo'
				) }
				recommendedSize={ __( 'Recommended size: 1200 x 630 px', 'lunar-seo' ) }
				imageId={ openGraph.image_id || 0 }
				onSelect={ ( id ) => onChange( 'open_graph', 'image_id', id ) }
				onRemove={ () => onChange( 'open_graph', 'image_id', 0 ) }
			/>

			<h3>{ __( 'Twitter (X) Card', 'lunar-seo' ) }</h3>
			<ToggleControl
				label={ __( 'Enable Twitter Card', 'lunar-seo' ) }
				help={ __( 'Add Twitter Card meta tags to your site.', 'lunar-seo' ) }
				checked={ !! twitterCard.enabled }
				onChange={ ( checked ) => onChange( 'twitter_card', 'enabled', checked ) }
			/>
			<MediaUploadField
				label={ __( 'Default Twitter Image', 'lunar-seo' ) }
				help={ __(
					'This image will be used if a post or page does not have a featured image.',
					'lunar-seo'
				) }
				recommendedSize={ __( 'Recommended size: 1200 x 600 px', 'lunar-seo' ) }
				imageId={ twitterCard.image_id || 0 }
				onSelect={ ( id ) => onChange( 'twitter_card', 'image_id', id ) }
				onRemove={ () => onChange( 'twitter_card', 'image_id', 0 ) }
			/>
		</>
	);
}
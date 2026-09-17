/**
 * Site Info Panel.
 *
 * Fields for the "Site Info" section — rendered inside a PanelBody by
 * app.js. value/onChange follow the same (field, value) shape as every
 * other section handler in App().
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';

import TitleSeparatorPicker from './title-separator-picker';
import MediaUploadField from './media-upload-field';

export default function SiteInfoPanel( { value, onChange } ) {
	const titleSeparator = value.title_separator || '|';

	return (
		<>
			<TextControl
				label={ __( 'Website Name', 'lunar-seo' ) }
				help={ __( 'The name of your website.', 'lunar-seo' ) }
				value={ value.website_name || '' }
				onChange={ ( v ) => onChange( 'website_name', v ) }
			/>
			<TextControl
				label={ __( 'Alternate Website Name', 'lunar-seo' ) }
				help={ __(
					'Use the alternate website name for acronyms, or a shorter version of your website\u2019s name.',
					'lunar-seo'
				) }
				value={ value.alternate_website_name || '' }
				onChange={ ( v ) => onChange( 'alternate_website_name', v ) }
			/>

			<div className="lunar-field">
				<p id="lunar-separator-picker-label">
					<strong>{ __( 'Title Separator', 'lunar-seo' ) }</strong>
				</p>
				<p className="lunar-field__help">
					{ __( 'Choose the separator used in title templates.', 'lunar-seo' ) }
				</p>
				<TitleSeparatorPicker
					value={ titleSeparator }
					onChange={ ( v ) => onChange( 'title_separator', v ) }
					labelledBy="lunar-separator-picker-label"
				/>
				<p className="lunar-field__preview" aria-live="polite">
					{ __( 'Preview', 'lunar-seo' ) }
					{ ': ' }
					{ __( 'Example Post Title', 'lunar-seo' ) }
					{ ' ' }
					{ titleSeparator }
					{ ' ' }
					{ value.website_name || __( '(Website Name)', 'lunar-seo' ) }
				</p>
			</div>

			<MediaUploadField
				label={ __( 'Site Image', 'lunar-seo' ) }
				help={ __(
					'Used as a fallback for posts/pages that don\u2019t have any images set.',
					'lunar-seo'
				) }
				recommendedSize={ __( 'Recommended size: 1200 x 630 px', 'lunar-seo' ) }
				imageId={ value.site_image_id || 0 }
				onSelect={ ( id ) => onChange( 'site_image_id', id ) }
				onRemove={ () => onChange( 'site_image_id', 0 ) }
			/>
		</>
	);
}
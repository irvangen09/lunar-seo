/**
 * Content Panel.
 *
 * Fields for the "Content" section — one ContentTypeFields group per
 * content type (Homepage, Post, Page, Search, 404). Rendered inside a
 * PanelBody by app.js.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';

import ContentTypeFields from './content-type-fields';

export default function ContentPanel( { value, onChange } ) {
	return (
		<>
			<h3>{ __( 'Homepage', 'lunar-seo' ) }</h3>
			<ContentTypeFields
				value={ value.homepage }
				onChange={ ( v ) => onChange( 'homepage', v ) }
				hasDescription
				titleVariables={ [ 'title', 'separator', 'site_name', 'tagline' ] }
				descriptionVariables={ [ 'title', 'tagline', 'site_name' ] }
				titlePlaceholder="{site_name} {separator} {tagline}"
			/>

			<h3>{ __( 'Post', 'lunar-seo' ) }</h3>
			<ContentTypeFields
				value={ value.post }
				onChange={ ( v ) => onChange( 'post', v ) }
				hasDescription
				titleVariables={ [ 'title', 'separator', 'site_name' ] }
				descriptionVariables={ [ 'title', 'site_name' ] }
			/>

			<h3>{ __( 'Page', 'lunar-seo' ) }</h3>
			<ContentTypeFields
				value={ value.page }
				onChange={ ( v ) => onChange( 'page', v ) }
				hasDescription
				titleVariables={ [ 'title', 'separator', 'site_name' ] }
				descriptionVariables={ [ 'title', 'site_name' ] }
			/>

			<h3>{ __( 'Search', 'lunar-seo' ) }</h3>
			<ContentTypeFields
				value={ value.search }
				onChange={ ( v ) => onChange( 'search', v ) }
				hasDescription={ false }
				titleVariables={ [ 'query', 'separator', 'site_name' ] }
			/>

			<h3>{ __( '404 (Not Found)', 'lunar-seo' ) }</h3>
			<ContentTypeFields
				value={ value.not_found }
				onChange={ ( v ) => onChange( 'not_found', v ) }
				hasDescription={ false }
				titleVariables={ [ 'separator', 'site_name' ] }
			/>
		</>
	);
}
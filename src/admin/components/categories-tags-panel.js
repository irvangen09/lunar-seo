/**
 * Categories & Tags Panel.
 *
 * Fields for the "Categories & Tags" section — one TaxonomyFields
 * group per taxonomy (Categories, Tags). Rendered inside a PanelBody
 * by app.js.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';

import TaxonomyFields from './taxonomy-fields';

export default function CategoriesTagsPanel( { value, onChange } ) {
	return (
		<>
			<h3>{ __( 'Categories', 'lunar-seo' ) }</h3>
			<TaxonomyFields
				value={ value.categories }
				onChange={ ( v ) => onChange( 'categories', v ) }
				titleVariables={ [ 'term_title', 'separator', 'site_name' ] }
				descriptionVariables={ [ 'term_title', 'site_name' ] }
			/>

			<h3>{ __( 'Tags', 'lunar-seo' ) }</h3>
			<TaxonomyFields
				value={ value.tags }
				onChange={ ( v ) => onChange( 'tags', v ) }
				titleVariables={ [ 'term_title', 'separator', 'site_name' ] }
				descriptionVariables={ [ 'term_title', 'site_name' ] }
			/>
		</>
	);
}
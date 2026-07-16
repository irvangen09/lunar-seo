/**
 * Taxonomy Fields.
 *
 * Fields untuk satu tipe taksonomi (Categories atau Tags) pada
 * section Categories & Tags. Konsisten dengan
 * Settings/CategoriesTags.php (PHP): show_in_search_results,
 * seo_title, meta_description, auto_generate_description.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

import TemplateField from '../../shared/template-field';

export default function TaxonomyFields( { value, onChange, titleVariables, descriptionVariables } ) {
	const data = value || {};

	const updateField = ( field, fieldValue ) => {
		onChange( { ...data, [ field ]: fieldValue } );
	};

	return (
		<div className="lunar-taxonomy-fields">
			<ToggleControl
				label={ __( 'Show in search results', 'lunar-seo' ) }
				help={ __(
					'Enable to allow archives to appear in search engine results.',
					'lunar-seo'
				) }
				checked={ !! data.show_in_search_results }
				onChange={ ( checked ) => updateField( 'show_in_search_results', checked ) }
			/>

			<TemplateField
				label={ __( 'SEO Title', 'lunar-seo' ) }
				value={ data.seo_title }
				onChange={ ( v ) => updateField( 'seo_title', v ) }
				variables={ titleVariables }
				maxLength={ 60 }
			/>

			<TemplateField
				label={ __( 'Meta Description', 'lunar-seo' ) }
				value={ data.meta_description }
				onChange={ ( v ) => updateField( 'meta_description', v ) }
				variables={ descriptionVariables }
				maxLength={ 160 }
				multiline
			/>

			<ToggleControl
				label={ __( 'Auto-generate meta description', 'lunar-seo' ) }
				help={ __(
					"If the meta description is empty, we'll automatically generate one from your content.",
					'lunar-seo'
				) }
				checked={ !! data.auto_generate_description }
				onChange={ ( checked ) => updateField( 'auto_generate_description', checked ) }
			/>
		</div>
	);
}

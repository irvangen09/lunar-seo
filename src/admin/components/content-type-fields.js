/**
 * Content Type Fields.
 *
 * Fields for one content type in the Content section. Homepage/Post/
 * Page have SEO Title + Meta Description + an auto-generate toggle;
 * Search/404 have only SEO Title (consistent with
 * Settings/Content.php::TYPES_WITH_DESCRIPTION vs TYPES_TITLE_ONLY).
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

import TemplateField from '../../shared/template-field';

export default function ContentTypeFields( {
	value,
	onChange,
	hasDescription,
	titleVariables,
	descriptionVariables,
	titlePlaceholder,
} ) {
	const data = value || {};

	const updateField = ( field, fieldValue ) => {
		onChange( { ...data, [ field ]: fieldValue } );
	};

	return (
		<div className="lunar-content-type-fields">
			<TemplateField
				label={ __( 'SEO Title', 'lunar-seo' ) }
				value={ data.seo_title }
				onChange={ ( v ) => updateField( 'seo_title', v ) }
				variables={ titleVariables }
				maxLength={ 60 }
				placeholder={ titlePlaceholder }
			/>

			{ hasDescription && (
				<>
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
				</>
			) }
		</div>
	);
}
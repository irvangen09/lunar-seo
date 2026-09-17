/**
 * Sitemap Content Fields.
 *
 * Fixed checkboxes (WordPress standard content) + dynamic checkboxes
 * for Custom Post Types/Taxonomies (fetched from the REST metadata
 * endpoint "/lunar-seo/v1/sitemap-content-types" — see Settings.php).
 *
 * @package Lunar\SEO
 */

import { __, sprintf } from '@wordpress/i18n';
import { CheckboxControl, TextControl } from '@wordpress/components';

const CORE_CHECKBOXES = [
	{ key: 'include_homepage', label: __( 'Include homepage', 'lunar-seo' ) },
	{ key: 'include_posts', label: __( 'Include posts', 'lunar-seo' ) },
	{ key: 'include_static_pages', label: __( 'Include static pages', 'lunar-seo' ) },
	{ key: 'include_categories', label: __( 'Include categories', 'lunar-seo' ) },
	{ key: 'include_archives', label: __( 'Include archives', 'lunar-seo' ) },
	{ key: 'include_author_pages', label: __( 'Include author pages', 'lunar-seo' ) },
	{ key: 'include_tag_pages', label: __( 'Include tag pages', 'lunar-seo' ) },
];

export default function SitemapContentFields( { value, onChange, contentTypes } ) {
	const data = value || {};
	const customPostTypes = data.custom_post_types || {};
	const customTaxonomies = data.custom_taxonomies || {};

	const updateField = ( field, fieldValue ) => {
		onChange( { ...data, [ field ]: fieldValue } );
	};

	const updateCustomToggle = ( group, slug, checked ) => {
		const current = data[ group ] || {};
		onChange( { ...data, [ group ]: { ...current, [ slug ]: checked } } );
	};

	return (
		<div className="lunar-sitemap-content-fields">
			<h3 id="lunar-sitemap-core-label">{ __( 'WordPress standard content', 'lunar-seo' ) }</h3>
			<div role="group" aria-labelledby="lunar-sitemap-core-label">
				{ CORE_CHECKBOXES.map( ( item ) => (
					<CheckboxControl
						key={ item.key }
						label={ item.label }
						checked={ !! data[ item.key ] }
						onChange={ ( checked ) => updateField( item.key, checked ) }
					/>
				) ) }
			</div>

			{ contentTypes && contentTypes.post_types && contentTypes.post_types.length > 0 && (
				<>
					<h3 id="lunar-sitemap-cpt-label">{ __( 'Custom post types', 'lunar-seo' ) }</h3>
					<div role="group" aria-labelledby="lunar-sitemap-cpt-label">
						{ contentTypes.post_types.map( ( postType ) => (
							<CheckboxControl
								key={ postType.slug }
								label={ sprintf(
									/* translators: %s: custom post type name */
									__( 'Include custom post type %s', 'lunar-seo' ),
									postType.label
								) }
								checked={ !! customPostTypes[ postType.slug ] }
								onChange={ ( checked ) =>
									updateCustomToggle( 'custom_post_types', postType.slug, checked )
								}
							/>
						) ) }
					</div>
				</>
			) }

			{ contentTypes && contentTypes.taxonomies && contentTypes.taxonomies.length > 0 && (
				<>
					<h3 id="lunar-sitemap-taxonomy-label">{ __( 'Custom taxonomies', 'lunar-seo' ) }</h3>
					<div role="group" aria-labelledby="lunar-sitemap-taxonomy-label">
						{ contentTypes.taxonomies.map( ( taxonomy ) => (
							<CheckboxControl
								key={ taxonomy.slug }
								label={ sprintf(
									/* translators: %s: custom taxonomy name */
									__( 'Include custom taxonomy %s', 'lunar-seo' ),
									taxonomy.label
								) }
								checked={ !! customTaxonomies[ taxonomy.slug ] }
								onChange={ ( checked ) =>
									updateCustomToggle( 'custom_taxonomies', taxonomy.slug, checked )
								}
							/>
						) ) }
					</div>
				</>
			) }

			<h3 id="lunar-sitemap-further-label">{ __( 'Further options', 'lunar-seo' ) }</h3>
			<div role="group" aria-labelledby="lunar-sitemap-further-label">
				<CheckboxControl
					label={ __( 'Include the last modification time.', 'lunar-seo' ) }
					help={ __(
						'This is highly recommended and helps the search engines to know when your content has changed. This option affects all sitemap entries.',
						'lunar-seo'
					) }
					checked={ !! data.include_last_modified }
					onChange={ ( checked ) => updateField( 'include_last_modified', checked ) }
				/>

				<TextControl
					label={ __( 'Links per page', 'lunar-seo' ) }
					type="number"
					value={ data.links_per_page || 1000 }
					onChange={ ( v ) => updateField( 'links_per_page', parseInt( v, 10 ) || 1000 ) }
				/>
			</div>
		</div>
	);
}
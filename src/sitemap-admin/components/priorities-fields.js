/**
 * Priorities Fields.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { SelectControl, ToggleControl } from '@wordpress/components';

const PRIORITY_OPTIONS = [ '1.0', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1', '0.0' ].map(
	( v ) => ( { label: v, value: v } )
);

const FIELDS = [
	{ key: 'homepage', label: __( 'Homepage', 'lunar-seo' ) },
	{ key: 'posts', label: __( 'Posts (If auto calculation is disabled)', 'lunar-seo' ) },
	{
		key: 'minimum_post_priority',
		label: __( 'Minimum post priority (Even if auto calculation is enabled)', 'lunar-seo' ),
	},
	{ key: 'static_pages', label: __( 'Static pages', 'lunar-seo' ) },
	{ key: 'categories', label: __( 'Categories', 'lunar-seo' ) },
	{ key: 'archives', label: __( 'Archives', 'lunar-seo' ) },
	{ key: 'tag_pages', label: __( 'Tag pages', 'lunar-seo' ) },
	{ key: 'author_pages', label: __( 'Author pages', 'lunar-seo' ) },
	{ key: 'custom_post_type_default', label: __( 'Custom post types (default)', 'lunar-seo' ) },
	{ key: 'custom_taxonomy_default', label: __( 'Custom taxonomies (default)', 'lunar-seo' ) },
];

export default function PrioritiesFields( { value, onChange } ) {
	const data = value || {};

	const updateField = ( field, fieldValue ) => {
		onChange( { ...data, [ field ]: fieldValue } );
	};

	return (
		<div className="lunar-priorities-fields">
			<ToggleControl
				label={ __( 'Automatic priority calculation for Posts', 'lunar-seo' ) }
				help={ __(
					'Newer posts get priority closer to "Posts", older posts closer to "Minimum post priority".',
					'lunar-seo'
				) }
				checked={ !! data.auto_calculate_post_priority }
				onChange={ ( checked ) => updateField( 'auto_calculate_post_priority', checked ) }
			/>

			{ FIELDS.map( ( field ) => (
				<SelectControl
					key={ field.key }
					label={ field.label }
					value={ String( data[ field.key ] ?? '0.3' ) }
					options={ PRIORITY_OPTIONS }
					onChange={ ( v ) => updateField( field.key, parseFloat( v ) ) }
				/>
			) ) }
		</div>
	);
}

/**
 * Changefreq Fields.
 *
 * Tidak ada mockup untuk UI ini - struktur meniru PrioritiesFields
 * (dikonfirmasi dengan pengguna).
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

const CHANGEFREQ_OPTIONS = [ 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never' ].map(
	( v ) => ( { label: v, value: v } )
);

const FIELDS = [
	{ key: 'homepage', label: __( 'Homepage', 'lunar-seo' ) },
	{ key: 'posts', label: __( 'Posts', 'lunar-seo' ) },
	{ key: 'static_pages', label: __( 'Static pages', 'lunar-seo' ) },
	{ key: 'categories', label: __( 'Categories', 'lunar-seo' ) },
	{ key: 'archives', label: __( 'Archives', 'lunar-seo' ) },
	{ key: 'tag_pages', label: __( 'Tag pages', 'lunar-seo' ) },
	{ key: 'author_pages', label: __( 'Author pages', 'lunar-seo' ) },
	{ key: 'custom_post_type_default', label: __( 'Custom post types (default)', 'lunar-seo' ) },
	{ key: 'custom_taxonomy_default', label: __( 'Custom taxonomies (default)', 'lunar-seo' ) },
];

export default function ChangefreqFields( { value, onChange } ) {
	const data = value || {};

	const updateField = ( field, fieldValue ) => {
		onChange( { ...data, [ field ]: fieldValue } );
	};

	return (
		<div className="lunar-changefreq-fields">
			{ FIELDS.map( ( field ) => (
				<SelectControl
					key={ field.key }
					label={ field.label }
					value={ data[ field.key ] || 'monthly' }
					options={ CHANGEFREQ_OPTIONS }
					onChange={ ( v ) => updateField( field.key, v ) }
				/>
			) ) }
		</div>
	);
}

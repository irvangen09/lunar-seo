/**
 * Excluded Items Fields.
 *
 * Checklist kategori diambil dari endpoint REST BAWAAN WordPress
 * (/wp/v2/categories) - berbeda dari data setting kita sendiri,
 * daftar kategori adalah resource WP core standar sehingga tidak
 * memiliki keterbatasan nested-object seperti /wp/v2/settings (lihat
 * penjelasan pada modules/general/Settings/Settings.php).
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { CheckboxControl, TextControl } from '@wordpress/components';

export default function ExcludedItemsFields( { value, onChange } ) {
	const data = value || {};
	const excludedCategories = data.excluded_categories || [];
	const excludedPosts = data.excluded_posts || [];

	const [ categories, setCategories ] = useState( [] );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/categories?per_page=100&orderby=name&order=asc' } ).then(
			setCategories
		);
	}, [] );

	const toggleCategory = ( id, checked ) => {
		const next = checked
			? [ ...excludedCategories, id ]
			: excludedCategories.filter( ( catId ) => catId !== id );

		onChange( { ...data, excluded_categories: next } );
	};

	const updateExcludedPosts = ( text ) => {
		const ids = text
			.split( ',' )
			.map( ( part ) => parseInt( part.trim(), 10 ) )
			.filter( ( id ) => ! Number.isNaN( id ) );

		onChange( { ...data, excluded_posts: ids } );
	};

	return (
		<div className="lunar-excluded-items-fields">
			<p>
				<strong>{ __( 'Excluded categories', 'lunar-seo' ) }</strong>
			</p>
			<div className="lunar-excluded-categories-list">
				{ categories.map( ( category ) => (
					<CheckboxControl
						key={ category.id }
						label={ category.name }
						checked={ excludedCategories.includes( category.id ) }
						onChange={ ( checked ) => toggleCategory( category.id, checked ) }
					/>
				) ) }
			</div>

			<TextControl
				label={ __( 'Exclude posts', 'lunar-seo' ) }
				help={ __(
					"Exclude the following posts or pages: List of IDs, separated by comma. Note: Child posts won't be excluded automatically!",
					'lunar-seo'
				) }
				value={ excludedPosts.join( ',' ) }
				onChange={ updateExcludedPosts }
			/>
		</div>
	);
}

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
		let isMounted = true;

		/**
		 * Ambil SELURUH kategori lewat pagination, bukan hanya 100
		 * pertama - 100 adalah batas maksimum per_page yang diizinkan
		 * WP REST API, sehingga kategori di halaman berikutnya perlu
		 * diambil lewat request terpisah agar tidak ada yang hilang
		 * dari checklist (relevan untuk situs dengan banyak kategori).
		 */
		const fetchAllCategories = async () => {
			const perPage = 100;
			let page = 1;
			let allCategories = [];

			// eslint-disable-next-line no-constant-condition
			while ( true ) {
				let batch;

				try {
					batch = await apiFetch( {
						path: `/wp/v2/categories?per_page=${ perPage }&page=${ page }&orderby=name&order=asc`,
					} );
				} catch ( error ) {
					// WP REST API menolak page yang melebihi total halaman -
					// berhenti dengan aman, kategori yang sudah terkumpul
					// tetap ditampilkan.
					break;
				}

				allCategories = allCategories.concat( batch );

				if ( batch.length < perPage ) {
					break;
				}

				page += 1;
			}

			if ( isMounted ) {
				setCategories( allCategories );
			}
		};

		fetchAllCategories();

		return () => {
			isMounted = false;
		};
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

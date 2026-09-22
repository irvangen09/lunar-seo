/**
 * Excluded Items Fields.
 *
 * The category checklist is fetched from WordPress's own BUILT-IN
 * REST endpoint (/wp/v2/categories) — unlike our own setting data, the
 * category list is a standard WP core resource, so it doesn't have the
 * nested-object limitation /wp/v2/settings has (see the explanation in
 * modules/general/Settings/Settings.php).
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
		 * Fetches EVERY category via pagination, not just the first 100
		 * — 100 is the maximum per_page the WP REST API allows, so
		 * categories on the next page need a separate request or
		 * they'd be missing from the checklist (relevant for sites with
		 * many categories).
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
					// The WP REST API rejects a page beyond the total page
					// count — stop safely, whatever categories were
					// already collected are still shown.
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
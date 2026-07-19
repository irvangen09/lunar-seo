/**
 * useRestSettings.
 *
 * Hook shared untuk fetch & save data settings via REST route custom
 * module (lunar-seo/v1/{module}-settings). Diekstrak dari logic yang
 * sebelumnya duplikat 1:1 di src/admin/app.js dan
 * src/sitemap-admin/app.js.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * @param {string} restPath Path REST route, contoh: '/lunar-seo/v1/general-settings'.
 * @return {{settings: (Object|null), setSettings: Function, isSaving: boolean, notice: (Object|null), save: Function}}
 */
export default function useRestSettings( restPath ) {
	const [ settings, setSettings ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		let isMounted = true;

		apiFetch( { path: restPath } )
			.then( ( response ) => {
				if ( isMounted ) {
					setSettings( response || {} );
				}
			} )
			.catch( () => {
				// Tanpa .catch() ini, kegagalan request pertama membuat
				// `settings` tetap null selamanya - UI macet di <Spinner />
				// tanpa pesan error apapun.
				if ( isMounted ) {
					setNotice( {
						status: 'error',
						message: __(
							'Failed to load settings. Please refresh the page.',
							'lunar-seo'
						),
					} );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [ restPath ] );

	const save = () => {
		setIsSaving( true );
		setNotice( null );

		apiFetch( {
			path: restPath,
			method: 'POST',
			data: settings,
		} )
			.then( ( response ) => {
				setSettings( response || settings );
				setNotice( {
					status: 'success',
					message: __( 'Settings saved successfully.', 'lunar-seo' ),
				} );
			} )
			.catch( () => {
				setNotice( {
					status: 'error',
					message: __( 'Failed to save settings.', 'lunar-seo' ),
				} );
			} )
			.finally( () => setIsSaving( false ) );
	};

	return { settings, setSettings, isSaving, notice, save };
}

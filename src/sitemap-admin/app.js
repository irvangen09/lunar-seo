/**
 * React Settings App - Lunar SEO Sitemap.
 *
 * Shell utama halaman Settings module Sitemap. Pola identik dengan
 * src/admin/app.js (module General) - REST route custom, bukan
 * /wp/v2/settings.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice, Spinner, Panel, PanelBody } from '@wordpress/components';

import SitemapContentFields from './components/sitemap-content-fields';
import ExcludedItemsFields from './components/excluded-items-fields';
import PrioritiesFields from './components/priorities-fields';
import ChangefreqFields from './components/changefreq-fields';

const REST_PATH = '/lunar-seo/v1/sitemap-settings';
const CONTENT_TYPES_PATH = '/lunar-seo/v1/sitemap-content-types';

export default function App() {
	const [ settings, setSettings ] = useState( null );
	const [ contentTypes, setContentTypes ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		apiFetch( { path: REST_PATH } ).then( ( response ) => setSettings( response || {} ) );
		apiFetch( { path: CONTENT_TYPES_PATH } ).then( setContentTypes );
	}, [] );

	if ( null === settings ) {
		return <Spinner />;
	}

	const updateSection = ( section, value ) => {
		setSettings( { ...settings, [ section ]: value } );
	};

	const handleSave = () => {
		setIsSaving( true );
		setNotice( null );

		apiFetch( {
			path: REST_PATH,
			method: 'POST',
			data: settings,
		} )
			.then( ( response ) => {
				setSettings( response || settings );
				setNotice( {
					status: 'success',
					message: __( 'Pengaturan berhasil disimpan.', 'lunar-seo' ),
				} );
			} )
			.catch( () => {
				setNotice( {
					status: 'error',
					message: __( 'Gagal menyimpan pengaturan.', 'lunar-seo' ),
				} );
			} )
			.finally( () => setIsSaving( false ) );
	};

	return (
		<div className="lunar-settings">
			<div className="lunar-settings__header">
				<h1>{ __( 'Lunar SEO - Sitemap', 'lunar-seo' ) }</h1>
				<Button variant="primary" isBusy={ isSaving } disabled={ isSaving } onClick={ handleSave }>
					{ __( 'Save Changes', 'lunar-seo' ) }
				</Button>
			</div>

			{ notice && (
				<Notice status={ notice.status } isDismissible={ false }>
					{ notice.message }
				</Notice>
			) }

			<Panel>
				<PanelBody title={ __( 'Sitemap Content', 'lunar-seo' ) } initialOpen>
					<SitemapContentFields
						value={ settings.sitemap_content }
						onChange={ ( v ) => updateSection( 'sitemap_content', v ) }
						contentTypes={ contentTypes }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Excluded Items', 'lunar-seo' ) } initialOpen={ false }>
					<ExcludedItemsFields
						value={ settings.excluded_items }
						onChange={ ( v ) => updateSection( 'excluded_items', v ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Priorities', 'lunar-seo' ) } initialOpen={ false }>
					<PrioritiesFields
						value={ settings.priorities }
						onChange={ ( v ) => updateSection( 'priorities', v ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Changefreq', 'lunar-seo' ) } initialOpen={ false }>
					<ChangefreqFields
						value={ settings.changefreq }
						onChange={ ( v ) => updateSection( 'changefreq', v ) }
					/>
				</PanelBody>
			</Panel>
		</div>
	);
}

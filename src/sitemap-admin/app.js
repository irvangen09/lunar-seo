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

import useRestSettings from '../shared/use-rest-settings';
import SitemapContentFields from './components/sitemap-content-fields';
import ExcludedItemsFields from './components/excluded-items-fields';
import PrioritiesFields from './components/priorities-fields';
import ChangefreqFields from './components/changefreq-fields';

const REST_PATH = '/lunar-seo/v1/sitemap-settings';
const CONTENT_TYPES_PATH = '/lunar-seo/v1/sitemap-content-types';

export default function App() {
	const { settings, setSettings, isSaving, notice, save } = useRestSettings( REST_PATH );
	const [ contentTypes, setContentTypes ] = useState( null );

	useEffect( () => {
		let isMounted = true;

		apiFetch( { path: CONTENT_TYPES_PATH } )
			.then( ( response ) => {
				if ( isMounted ) {
					setContentTypes( response );
				}
			} )
			.catch( () => {
				// Gagal ambil daftar Custom Post Type/Taxonomy bukan hal
				// fatal - SitemapContentFields tetap bisa merender
				// checkbox WordPress Standard Content tanpa daftar CPT
				// dinamis apabila contentTypes tetap null.
			} );

		return () => {
			isMounted = false;
		};
	}, [] );

	if ( null === settings ) {
		return notice ? (
			<Notice status={ notice.status } isDismissible={ false }>
				{ notice.message }
			</Notice>
		) : (
			<Spinner />
		);
	}

	const updateSection = ( section, value ) => {
		setSettings( { ...settings, [ section ]: value } );
	};

	return (
		<div className="lunar-settings">
			<div className="lunar-settings__header">
				<h1>{ __( 'Lunar SEO - Sitemap', 'lunar-seo' ) }</h1>
				<Button variant="primary" isBusy={ isSaving } disabled={ isSaving } onClick={ save }>
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

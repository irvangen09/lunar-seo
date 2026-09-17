/**
 * React Settings App — Lunar SEO.
 *
 * Main shell for the Settings page. Reads and saves data via the
 * General module's own REST route ("lunar-seo/v1/general-settings"),
 * not the generic /wp/v2/settings endpoint — see Settings.php for why
 * the generic endpoint doesn't work for our nested object data.
 *
 * The data shape mirrors what PHP sanitizes (Settings/*.php) — one key
 * per section (site_info, content, categories_tags, social,
 * verification, robots_url) inside the "lunar_seo_general_settings"
 * option.
 *
 * This shell owns the settings state and the save/update handlers;
 * each section's fields live in their own component under
 * ./components/, following the same split already used by
 * sitemap-admin/app.js.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { Button, Notice, Spinner, Panel, PanelBody } from '@wordpress/components';

import useRestSettings from '../shared/use-rest-settings';
import SiteInfoPanel from './components/site-info-panel';
import ContentPanel from './components/content-panel';
import CategoriesTagsPanel from './components/categories-tags-panel';
import SocialPanel from './components/social-panel';
import VerificationPanel from './components/verification-panel';
import RobotsUrlPanel from './components/robots-url-panel';

// The General module's own REST route (see Settings.php ->
// register_rest_routes()). Not the generic /wp/v2/settings endpoint,
// which can't reliably save nested object data like our settings
// structure.
const REST_PATH = '/lunar-seo/v1/general-settings';

export default function App() {
	const { settings, setSettings, isSaving, notice, save } = useRestSettings( REST_PATH );

	if ( null === settings ) {
		return notice ? (
			<Notice status={ notice.status } isDismissible={ false }>
				{ notice.message }
			</Notice>
		) : (
			<Spinner />
		);
	}

	const siteInfo = settings.site_info || {};
	const content = settings.content || {};
	const categoriesTags = settings.categories_tags || {};
	const social = settings.social || {};
	const verification = settings.verification || {};
	const robotsUrl = settings.robots_url || {};

	const updateSiteInfo = ( field, value ) => {
		setSettings( {
			...settings,
			site_info: { ...siteInfo, [ field ]: value },
		} );
	};

	const updateContent = ( contentType, value ) => {
		setSettings( {
			...settings,
			content: { ...content, [ contentType ]: value },
		} );
	};

	const updateCategoriesTags = ( taxonomyType, value ) => {
		setSettings( {
			...settings,
			categories_tags: { ...categoriesTags, [ taxonomyType ]: value },
		} );
	};

	const updateSocialPlatform = ( platform, field, fieldValue ) => {
		setSettings( {
			...settings,
			social: {
				...social,
				[ platform ]: { ...( social[ platform ] || {} ), [ field ]: fieldValue },
			},
		} );
	};

	const updateVerification = ( platform, fieldValue ) => {
		setSettings( {
			...settings,
			verification: { ...verification, [ platform ]: fieldValue },
		} );
	};

	const updateRobotsUrl = ( field, value ) => {
		setSettings( {
			...settings,
			robots_url: { ...robotsUrl, [ field ]: value },
		} );
	};

	return (
		<div className="lunar-settings">
			<div className="lunar-settings__header">
				<h1>{ __( 'Lunar SEO', 'lunar-seo' ) }</h1>
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
				<PanelBody title={ __( 'Site Info', 'lunar-seo' ) } initialOpen>
					<SiteInfoPanel value={ siteInfo } onChange={ updateSiteInfo } />
				</PanelBody>

				<PanelBody title={ __( 'Content', 'lunar-seo' ) } initialOpen={ false }>
					<ContentPanel value={ content } onChange={ updateContent } />
				</PanelBody>

				<PanelBody title={ __( 'Categories & Tags', 'lunar-seo' ) } initialOpen={ false }>
					<CategoriesTagsPanel value={ categoriesTags } onChange={ updateCategoriesTags } />
				</PanelBody>

				<PanelBody title={ __( 'Social', 'lunar-seo' ) } initialOpen={ false }>
					<SocialPanel value={ social } onChange={ updateSocialPlatform } />
				</PanelBody>

				<PanelBody title={ __( 'Verification', 'lunar-seo' ) } initialOpen={ false }>
					<VerificationPanel value={ verification } onChange={ updateVerification } />
				</PanelBody>

				<PanelBody title={ __( 'Robots & URL', 'lunar-seo' ) } initialOpen={ false }>
					<RobotsUrlPanel value={ robotsUrl } onChange={ updateRobotsUrl } />
				</PanelBody>
			</Panel>
		</div>
	);
}
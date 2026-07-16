/**
 * React Settings App - Lunar SEO.
 *
 * Shell utama halaman Settings, membaca dan menyimpan data via REST
 * route KHUSUS module General ("lunar-seo/v1/general-settings"),
 * BUKAN endpoint generic /wp/v2/settings - lihat penjelasan di
 * Settings.php soal keterbatasan endpoint generic untuk data object
 * bersarang.
 *
 * Struktur data mengikuti bentuk yang sama dengan yang disanitasi
 * PHP (Settings/*.php) - satu key per section (site_info, content,
 * categories_tags, social, verification, robots_url) di dalam
 * option "lunar_seo_general_settings".
 *
 * Section Site Info sudah lengkap (Website Name, Alternate Website
 * Name, Title Separator, Site Image) sebagai pola rujukan. Section
 * lain masih berupa placeholder accordion kosong.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	TextControl,
	ToggleControl,
	CheckboxControl,
	SelectControl,
	Notice,
	Spinner,
	Panel,
	PanelBody,
} from '@wordpress/components';

import TitleSeparatorPicker from './components/title-separator-picker';
import MediaUploadField from './components/media-upload-field';
import ContentTypeFields from './components/content-type-fields';
import TaxonomyFields from './components/taxonomy-fields';

// Endpoint REST khusus module General (lihat Settings.php -
// register_rest_routes()). BUKAN endpoint generic /wp/v2/settings,
// karena endpoint tersebut memiliki keterbatasan untuk data object
// bersarang seperti struktur setting kita.
const REST_PATH = '/lunar-seo/v1/general-settings';

// Konsisten dengan Settings/RobotsUrl.php::ALLOWED_ROBOTS_DIRECTIVES.
// "index"/"follow" sengaja TIDAK termasuk - keduanya adalah perilaku
// default crawler yang tidak perlu (dan tidak bisa) dinyatakan
// eksplisit di robots meta tag (lihat MetaRenderer.php).
const ROBOTS_DIRECTIVES = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

// Konsisten dengan Settings/RobotsUrl.php::ALLOWED_ROBOTS_PRESETS.
const ROBOTS_PRESET_OPTIONS = [
	{ label: __( 'Use Default Robots Meta', 'lunar-seo' ), value: 'default' },
	{ label: __( 'index, follow', 'lunar-seo' ), value: 'index_follow' },
	{ label: __( 'noindex, follow', 'lunar-seo' ), value: 'noindex_follow' },
	{ label: __( 'noindex, nofollow', 'lunar-seo' ), value: 'noindex_nofollow' },
];

export default function App() {
	const [ settings, setSettings ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		apiFetch( { path: REST_PATH } ).then( ( response ) => {
			setSettings( response || {} );
		} );
	}, [] );

	if ( null === settings ) {
		return <Spinner />;
	}

	const siteInfo = settings.site_info || {};
	const content = settings.content || {};

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

	const categoriesTags = settings.categories_tags || {};

	const updateCategoriesTags = ( taxonomyType, value ) => {
		setSettings( {
			...settings,
			categories_tags: { ...categoriesTags, [ taxonomyType ]: value },
		} );
	};

	const social = settings.social || {};

	const updateSocialPlatform = ( platform, field, fieldValue ) => {
		setSettings( {
			...settings,
			social: {
				...social,
				[ platform ]: { ...( social[ platform ] || {} ), [ field ]: fieldValue },
			},
		} );
	};

	const verification = settings.verification || {};

	const updateVerification = ( platform, fieldValue ) => {
		setSettings( {
			...settings,
			verification: { ...verification, [ platform ]: fieldValue },
		} );
	};

	const robotsUrl = settings.robots_url || {};
	const defaultRobotsMeta = robotsUrl.default_robots_meta || [];

	const updateRobotsUrl = ( field, value ) => {
		setSettings( {
			...settings,
			robots_url: { ...robotsUrl, [ field ]: value },
		} );
	};

	const toggleDefaultRobotsDirective = ( directive ) => {
		const next = defaultRobotsMeta.includes( directive )
			? defaultRobotsMeta.filter( ( item ) => item !== directive )
			: [ ...defaultRobotsMeta, directive ];

		updateRobotsUrl( 'default_robots_meta', next );
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
				<h1>{ __( 'Lunar SEO', 'lunar-seo' ) }</h1>
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
				<PanelBody title={ __( 'Site Info', 'lunar-seo' ) } initialOpen>
					<TextControl
						label={ __( 'Website Name', 'lunar-seo' ) }
						help={ __( 'The name of your website.', 'lunar-seo' ) }
						value={ siteInfo.website_name || '' }
						onChange={ ( value ) => updateSiteInfo( 'website_name', value ) }
					/>
					<TextControl
						label={ __( 'Alternate Website Name', 'lunar-seo' ) }
						help={ __(
							'Use the alternate website name for acronyms, or a shorter version of your website\u2019s name.',
							'lunar-seo'
						) }
						value={ siteInfo.alternate_website_name || '' }
						onChange={ ( value ) => updateSiteInfo( 'alternate_website_name', value ) }
					/>

					<div className="lunar-field">
						<p>
							<strong>{ __( 'Title Separator', 'lunar-seo' ) }</strong>
						</p>
						<p className="lunar-field__help">
							{ __( 'Choose the separator used in title templates.', 'lunar-seo' ) }
						</p>
						<TitleSeparatorPicker
							value={ siteInfo.title_separator || '|' }
							onChange={ ( value ) => updateSiteInfo( 'title_separator', value ) }
						/>
						<p className="lunar-field__preview">
							{ __( 'Preview', 'lunar-seo' ) }
							{ ': ' }
							{ __( 'Example Post Title', 'lunar-seo' ) }
							{ ' ' }
							{ siteInfo.title_separator || '|' }
							{ ' ' }
							{ siteInfo.website_name || __( '(Website Name)', 'lunar-seo' ) }
						</p>
					</div>

					<MediaUploadField
						label={ __( 'Site Image', 'lunar-seo' ) }
						help={ __(
							'Used as a fallback for posts/pages that don\u2019t have any images set.',
							'lunar-seo'
						) }
						recommendedSize={ __( 'Recommended size: 1200 x 630 px', 'lunar-seo' ) }
						imageId={ siteInfo.site_image_id || 0 }
						onSelect={ ( id ) => updateSiteInfo( 'site_image_id', id ) }
						onRemove={ () => updateSiteInfo( 'site_image_id', 0 ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Content', 'lunar-seo' ) } initialOpen={ false }>
					<h3>{ __( 'Homepage', 'lunar-seo' ) }</h3>
					<ContentTypeFields
						value={ content.homepage }
						onChange={ ( v ) => updateContent( 'homepage', v ) }
						hasDescription
						titleVariables={ [ 'title', 'separator', 'site_name', 'tagline' ] }
						descriptionVariables={ [ 'title', 'tagline', 'site_name' ] }
						titlePlaceholder="{site_name} {separator} {tagline}"
					/>

					<h3>{ __( 'Post', 'lunar-seo' ) }</h3>
					<ContentTypeFields
						value={ content.post }
						onChange={ ( v ) => updateContent( 'post', v ) }
						hasDescription
						titleVariables={ [ 'title', 'separator', 'site_name' ] }
						descriptionVariables={ [ 'title', 'site_name' ] }
					/>

					<h3>{ __( 'Page', 'lunar-seo' ) }</h3>
					<ContentTypeFields
						value={ content.page }
						onChange={ ( v ) => updateContent( 'page', v ) }
						hasDescription
						titleVariables={ [ 'title', 'separator', 'site_name' ] }
						descriptionVariables={ [ 'title', 'site_name' ] }
					/>

					<h3>{ __( 'Search', 'lunar-seo' ) }</h3>
					<ContentTypeFields
						value={ content.search }
						onChange={ ( v ) => updateContent( 'search', v ) }
						hasDescription={ false }
						titleVariables={ [ 'query', 'separator', 'site_name' ] }
					/>

					<h3>{ __( '404 (Not Found)', 'lunar-seo' ) }</h3>
					<ContentTypeFields
						value={ content.not_found }
						onChange={ ( v ) => updateContent( 'not_found', v ) }
						hasDescription={ false }
						titleVariables={ [ 'separator', 'site_name' ] }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Categories & Tags', 'lunar-seo' ) } initialOpen={ false }>
					<h3>{ __( 'Categories', 'lunar-seo' ) }</h3>
					<TaxonomyFields
						value={ categoriesTags.categories }
						onChange={ ( v ) => updateCategoriesTags( 'categories', v ) }
						titleVariables={ [ 'term_title', 'separator', 'site_name' ] }
						descriptionVariables={ [ 'term_title', 'site_name' ] }
					/>

					<h3>{ __( 'Tags', 'lunar-seo' ) }</h3>
					<TaxonomyFields
						value={ categoriesTags.tags }
						onChange={ ( v ) => updateCategoriesTags( 'tags', v ) }
						titleVariables={ [ 'term_title', 'separator', 'site_name' ] }
						descriptionVariables={ [ 'term_title', 'site_name' ] }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Social', 'lunar-seo' ) } initialOpen={ false }>
					<h3>{ __( 'Open Graph', 'lunar-seo' ) }</h3>
					<ToggleControl
						label={ __( 'Enable Open Graph', 'lunar-seo' ) }
						help={ __( 'Add Open Graph meta tags to your site.', 'lunar-seo' ) }
						checked={ !! ( social.open_graph && social.open_graph.enabled ) }
						onChange={ ( checked ) => updateSocialPlatform( 'open_graph', 'enabled', checked ) }
					/>
					<MediaUploadField
						label={ __( 'Default Social Image', 'lunar-seo' ) }
						help={ __(
							'This image will be used if a post or page does not have a featured image.',
							'lunar-seo'
						) }
						recommendedSize={ __( 'Recommended size: 1200 x 630 px', 'lunar-seo' ) }
						imageId={ ( social.open_graph && social.open_graph.image_id ) || 0 }
						onSelect={ ( id ) => updateSocialPlatform( 'open_graph', 'image_id', id ) }
						onRemove={ () => updateSocialPlatform( 'open_graph', 'image_id', 0 ) }
					/>

					<h3>{ __( 'Twitter (X) Card', 'lunar-seo' ) }</h3>
					<ToggleControl
						label={ __( 'Enable Twitter Card', 'lunar-seo' ) }
						help={ __( 'Add Twitter Card meta tags to your site.', 'lunar-seo' ) }
						checked={ !! ( social.twitter_card && social.twitter_card.enabled ) }
						onChange={ ( checked ) => updateSocialPlatform( 'twitter_card', 'enabled', checked ) }
					/>
					<MediaUploadField
						label={ __( 'Default Twitter Image', 'lunar-seo' ) }
						help={ __(
							'This image will be used if a post or page does not have a featured image.',
							'lunar-seo'
						) }
						recommendedSize={ __( 'Recommended size: 1200 x 600 px', 'lunar-seo' ) }
						imageId={ ( social.twitter_card && social.twitter_card.image_id ) || 0 }
						onSelect={ ( id ) => updateSocialPlatform( 'twitter_card', 'image_id', id ) }
						onRemove={ () => updateSocialPlatform( 'twitter_card', 'image_id', 0 ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Verification', 'lunar-seo' ) } initialOpen={ false }>
					<TextControl
						label={ __( 'Google', 'lunar-seo' ) }
						help={ __( 'Get your verification code in Google Search Console.', 'lunar-seo' ) }
						placeholder={ __( 'Add verification code', 'lunar-seo' ) }
						value={ verification.google || '' }
						onChange={ ( value ) => updateVerification( 'google', value ) }
					/>

					<TextControl
						label={ __( 'Bing', 'lunar-seo' ) }
						help={ __( 'Get your verification code in Bing Webmaster Tools.', 'lunar-seo' ) }
						placeholder={ __( 'Add verification code', 'lunar-seo' ) }
						value={ verification.bing || '' }
						onChange={ ( value ) => updateVerification( 'bing', value ) }
					/>

					<TextControl
						label={ __( 'Yandex', 'lunar-seo' ) }
						help={ __( 'Get your verification code in Yandex Webmaster.', 'lunar-seo' ) }
						placeholder={ __( 'Add verification code', 'lunar-seo' ) }
						value={ verification.yandex || '' }
						onChange={ ( value ) => updateVerification( 'yandex', value ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Robots & URL', 'lunar-seo' ) } initialOpen={ false }>
					<h3>{ __( 'Default Robots Meta', 'lunar-seo' ) }</h3>
					<p className="lunar-field__help">
						{ __(
							'Choose the default search engine instructions for your site content.',
							'lunar-seo'
						) }
					</p>
					{ ROBOTS_DIRECTIVES.map( ( directive ) => (
						<CheckboxControl
							key={ directive }
							label={ directive }
							checked={ defaultRobotsMeta.includes( directive ) }
							onChange={ () => toggleDefaultRobotsDirective( directive ) }
						/>
					) ) }

					<SelectControl
						label={ __( 'Default Robots for Archives', 'lunar-seo' ) }
						help={ __(
							'Apply these settings to archive pages such as categories, tags, authors, search results, and date archives.',
							'lunar-seo'
						) }
						value={ robotsUrl.archives_robots || 'default' }
						options={ ROBOTS_PRESET_OPTIONS }
						onChange={ ( value ) => updateRobotsUrl( 'archives_robots', value ) }
					/>

					<SelectControl
						label={ __( 'Default Robots for 404 Pages', 'lunar-seo' ) }
						help={ __( 'Choose how search engines should handle 404 (Not Found) pages.', 'lunar-seo' ) }
						value={ robotsUrl.not_found_robots || 'noindex_follow' }
						options={ ROBOTS_PRESET_OPTIONS }
						onChange={ ( value ) => updateRobotsUrl( 'not_found_robots', value ) }
					/>

					<h3>{ __( 'URL', 'lunar-seo' ) }</h3>

					<ToggleControl
						label={ __( 'Remove Category Base', 'lunar-seo' ) }
						help={ __( 'Remove /category/ from category URLs.', 'lunar-seo' ) }
						checked={ !! robotsUrl.remove_category_base }
						onChange={ ( checked ) => updateRobotsUrl( 'remove_category_base', checked ) }
					/>

					<ToggleControl
						label={ __( 'Remove Tag Base', 'lunar-seo' ) }
						help={ __( 'Remove /tag/ from tag URLs.', 'lunar-seo' ) }
						checked={ !! robotsUrl.remove_tag_base }
						onChange={ ( checked ) => updateRobotsUrl( 'remove_tag_base', checked ) }
					/>

					<ToggleControl
						label={ __( 'Redirect Attachments to Parent', 'lunar-seo' ) }
						help={ __(
							'Redirect attachment pages to their parent post or page.',
							'lunar-seo'
						) }
						checked={ !! robotsUrl.redirect_attachments_to_parent }
						onChange={ ( checked ) =>
							updateRobotsUrl( 'redirect_attachments_to_parent', checked )
						}
					/>
				</PanelBody>
			</Panel>
		</div>
	);
}

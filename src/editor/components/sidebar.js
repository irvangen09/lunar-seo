/**
 * PluginSidebar - Lunar SEO.
 *
 * Berisi SEO Preview dan form input (SEO Title, Meta Description,
 * Canonical URL, Robots override), sesuai keputusan Editor
 * Integration Strategy (GENERAL_MODULE_ARCHITECTURE.md §4).
 *
 * Membaca/menulis post meta via useEntityProp - meta key HARUS
 * konsisten dengan yang diregistrasikan di Editor.php/PostMetaKeys.php
 * (PHP). Nilai batas karakter (60/160) hanya sebagai panduan visual
 * bagi penulis, bukan validasi keras.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl, CheckboxControl, PanelBody } from '@wordpress/components';

import Preview from './preview';
import TemplateField from '../../shared/template-field';
import {
	META_KEY_TITLE,
	META_KEY_DESCRIPTION,
	META_KEY_CANONICAL,
	META_KEY_ROBOTS,
	TITLE_VARIABLES,
	DESCRIPTION_VARIABLES,
	ROBOTS_DIRECTIVES,
	TITLE_MAX_LENGTH,
	DESCRIPTION_MAX_LENGTH,
	SUPPORTED_POST_TYPES,
} from '../constants';

const SIDEBAR_NAME = 'lunar-seo-sidebar';

export default function Sidebar() {
	const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
	const postTitle = useSelect(
		( select ) => select( 'core/editor' ).getEditedPostAttribute( 'title' ),
		[]
	);
	const permalink = useSelect( ( select ) => select( 'core/editor' ).getPermalink(), [] );

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	// Defense-in-depth: Assets.php sudah membatasi bundle ini agar
	// hanya termuat di post type yang didukung (post/page) - guard
	// ini murni jaga-jaga apabila suatu saat bundle tetap termuat
	// di context lain (attachment, CPT lain, dst).
	if ( ! SUPPORTED_POST_TYPES.includes( postType ) ) {
		return null;
	}

	// meta belum tersedia (misal saat post baru belum tersimpan) -
	// jangan render form untuk menghindari error pada undefined.
	if ( ! meta ) {
		return null;
	}

	const seoTitle = meta[ META_KEY_TITLE ] || '';
	const metaDescription = meta[ META_KEY_DESCRIPTION ] || '';
	const canonical = meta[ META_KEY_CANONICAL ] || '';
	const robots = meta[ META_KEY_ROBOTS ] || [];

	const updateMeta = ( key, value ) => {
		setMeta( { ...meta, [ key ]: value } );
	};

	const toggleRobotsDirective = ( directive ) => {
		const next = robots.includes( directive )
			? robots.filter( ( item ) => item !== directive )
			: [ ...robots, directive ];

		updateMeta( META_KEY_ROBOTS, next );
	};

	return (
		<>
			<PluginSidebarMoreMenuItem target={ SIDEBAR_NAME } icon="search">
				{ __( 'Lunar SEO', 'lunar-seo' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar name={ SIDEBAR_NAME } title={ __( 'Lunar SEO', 'lunar-seo' ) } icon="search">
				<PanelBody title={ __( 'SEO Preview', 'lunar-seo' ) } initialOpen>
					<Preview
						title={ seoTitle || postTitle }
						description={ metaDescription }
						url={ permalink }
					/>
				</PanelBody>

				<PanelBody title={ __( 'General', 'lunar-seo' ) } initialOpen>
					<TemplateField
						label={ __( 'SEO Title', 'lunar-seo' ) }
						value={ seoTitle }
						onChange={ ( value ) => updateMeta( META_KEY_TITLE, value ) }
						variables={ TITLE_VARIABLES }
						maxLength={ TITLE_MAX_LENGTH }
					/>

					<TemplateField
						label={ __( 'Meta Description', 'lunar-seo' ) }
						value={ metaDescription }
						onChange={ ( value ) => updateMeta( META_KEY_DESCRIPTION, value ) }
						variables={ DESCRIPTION_VARIABLES }
						maxLength={ DESCRIPTION_MAX_LENGTH }
						multiline
					/>

					<TextControl
						label={ __( 'Canonical URL', 'lunar-seo' ) }
						value={ canonical }
						onChange={ ( value ) => updateMeta( META_KEY_CANONICAL, value ) }
						placeholder={ permalink }
						help={ __( 'Leave empty to use the default URL.', 'lunar-seo' ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Robots', 'lunar-seo' ) } initialOpen={ false }>
					<div role="group" aria-label={ __( 'Robots', 'lunar-seo' ) }>
						{ ROBOTS_DIRECTIVES.map( ( directive ) => (
							<CheckboxControl
								key={ directive }
								label={ directive }
								checked={ robots.includes( directive ) }
								onChange={ () => toggleRobotsDirective( directive ) }
							/>
						) ) }
					</div>
				</PanelBody>
			</PluginSidebar>
		</>
	);
}

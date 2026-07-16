/**
 * Entry point Editor.
 *
 * Registrasi PluginSidebar (preview + form lengkap) dan
 * PluginDocumentSettingPanel (indikator ringkas), sesuai keputusan
 * Editor Integration Strategy (GENERAL_MODULE_ARCHITECTURE.md §4).
 *
 * @package Lunar\SEO
 */

import { registerPlugin } from '@wordpress/plugins';

import Sidebar from './components/sidebar';
import DocumentPanel from './components/document-panel';

const PLUGIN_NAME = 'lunar-seo';

registerPlugin( PLUGIN_NAME, {
	render: Sidebar,
} );

registerPlugin( `${ PLUGIN_NAME }-document-panel`, {
	render: DocumentPanel,
} );

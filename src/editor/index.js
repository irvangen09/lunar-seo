/**
 * Editor entry point.
 *
 * Registers PluginSidebar (preview + full form) and
 * PluginDocumentSettingPanel (quick-glance indicator) — the sidebar
 * carries the SEO Preview and full form since it has room for both
 * side by side, while the document panel stays a lightweight status
 * indicator in the Document sidebar.
 *
 * @package Lunar\SEO
 */

import { registerPlugin } from '@wordpress/plugins';

import Sidebar from './components/sidebar';
import DocumentPanel from './components/document-panel';
import './style.css';

const PLUGIN_NAME = 'lunar-seo';

registerPlugin( PLUGIN_NAME, {
	render: Sidebar,
} );

registerPlugin( `${ PLUGIN_NAME }-document-panel`, {
	render: DocumentPanel,
} );
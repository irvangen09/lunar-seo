/**
 * PluginDocumentSettingPanel - Lunar SEO.
 *
 * Indikator status ringkas (quick-glance) di sidebar Document,
 * sejajar dengan panel bawaan WordPress (Categories, Tags, Featured
 * Image). Bukan tempat form lengkap - form lengkap ada di
 * PluginSidebar (lihat sidebar.js), sesuai keputusan Editor
 * Integration Strategy (GENERAL_MODULE_ARCHITECTURE.md §4).
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/editor';

export default function DocumentPanel() {
	return (
		<PluginDocumentSettingPanel
			name="lunar-seo-panel"
			title={ __( 'Lunar SEO', 'lunar-seo' ) }
		>
			<p>
				{ __(
					'Indikator status SEO Title & Meta Description akan tersedia di sini pada tahap berikutnya.',
					'lunar-seo'
				) }
			</p>
		</PluginDocumentSettingPanel>
	);
}

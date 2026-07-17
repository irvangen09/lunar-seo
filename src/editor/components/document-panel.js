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

import { __, sprintf } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

import {
	META_KEY_TITLE,
	META_KEY_DESCRIPTION,
	TITLE_MAX_LENGTH,
	DESCRIPTION_MAX_LENGTH,
} from '../constants';

export default function DocumentPanel() {
	const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
	const postTitle = useSelect(
		( select ) => select( 'core/editor' ).getEditedPostAttribute( 'title' ),
		[]
	);

	const [ meta ] = useEntityProp( 'postType', postType, 'meta' );

	// meta belum tersedia (misal saat post baru belum tersimpan) -
	// jangan render apapun, sama pola dengan sidebar.js.
	if ( ! meta ) {
		return null;
	}

	const seoTitle = meta[ META_KEY_TITLE ] || '';
	const metaDescription = meta[ META_KEY_DESCRIPTION ] || '';

	// SEO Title SELALU resolve ke sesuatu (fallback ke judul post) -
	// konsisten dengan TitleResolver.php di frontend, sekadar
	// ditampilkan sebagai quick-glance, bukan preview lengkap (itu
	// tugas PluginSidebar/Preview.js).
	const resolvedTitleLength = ( seoTitle || postTitle || '' ).length;

	return (
		<PluginDocumentSettingPanel
			name="lunar-seo-panel"
			title={ __( 'Lunar SEO', 'lunar-seo' ) }
		>
			<p>
				{ __( 'SEO Title', 'lunar-seo' ) + ': ' }
				{ seoTitle
					? sprintf(
							/* translators: 1: jumlah karakter saat ini, 2: batas karakter yang disarankan */
							__( '%1$d/%2$d karakter', 'lunar-seo' ),
							resolvedTitleLength,
							TITLE_MAX_LENGTH
					  )
					: __( 'Memakai judul post (belum di-override)', 'lunar-seo' ) }
			</p>
			<p>
				{ __( 'Meta Description', 'lunar-seo' ) + ': ' }
				{ metaDescription
					? sprintf(
							/* translators: 1: jumlah karakter saat ini, 2: batas karakter yang disarankan */
							__( '%1$d/%2$d karakter', 'lunar-seo' ),
							metaDescription.length,
							DESCRIPTION_MAX_LENGTH
					  )
					: __( 'Memakai cuplikan otomatis (belum di-override)', 'lunar-seo' ) }
			</p>
		</PluginDocumentSettingPanel>
	);
}

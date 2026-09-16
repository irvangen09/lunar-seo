/**
 * PluginDocumentSettingPanel — Lunar SEO.
 *
 * A quick-glance status indicator in the Document sidebar, alongside
 * WordPress's built-in panels (Categories, Tags, Featured Image). Not
 * the full form — that lives in PluginSidebar (see sidebar.js).
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

	// Meta isn't available yet (e.g. a new, unsaved post) — render
	// nothing, same as sidebar.js.
	if ( ! meta ) {
		return null;
	}

	const seoTitle = meta[ META_KEY_TITLE ] || '';
	const metaDescription = meta[ META_KEY_DESCRIPTION ] || '';

	// SEO Title always resolves to something (falls back to the post
	// title), consistent with TitleResolver.php on the frontend — shown
	// here only as a quick-glance count, not a full preview (that's
	// PluginSidebar/Preview.js).
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
							/* translators: 1: current character count, 2: recommended character limit */
							__( '%1$d/%2$d characters', 'lunar-seo' ),
							resolvedTitleLength,
							TITLE_MAX_LENGTH
					  )
					: __( 'Using the post title (not overridden)', 'lunar-seo' ) }
			</p>
			<p>
				{ __( 'Meta Description', 'lunar-seo' ) + ': ' }
				{ metaDescription
					? sprintf(
							/* translators: 1: current character count, 2: recommended character limit */
							__( '%1$d/%2$d characters', 'lunar-seo' ),
							metaDescription.length,
							DESCRIPTION_MAX_LENGTH
					  )
					: __( 'Using the auto-generated excerpt (not overridden)', 'lunar-seo' ) }
			</p>
		</PluginDocumentSettingPanel>
	);
}
/**
 * SEO Preview.
 *
 * Simulasi ringan tampilan hasil pencarian Google berdasarkan nilai
 * SEO Title, Meta Description, dan URL saat ini. Murni presentasi
 * client-side (tidak memanggil REST), agar update secara real-time
 * mengikuti perubahan input di form.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';

export default function Preview( { title, description, url } ) {
	return (
		<div className="lunar-seo-preview">
			<div className="lunar-seo-preview__url">{ url }</div>
			<div className="lunar-seo-preview__title">
				{ title || __( '(SEO Title not filled in)', 'lunar-seo' ) }
			</div>
			<div className="lunar-seo-preview__description">
				{ description || __( '(Meta Description not filled in)', 'lunar-seo' ) }
			</div>
		</div>
	);
}

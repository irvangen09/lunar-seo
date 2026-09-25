/**
 * SEO Preview.
 *
 * A lightweight simulation of how the page looks in Google search
 * results, based on the current SEO Title, Meta Description, and
 * URL values. Purely client-side presentation (no REST calls), so
 * it updates in real time as the form inputs change.
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
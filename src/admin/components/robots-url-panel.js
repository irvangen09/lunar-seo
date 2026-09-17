/**
 * Robots & URL Panel.
 *
 * Fields for the "Robots & URL" section — default robots meta
 * directives, robots presets for archives/404, and URL rewrite
 * toggles (Remove Category Base, Remove Tag Base, Redirect
 * Attachments). Rendered inside a PanelBody by app.js.
 *
 * @package Lunar\SEO
 */

import { __ } from '@wordpress/i18n';
import { CheckboxControl, SelectControl, ToggleControl } from '@wordpress/components';

// Consistent with Settings/RobotsUrl.php::ALLOWED_ROBOTS_DIRECTIVES.
// "index"/"follow" are deliberately NOT included — both are the
// crawler's default behavior, which doesn't need to (and can't) be
// stated explicitly in a robots meta tag (see MetaRenderer.php).
const ROBOTS_DIRECTIVES = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

// Consistent with Settings/RobotsUrl.php::ALLOWED_ROBOTS_PRESETS.
const ROBOTS_PRESET_OPTIONS = [
	{ label: __( 'Use Default Robots Meta', 'lunar-seo' ), value: 'default' },
	{ label: __( 'index, follow', 'lunar-seo' ), value: 'index_follow' },
	{ label: __( 'noindex, follow', 'lunar-seo' ), value: 'noindex_follow' },
	{ label: __( 'noindex, nofollow', 'lunar-seo' ), value: 'noindex_nofollow' },
];

export default function RobotsUrlPanel( { value, onChange } ) {
	const defaultRobotsMeta = value.default_robots_meta || [];

	const toggleDefaultRobotsDirective = ( directive ) => {
		const next = defaultRobotsMeta.includes( directive )
			? defaultRobotsMeta.filter( ( item ) => item !== directive )
			: [ ...defaultRobotsMeta, directive ];

		onChange( 'default_robots_meta', next );
	};

	return (
		<>
			<h3 id="lunar-robots-meta-label">{ __( 'Default Robots Meta', 'lunar-seo' ) }</h3>
			<p className="lunar-field__help">
				{ __(
					'Choose the default search engine instructions for your site content.',
					'lunar-seo'
				) }
			</p>
			<div role="group" aria-labelledby="lunar-robots-meta-label">
				{ ROBOTS_DIRECTIVES.map( ( directive ) => (
					<CheckboxControl
						key={ directive }
						label={ directive }
						checked={ defaultRobotsMeta.includes( directive ) }
						onChange={ () => toggleDefaultRobotsDirective( directive ) }
					/>
				) ) }
			</div>

			<SelectControl
				label={ __( 'Default Robots for Archives', 'lunar-seo' ) }
				help={ __(
					'Apply these settings to archive pages such as categories, tags, authors, search results, and date archives.',
					'lunar-seo'
				) }
				value={ value.archives_robots || 'default' }
				options={ ROBOTS_PRESET_OPTIONS }
				onChange={ ( v ) => onChange( 'archives_robots', v ) }
			/>

			<SelectControl
				label={ __( 'Default Robots for 404 Pages', 'lunar-seo' ) }
				help={ __( 'Choose how search engines should handle 404 (Not Found) pages.', 'lunar-seo' ) }
				value={ value.not_found_robots || 'noindex_follow' }
				options={ ROBOTS_PRESET_OPTIONS }
				onChange={ ( v ) => onChange( 'not_found_robots', v ) }
			/>

			<h3>{ __( 'URL', 'lunar-seo' ) }</h3>

			<ToggleControl
				label={ __( 'Remove Category Base', 'lunar-seo' ) }
				help={ __( 'Remove /category/ from category URLs.', 'lunar-seo' ) }
				checked={ !! value.remove_category_base }
				onChange={ ( checked ) => onChange( 'remove_category_base', checked ) }
			/>

			<ToggleControl
				label={ __( 'Remove Tag Base', 'lunar-seo' ) }
				help={ __( 'Remove /tag/ from tag URLs.', 'lunar-seo' ) }
				checked={ !! value.remove_tag_base }
				onChange={ ( checked ) => onChange( 'remove_tag_base', checked ) }
			/>

			<ToggleControl
				label={ __( 'Redirect Attachments to Parent', 'lunar-seo' ) }
				help={ __(
					'Redirect attachment pages to their parent post or page.',
					'lunar-seo'
				) }
				checked={ !! value.redirect_attachments_to_parent }
				onChange={ ( checked ) => onChange( 'redirect_attachments_to_parent', checked ) }
			/>
		</>
	);
}
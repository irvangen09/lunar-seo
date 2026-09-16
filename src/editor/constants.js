/**
 * Shared Editor constants — meta keys, character limits, placeholder
 * variables, and the robots directive whitelist.
 *
 * Split out from sidebar.js so document-panel.js (PluginDocumentSettingPanel)
 * and sidebar.js (PluginSidebar) read from a single source — the meta
 * keys here must stay in sync with PostMetaKeys.php (PHP).
 *
 * @package Lunar\SEO
 */

// Must match PostMetaKeys.php (PHP) exactly.
export const META_KEY_TITLE = '_lunar_seo_title';
export const META_KEY_DESCRIPTION = '_lunar_seo_description';
export const META_KEY_CANONICAL = '_lunar_seo_canonical';
export const META_KEY_ROBOTS = '_lunar_seo_robots';

// Placeholder variables available in the post/page context (consistent
// with TitleRenderer.php/MetaRenderer.php).
export const TITLE_VARIABLES = [ 'title', 'separator', 'site_name', 'tagline' ];
export const DESCRIPTION_VARIABLES = [ 'title', 'site_name', 'tagline' ];

// Directive whitelist — consistent with Settings/RobotsUrl.php (PHP).
// "index"/"follow" are deliberately excluded — both are the crawler's
// default behavior and don't need to be stated explicitly (see MetaRenderer.php).
export const ROBOTS_DIRECTIVES = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

export const TITLE_MAX_LENGTH = 60;
export const DESCRIPTION_MAX_LENGTH = 160;
/**
 * Konstanta bersama Editor - meta key, batas karakter, variable
 * placeholder, dan whitelist robots directive.
 *
 * Dipisah dari sidebar.js supaya document-panel.js (PluginDocumentSettingPanel)
 * dan sidebar.js (PluginSidebar) membaca SATU sumber yang sama - meta
 * key di sini HARUS tetap konsisten dengan PostMetaKeys.php (PHP).
 *
 * @package Lunar\SEO
 */

// Meta key HARUS sama persis dengan PostMetaKeys.php (PHP).
export const META_KEY_TITLE = '_lunar_seo_title';
export const META_KEY_DESCRIPTION = '_lunar_seo_description';
export const META_KEY_CANONICAL = '_lunar_seo_canonical';
export const META_KEY_ROBOTS = '_lunar_seo_robots';

// Post type yang didukung override per-post - HARUS sama persis
// dengan Editor::SUPPORTED_POST_TYPES (PHP). Dipakai sidebar.js/
// document-panel.js sebagai defense-in-depth: Assets.php sudah
// membatasi bundle ini agar hanya termuat di post type yang
// didukung, guard ini murni jaga-jaga apabila suatu saat bundle
// tetap termuat di context lain.
export const SUPPORTED_POST_TYPES = [ 'post', 'page' ];

// Variable yang tersedia untuk context post/page (konsisten dengan
// TitleRenderer.php/MetaRenderer.php).
export const TITLE_VARIABLES = [ 'title', 'separator', 'site_name', 'tagline' ];
export const DESCRIPTION_VARIABLES = [ 'title', 'site_name', 'tagline' ];

// Whitelist directive - konsisten dengan Settings/RobotsUrl.php (PHP).
// "index"/"follow" sengaja TIDAK termasuk - keduanya perilaku default
// crawler yang tidak perlu dinyatakan eksplisit (lihat MetaRenderer.php).
export const ROBOTS_DIRECTIVES = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

export const TITLE_MAX_LENGTH = 60;
export const DESCRIPTION_MAX_LENGTH = 160;

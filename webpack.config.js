/**
 * Konfigurasi Webpack custom, mewarisi (extend) default dari
 * @wordpress/scripts agar tetap mendapat semua benefit resmi
 * (Babel, ESLint config, dependency extraction, dst) tanpa perlu
 * membangun konfigurasi dari nol.
 *
 * Entry "editor" -> build/editor.js, dipakai untuk
 * PluginSidebar/PluginDocumentSettingPanel pada Block Editor
 * (module General).
 *
 * Entry "admin" -> build/admin.js, React Settings app module General.
 *
 * Entry "sitemap-admin" -> build/sitemap-admin.js, React Settings
 * app module Sitemap.
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		editor: './src/editor/index.js',
		admin: './src/admin/index.js',
		'sitemap-admin': './src/sitemap-admin/index.js',
	},
};

/**
 * Custom Webpack config, extending the @wordpress/scripts default so
 * it still gets every official benefit (Babel, ESLint config,
 * dependency extraction, etc.) without building a config from
 * scratch.
 *
 * Entry "editor" -> build/editor.js, used for
 * PluginSidebar/PluginDocumentSettingPanel in the Block Editor
 * (General module).
 *
 * Entry "admin" -> build/admin.js, the General module's React
 * Settings app.
 *
 * Entry "sitemap-admin" -> build/sitemap-admin.js, the Sitemap
 * module's React Settings app.
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
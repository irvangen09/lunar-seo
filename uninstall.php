<?php
/**
 * Uninstall Lunar SEO.
 *
 * Runs only when the plugin is deleted via the Plugins screen, not on
 * deactivation. Cleans up every piece of data this plugin created in
 * the database.
 *
 * Written as pure procedural code, with NO dependency on the Composer
 * autoloader or any plugin class — WordPress calls this file
 * separately from the normal bootstrap flow, and uninstall must still
 * succeed at cleaning up data even if the autoloader fails to load for
 * some reason.
 *
 * @package Lunar\SEO
 */

// Block direct execution — only WordPress's own uninstall process may
// call this file.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Deletes every piece of Lunar SEO data on one site/database.
 *
 * Called once for a single site, or once per site on a multisite
 * install (see the call at the bottom of this file).
 */
function lunar_seo_uninstall_cleanup_site() {
	global $wpdb;

	// 1. Options — one per module, matching
	// OptionManager::OPTION_PREFIX ('lunar_seo_' + module_slug +
	// '_settings') plus SiteIdentity.
	delete_option( 'lunar_seo_general_settings' );
	delete_option( 'lunar_seo_sitemap_settings' );
	delete_option( 'lunar_seo_site_identity' );

	// 2. Per-post meta overrides (General\PostMetaKeys) — deleted
	// across EVERY post type at once, with no need to know the
	// specific post type list (delete_post_meta_by_key operates
	// globally).
	$post_meta_keys = array(
		'_lunar_seo_title',
		'_lunar_seo_description',
		'_lunar_seo_canonical',
		'_lunar_seo_robots',
	);

	foreach ( $post_meta_keys as $meta_key ) {
		delete_post_meta_by_key( $meta_key );
	}

	// 3. Sitemap transients (Sitemap\SitemapCache) — created with no
	// expiration (pure event-driven invalidation), so they do NOT
	// expire on their own and must be cleaned up manually here. Same
	// query pattern as SitemapCache::flush_all() — a LIKE match is
	// needed because the transient name is dynamic per content type
	// (post, page, category, category2, etc.).
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_lunar_seo_sitemap_' ) . '%'
		)
	);

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( '_transient_timeout_lunar_seo_sitemap_' ) . '%'
		)
	);
}

// Multisite: this plugin has no per-site or network-wide option logic
// of its own, so the same cleanup just runs once per site on a
// network-wide delete. A single-site install goes through the else
// branch as usual.
if ( is_multisite() ) {
	// 'number' => 0 explicitly (unlimited) — get_sites()'s default is
	// 100 (WP_Site_Query), which would silently skip part of a larger
	// network with no error at all.
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		lunar_seo_uninstall_cleanup_site();
		restore_current_blog();
	}
} else {
	lunar_seo_uninstall_cleanup_site();
}
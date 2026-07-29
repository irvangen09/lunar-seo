<?php
/**
 * Uninstall Lunar SEO.
 *
 * Dijalankan WordPress hanya saat plugin dihapus (Delete) lewat
 * halaman Plugins - bukan saat sekadar dinonaktifkan (Deactivate).
 * Bertanggung jawab membersihkan seluruh data yang dibuat plugin ini
 * dari database (ARCHITECTURE.md §17 - Backward Compatibility tidak
 * relevan lagi di titik ini karena user secara eksplisit meminta
 * penghapusan penuh).
 *
 * Ditulis prosedural murni, TIDAK bergantung pada Composer autoloader
 * maupun class plugin manapun - WordPress memanggil file ini secara
 * terpisah dari alur bootstrap normal, dan proses uninstall harus
 * tetap berhasil membersihkan data walau autoloader karena suatu
 * sebab gagal dimuat (CODING_STANDARD.md §11 - Fail Gracefully).
 *
 * @package Lunar\SEO
 */

// Cegah eksekusi langsung - hanya boleh dipanggil WordPress saat
// proses uninstall plugin yang sah.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Hapus seluruh data Lunar SEO pada satu site/database.
 *
 * Dipanggil sekali untuk site tunggal, atau berulang per-site pada
 * instalasi multisite (lihat pemanggilan di bagian bawah file).
 *
 * @return void
 */
function lunar_seo_uninstall_cleanup_site() {
	global $wpdb;

	// 1. Options - satu per module, sesuai OptionManager::OPTION_PREFIX
	// ('lunar_seo_' + module_slug + '_settings') dan SiteIdentity.
	delete_option( 'lunar_seo_general_settings' );
	delete_option( 'lunar_seo_sitemap_settings' );
	delete_option( 'lunar_seo_site_identity' );

	// 2. Post meta override per-post (General\PostMetaKeys) - dihapus
	// lintas SELURUH post type sekaligus, tanpa perlu tahu daftar post
	// type spesifik (delete_post_meta_by_key beroperasi global).
	$post_meta_keys = array(
		'_lunar_seo_title',
		'_lunar_seo_description',
		'_lunar_seo_canonical',
		'_lunar_seo_robots',
	);

	foreach ( $post_meta_keys as $meta_key ) {
		delete_post_meta_by_key( $meta_key );
	}

	// 3. Transient sitemap (Sitemap\SitemapCache) - dibuat dengan
	// expiration 0 (tanpa timeout, murni event-driven invalidation),
	// sehingga TIDAK otomatis hilang sendiri dan harus dibersihkan
	// manual di sini. Pola query sama persis dengan
	// SitemapCache::flush_all() - LIKE match karena nama transient
	// dinamis per tipe konten (post, page, category, category2, dst).
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

// Multisite: plugin ini tidak memiliki logic khusus per-site maupun
// option network-wide, jadi cukup ulangi proses cleanup yang sama di
// setiap site saat dihapus secara network-wide. Single site berjalan
// seperti biasa lewat cabang else.
if ( is_multisite() ) {
	// 'number' => 0 eksplisit (unlimited) - default get_sites() adalah
	// 100 (WP_Site_Query), yang akan diam-diam melewatkan sebagian
	// site pada jaringan besar tanpa error apapun.
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

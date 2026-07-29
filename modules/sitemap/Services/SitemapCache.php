<?php
/**
 * Sitemap Cache.
 *
 * Cache HASIL MENTAH (array daftar URL entry, BUKAN string XML)
 * per tipe konten, menggunakan WordPress Transients API. Caching
 * dilakukan di level "entries" (bukan per halaman/pagination) agar
 * invalidasi tetap sederhana - 1 cache key per tipe konten,
 * terlepas dari berapa banyak halaman pagination yang dihasilkan
 * saat rendering XML (XmlBuilder yang menangani pagination dari
 * array yang sudah di-cache).
 *
 * Tidak memakai expiration time (transient permanen) - murni
 * event-driven invalidation saat konten benar-benar berubah
 * (SITEMAP_MODULE_ARCHITECTURE.md §4).
 *
 * @package Lunar\SEO\Modules\Sitemap\Services
 */

namespace Lunar\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SitemapCache {

	/**
	 * Prefix transient, dipakai juga sebagai basis pencarian saat
	 * flush_all() (lihat catatan pada method tersebut).
	 *
	 * @var string
	 */
	private const TRANSIENT_PREFIX = 'lunar_seo_sitemap_';

	/**
	 * Ambil entries yang sudah di-cache untuk satu tipe konten.
	 *
	 * @param string $type Identifier tipe konten (contoh: "post", "category", "homepage").
	 * @return array|null Null apabila belum ada cache (cache miss).
	 */
	public function get_entries( string $type ): ?array {
		$value = get_transient( self::TRANSIENT_PREFIX . $type );

		return false !== $value ? $value : null;
	}

	/**
	 * Simpan entries hasil generate ke cache.
	 *
	 * @param string $type    Identifier tipe konten.
	 * @param array  $entries Daftar URL entry.
	 * @return void
	 */
	public function set_entries( string $type, array $entries ): void {
		set_transient( self::TRANSIENT_PREFIX . $type, $entries, 0 );
	}

	/**
	 * Hapus cache satu tipe konten.
	 *
	 * @param string $type Identifier tipe konten.
	 * @return void
	 */
	public function delete_entries( string $type ): void {
		delete_transient( self::TRANSIENT_PREFIX . $type );
	}

	/**
	 * Hapus SELURUH cache sitemap (semua tipe konten sekaligus).
	 *
	 * WordPress tidak menyediakan API bawaan untuk menghapus
	 * transient berdasarkan prefix (hanya per-key), sehingga query
	 * langsung ke $wpdb menjadi satu-satunya cara. Ini pengecualian
	 * yang disengaja dan umum dipakai di ekosistem WordPress untuk
	 * kasus spesifik ini.
	 *
	 * @return void
	 */
	public function flush_all(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::TRANSIENT_PREFIX ) . '%'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_timeout_' . self::TRANSIENT_PREFIX ) . '%'
			)
		);
	}

	/**
	 * Daftarkan hook invalidasi otomatis.
	 *
	 * @return void
	 */
	public function register_invalidation_hooks(): void {
		add_action( 'save_post', [ $this, 'invalidate_for_post' ] );
		add_action( 'delete_post', [ $this, 'invalidate_for_post' ] );
		add_action( 'trashed_post', [ $this, 'invalidate_for_post' ] );

		add_action( 'edited_term', [ $this, 'invalidate_for_term' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'invalidate_for_term' ], 10, 3 );
		add_action( 'created_term', [ $this, 'invalidate_for_term' ], 10, 3 );

		// Perubahan Settings berpotensi mengubah struktur keseluruhan
		// (toggle Include/Exclude) - invalidasi semua, bukan selektif.
		add_action( 'lunar_seo_sitemap_settings_updated', [ $this, 'flush_all' ] );
	}

	/**
	 * Invalidasi cache untuk post type terkait saat post
	 * disimpan/dihapus/di-trash.
	 *
	 * Selain cache post type yang bersangkutan, turut invalidasi:
	 * - "authors"  - AuthorProvider memakai get_users(has_published_posts)
	 *                yang mencakup SEMUA public post type, sehingga setiap
	 *                perubahan post (post type apapun) berpotensi mengubah
	 *                daftar author yang punya published post.
	 * - "archives" - DateArchiveProvider hanya menghitung arsip bulanan dari
	 *                post type "post", sehingga hanya perlu diinvalidasi
	 *                untuk post type tersebut.
	 *
	 * @param int $post_id ID post yang berubah.
	 * @return void
	 */
	public function invalidate_for_post( int $post_id ): void {
		// save_post juga terpicu untuk setiap revision dan autosave
		// (berjalan otomatis setiap ~60 detik selama editor terbuka),
		// bukan hanya publish/update yang disengaja user. Tanpa guard
		// ini, cache 'authors'/post-type yang bersangkutan diinvalidasi
		// jauh lebih sering dari yang perlu.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( false === $post_type ) {
			return;
		}

		$this->delete_entries( $post_type );
		$this->delete_entries( 'authors' );

		if ( 'post' === $post_type ) {
			$this->delete_entries( 'archives' );
		}
	}

	/**
	 * Invalidasi cache untuk taxonomy terkait saat term
	 * ditambah/diubah/dihapus.
	 *
	 * @param int    $term_id  ID term.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy terkait.
	 * @return void
	 */
	public function invalidate_for_term( int $term_id, int $tt_id, string $taxonomy ): void {
		$this->delete_entries( $taxonomy );
	}
}

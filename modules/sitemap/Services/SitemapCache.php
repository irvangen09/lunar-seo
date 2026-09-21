<?php
/**
 * Sitemap Cache.
 *
 * Caches the RAW RESULT (the array of URL entries, NOT the XML
 * string) per content type, using the WordPress Transients API.
 * Caching happens at the "entries" level (not per page/pagination) so
 * invalidation stays simple — one cache key per content type,
 * regardless of how many pagination pages the XML rendering produces
 * (XmlBuilder handles pagination from the already-cached array).
 *
 * No expiration time is used (a permanent transient) — purely
 * event-driven invalidation when content actually changes.
 *
 * @package Lunar\SEO\Modules\Sitemap\Services
 */

namespace Lunar\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SitemapCache {

	// Also used as the basis for the LIKE search in flush_all() (see
	// that method's own note).
	private const TRANSIENT_PREFIX = 'lunar_seo_sitemap_';

	/**
	 * @return array|null Null on a cache miss.
	 */
	public function get_entries( string $type ): ?array {
		$value = get_transient( self::TRANSIENT_PREFIX . $type );

		return false !== $value ? $value : null;
	}

	public function set_entries( string $type, array $entries ): void {
		set_transient( self::TRANSIENT_PREFIX . $type, $entries, 0 );
	}

	public function delete_entries( string $type ): void {
		delete_transient( self::TRANSIENT_PREFIX . $type );
	}

	/**
	 * Deletes EVERY sitemap cache entry (every content type at once).
	 *
	 * WordPress provides no built-in API to delete transients by
	 * prefix (only per-key), so a direct $wpdb query is the only way.
	 * This is a deliberate, commonly-used exception in the WordPress
	 * ecosystem for this specific case.
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

	public function register_invalidation_hooks(): void {
		add_action( 'save_post', [ $this, 'invalidate_for_post' ] );
		add_action( 'delete_post', [ $this, 'invalidate_for_post' ] );
		add_action( 'trashed_post', [ $this, 'invalidate_for_post' ] );

		add_action( 'edited_term', [ $this, 'invalidate_for_term' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'invalidate_for_term' ], 10, 3 );
		add_action( 'created_term', [ $this, 'invalidate_for_term' ], 10, 3 );

		// A Settings change can potentially alter the overall structure
		// (an Include/Exclude toggle) — invalidate everything, not
		// selectively.
		add_action( 'lunar_seo_sitemap_settings_updated', [ $this, 'flush_all' ] );
	}

	/**
	 * Invalidates the cache for the relevant post type when a post is
	 * saved/deleted/trashed.
	 *
	 * Besides the cache for that post's own post type, this also
	 * invalidates:
	 * - "authors"  — AuthorProvider uses get_users(has_published_posts),
	 *                which covers EVERY public post type, so any post
	 *                change (whatever its post type) could change the
	 *                list of authors with a published post.
	 * - "archives" — DateArchiveProvider only counts monthly archives
	 *                from the "post" post type, so it only needs
	 *                invalidating for that specific post type.
	 */
	public function invalidate_for_post( int $post_id ): void {
		// save_post also fires for every revision and autosave (which
		// runs automatically roughly every ~60 seconds while the
		// editor is open), not just a deliberate publish/update by the
		// user. Without this guard, the 'authors'/relevant post-type
		// cache would be invalidated far more often than necessary.
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
	 * Invalidates the cache for the relevant taxonomy when a term is
	 * added/edited/deleted.
	 */
	public function invalidate_for_term( int $term_id, int $tt_id, string $taxonomy ): void {
		$this->delete_entries( $taxonomy );
	}
}
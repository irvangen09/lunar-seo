<?php
/**
 * Section: Sitemap Content.
 *
 * Menentukan jenis konten yang di-include ke dalam XML Sitemap:
 * toggle bawaan (Homepage, Posts, Pages, Categories, Archives,
 * Author Pages, Tags) plus toggle dinamis untuk Custom Post Type
 * dan Custom Taxonomy (WooCommerce atau plugin lain yang
 * mendaftarkan post type/taxonomy sendiri otomatis tertangani di
 * sini, TANPA kita hardcode nama plugin tersebut).
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

use Lunar\SEO\Modules\Sitemap\Services\ContentTypeRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SitemapContent implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'sitemap_content';

	/**
	 * Default Links Per Page, sesuai dokumen (maksimal 1000 URL
	 * per sitemap sebelum dipecah ke halaman berikutnya).
	 *
	 * @var int
	 */
	private const DEFAULT_LINKS_PER_PAGE = 1000;

	/**
	 * Batas wajar Links Per Page - mencegah admin memasukkan angka
	 * ekstrem yang bisa membebani server (query terlalu besar per
	 * request).
	 *
	 * @var int
	 */
	private const MAX_LINKS_PER_PAGE = 50000;

	/**
	 * @var ContentTypeRegistry
	 */
	private ContentTypeRegistry $content_type_registry;

	/**
	 * @param ContentTypeRegistry $content_type_registry Service deteksi Custom Post Type/Taxonomy.
	 */
	public function __construct( ContentTypeRegistry $content_type_registry ) {
		$this->content_type_registry = $content_type_registry;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( array $input ): array {
		return [
			'include_homepage'      => ! empty( $input['include_homepage'] ),
			'include_posts'         => ! empty( $input['include_posts'] ),
			'include_static_pages'  => ! empty( $input['include_static_pages'] ),
			'include_categories'    => ! empty( $input['include_categories'] ),
			'include_archives'      => ! empty( $input['include_archives'] ),
			'include_author_pages'  => ! empty( $input['include_author_pages'] ),
			'include_tag_pages'     => ! empty( $input['include_tag_pages'] ),
			'include_last_modified' => ! empty( $input['include_last_modified'] ),
			'links_per_page'        => $this->sanitize_links_per_page( $input['links_per_page'] ?? self::DEFAULT_LINKS_PER_PAGE ),
			'custom_post_types'     => $this->sanitize_custom_toggles(
				$input['custom_post_types'] ?? [],
				array_keys( $this->content_type_registry->get_custom_post_types() )
			),
			'custom_taxonomies'     => $this->sanitize_custom_toggles(
				$input['custom_taxonomies'] ?? [],
				array_keys( $this->content_type_registry->get_custom_taxonomies() )
			),
		];
	}

	/**
	 * Sanitasi Links Per Page - integer positif, dibatasi maksimum wajar.
	 *
	 * @param mixed $value Nilai mentah.
	 * @return int
	 */
	private function sanitize_links_per_page( $value ): int {
		$value = absint( $value );

		if ( 0 === $value ) {
			return self::DEFAULT_LINKS_PER_PAGE;
		}

		return min( $value, self::MAX_LINKS_PER_PAGE );
	}

	/**
	 * Sanitasi toggle Custom Post Type/Taxonomy - HANYA slug yang
	 * benar-benar terdaftar di situs (whitelist dinamis) yang
	 * diterima, mencegah key sembarangan tersimpan ke database.
	 *
	 * @param mixed    $value          Nilai mentah (associative array slug => bool).
	 * @param string[] $allowed_slugs  Slug yang benar-benar terdaftar saat ini.
	 * @return array<string, bool>
	 */
	private function sanitize_custom_toggles( $value, array $allowed_slugs ): array {
		$value = is_array( $value ) ? $value : [];
		$result = [];

		foreach ( $allowed_slugs as $slug ) {
			$result[ $slug ] = ! empty( $value[ $slug ] );
		}

		return $result;
	}
}

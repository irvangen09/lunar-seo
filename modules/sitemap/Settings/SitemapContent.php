<?php
/**
 * Section: Sitemap Content.
 *
 * Determines what content is included in the XML Sitemap: the native
 * toggles (Homepage, Posts, Pages, Categories, Archives, Author Pages,
 * Tags) plus dynamic toggles for Custom Post Types and Custom
 * Taxonomies (WooCommerce or any other plugin that registers its own
 * post type/taxonomy is automatically handled here, WITHOUT
 * hardcoding that plugin's name).
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

use Lunar\SEO\Modules\Sitemap\Services\ContentTypeRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SitemapContent implements SectionInterface {

	private const SECTION_KEY = 'sitemap_content';

	private const DEFAULT_LINKS_PER_PAGE = 1000;

	// A reasonable ceiling on Links Per Page — prevents an admin from
	// entering an extreme number that could overload the server (too
	// large a query per request).
	private const MAX_LINKS_PER_PAGE = 50000;

	private ContentTypeRegistry $content_type_registry;

	public function __construct( ContentTypeRegistry $content_type_registry ) {
		$this->content_type_registry = $content_type_registry;
	}

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

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

	private function sanitize_links_per_page( $value ): int {
		$value = absint( $value );

		if ( 0 === $value ) {
			return self::DEFAULT_LINKS_PER_PAGE;
		}

		return min( $value, self::MAX_LINKS_PER_PAGE );
	}

	/**
	 * Only accepts a slug that's ACTUALLY registered on the site
	 * (a dynamic whitelist), preventing an arbitrary key from being
	 * saved to the database.
	 *
	 * @param mixed    $value         Raw value (associative array slug => bool).
	 * @param string[] $allowed_slugs Slugs actually registered right now.
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
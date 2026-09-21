<?php
/**
 * Content Type Registry.
 *
 * Detects Custom Post Types and Custom Taxonomies registered on the
 * site (including ones registered by other plugins, e.g. WooCommerce
 * — WITHOUT needing to know or hardcode that plugin's name at all).
 * Native post types/taxonomies already handled explicitly (post,
 * page, category, post_tag) are excluded from the "custom" list so
 * they don't appear twice in the UI.
 *
 * @package Lunar\SEO\Modules\Sitemap\Services
 */

namespace Lunar\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContentTypeRegistry {

	// Native post types already explicitly handled by Sitemap Content
	// (Posts, Pages) — excluded from the Custom Post Type list.
	private const BUILT_IN_POST_TYPES = [ 'post', 'page', 'attachment' ];

	// Native taxonomies already explicitly handled by Sitemap Content
	// (Categories, Tags) — excluded from the Custom Taxonomy list.
	private const BUILT_IN_TAXONOMIES = [ 'category', 'post_tag', 'post_format' ];

	/**
	 * @return \WP_Post_Type[] Keyed by post type slug.
	 */
	public function get_custom_post_types(): array {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );

		return array_diff_key( $post_types, array_flip( self::BUILT_IN_POST_TYPES ) );
	}

	/**
	 * @return \WP_Taxonomy[] Keyed by taxonomy slug.
	 */
	public function get_custom_taxonomies(): array {
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

		return array_diff_key( $taxonomies, array_flip( self::BUILT_IN_TAXONOMIES ) );
	}
}
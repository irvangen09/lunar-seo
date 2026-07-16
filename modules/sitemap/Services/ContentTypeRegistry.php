<?php
/**
 * Content Type Registry.
 *
 * Mendeteksi Custom Post Type dan Custom Taxonomy yang terdaftar di
 * situs (termasuk yang didaftarkan plugin lain, misal WooCommerce -
 * TANPA kita perlu tahu atau hardcode nama plugin tersebut sama
 * sekali). Post type/taxonomy bawaan yang sudah ditangani secara
 * eksplisit (post, page, category, post_tag) dikecualikan dari
 * daftar "custom" agar tidak muncul dua kali di UI.
 *
 * @package Lunar\SEO\Modules\Sitemap\Services
 */

namespace Lunar\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContentTypeRegistry {

	/**
	 * Post type bawaan yang sudah ditangani eksplisit oleh Sitemap
	 * Content (Posts, Pages) - dikecualikan dari daftar Custom Post Type.
	 *
	 * @var string[]
	 */
	private const BUILT_IN_POST_TYPES = [ 'post', 'page', 'attachment' ];

	/**
	 * Taxonomy bawaan yang sudah ditangani eksplisit oleh Sitemap
	 * Content (Categories, Tags) - dikecualikan dari daftar Custom Taxonomy.
	 *
	 * @var string[]
	 */
	private const BUILT_IN_TAXONOMIES = [ 'category', 'post_tag', 'post_format' ];

	/**
	 * Ambil daftar Custom Post Type yang terdaftar di situs (public,
	 * di luar bawaan WordPress).
	 *
	 * @return \WP_Post_Type[] Keyed by post type slug.
	 */
	public function get_custom_post_types(): array {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );

		return array_diff_key( $post_types, array_flip( self::BUILT_IN_POST_TYPES ) );
	}

	/**
	 * Ambil daftar Custom Taxonomy yang terdaftar di situs (public,
	 * di luar bawaan WordPress).
	 *
	 * @return \WP_Taxonomy[] Keyed by taxonomy slug.
	 */
	public function get_custom_taxonomies(): array {
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

		return array_diff_key( $taxonomies, array_flip( self::BUILT_IN_TAXONOMIES ) );
	}
}

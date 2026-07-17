<?php
/**
 * BreadcrumbList Node.
 *
 * Output HANYA di context singular (post/page) - SCHEMA_MODULE_ARCHITECTURE.md §5.5.
 *
 * Untuk post type "post": rantai dari kategori utama (kategori pertama
 * dari get_the_category()) naik ke root via get_ancestors(), mendukung
 * baik struktur flat (satu kategori = satu game, sesuai
 * LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md §3) maupun
 * kategori nested apabila suatu saat dipakai.
 *
 * Untuk post type "page": rantai parent page via get_post_ancestors().
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BreadcrumbListNode implements NodeInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_node(): ?array {
		if ( ! is_singular() ) {
			return null;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		$permalink = get_permalink( $post );
		$permalink = false !== $permalink ? $permalink : home_url( '/' );

		return [
			'@type'           => 'BreadcrumbList',
			'@id'             => SchemaId::breadcrumb( $permalink ),
			'itemListElement' => $this->build_item_list( $post ),
		];
	}

	/**
	 * Bangun seluruh itemListElement: Home -> rantai (kategori/parent
	 * page) -> post/page saat ini.
	 *
	 * @param \WP_Post $post Post/page saat ini.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_item_list( \WP_Post $post ): array {
		$trail = [
			[
				'name' => __( 'Home', 'lunar-seo' ),
				'url'  => home_url( '/' ),
			],
		];

		if ( 'page' === $post->post_type ) {
			$trail = array_merge( $trail, $this->build_page_ancestor_trail( $post ) );
		} else {
			$trail = array_merge( $trail, $this->build_category_trail( $post ) );
		}

		$trail[] = [
			'name' => get_the_title( $post ),
			'url'  => get_permalink( $post ),
		];

		return $this->to_list_items( $trail );
	}

	/**
	 * Rantai kategori utama, dari root (ancestor terjauh) turun ke
	 * kategori terdekat dengan post - get_ancestors() mengembalikan
	 * urutan dari ancestor TERDEKAT ke TERJAUH, sehingga di-reverse
	 * agar breadcrumb terbaca dari umum ke spesifik.
	 *
	 * Kembalikan array kosong apabila post tidak memiliki kategori
	 * (Uncategorized dianggap tidak menghasilkan trail tambahan,
	 * bukan error).
	 *
	 * @param \WP_Post $post Post saat ini.
	 * @return array<int, array<string, string>>
	 */
	private function build_category_trail( \WP_Post $post ): array {
		$categories = get_the_category( $post->ID );

		if ( empty( $categories ) ) {
			return [];
		}

		// Kategori PERTAMA dianggap kategori utama - konsisten dengan
		// struktur situs "satu kategori = satu game"
		// (LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md §3).
		$primary_category = $categories[0];

		$ancestor_ids = array_reverse(
			get_ancestors( $primary_category->term_id, 'category', 'taxonomy' )
		);

		$trail = [];

		foreach ( $ancestor_ids as $ancestor_id ) {
			$term = get_term( $ancestor_id, 'category' );

			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$trail[] = $this->term_to_trail_entry( $term );
		}

		$trail[] = $this->term_to_trail_entry( $primary_category );

		return $trail;
	}

	/**
	 * Konversi WP_Term jadi entry trail (name + url), skip URL
	 * apabila get_term_link() gagal (WP_Error) - name tetap
	 * ditampilkan tanpa url daripada gagal seluruhnya.
	 *
	 * @param \WP_Term $term Term kategori.
	 * @return array<string, string>
	 */
	private function term_to_trail_entry( \WP_Term $term ): array {
		$term_link = get_term_link( $term );

		return [
			'name' => $term->name,
			'url'  => is_wp_error( $term_link ) ? '' : $term_link,
		];
	}

	/**
	 * Rantai parent page, dari root turun ke parent terdekat -
	 * get_post_ancestors() mengembalikan urutan dari parent TERDEKAT
	 * ke TERJAUH, sehingga di-reverse (sama alasan dengan kategori).
	 *
	 * @param \WP_Post $post Page saat ini.
	 * @return array<int, array<string, string>>
	 */
	private function build_page_ancestor_trail( \WP_Post $post ): array {
		$ancestor_ids = array_reverse( get_post_ancestors( $post ) );

		$trail = [];

		foreach ( $ancestor_ids as $ancestor_id ) {
			$link = get_permalink( $ancestor_id );

			$trail[] = [
				'name' => get_the_title( $ancestor_id ),
				'url'  => false !== $link ? $link : '',
			];
		}

		return $trail;
	}

	/**
	 * Konversi trail (name + url) jadi array ListItem sesuai
	 * Schema.org, dengan position berurutan mulai dari 1.
	 *
	 * @param array<int, array<string, string>> $trail Trail sederhana.
	 * @return array<int, array<string, mixed>>
	 */
	private function to_list_items( array $trail ): array {
		$items = [];

		foreach ( $trail as $index => $entry ) {
			$items[] = [
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $entry['name'],
				'item'     => $entry['url'],
			];
		}

		return $items;
	}
}

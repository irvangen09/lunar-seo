<?php
/**
 * BreadcrumbList Node.
 *
 * Output ONLY in a singular context (post/page).
 *
 * For the "post" post type: a chain from the primary category (the
 * first category from get_the_category()) up to the root via
 * get_ancestors(), supporting both a flat structure (one category =
 * one game) and nested categories if that's ever used.
 *
 * For the "page" post type: a parent-page chain via
 * get_post_ancestors().
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BreadcrumbListNode implements NodeInterface {

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
	 * Builds the full itemListElement: Home -> chain (category/parent
	 * page) -> the current post/page.
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
	 * The primary category chain, from the root (furthest ancestor)
	 * down to the category closest to the post — get_ancestors()
	 * returns the order from CLOSEST ancestor to FURTHEST, so it's
	 * reversed here for the breadcrumb to read general-to-specific.
	 *
	 * Returns an empty array if the post has no category (Uncategorized
	 * is treated as producing no additional trail, not an error).
	 */
	private function build_category_trail( \WP_Post $post ): array {
		$categories = get_the_category( $post->ID );

		if ( empty( $categories ) ) {
			return [];
		}

		// The FIRST category is treated as the primary one, consistent
		// with this site's "one category = one game" structure.
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
	 * Converts a WP_Term into a trail entry (name + url), skipping the
	 * URL if get_term_link() fails (WP_Error) — the name is still shown
	 * without a url rather than failing entirely.
	 */
	private function term_to_trail_entry( \WP_Term $term ): array {
		$term_link = get_term_link( $term );

		return [
			'name' => $term->name,
			'url'  => is_wp_error( $term_link ) ? '' : $term_link,
		];
	}

	/**
	 * The parent-page chain, from the root down to the closest parent —
	 * get_post_ancestors() returns the order from CLOSEST parent to
	 * FURTHEST, so it's reversed (same reason as the category chain).
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
	 * Converts a trail (name + url) into a Schema.org-compliant
	 * ListItem array, with sequential position starting at 1.
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
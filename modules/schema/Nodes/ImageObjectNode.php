<?php
/**
 * ImageObject Node (Helper).
 *
 * NOT a top-level node — doesn't implement NodeInterface, isn't
 * called directly by SchemaGraphBuilder. Called internally by
 * ArticleNode/WebPageNode to build the nested "image" object.
 *
 * Only handles the Primary/Featured Image — there is NO ImageObject
 * for screenshots inside an article's body (a final, locked decision
 * from this module's original scoping brief).
 *
 * There is NO license/acquireLicensePage/creator/creditText/
 * copyrightNotice field at all — removed from scope entirely, not
 * deferred: Lunar SEO will not produce structured data that requires
 * assumptions about an image's copyright or license.
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ImageObjectNode {

	/**
	 * Builds a nested ImageObject from the post/page's Featured Image.
	 *
	 * Returns null if the post has no featured image, or its
	 * attachment is no longer valid (e.g. the media was deleted from
	 * the Media Library) — the parent node (ArticleNode/WebPageNode)
	 * MUST skip the "image" field entirely when this returns null,
	 * rather than inserting an empty object.
	 */
	public function build_for_post( \WP_Post $post ): ?array {
		$attachment_id = get_post_thumbnail_id( $post );

		if ( ! $attachment_id ) {
			return null;
		}

		$src = wp_get_attachment_image_src( $attachment_id, 'full' );

		if ( false === $src ) {
			return null;
		}

		[ $url, $width, $height ] = $src;

		return [
			'@type'  => 'ImageObject',
			'@id'    => SchemaId::primary_image( $this->get_permalink( $post ) ),
			'url'    => $url,
			'width'  => $width,
			'height' => $height,
		];
	}

	/**
	 * The post/page's permalink, falling back to home_url() if
	 * get_permalink() fails (e.g. the post isn't published yet) — the
	 * same fallback pattern as BreadcrumbListNode/ArticleNode.
	 */
	private function get_permalink( \WP_Post $post ): string {
		$permalink = get_permalink( $post );

		return false !== $permalink ? $permalink : home_url( '/' );
	}
}
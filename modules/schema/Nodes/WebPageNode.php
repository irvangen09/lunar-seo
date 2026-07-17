<?php
/**
 * WebPage Node.
 *
 * Hanya untuk post type "page" (SCHEMA_MODULE_ARCHITECTURE.md §4,
 * Opsi B dikonfirmasi). Sengaja dibuat generik/minimal (name, url,
 * isPartOf) dibanding ArticleNode - Page bisa berupa halaman apapun
 * (About, Contact, dst) yang belum tentu representasinya sebagai
 * "artikel" (punya headline/author/tanggal terbit dalam pengertian
 * editorial) itu akurat, konsisten dengan prinsip "tidak membuat
 * klaim yang tidak dapat diverifikasi"
 * (LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md §11).
 *
 * "image" tetap disertakan (opsional) - cakupan ImageObject di §0
 * eksplisit berlaku untuk "Post/Page yang punya featured image",
 * bukan hanya Post.
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WebPageNode implements NodeInterface {

	/**
	 * @var ImageObjectNode
	 */
	private ImageObjectNode $image_object_node;

	/**
	 * @param ImageObjectNode $image_object_node Helper Primary Image (Tahap 2.6).
	 */
	public function __construct( ImageObjectNode $image_object_node ) {
		$this->image_object_node = $image_object_node;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_node(): ?array {
		if ( ! is_singular( 'page' ) ) {
			return null;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		$permalink = $this->get_permalink( $post );

		$node = [
			'@type'    => 'WebPage',
			'@id'      => SchemaId::webpage( $permalink ),
			'url'      => $permalink,
			'name'     => get_the_title( $post ),
			'isPartOf' => [ '@id' => SchemaId::website() ],
		];

		$image = $this->image_object_node->build_for_post( $post );

		if ( null !== $image ) {
			$node['image'] = $image;
		}

		return $node;
	}

	/**
	 * @param \WP_Post $post Page saat ini.
	 * @return string
	 */
	private function get_permalink( \WP_Post $post ): string {
		$permalink = get_permalink( $post );

		return false !== $permalink ? $permalink : home_url( '/' );
	}
}

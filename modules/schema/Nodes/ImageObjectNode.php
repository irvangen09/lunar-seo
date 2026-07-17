<?php
/**
 * ImageObject Node (Helper).
 *
 * BUKAN top-level node - tidak implements NodeInterface, tidak
 * dipanggil langsung oleh SchemaGraphBuilder. Dipanggil secara
 * internal oleh ArticleNode/WebPageNode (Tahap 2.7) untuk membangun
 * nested object "image" (SCHEMA_MODULE_ARCHITECTURE.md §5.5).
 *
 * Hanya menangani Primary/Featured Image - TIDAK ada ImageObject
 * untuk screenshot di dalam body artikel
 * (LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md §4, Final
 * Decision, LOCKED di dokumen sumbernya).
 *
 * TIDAK ADA field license/acquireLicensePage/creator/creditText/
 * copyrightNotice sama sekali - dihapus total dari scope, bukan
 * ditunda (LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md §5,
 * §11 - Engineering Principle: "Lunar SEO tidak akan menghasilkan
 * structured data yang memerlukan asumsi mengenai hak cipta atau
 * lisensi suatu gambar").
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ImageObjectNode {

	/**
	 * Bangun nested ImageObject dari Featured Image post/page.
	 *
	 * Kembalikan null apabila post tidak punya featured image, atau
	 * attachment-nya sudah tidak valid (misal media terhapus dari
	 * Media Library) - parent node (ArticleNode/WebPageNode) WAJIB
	 * men-skip field "image" seluruhnya saat null, bukan menyisipkan
	 * object kosong (ARCHITECTURE.md §10 - "Output frontend hanya
	 * dimuat apabila diperlukan").
	 *
	 * @param \WP_Post $post Post/page yang sedang di-render.
	 * @return array<string, mixed>|null
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
	 * Permalink post/page, dengan fallback ke home_url() apabila
	 * get_permalink() gagal (mis. post belum published) - sama pola
	 * fallback dengan BreadcrumbListNode/ArticleNode.
	 *
	 * @param \WP_Post $post Post/page yang sedang di-render.
	 * @return string
	 */
	private function get_permalink( \WP_Post $post ): string {
		$permalink = get_permalink( $post );

		return false !== $permalink ? $permalink : home_url( '/' );
	}
}

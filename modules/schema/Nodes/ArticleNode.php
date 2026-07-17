<?php
/**
 * Article Node.
 *
 * Hanya untuk post type "post" (SCHEMA_MODULE_ARCHITECTURE.md §4,
 * Opsi B dikonfirmasi - "page" ditangani WebPageNode terpisah).
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ArticleNode implements NodeInterface {

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
		if ( ! is_singular( 'post' ) ) {
			return null;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return null;
		}

		$permalink = $this->get_permalink( $post );

		$node = [
			'@type'     => 'Article',
			'@id'       => SchemaId::article( $permalink ),
			// Judul post ASLI, tanpa dipotong - Google merekomendasikan
			// <=110 karakter tapi tidak mewajibkan, memotong berisiko
			// memenggal makna (SCHEMA_MODULE_ARCHITECTURE.md §5.5).
			'headline'  => get_the_title( $post ),
			'publisher' => [ '@id' => SchemaId::organization() ],
			'isPartOf'  => [ '@id' => SchemaId::website() ],
		];

		$published = $this->format_datetime( $post, 'date' );

		if ( null !== $published ) {
			$node['datePublished'] = $published;
		}

		$modified = $this->format_datetime( $post, 'modified' );

		if ( null !== $modified ) {
			$node['dateModified'] = $modified;
		}

		$author_name = get_the_author_meta( 'display_name', (int) $post->post_author );

		if ( '' !== $author_name ) {
			// Tanpa "url" di Fase 1 - tidak diminta
			// (SCHEMA_MODULE_ARCHITECTURE.md §5.5).
			$node['author'] = [
				'@type' => 'Person',
				'name'  => $author_name,
			];
		}

		// Nested object PENUH (bukan referensi @id) - ImageObjectNode
		// bukan top-level node di "@graph", jadi tidak ada entry lain
		// yang bisa direferensi lewat @id (SCHEMA_MODULE_ARCHITECTURE.md §5.5).
		$image = $this->image_object_node->build_for_post( $post );

		if ( null !== $image ) {
			$node['image'] = $image;
		}

		return $node;
	}

	/**
	 * @param \WP_Post $post Post saat ini.
	 * @return string
	 */
	private function get_permalink( \WP_Post $post ): string {
		$permalink = get_permalink( $post );

		return false !== $permalink ? $permalink : home_url( '/' );
	}

	/**
	 * Format tanggal post jadi ISO 8601 (DATE_W3C) via
	 * get_post_datetime() - null apabila WordPress tidak bisa
	 * menghasilkan datetime yang valid (field di-skip, tidak
	 * menyisipkan tanggal yang salah/kosong).
	 *
	 * @param \WP_Post $post  Post saat ini.
	 * @param string   $field "date" atau "modified".
	 * @return string|null
	 */
	private function format_datetime( \WP_Post $post, string $field ): ?string {
		$datetime = get_post_datetime( $post, $field );

		if ( false === $datetime ) {
			return null;
		}

		return $datetime->format( DATE_W3C );
	}
}

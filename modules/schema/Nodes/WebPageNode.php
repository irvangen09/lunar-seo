<?php

namespace Lunar\SEO\Modules\Schema\Nodes;

use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Deliberately generic/minimal (name, url, isPartOf) compared to
// ArticleNode - a page can be anything (About, Contact, etc.) where
// headline/author/publish-date framing wouldn't accurately represent it.
final class WebPageNode implements NodeInterface {

	private ImageObjectNode $image_object_node;

	private SupportedPostTypes $supported_post_types;

	public function __construct( ImageObjectNode $image_object_node, SupportedPostTypes $supported_post_types ) {
		$this->image_object_node    = $image_object_node;
		$this->supported_post_types = $supported_post_types;
	}

	public function get_node(): ?array {
		if ( ! is_singular() || 'webpage' !== $this->supported_post_types->schema_node( (string) get_post_type() ) ) {
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

	private function get_permalink( \WP_Post $post ): string {
		$permalink = get_permalink( $post );

		return false !== $permalink ? $permalink : home_url( '/' );
	}
}
<?php

namespace Lunar\SEO\Modules\Schema\Nodes;

use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ArticleNode implements NodeInterface {

	private ImageObjectNode $image_object_node;

	private SupportedPostTypes $supported_post_types;

	public function __construct( ImageObjectNode $image_object_node, SupportedPostTypes $supported_post_types ) {
		$this->image_object_node    = $image_object_node;
		$this->supported_post_types = $supported_post_types;
	}

	public function get_node(): ?array {
		if ( ! is_singular() || 'article' !== $this->supported_post_types->schema_node( (string) get_post_type() ) ) {
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
			// Separate from @id (which has the #article anchor) - Google
			// recommends "url" exist as its own property, found via Rich
			// Results Test during live testing.
			'url'       => $permalink,
			// Original post title, not truncated - Google recommends
			// <=110 characters but doesn't require it, and truncating
			// risks cutting off meaning.
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
			$author_node = [
				'@type' => 'Person',
				'name'  => $author_name,
			];

			// WordPress's native author archive URL - not an assumption
			// about content ownership, just data WordPress already exposes.
			$author_url = get_author_posts_url( (int) $post->post_author );

			if ( '' !== $author_url ) {
				$author_node['url'] = $author_url;
			}

			$node['author'] = $author_node;
		}

		// Full nested object, not an @id reference - ImageObjectNode is
		// not a top-level node in "@graph", so there's nothing else to
		// reference it by @id.
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

	// Returns null (field skipped by the caller) when WordPress can't
	// produce a valid datetime, rather than inserting an empty/wrong date.
	private function format_datetime( \WP_Post $post, string $field ): ?string {
		$datetime = get_post_datetime( $post, $field );

		if ( false === $datetime ) {
			return null;
		}

		return $datetime->format( DATE_W3C );
	}
}
<?php

namespace Lunar\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SupportedPostTypes {

	private const DEFAULT = [
		'post' => [
			'content_group' => 'post',
			'schema_node'   => 'article',
		],
		'page' => [
			'content_group' => 'page',
			'schema_node'   => 'webpage',
		],
	];

	public function all(): array {
		/**
		 * Filters the post types Lunar SEO treats as supported for its
		 * per-post SEO fields (Editor/MetaBox), Content settings
		 * section, and Schema node type.
		 *
		 * @since Unreleased
		 *
		 * @param array $post_types Post type slug => [
		 *                              'content_group' => 'post'|'page',
		 *                              'schema_node'   => 'article'|'webpage',
		 *                          ].
		 */
		return apply_filters( 'lunar_seo_supported_post_types', self::DEFAULT );
	}

	public function is_supported( string $post_type ): bool {
		return isset( $this->all()[ $post_type ] );
	}

	public function content_group( string $post_type ): ?string {
		return $this->all()[ $post_type ]['content_group'] ?? null;
	}

	public function schema_node( string $post_type ): ?string {
		return $this->all()[ $post_type ]['schema_node'] ?? null;
	}
}
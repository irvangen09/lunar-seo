<?php
/**
 * Section: Categories & Tags.
 *
 * Manages settings for the Categories and Tags archives: Show in
 * Search Results, SEO Title, Meta Description (with an auto-generate
 * option).
 *
 * Both types (categories/tags) share an identical data structure, so
 * the sanitization logic is shared through one private method to
 * avoid duplication.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CategoriesTags implements SectionInterface {

	private const SECTION_KEY = 'categories_tags';

	private const TAXONOMY_TYPES = [ 'categories', 'tags' ];

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		$sanitized = [];

		foreach ( self::TAXONOMY_TYPES as $type ) {
			$raw               = ( isset( $input[ $type ] ) && is_array( $input[ $type ] ) ) ? $input[ $type ] : [];
			$sanitized[ $type ] = $this->sanitize_taxonomy( $raw );
		}

		return $sanitized;
	}

	private function sanitize_taxonomy( array $raw ): array {
		return [
			'show_in_search_results'    => ! empty( $raw['show_in_search_results'] ),
			'seo_title'                 => isset( $raw['seo_title'] )
				? sanitize_text_field( $raw['seo_title'] )
				: '',
			'meta_description'         => isset( $raw['meta_description'] )
				? sanitize_textarea_field( $raw['meta_description'] )
				: '',
			'auto_generate_description' => ! empty( $raw['auto_generate_description'] ),
		];
	}
}
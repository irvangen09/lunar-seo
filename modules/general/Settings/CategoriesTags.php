<?php
/**
 * Section: Categories & Tags.
 *
 * Mengelola setting untuk archive Categories dan Tags:
 * Show in Search Results, SEO Title, Meta Description
 * (dengan opsi auto-generate).
 *
 * Struktur data kedua tipe (categories/tags) identik, sehingga
 * logic sanitasi dibagi lewat satu method privat untuk menghindari
 * duplikasi (CODING_STANDARD.md §2 - DRY).
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CategoriesTags implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'categories_tags';

	/**
	 * Tipe taksonomi yang dikelola section ini.
	 *
	 * @var string[]
	 */
	private const TAXONOMY_TYPES = [ 'categories', 'tags' ];

	/**
	 * {@inheritDoc}
	 */
	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( array $input ): array {
		$sanitized = [];

		foreach ( self::TAXONOMY_TYPES as $type ) {
			$raw               = ( isset( $input[ $type ] ) && is_array( $input[ $type ] ) ) ? $input[ $type ] : [];
			$sanitized[ $type ] = $this->sanitize_taxonomy( $raw );
		}

		return $sanitized;
	}

	/**
	 * Sanitasi satu tipe taksonomi (categories atau tags).
	 *
	 * @param array $raw Data mentah satu tipe taksonomi.
	 * @return array
	 */
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

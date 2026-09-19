<?php
/**
 * Section: Content.
 *
 * Manages default SEO Title & Meta Description for: Homepage, Post,
 * Page (with an auto-generate description option), plus an SEO Title
 * Template for Search and 404 (no description, since those pages have
 * no real content to generate one from).
 *
 * NOTE: the "404" key on the mockup is represented internally as
 * "not_found", because PHP automatically converts a numeric array key
 * ("404") to an integer — which can cause unexpected behavior when
 * encoding to JSON/REST.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Content implements SectionInterface {

	private const SECTION_KEY = 'content';

	// Content types with both SEO Title & Meta Description (with an
	// auto-generate option).
	private const TYPES_WITH_DESCRIPTION = [ 'homepage', 'post', 'page' ];

	// Content types with only an SEO Title Template (no Meta
	// Description — Search & 404 have no real content to generate one
	// from).
	private const TYPES_TITLE_ONLY = [ 'search', 'not_found' ];

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		$sanitized = [];

		foreach ( self::TYPES_WITH_DESCRIPTION as $type ) {
			$raw               = ( isset( $input[ $type ] ) && is_array( $input[ $type ] ) ) ? $input[ $type ] : [];
			$sanitized[ $type ] = $this->sanitize_with_description( $raw );
		}

		foreach ( self::TYPES_TITLE_ONLY as $type ) {
			$raw               = ( isset( $input[ $type ] ) && is_array( $input[ $type ] ) ) ? $input[ $type ] : [];
			$sanitized[ $type ] = $this->sanitize_title_only( $raw );
		}

		return $sanitized;
	}

	private function sanitize_with_description( array $raw ): array {
		return [
			'seo_title'                 => isset( $raw['seo_title'] )
				? sanitize_text_field( $raw['seo_title'] )
				: '',
			'meta_description'         => isset( $raw['meta_description'] )
				? sanitize_textarea_field( $raw['meta_description'] )
				: '',
			'auto_generate_description' => ! empty( $raw['auto_generate_description'] ),
		];
	}

	private function sanitize_title_only( array $raw ): array {
		return [
			'seo_title' => isset( $raw['seo_title'] )
				? sanitize_text_field( $raw['seo_title'] )
				: '',
		];
	}
}
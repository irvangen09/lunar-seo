<?php
/**
 * Section: Content.
 *
 * Mengelola default SEO Title & Meta Description untuk:
 * Homepage, Post, Page (dengan opsi auto-generate description),
 * serta SEO Title Template untuk Search dan 404 (tanpa description,
 * karena halaman tersebut tidak memiliki konten nyata untuk digenerate).
 *
 * CATATAN: Key "404" pada mockup direpresentasikan sebagai
 * "not_found" secara internal, karena PHP secara otomatis mengubah
 * key array numerik ("404") menjadi integer - berpotensi
 * menyebabkan perilaku tak terduga saat encode ke JSON/REST.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Content implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'content';

	/**
	 * Tipe konten yang memiliki SEO Title & Meta Description
	 * (dengan opsi auto-generate).
	 *
	 * @var string[]
	 */
	private const TYPES_WITH_DESCRIPTION = [ 'homepage', 'post', 'page' ];

	/**
	 * Tipe konten yang hanya memiliki SEO Title Template
	 * (tidak ada Meta Description - Search & 404 tidak punya
	 * konten nyata untuk digenerate).
	 *
	 * @var string[]
	 */
	private const TYPES_TITLE_ONLY = [ 'search', 'not_found' ];

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

	/**
	 * Sanitasi tipe konten yang memiliki SEO Title & Meta Description.
	 *
	 * @param array $raw Data mentah satu tipe konten.
	 * @return array
	 */
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

	/**
	 * Sanitasi tipe konten yang hanya memiliki SEO Title Template.
	 *
	 * @param array $raw Data mentah satu tipe konten.
	 * @return array
	 */
	private function sanitize_title_only( array $raw ): array {
		return [
			'seo_title' => isset( $raw['seo_title'] )
				? sanitize_text_field( $raw['seo_title'] )
				: '',
		];
	}
}

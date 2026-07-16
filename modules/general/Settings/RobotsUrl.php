<?php
/**
 * Section: Robots & URL.
 *
 * Mengelola Default Robots Meta (whitelist directive), Default
 * Robots untuk Archives & 404 (preset whitelist), serta URL
 * settings (Remove Category Base, Remove Tag Base, Redirect
 * Attachments to Parent).
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RobotsUrl implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'robots_url';

	/**
	 * Directive robots meta yang diizinkan (whitelist).
	 *
	 * "index"/"follow" SENGAJA TIDAK termasuk - keduanya adalah
	 * perilaku default crawler yang tidak memiliki representasi
	 * eksplisit pada robots meta tag (WordPress core "wp_robots()"
	 * juga tidak pernah mencetak string "index"/"follow" secara
	 * harfiah). Hanya directive NEGATIF yang benar-benar berarti
	 * sesuatu untuk dicetak.
	 *
	 * @var string[]
	 */
	private const ALLOWED_ROBOTS_DIRECTIVES = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

	/**
	 * Default directive apabila option belum pernah diisi sama
	 * sekali. Array kosong = tidak ada restriksi (index+follow,
	 * perilaku default WordPress/crawler).
	 *
	 * @var string[]
	 */
	private const DEFAULT_ROBOTS_DIRECTIVES = [];

	/**
	 * Preset dropdown untuk Robots Archives & 404 (whitelist).
	 * "default" berarti mengikuti Default Robots Meta di atasnya.
	 *
	 * @var string[]
	 */
	private const ALLOWED_ROBOTS_PRESETS = [ 'default', 'index_follow', 'noindex_follow', 'noindex_nofollow' ];

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
		return [
			'default_robots_meta' => $this->sanitize_directives( $input['default_robots_meta'] ?? [] ),
			'archives_robots'     => $this->sanitize_preset( $input['archives_robots'] ?? '', 'default' ),
			'not_found_robots'    => $this->sanitize_preset( $input['not_found_robots'] ?? '', 'noindex_follow' ),
			'remove_category_base'           => ! empty( $input['remove_category_base'] ),
			'remove_tag_base'                => ! empty( $input['remove_tag_base'] ),
			'redirect_attachments_to_parent' => ! empty( $input['redirect_attachments_to_parent'] ),
		];
	}

	/**
	 * Sanitasi daftar directive robots meta terhadap whitelist.
	 *
	 * Array kosong adalah PILIHAN SAH (admin sengaja uncheck seluruh
	 * directive), bukan indikasi input tidak valid - hanya fallback
	 * ke default apabila $value bukan array sama sekali (option
	 * belum pernah diisi/rusak).
	 *
	 * @param mixed $value Nilai mentah dari input.
	 * @return string[]
	 */
	private function sanitize_directives( $value ): array {
		if ( ! is_array( $value ) ) {
			return self::DEFAULT_ROBOTS_DIRECTIVES;
		}

		return array_values(
			array_intersect( array_unique( $value ), self::ALLOWED_ROBOTS_DIRECTIVES )
		);
	}

	/**
	 * Sanitasi preset dropdown Robots (Archives/404) terhadap whitelist.
	 *
	 * @param mixed  $value           Nilai mentah dari input.
	 * @param string $default_preset  Preset default apabila nilai tidak valid.
	 * @return string
	 */
	private function sanitize_preset( $value, string $default_preset ): string {
		if ( ! is_string( $value ) ) {
			return $default_preset;
		}

		return in_array( $value, self::ALLOWED_ROBOTS_PRESETS, true ) ? $value : $default_preset;
	}
}

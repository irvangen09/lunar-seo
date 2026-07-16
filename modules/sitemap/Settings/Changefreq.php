<?php
/**
 * Section: Changefreq.
 *
 * Mengelola nilai <changefreq> XML Sitemap per tipe konten. Tidak
 * ada mockup untuk UI ini (dikonfirmasi dengan pengguna) - struktur
 * meniru Priorities.php, whitelist nilai mengikuti protokol resmi
 * sitemaps.org.
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Changefreq implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'changefreq';

	/**
	 * Nilai yang diizinkan (whitelist), sesuai protokol sitemaps.org.
	 *
	 * @var string[]
	 */
	private const ALLOWED_VALUES = [ 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never' ];

	/**
	 * Default value per field - seragam "monthly", konsisten dengan
	 * contoh XML pada dokumen referensi.
	 *
	 * @var array<string, string>
	 */
	private const DEFAULTS = [
		'homepage'                 => 'monthly',
		'posts'                    => 'monthly',
		'static_pages'             => 'monthly',
		'categories'               => 'monthly',
		'archives'                 => 'monthly',
		'tag_pages'                => 'monthly',
		'author_pages'             => 'monthly',
		'custom_post_type_default' => 'monthly',
		'custom_taxonomy_default'  => 'monthly',
	];

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

		foreach ( self::DEFAULTS as $field => $default ) {
			$sanitized[ $field ] = $this->sanitize_value( $input[ $field ] ?? $default, $default );
		}

		return $sanitized;
	}

	/**
	 * Sanitasi nilai changefreq terhadap whitelist.
	 *
	 * @param mixed  $value   Nilai mentah.
	 * @param string $default Nilai default apabila tidak valid.
	 * @return string
	 */
	private function sanitize_value( $value, string $default ): string {
		if ( ! is_string( $value ) ) {
			return $default;
		}

		return in_array( $value, self::ALLOWED_VALUES, true ) ? $value : $default;
	}
}

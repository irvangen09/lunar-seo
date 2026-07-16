<?php
/**
 * Section: Priorities.
 *
 * Mengelola nilai <priority> XML Sitemap per tipe konten, serta
 * toggle mode Automatic/Manual khusus untuk Posts (rumus Automatic
 * dijelaskan di Services/PriorityCalculator.php).
 *
 * "Minimum Post Priority" SELALU berlaku sebagai batas bawah, baik
 * mode Automatic maupun Manual aktif (sesuai dokumen).
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Priorities implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'priorities';

	/**
	 * Default value per field, sesuai nilai pada mockup.
	 *
	 * @var array<string, float>
	 */
	private const DEFAULTS = [
		'homepage'                 => 1.0,
		'posts'                    => 0.8,
		'minimum_post_priority'    => 0.2,
		'static_pages'             => 0.6,
		'categories'               => 0.3,
		'archives'                 => 0.3,
		'tag_pages'                => 0.3,
		'author_pages'             => 0.3,
		'custom_post_type_default' => 0.3,
		'custom_taxonomy_default'  => 0.3,
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
			$sanitized[ $field ] = $this->sanitize_priority( $input[ $field ] ?? $default, $default );
		}

		$sanitized['auto_calculate_post_priority'] = ! empty( $input['auto_calculate_post_priority'] );

		return $sanitized;
	}

	/**
	 * Sanitasi nilai priority - numerik, dibulatkan 1 desimal,
	 * dibatasi rentang valid protokol sitemap (0.0 - 1.0).
	 *
	 * @param mixed $value   Nilai mentah.
	 * @param float $default Nilai default apabila tidak valid.
	 * @return float
	 */
	private function sanitize_priority( $value, float $default ): float {
		if ( ! is_numeric( $value ) ) {
			return $default;
		}

		$value = round( (float) $value, 1 );

		return max( 0.0, min( 1.0, $value ) );
	}
}

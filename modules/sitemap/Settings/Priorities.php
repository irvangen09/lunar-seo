<?php
/**
 * Section: Priorities.
 *
 * Manages the XML Sitemap <priority> value per content type, plus the
 * Automatic/Manual mode toggle specific to Posts (the Automatic
 * formula is explained in Services/PriorityCalculator.php).
 *
 * "Minimum Post Priority" ALWAYS applies as the floor, whether
 * Automatic or Manual mode is active.
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Priorities implements SectionInterface {

	private const SECTION_KEY = 'priorities';

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

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		$sanitized = [];

		foreach ( self::DEFAULTS as $field => $default ) {
			$sanitized[ $field ] = $this->sanitize_priority( $input[ $field ] ?? $default, $default );
		}

		$sanitized['auto_calculate_post_priority'] = ! empty( $input['auto_calculate_post_priority'] );

		return $sanitized;
	}

	/**
	 * Numeric, rounded to 1 decimal, clamped to the sitemap protocol's
	 * valid range (0.0–1.0).
	 */
	private function sanitize_priority( $value, float $default ): float {
		if ( ! is_numeric( $value ) ) {
			return $default;
		}

		$value = round( (float) $value, 1 );

		return max( 0.0, min( 1.0, $value ) );
	}
}
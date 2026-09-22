<?php
/**
 * Section: Changefreq.
 *
 * Manages the XML Sitemap <changefreq> value per content type. No
 * mockup exists for this UI — its structure mirrors Priorities.php,
 * and its value whitelist follows the official sitemaps.org protocol.
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Changefreq implements SectionInterface {

	private const SECTION_KEY = 'changefreq';

	private const ALLOWED_VALUES = [ 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never' ];

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

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		$sanitized = [];

		foreach ( self::DEFAULTS as $field => $default ) {
			$sanitized[ $field ] = $this->sanitize_value( $input[ $field ] ?? $default, $default );
		}

		return $sanitized;
	}

	private function sanitize_value( $value, string $default ): string {
		if ( ! is_string( $value ) ) {
			return $default;
		}

		return in_array( $value, self::ALLOWED_VALUES, true ) ? $value : $default;
	}
}
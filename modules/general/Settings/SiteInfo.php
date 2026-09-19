<?php
/**
 * Section: Site Info.
 *
 * Manages: Website Name, Alternate Website Name, Title Separator,
 * Site Image.
 *
 * IMPORTANT: the "Tagline" field on the mockup is NOT stored in this
 * section. Tagline is proxied directly to WordPress's own native
 * setting (get_bloginfo('description') / the core "blogdescription"
 * option), which is already automatically exposed through WordPress's
 * built-in REST endpoint (/wp/v2/settings, "description" field) with
 * no extra code needed. This avoids duplicating data between our
 * setting and WordPress's native one.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteInfo implements SectionInterface {

	private const SECTION_KEY = 'site_info';

	// Whitelist (not free text) to prevent unexpected input, matching
	// the choices on the Site Info UI mockup.
	private const ALLOWED_SEPARATORS = [ '|', '-', '—', ':', '.', '•', '*', '~', '«', '»', '/', '\\', '>', '<' ];

	private const DEFAULT_SEPARATOR = '|';

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		return [
			'website_name'           => isset( $input['website_name'] )
				? sanitize_text_field( $input['website_name'] )
				: '',
			'alternate_website_name' => isset( $input['alternate_website_name'] )
				? sanitize_text_field( $input['alternate_website_name'] )
				: '',
			'title_separator'        => $this->sanitize_separator( $input['title_separator'] ?? '' ),
			'site_image_id'          => isset( $input['site_image_id'] )
				? absint( $input['site_image_id'] )
				: 0,
		];
	}

	private function sanitize_separator( $value ): string {
		if ( ! is_string( $value ) ) {
			return self::DEFAULT_SEPARATOR;
		}

		return in_array( $value, self::ALLOWED_SEPARATORS, true )
			? $value
			: self::DEFAULT_SEPARATOR;
	}
}
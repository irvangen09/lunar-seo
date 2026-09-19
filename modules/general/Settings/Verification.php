<?php
/**
 * Section: Verification.
 *
 * Manages site-ownership verification codes for Google Search
 * Console, Bing Webmaster, and Yandex Webmaster.
 *
 * Pinterest verification is out of scope — not supported by this
 * section.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Verification implements SectionInterface {

	private const SECTION_KEY = 'verification';

	private const PLATFORMS = [ 'google', 'bing', 'yandex' ];

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		$sanitized = [];

		foreach ( self::PLATFORMS as $platform ) {
			$sanitized[ $platform ] = isset( $input[ $platform ] )
				? sanitize_text_field( $input[ $platform ] )
				: '';
		}

		return $sanitized;
	}
}
<?php
/**
 * Section: Verification.
 *
 * Mengelola kode verifikasi kepemilikan situs untuk Google Search
 * Console, Bing Webmaster, dan Yandex Webmaster.
 *
 * Pinterest sengaja tidak termasuk - dikeluarkan dari scope module
 * General sesuai keputusan project.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Verification implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'verification';

	/**
	 * Platform verifikasi yang didukung.
	 *
	 * @var string[]
	 */
	private const PLATFORMS = [ 'google', 'bing', 'yandex' ];

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

		foreach ( self::PLATFORMS as $platform ) {
			$sanitized[ $platform ] = isset( $input[ $platform ] )
				? sanitize_text_field( $input[ $platform ] )
				: '';
		}

		return $sanitized;
	}
}

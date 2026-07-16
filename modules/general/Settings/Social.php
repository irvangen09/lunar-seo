<?php
/**
 * Section: Social.
 *
 * Mengelola setting Open Graph (enable + default image) dan
 * Twitter Card (enable + default image).
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Social implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'social';

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
			'open_graph'   => $this->sanitize_platform( $input['open_graph'] ?? [] ),
			'twitter_card' => $this->sanitize_platform( $input['twitter_card'] ?? [] ),
		];
	}

	/**
	 * Sanitasi satu platform (Open Graph atau Twitter Card).
	 *
	 * Kedua platform memiliki bentuk data identik (enabled + image_id),
	 * sehingga logic sanitasi digabung lewat satu method
	 * (CODING_STANDARD.md §2 - DRY).
	 *
	 * @param mixed $raw Data mentah satu platform.
	 * @return array
	 */
	private function sanitize_platform( $raw ): array {
		$raw = is_array( $raw ) ? $raw : [];

		return [
			'enabled'  => ! empty( $raw['enabled'] ),
			'image_id' => isset( $raw['image_id'] ) ? absint( $raw['image_id'] ) : 0,
		];
	}
}

<?php
/**
 * Section: Social.
 *
 * Manages Open Graph (enable + default image) and Twitter Card
 * (enable + default image) settings.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Social implements SectionInterface {

	private const SECTION_KEY = 'social';

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		return [
			'open_graph'   => $this->sanitize_platform( $input['open_graph'] ?? [] ),
			'twitter_card' => $this->sanitize_platform( $input['twitter_card'] ?? [] ),
		];
	}

	/**
	 * Open Graph and Twitter Card share the same data shape
	 * (enabled + image_id), so both are sanitized through this one
	 * method rather than two near-identical ones.
	 */
	private function sanitize_platform( $raw ): array {
		$raw = is_array( $raw ) ? $raw : [];

		return [
			'enabled'  => ! empty( $raw['enabled'] ),
			'image_id' => isset( $raw['image_id'] ) ? absint( $raw['image_id'] ) : 0,
		];
	}
}
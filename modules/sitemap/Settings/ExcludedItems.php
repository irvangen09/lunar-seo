<?php
/**
 * Section: Excluded Items.
 *
 * Manages the list of categories and posts/pages excluded from the
 * XML Sitemap even when the relevant Include toggle is on.
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ExcludedItems implements SectionInterface {

	private const SECTION_KEY = 'excluded_items';

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		return [
			'excluded_categories' => $this->sanitize_id_list( $input['excluded_categories'] ?? [] ),
			'excluded_posts'      => $this->sanitize_id_list( $input['excluded_posts'] ?? [] ),
		];
	}

	/**
	 * Accepts either an array of IDs or a comma-separated string (e.g.
	 * "110,121"), so it's flexible to whichever shape the UI sends
	 * (a checklist OR a text field).
	 *
	 * @return int[]
	 */
	private function sanitize_id_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_map( 'trim', explode( ',', $value ) );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		$ids = array_map( 'absint', $value );
		$ids = array_filter( $ids ); // Drop 0/invalid values.

		return array_values( array_unique( $ids ) );
	}
}
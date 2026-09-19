<?php
/**
 * Section: Robots & URL.
 *
 * Manages the Default Robots Meta (directive whitelist), Default
 * Robots for Archives & 404 (preset whitelist), and URL settings
 * (Remove Category Base, Remove Tag Base, Redirect Attachments to
 * Parent).
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RobotsUrl implements SectionInterface {

	private const SECTION_KEY = 'robots_url';

	// "index"/"follow" are DELIBERATELY NOT included — both are the
	// crawler's default behavior with no explicit representation in a
	// robots meta tag (WordPress core's own wp_robots() never prints
	// the literal string "index"/"follow" either). Only the NEGATIVE
	// directives actually mean something worth printing.
	private const ALLOWED_ROBOTS_DIRECTIVES = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

	// Default when the option has never been filled in at all. An
	// empty array = no restriction (index+follow, WordPress/crawler's
	// default behavior).
	private const DEFAULT_ROBOTS_DIRECTIVES = [];

	// "default" means follow the Default Robots Meta above it.
	private const ALLOWED_ROBOTS_PRESETS = [ 'default', 'index_follow', 'noindex_follow', 'noindex_nofollow' ];

	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	public function sanitize( array $input ): array {
		return [
			'default_robots_meta' => $this->sanitize_directives( $input['default_robots_meta'] ?? [] ),
			'archives_robots'     => $this->sanitize_preset( $input['archives_robots'] ?? '', 'default' ),
			'not_found_robots'    => $this->sanitize_preset( $input['not_found_robots'] ?? '', 'noindex_follow' ),
			'remove_category_base'           => ! empty( $input['remove_category_base'] ),
			'remove_tag_base'                => ! empty( $input['remove_tag_base'] ),
			'redirect_attachments_to_parent' => ! empty( $input['redirect_attachments_to_parent'] ),
		];
	}

	/**
	 * An empty array is a VALID CHOICE (the admin deliberately
	 * unchecked every directive), not a sign of invalid input — this
	 * only falls back to the default when $value isn't an array at all
	 * (option never filled in, or corrupted).
	 */
	private function sanitize_directives( $value ): array {
		if ( ! is_array( $value ) ) {
			return self::DEFAULT_ROBOTS_DIRECTIVES;
		}

		return array_values(
			array_intersect( array_unique( $value ), self::ALLOWED_ROBOTS_DIRECTIVES )
		);
	}

	private function sanitize_preset( $value, string $default_preset ): string {
		if ( ! is_string( $value ) ) {
			return $default_preset;
		}

		return in_array( $value, self::ALLOWED_ROBOTS_PRESETS, true ) ? $value : $default_preset;
	}
}
<?php
/**
 * Contract every Sitemap module settings section must implement.
 *
 * Deliberately does NOT use/extend the General module's
 * SectionInterface — each module must stand alone without depending
 * directly on another module. Duplicating a contract this small (2
 * methods) is a reasonable trade-off against cross-module coupling.
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SectionInterface {

	/**
	 * This section's unique key, used as the nested array key in the
	 * module's option.
	 */
	public function get_section_key(): string;

	/**
	 * Sanitizes this section's raw data before it's saved.
	 */
	public function sanitize( array $input ): array;
}
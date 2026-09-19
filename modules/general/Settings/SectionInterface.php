<?php
/**
 * Contract every settings section must implement.
 *
 * Each section (Site Info, Content, Categories & Tags, Social,
 * Verification, Robots & URL) manages its own fields and sanitization,
 * and registers itself with Settings.php as the orchestrator.
 *
 * Deliberately minimal (2 methods), avoiding abstraction that isn't
 * needed yet.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SectionInterface {

	/**
	 * This section's unique key, used as the nested array key in the
	 * module's option (e.g. "site_info", "categories_tags").
	 */
	public function get_section_key(): string;

	/**
	 * Sanitizes this section's raw data before it's saved.
	 *
	 * Receives ONLY this section's own sub-array (not the whole
	 * module option), so no section needs to know another section's
	 * structure.
	 */
	public function sanitize( array $input ): array;
}
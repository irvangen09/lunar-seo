<?php
/**
 * Admin Menu.
 *
 * Shared Service holding the "Lunar SEO" top-level menu slug. Any
 * module that needs to register a submenu under it (Sitemap, Schema,
 * and future modules) reads this slug via Constructor Injection —
 * never by reaching into another module's class directly.
 *
 * @package Lunar\SEO\Services
 */

namespace Lunar\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminMenu {

	// Owned here (not in modules/general/Admin.php) so any module can
	// read it from one neutral source, without depending on a specific
	// module's class.
	private const TOP_LEVEL_SLUG = 'lunar-seo-general';

	public function get_top_level_slug(): string {
		return self::TOP_LEVEL_SLUG;
	}
}
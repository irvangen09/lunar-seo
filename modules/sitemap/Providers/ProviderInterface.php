<?php
/**
 * Contract every sitemap content Provider must implement.
 *
 * Each Provider is responsible for producing a list of raw URL
 * entries for ONE content type. A Provider does NOT handle caching
 * (that's SitemapCache's responsibility at the Frontend.php level) or
 * XML rendering (that's XmlBuilder's responsibility) — a pure data
 * source.
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ProviderInterface {

	/**
	 * @return array<int, array{loc: string, lastmod: string|null, changefreq: string|null, priority: float|null}>
	 */
	public function get_entries(): array;
}
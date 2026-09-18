<?php
/**
 * Date Archive Provider.
 *
 * Produces one entry per monthly archive that has at least one
 * published post. Scoped to the native `post` post type only, and to
 * monthly granularity (not yearly) — WordPress's own date archive
 * URLs are monthly (/YYYY/MM/), so that's the natural unit here.
 *
 * Uses a direct $wpdb query because no WordPress API produces the list
 * of distinct year/month combinations efficiently (wp_get_archives()
 * is built for HTML widget output, not structured data) — the same
 * exception as SitemapCache::flush_all().
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DateArchiveProvider implements ProviderInterface {

	private const MODULE_SLUG = 'sitemap';

	private OptionManager $option_manager;

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	public function get_entries(): array {
		global $wpdb;

		$priorities = $this->option_manager->get_section( self::MODULE_SLUG, 'priorities' );
		$changefreq = $this->option_manager->get_section( self::MODULE_SLUG, 'changefreq' );

		$priority         = (float) ( $priorities['archives'] ?? 0.3 );
		$entry_changefreq = $changefreq['archives'] ?? 'monthly';

		// phpcs:ignore -- No user input involved, this is a static query.
		$months = $wpdb->get_results(
			"SELECT DISTINCT YEAR(post_date) AS year, MONTH(post_date) AS month
			FROM {$wpdb->posts}
			WHERE post_status = 'publish' AND post_type = 'post'
			ORDER BY post_date DESC"
		);

		$entries = [];

		foreach ( $months as $month ) {
			$entries[] = [
				'loc'        => get_month_link( (int) $month->year, (int) $month->month ),
				'lastmod'    => null,
				'changefreq' => $entry_changefreq,
				'priority'   => $priority,
			];
		}

		return $entries;
	}
}
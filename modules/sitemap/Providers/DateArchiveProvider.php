<?php
/**
 * Date Archive Provider.
 *
 * Menghasilkan entry untuk setiap arsip bulanan yang memiliki
 * minimal satu post terpublish (interpretasi dari toggle
 * "Include archives" pada dokumen - lihat catatan pada Frontend.php
 * soal asumsi ini).
 *
 * Memakai query $wpdb langsung karena tidak ada WordPress API
 * bawaan yang menghasilkan daftar kombinasi tahun/bulan unik secara
 * efisien (wp_get_archives() dirancang untuk output HTML widget,
 * bukan data terstruktur) - pengecualian yang sama seperti pada
 * SitemapCache::flush_all().
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DateArchiveProvider implements ProviderInterface {

	/**
	 * Slug module, dipakai untuk membaca Global Settings.
	 *
	 * @var string
	 */
	private const MODULE_SLUG = 'sitemap';

	/**
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 */
	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_entries(): array {
		global $wpdb;

		$priorities = $this->option_manager->get_section( self::MODULE_SLUG, 'priorities' );
		$changefreq = $this->option_manager->get_section( self::MODULE_SLUG, 'changefreq' );

		$priority         = (float) ( $priorities['archives'] ?? 0.3 );
		$entry_changefreq = $changefreq['archives'] ?? 'monthly';

		// phpcs:ignore -- Tidak ada parameter dari input pengguna, query statis.
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

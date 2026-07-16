<?php
/**
 * XML Builder.
 *
 * Bangun string XML (sitemap index maupun urlset per tipe konten)
 * dari array entries mentah. Format mengikuti protokol resmi
 * sitemaps.org, dikonfirmasi cocok dengan referensi mockup yang
 * diberikan pengguna.
 *
 * Method pagination di sini murni operasi array (array_slice) pada
 * data yang SUDAH di-cache oleh SitemapCache - tidak melakukan
 * query database, sehingga ringan dipanggil berkali-kali
 * (SITEMAP_MODULE_ARCHITECTURE.md §4).
 *
 * @package Lunar\SEO\Modules\Sitemap\Services
 */

namespace Lunar\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class XmlBuilder {

	/**
	 * Bangun XML urlset (daftar URL) dari array entries.
	 *
	 * @param array $entries                Daftar entry, tiap entry: [ 'loc', 'lastmod'?, 'changefreq'?, 'priority'? ].
	 * @param bool  $include_last_modified  Apakah <lastmod> disertakan (Sitemap Content - "Include the last modification time").
	 * @return string
	 */
	public function build_urlset( array $entries, bool $include_last_modified ): string {
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

		foreach ( $entries as $entry ) {
			$xml .= "\t<url>\n";
			$xml .= "\t\t<loc>" . esc_url( $entry['loc'] ) . "</loc>\n";

			if ( $include_last_modified && ! empty( $entry['lastmod'] ) ) {
				$xml .= "\t\t<lastmod>" . esc_html( $entry['lastmod'] ) . "</lastmod>\n";
			}

			if ( ! empty( $entry['changefreq'] ) ) {
				$xml .= "\t\t<changefreq>" . esc_html( $entry['changefreq'] ) . "</changefreq>\n";
			}

			if ( isset( $entry['priority'] ) ) {
				$xml .= "\t\t<priority>" . esc_html( (string) $entry['priority'] ) . "</priority>\n";
			}

			$xml .= "\t</url>\n";
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * Bangun XML sitemap index (daftar sitemap) dari array sitemaps.
	 *
	 * @param array $sitemaps Daftar sitemap, tiap entry: [ 'loc', 'lastmod'? ].
	 * @return string
	 */
	public function build_sitemap_index( array $sitemaps ): string {
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">' . "\n";

		foreach ( $sitemaps as $sitemap ) {
			$xml .= "\t<sitemap>\n";
			$xml .= "\t\t<loc>" . esc_url( $sitemap['loc'] ) . "</loc>\n";

			if ( ! empty( $sitemap['lastmod'] ) ) {
				$xml .= "\t\t<lastmod>" . esc_html( $sitemap['lastmod'] ) . "</lastmod>\n";
			}

			$xml .= "\t</sitemap>\n";
		}

		$xml .= '</sitemapindex>';

		return $xml;
	}

	/**
	 * Potong entries sesuai halaman & Links Per Page (pagination).
	 *
	 * @param array $entries        Seluruh entries (dari cache).
	 * @param int   $links_per_page Batas URL per halaman.
	 * @param int   $page           Nomor halaman (1-based).
	 * @return array
	 */
	public function paginate_entries( array $entries, int $links_per_page, int $page ): array {
		if ( $links_per_page <= 0 ) {
			return $entries;
		}

		$offset = ( max( 1, $page ) - 1 ) * $links_per_page;

		return array_slice( $entries, $offset, $links_per_page );
	}

	/**
	 * Hitung jumlah halaman yang dibutuhkan berdasarkan total
	 * entries dan Links Per Page.
	 *
	 * @param array $entries        Seluruh entries.
	 * @param int   $links_per_page Batas URL per halaman.
	 * @return int
	 */
	public function count_pages( array $entries, int $links_per_page ): int {
		if ( empty( $entries ) || $links_per_page <= 0 ) {
			return 0;
		}

		return (int) ceil( count( $entries ) / $links_per_page );
	}
}

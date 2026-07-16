<?php
/**
 * Kontrak yang wajib diimplementasikan setiap Provider konten sitemap.
 *
 * Setiap Provider bertanggung jawab menghasilkan daftar URL entry
 * mentah untuk SATU tipe konten. Provider tidak menangani caching
 * (itu tanggung jawab SitemapCache di level Frontend.php) maupun
 * rendering XML (itu tanggung jawab XmlBuilder) - murni sumber data.
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ProviderInterface {

	/**
	 * Hasilkan daftar URL entry mentah.
	 *
	 * @return array<int, array{loc: string, lastmod: string|null, changefreq: string|null, priority: float|null}>
	 */
	public function get_entries(): array;
}

<?php
/**
 * Kontrak yang wajib diimplementasikan setiap section settings
 * module Sitemap.
 *
 * Sengaja TIDAK memakai/mewarisi SectionInterface milik module
 * General - setiap module harus berdiri sendiri tanpa bergantung
 * langsung pada module lain (ARCHITECTURE.md §4, §22). Duplikasi
 * kontrak sekecil ini (2 method) adalah trade-off yang wajar
 * dibandingkan coupling antar module.
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SectionInterface {

	/**
	 * Key unik section, dipakai sebagai key nested array pada
	 * option module.
	 *
	 * @return string
	 */
	public function get_section_key(): string;

	/**
	 * Sanitasi data mentah milik section ini sebelum disimpan.
	 *
	 * @param array $input Data mentah dari input pengguna.
	 * @return array Data yang telah tersanitasi.
	 */
	public function sanitize( array $input ): array;
}

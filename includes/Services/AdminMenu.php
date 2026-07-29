<?php
/**
 * Admin Menu.
 *
 * Shared Service yang menyimpan slug menu top-level "Lunar SEO".
 * Module manapun yang perlu mendaftarkan submenu di bawahnya
 * (Sitemap, Schema, dst di masa depan) membaca slug ini melalui
 * Constructor Injection - bukan mengakses class module lain secara
 * langsung.
 *
 * Menggantikan pola sebelumnya di mana modules/sitemap/Admin.php
 * meng-import Lunar\SEO\Modules\General\Admin secara langsung untuk
 * membaca MENU_SLUG-nya. Pola itu melanggar isolasi module
 * (ARCHITECTURE.md §22 - "Module tidak mengakses module lain secara
 * langsung"; PLUGIN_BLUEPRINT.md §16 - Forbidden Dependencies:
 * "Module saling bergantung langsung"), meski motivasi aslinya baik
 * (fail loudly saat load apabila slug berubah, alih-alih submenu
 * diam-diam hilang tanpa error). Shared Service ini mempertahankan
 * motivasi tersebut - satu sumber kebenaran, constant PHP yang tetap
 * fail loudly kalau typo - tanpa coupling antar-module.
 *
 * @package Lunar\SEO\Services
 */

namespace Lunar\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminMenu {

	/**
	 * Slug menu top-level "Lunar SEO".
	 *
	 * Dimiliki di sini (bukan di modules/general/Admin.php) supaya
	 * module manapun yang perlu menambahkan submenu di bawahnya
	 * dapat membaca dari satu sumber kebenaran yang netral, tanpa
	 * bergantung pada class module tertentu.
	 *
	 * @var string
	 */
	private const TOP_LEVEL_SLUG = 'lunar-seo-general';

	/**
	 * Ambil slug menu top-level "Lunar SEO".
	 *
	 * @return string
	 */
	public function get_top_level_slug(): string {
		return self::TOP_LEVEL_SLUG;
	}
}

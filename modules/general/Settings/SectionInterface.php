<?php
/**
 * Kontrak yang wajib diimplementasikan setiap section settings.
 *
 * Setiap section (Site Info, Content, Categories & Tags, Social,
 * Verification, Robots & URL) mengelola field dan sanitasinya
 * sendiri, didaftarkan ke Settings.php sebagai orchestrator.
 *
 * Interface sengaja minimal (2 method) sesuai prinsip menghindari
 * abstraction yang belum diperlukan.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SectionInterface {

	/**
	 * Key unik section, dipakai sebagai key nested array pada
	 * option module (contoh: "site_info", "categories_tags").
	 *
	 * @return string
	 */
	public function get_section_key(): string;

	/**
	 * Sanitasi data mentah milik section ini sebelum disimpan.
	 *
	 * Menerima HANYA sub-array milik section ini (bukan seluruh
	 * option module), agar setiap section tidak perlu mengetahui
	 * struktur section lain (ARCHITECTURE.md §8).
	 *
	 * @param array $input Data mentah dari input pengguna.
	 * @return array Data yang telah tersanitasi.
	 */
	public function sanitize( array $input ): array;
}

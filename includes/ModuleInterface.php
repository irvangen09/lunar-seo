<?php
/**
 * Kontrak yang wajib diimplementasikan setiap module.
 *
 * Interface ini sengaja dibuat minimal (bukan abstract class dengan
 * banyak method) untuk menghindari over-engineering sesuai
 * ENGINEERING_PRINCIPLES.md - "Hindari abstraction yang belum diperlukan".
 *
 * @package Lunar\SEO
 */

namespace Lunar\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ModuleInterface {

	/**
	 * Slug unik module, digunakan sebagai key registrasi dan
	 * penentu status aktif/nonaktif.
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Inisialisasi module.
	 *
	 * Dipanggil oleh Module Registry HANYA apabila module berstatus
	 * aktif. Module yang nonaktif tidak boleh memanggil method ini,
	 * sehingga tidak memuat asset maupun hook (ARCHITECTURE.md §7).
	 *
	 * @return void
	 */
	public function init(): void;
}

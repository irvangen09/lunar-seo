<?php
/**
 * Kontrak yang wajib diimplementasikan setiap Renderer output frontend.
 *
 * Kontrak menggunakan init() (bukan render() yang dipanggil manual
 * dari satu loop wp_head) karena masing-masing kategori output
 * punya titik integrasi WordPress yang berbeda:
 * - Title  -> filter "pre_get_document_title" (harus terdaftar
 *             SEBELUM wp_head, karena WordPress core mencetak tag
 *             <title> di wp_head prioritas 1).
 * - Meta, Open Graph, Twitter Card, Verification -> action "wp_head".
 *
 * Setiap Renderer bertanggung jawab mendaftarkan hook yang sesuai
 * di dalam init()-nya sendiri, bukan disamaratakan oleh orchestrator
 * (Frontend.php).
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RendererInterface {

	/**
	 * Daftarkan hook WordPress yang sesuai untuk Renderer ini.
	 *
	 * Implementasi wajib melakukan escaping sesuai konteks
	 * (esc_attr/esc_url) dan early-return apabila kondisi render
	 * tidak terpenuhi (toggle nonaktif, field kosong, context tidak
	 * relevan) - tidak mencetak tag kosong.
	 *
	 * @return void
	 */
	public function init(): void;
}

<?php
/**
 * Admin.
 *
 * Bertanggung jawab meregistrasikan menu admin dan merender root
 * container untuk React admin app. Data setting diakses oleh JS
 * melalui REST API (/wp/v2/settings), bukan dibaca langsung oleh
 * PHP di sini - sehingga class ini tidak membutuhkan OptionManager
 * (ENGINEERING_PRINCIPLES.md #1 - Write with Purpose).
 *
 * Lihat GENERAL_MODULE_ARCHITECTURE.md §7 untuk alasan pendekatan
 * React + REST dibandingkan form PHP klasik.
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	/**
	 * Slug halaman menu admin.
	 *
	 * Bersifat public agar dapat direferensikan oleh Assets.php
	 * tanpa menduplikasi string literal (CODING_STANDARD.md §2 - DRY).
	 *
	 * @var string
	 */
	public const MENU_SLUG = 'lunar-seo-general';

	/**
	 * ID elemen root untuk di-mount React admin app.
	 *
	 * @var string
	 */
	private const ROOT_ELEMENT_ID = 'lunar-seo-general-settings-root';

	/**
	 * Hook suffix ASLI yang dikembalikan add_menu_page(), dipakai
	 * Assets.php untuk membatasi enqueue hanya di halaman ini.
	 *
	 * Disimpan sebagai nilai asli (bukan ditebak ulang via string
	 * concatenation) agar tidak ada risiko mismatch format hook
	 * suffix WordPress (CODING_STANDARD.md #17 - AI Coding Guidelines:
	 * tidak berasumsi terhadap detail internal WordPress apabila
	 * WordPress sendiri menyediakan nilai yang pasti).
	 *
	 * @var string|null
	 */
	private ?string $hook_suffix = null;

	/**
	 * Inisialisasi - hook registrasi menu ke admin_menu.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Registrasikan menu admin.
	 *
	 * Untuk saat ini didaftarkan sebagai menu top-level. Apabila
	 * module Sitemap/Schema sudah dibangun, pertimbangkan membuat
	 * parent menu bersama "Lunar SEO" di Shared Services/Admin
	 * Framework - belum dilakukan sekarang untuk menghindari
	 * membangun struktur yang belum diperlukan (Minimal Change
	 * Principle).
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$this->hook_suffix = add_menu_page(
			__( 'Lunar SEO', 'lunar-seo' ),
			__( 'SEO', 'lunar-seo' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ],
			'dashicons-search',
			80
		);
	}

	/**
	 * Render halaman admin.
	 *
	 * Hanya berupa root container kosong - seluruh UI (Site Info,
	 * Content, Categories & Tags, dst) dirender oleh React app yang
	 * di-enqueue melalui Assets.php.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div id="%s" class="lunar-settings"></div>',
			esc_attr( self::ROOT_ELEMENT_ID )
		);
	}

	/**
	 * Ambil hook suffix ASLI halaman ini (dari add_menu_page()),
	 * dipakai Assets.php untuk membatasi enqueue.
	 *
	 * @return string|null Null apabila admin_menu belum berjalan.
	 */
	public function get_hook_suffix(): ?string {
		return $this->hook_suffix;
	}
}

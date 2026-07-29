<?php
/**
 * Admin.
 *
 * Bertanggung jawab meregistrasikan menu admin dan merender root
 * container untuk React admin app. Data setting diakses oleh JS
 * melalui REST route custom (lunar-seo/v1/general-settings), bukan
 * endpoint generic /wp/v2/settings (endpoint generic tersebut
 * terbukti gagal menyimpan data object bersarang, lihat
 * GENERAL_MODULE_ARCHITECTURE.md §7.1) dan bukan dibaca langsung
 * oleh PHP di sini - sehingga class ini tidak membutuhkan
 * OptionManager (ENGINEERING_PRINCIPLES.md #1 - Write with Purpose).
 *
 * Lihat GENERAL_MODULE_ARCHITECTURE.md §7 untuk alasan pendekatan
 * React + REST dibandingkan form PHP klasik.
 *
 * Slug menu top-level "Lunar SEO" dimiliki oleh Shared Service
 * AdminMenu (includes/Services/AdminMenu.php), bukan oleh class ini
 * secara langsung - agar module lain (Sitemap, Schema, dst) yang
 * perlu mendaftarkan submenu di bawahnya tidak perlu mengakses class
 * module General secara langsung (ARCHITECTURE.md §22).
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\Services\AdminMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	/**
	 * ID elemen root untuk di-mount React admin app.
	 *
	 * @var string
	 */
	private const ROOT_ELEMENT_ID = 'lunar-seo-general-settings-root';

	/**
	 * Shared service Admin Menu - sumber kebenaran slug menu
	 * top-level "Lunar SEO".
	 *
	 * @var AdminMenu
	 */
	private AdminMenu $admin_menu;

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
	 * @param AdminMenu $admin_menu Shared service Admin Menu.
	 */
	public function __construct( AdminMenu $admin_menu ) {
		$this->admin_menu = $admin_menu;
	}

	/**
	 * Inisialisasi - hook registrasi menu ke admin_menu.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Registrasikan menu top-level "Lunar SEO".
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$this->hook_suffix = add_menu_page(
			__( 'Lunar SEO', 'lunar-seo' ),
			__( 'SEO', 'lunar-seo' ),
			'manage_options',
			$this->admin_menu->get_top_level_slug(),
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

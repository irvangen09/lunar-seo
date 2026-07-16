<?php
/**
 * Admin.
 *
 * Bertanggung jawab meregistrasikan submenu admin dan merender root
 * container untuk React admin app. Pola identik dengan
 * modules/general/Admin.php.
 *
 * Didaftarkan sebagai SUBMENU di bawah menu top-level "Lunar SEO"
 * yang sudah dibuat module General (bukan menu top-level baru) -
 * menjaga satu titik masuk navigasi yang konsisten untuk seluruh
 * module plugin (DESIGN_SYSTEM.md §14 - Admin Experience).
 *
 * @package Lunar\SEO\Modules\Sitemap
 */

namespace Lunar\SEO\Modules\Sitemap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	/**
	 * Slug menu parent (menu top-level "Lunar SEO" dari module General).
	 *
	 * @var string
	 */
	private const PARENT_MENU_SLUG = 'lunar-seo-general';

	/**
	 * Slug halaman menu admin.
	 *
	 * @var string
	 */
	public const MENU_SLUG = 'lunar-seo-sitemap';

	/**
	 * ID elemen root untuk di-mount React admin app.
	 *
	 * @var string
	 */
	private const ROOT_ELEMENT_ID = 'lunar-seo-sitemap-settings-root';

	/**
	 * Hook suffix ASLI yang dikembalikan add_submenu_page(), dipakai
	 * Assets.php untuk membatasi enqueue.
	 *
	 * @var string|null
	 */
	private ?string $hook_suffix = null;

	/**
	 * Inisialisasi - hook registrasi menu ke admin_menu.
	 *
	 * Prioritas dilebihkan (20) dari registrasi menu top-level di
	 * General (default 10), memastikan menu top-level "Lunar SEO"
	 * sudah terdaftar lebih dulu sebelum submenu ini ditambahkan.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 20 );
	}

	/**
	 * Registrasikan submenu admin.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$this->hook_suffix = add_submenu_page(
			self::PARENT_MENU_SLUG,
			__( 'Lunar SEO - Sitemap', 'lunar-seo' ),
			__( 'Sitemap', 'lunar-seo' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Render halaman admin - root container kosong untuk React app.
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
	 * Ambil hook suffix ASLI halaman ini, dipakai Assets.php.
	 *
	 * @return string|null
	 */
	public function get_hook_suffix(): ?string {
		return $this->hook_suffix;
	}
}

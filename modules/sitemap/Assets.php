<?php
/**
 * Assets.
 *
 * Bertanggung jawab memuat CSS/JS module Sitemap. Hanya ada konteks
 * Admin (tidak ada Editor - lihat Module.php) dan tidak ada asset
 * frontend (output XML murni, bukan HTML/CSS/JS).
 *
 * @package Lunar\SEO\Modules\Sitemap
 */

namespace Lunar\SEO\Modules\Sitemap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	/**
	 * Instance Admin yang sama dengan yang di-boot Module.php -
	 * dipakai untuk membaca hook_suffix ASLI (bukan ditebak ulang).
	 *
	 * @var Admin
	 */
	private Admin $admin;

	/**
	 * @param Admin $admin Instance Admin (sumber hook_suffix asli).
	 */
	public function __construct( Admin $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Inisialisasi - hook enqueue ke admin_enqueue_scripts.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
	}

	/**
	 * Enqueue asset halaman Settings (React admin app).
	 *
	 * @param string $hook_suffix Hook suffix halaman admin saat ini.
	 * @return void
	 */
	public function enqueue_admin( string $hook_suffix ): void {
		if ( null === $this->admin->get_hook_suffix() || $hook_suffix !== $this->admin->get_hook_suffix() ) {
			return;
		}

		$asset_file = LUNAR_SEO_PATH . 'build/sitemap-admin.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			// Build belum dijalankan (npm run build) - fail gracefully.
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'lunar-seo-sitemap-admin',
			LUNAR_SEO_URL . 'build/sitemap-admin.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// CATATAN: @wordpress/scripts menamai output CSS berbeda dari
		// JS untuk entry yang sama - "build/style-sitemap-admin.css",
		// BUKAN "build/sitemap-admin.css" (pola sama dengan
		// modules/general/Assets.php).
		$style_path = LUNAR_SEO_PATH . 'build/style-sitemap-admin.css';

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'lunar-seo-sitemap-admin',
				LUNAR_SEO_URL . 'build/style-sitemap-admin.css',
				[],
				$asset['version']
			);
		}
	}
}

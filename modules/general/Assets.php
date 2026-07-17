<?php
/**
 * Assets.
 *
 * Bertanggung jawab memuat CSS/JS module General, dipisah per
 * konteks (Admin, Editor, Frontend) agar asset hanya dimuat
 * apabila benar-benar diperlukan (ARCHITECTURE.md §13).
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	/**
	 * Instance Admin yang sama dengan yang di-boot Module.php -
	 * dipakai untuk membaca hook_suffix ASLI (bukan ditebak ulang),
	 * menghindari risiko mismatch format string WordPress.
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
	 * Inisialisasi - hook enqueue ke masing-masing konteks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor' ] );
	}

	/**
	 * Enqueue asset halaman Settings (React admin app).
	 *
	 * Dibatasi hanya pada halaman module General, dengan
	 * membandingkan terhadap hook_suffix ASLI yang disimpan Admin.php
	 * saat add_menu_page() dipanggil (ARCHITECTURE.md §13).
	 *
	 * @param string $hook_suffix Hook suffix halaman admin saat ini.
	 * @return void
	 */
	public function enqueue_admin( string $hook_suffix ): void {
		if ( null === $this->admin->get_hook_suffix() || $hook_suffix !== $this->admin->get_hook_suffix() ) {
			return;
		}

		// Aktifkan Media Library modal (wp.media) untuk field Site
		// Image - tanpa ini, tombol "Select Image" tidak akan berfungsi.
		wp_enqueue_media();

		// CATATAN: @wordpress/scripts menghasilkan output FLAT
		// (build/admin.js, build/admin.asset.php) untuk entry bernama
		// "admin" pada webpack.config.js - BUKAN nested di dalam
		// subfolder (build/admin/index.js). Path di bawah ini harus
		// selalu mengikuti nama entry di webpack.config.js persis.
		$asset_file = LUNAR_SEO_PATH . 'build/admin.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			// Build belum dijalankan (npm run build) - fail gracefully,
			// jangan fatal error (CODING_STANDARD.md §11).
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'lunar-seo-admin',
			LUNAR_SEO_URL . 'build/admin.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// CATATAN: @wordpress/scripts menamai OUTPUT CSS berbeda dari
		// JS untuk entry yang sama - "build/style-admin.css", BUKAN
		// "build/admin.css" (MiniCssExtractPlugin memberi prefix
		// "style-" khusus untuk CSS, sementara JS tetap memakai nama
		// entry asli). Path di bawah ini harus selalu mengikuti pola
		// tersebut, sama seperti catatan flat-path untuk JS di atas.
		$style_path = LUNAR_SEO_PATH . 'build/style-admin.css';

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'lunar-seo-admin',
				LUNAR_SEO_URL . 'build/style-admin.css',
				[],
				$asset['version']
			);
		}
	}

	/**
	 * Enqueue asset Editor (PluginSidebar/PluginDocumentSettingPanel).
	 *
	 * Membaca build/editor.asset.php yang dihasilkan otomatis oleh
	 * @wordpress/scripts (berisi daftar dependency dan versi berbasis
	 * hash konten, untuk cache busting otomatis).
	 *
	 * @return void
	 */
	public function enqueue_editor(): void {
		// CATATAN: @wordpress/scripts menghasilkan output FLAT
		// (build/editor.js, build/editor.asset.php) untuk entry
		// bernama "editor" pada webpack.config.js - BUKAN nested
		// (build/editor/index.js).
		$asset_file = LUNAR_SEO_PATH . 'build/editor.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			// Build belum dijalankan (npm run build) - fail gracefully,
			// jangan fatal error (CODING_STANDARD.md §11).
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'lunar-seo-editor',
			LUNAR_SEO_URL . 'build/editor.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// Lihat catatan penamaan CSS di enqueue_admin() di atas -
		// pola yang sama berlaku untuk entry "editor".
		$style_path = LUNAR_SEO_PATH . 'build/style-editor.css';

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'lunar-seo-editor',
				LUNAR_SEO_URL . 'build/style-editor.css',
				[],
				$asset['version']
			);
		}
	}

	/**
	 * Frontend tidak memerlukan asset CSS/JS.
	 *
	 * Output module General di frontend murni berupa meta tag di
	 * <head> (lihat Frontend.php), bukan komponen visual - sehingga
	 * tidak ada hook enqueue_frontend yang didaftarkan di sini,
	 * menghindari asset kosong yang tidak diperlukan
	 * (ENGINEERING_PRINCIPLES.md #1 - Write with Purpose).
	 */
}

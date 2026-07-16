<?php
/**
 * Bootstrap.
 *
 * Bootstrap HANYA bertanggung jawab untuk:
 * - Menginisialisasi plugin.
 * - Memuat Core.
 * - Memuat Module Registry.
 * - Menginisialisasi module yang aktif.
 *
 * Bootstrap tidak mengandung business logic (PLUGIN_BLUEPRINT.md §7).
 * Urutan inisialisasi mengikuti ARCHITECTURE.md §21 (Lifecycle).
 *
 * @package Lunar\SEO
 */

namespace Lunar\SEO;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bootstrap {

	/**
	 * Instance tunggal Bootstrap.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Shared service: Option Manager.
	 *
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * Module Registry.
	 *
	 * @var ModuleRegistry
	 */
	private ModuleRegistry $module_registry;

	/**
	 * Ambil instance tunggal Bootstrap.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor bersifat private (Singleton).
	 */
	private function __construct() {}

	/**
	 * Jalankan lifecycle plugin.
	 *
	 * @return void
	 */
	public function run(): void {
		if ( ! $this->environment_check() ) {
			return;
		}

		$this->register_shared_services();
		$this->register_modules();
	}

	/**
	 * Pemeriksaan environment dasar.
	 *
	 * Header plugin ("Requires PHP", "Requires at least") sudah
	 * ditangani WordPress core sejak WP 5.2+, pemeriksaan ini
	 * bersifat defense-in-depth agar tetap Fail Gracefully
	 * (CODING_STANDARD.md §11) apabila plugin dimuat di luar jalur
	 * normal WordPress.
	 *
	 * @return bool
	 */
	private function environment_check(): bool {
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			return false;
		}

		global $wp_version;

		if ( isset( $wp_version ) && version_compare( $wp_version, '6.9', '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Registrasi shared services.
	 *
	 * Shared Services hanya menyediakan fungsi yang digunakan
	 * bersama, tidak berisi logic khusus module (ARCHITECTURE.md §11).
	 *
	 * @return void
	 */
	private function register_shared_services(): void {
		$this->option_manager = new OptionManager();
	}

	/**
	 * Registrasi dan inisialisasi module aktif melalui Module Registry.
	 *
	 * @return void
	 */
	private function register_modules(): void {
		$this->module_registry = new ModuleRegistry( $this->option_manager );
		$this->module_registry->register_active_modules();
	}

	/**
	 * Akses Option Manager oleh module.
	 *
	 * @return OptionManager
	 */
	public function get_option_manager(): OptionManager {
		return $this->option_manager;
	}
}

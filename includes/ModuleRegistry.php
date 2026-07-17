<?php
/**
 * Module Registry.
 *
 * Bertanggung jawab mendaftarkan seluruh module yang tersedia dan
 * menginisialisasi HANYA module yang berstatus aktif. Module yang
 * dinonaktifkan tidak diinisialisasi dan tidak memuat asset maupun
 * hook (ARCHITECTURE.md §7).
 *
 * @package Lunar\SEO
 */

namespace Lunar\SEO;

use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModuleRegistry {

	/**
	 * Daftar class module yang tersedia di plugin.
	 *
	 * Setiap module baru cukup ditambahkan di sini tanpa mengubah
	 * module lain (Extensibility - ARCHITECTURE.md §18).
	 *
	 * @var class-string<ModuleInterface>[]
	 */
	private array $available_modules = [
		\Lunar\SEO\Modules\General\Module::class,
		\Lunar\SEO\Modules\Sitemap\Module::class,
		\Lunar\SEO\Modules\Schema\Module::class,
	];

	/**
	 * Module yang berhasil diinisialisasi pada request ini.
	 *
	 * @var ModuleInterface[]
	 */
	private array $active_modules = [];

	/**
	 * Shared service yang disalurkan ke setiap module melalui
	 * constructor (Dependency Injection), bukan diakses lewat
	 * global state (CODING_STANDARD.md §3).
	 *
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * Shared service kedua yang disalurkan SERAGAM ke setiap module,
	 * sejajar OptionManager (SCHEMA_MODULE_ARCHITECTURE.md §3). Module
	 * yang belum membutuhkannya (misal Sitemap saat ini) cukup
	 * menerima tanpa memakainya - lebih konsisten daripada
	 * pengecualian khusus per module di dalam Registry, yang akan
	 * bertentangan dengan prinsip "seluruh module diregistrasikan
	 * secara seragam" (ARCHITECTURE.md §7).
	 *
	 * @var SiteIdentity
	 */
	private SiteIdentity $site_identity;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 * @param SiteIdentity  $site_identity  Shared service Site Identity.
	 */
	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity ) {
		$this->option_manager = $option_manager;
		$this->site_identity  = $site_identity;
	}

	/**
	 * Registrasikan dan inisialisasi seluruh module yang aktif.
	 *
	 * @return void
	 */
	public function register_active_modules(): void {
		foreach ( $this->available_modules as $module_class ) {
			if ( ! class_exists( $module_class ) ) {
				continue;
			}

			$module = new $module_class( $this->option_manager, $this->site_identity );

			if ( ! $module instanceof ModuleInterface ) {
				continue;
			}

			if ( ! $this->is_module_active( $module->get_slug() ) ) {
				continue;
			}

			$module->init();

			$this->active_modules[ $module->get_slug() ] = $module;
		}
	}

	/**
	 * Cek status aktif sebuah module.
	 *
	 * Untuk saat ini seluruh module dianggap aktif secara default.
	 * Mekanisme toggle aktif/nonaktif per module (via Settings)
	 * ditambahkan pada tahap Admin Framework, mengikuti prinsip
	 * Minimal Change - tidak membangun fitur yang belum diperlukan
	 * pada tahap ini.
	 *
	 * @param string $module_slug Slug module.
	 * @return bool
	 */
	private function is_module_active( string $module_slug ): bool {
		/**
		 * Filter status aktif module.
		 *
		 * @param bool   $is_active Status aktif default.
		 * @param string $module_slug Slug module yang diperiksa.
		 */
		return (bool) apply_filters( 'lunar_seo_module_is_active', true, $module_slug );
	}

	/**
	 * Ambil daftar module yang sedang aktif.
	 *
	 * @return ModuleInterface[]
	 */
	public function get_active_modules(): array {
		return $this->active_modules;
	}
}

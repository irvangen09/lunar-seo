<?php
/**
 * Module Sitemap - Bootstrap.
 *
 * Module hanya bertanggung jawab menginisialisasi komponen yang
 * dimilikinya. Business logic tidak ditempatkan pada bootstrap
 * (MODULE_DEVELOPMENT_GUIDE.md §6).
 *
 * Berbeda dari module General, module ini TIDAK memiliki Editor.php
 * - Sitemap tidak punya integrasi per-post di Gutenberg; pengecualian
 * konten dilakukan lewat Excluded Items di Global Settings (sesuai
 * SITEMAP_MODULE_ARCHITECTURE.md §1).
 *
 * @package Lunar\SEO\Modules\Sitemap
 */

namespace Lunar\SEO\Modules\Sitemap;

use Lunar\SEO\ModuleInterface;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Module implements ModuleInterface {

	/**
	 * Slug module, dipakai sebagai key Option Manager
	 * ("lunar_seo_sitemap_settings") dan filter status aktif.
	 *
	 * @var string
	 */
	private const SLUG = 'sitemap';

	/**
	 * Shared service Option Manager, diterima via constructor
	 * (Dependency Injection) dari Module Registry.
	 *
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * Instance Admin - disimpan sebagai property agar dapat
	 * dibagikan ke Assets.php (butuh hook_suffix asli), sama seperti
	 * pola pada module General.
	 *
	 * @var Admin
	 */
	private Admin $admin;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 * @param SiteIdentity  $site_identity  Shared service Site Identity - diterima
	 *                                      agar signature konstruktor seragam di
	 *                                      seluruh module (ModuleRegistry meneruskan
	 *                                      Shared Service yang sama ke semua module,
	 *                                      lihat SCHEMA_MODULE_ARCHITECTURE.md §3),
	 *                                      TIDAK dipakai module Sitemap saat ini.
	 */
	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity ) {
		$this->option_manager = $option_manager;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		$this->boot_settings();
		$this->boot_admin();
		$this->boot_frontend();
		$this->boot_assets();
	}

	/**
	 * Inisialisasi registrasi settings (WordPress Settings API).
	 *
	 * @return void
	 */
	private function boot_settings(): void {
		( new Settings\Settings( $this->option_manager ) )->init();
	}

	/**
	 * Inisialisasi halaman admin (menu + render root container React).
	 *
	 * @return void
	 */
	private function boot_admin(): void {
		$this->admin = new Admin();
		$this->admin->init();
	}

	/**
	 * Inisialisasi rewrite rules + output XML sitemap.
	 *
	 * @return void
	 */
	private function boot_frontend(): void {
		( new Frontend( $this->option_manager ) )->init();
	}

	/**
	 * Inisialisasi asset loading (admin).
	 *
	 * @return void
	 */
	private function boot_assets(): void {
		( new Assets( $this->admin ) )->init();
	}
}

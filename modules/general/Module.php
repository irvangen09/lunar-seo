<?php
/**
 * Module General - Bootstrap.
 *
 * Module hanya bertanggung jawab menginisialisasi komponen yang
 * dimilikinya. Business logic tidak ditempatkan pada bootstrap
 * (MODULE_DEVELOPMENT_GUIDE.md §6).
 *
 * Setiap komponen (Settings, Admin, Editor, Frontend, Assets)
 * bertanggung jawab mendaftarkan hook-nya sendiri pada context
 * yang sesuai (admin_init, admin_menu, wp_head, dst). Module ini
 * tidak melakukan pengecekan context (is_admin(), dsb) - keputusan
 * tersebut adalah tanggung jawab masing-masing komponen.
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\ModuleInterface;
use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Module implements ModuleInterface {

	/**
	 * Slug module, dipakai sebagai key Option Manager
	 * ("lunar_seo_general_settings") dan filter status aktif.
	 *
	 * @var string
	 */
	private const SLUG = 'general';

	/**
	 * Shared service Option Manager, diterima via constructor
	 * (Dependency Injection) dari Module Registry.
	 *
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * Instance Admin - disimpan sebagai property (bukan variabel
	 * lokal di boot_admin()) agar dapat dibagikan ke Assets.php,
	 * yang membutuhkan hook_suffix ASLI dari instance yang SAMA
	 * (lihat Assets::enqueue_admin()).
	 *
	 * @var Admin
	 */
	private Admin $admin;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 */
	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_slug(): string {
		return self::SLUG;
	}

	/**
	 * Inisialisasi seluruh komponen module General.
	 *
	 * Urutan pemanggilan tidak mengindikasikan urutan eksekusi hook
	 * (masing-masing komponen hook ke action WordPress yang berbeda),
	 * hanya urutan pendaftaran objek.
	 *
	 * {@inheritDoc}
	 */
	public function init(): void {
		$this->boot_settings();
		$this->boot_admin();
		$this->boot_editor();
		$this->boot_frontend();
		$this->boot_url_rewriter();
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
	 * Inisialisasi halaman admin (menu + render page shell).
	 *
	 * @return void
	 */
	private function boot_admin(): void {
		$this->admin = new Admin();
		$this->admin->init();
	}

	/**
	 * Inisialisasi integrasi Editor (Gutenberg sidebar/panel).
	 *
	 * @return void
	 */
	private function boot_editor(): void {
		( new Editor( $this->option_manager ) )->init();
	}

	/**
	 * Inisialisasi output frontend (meta tag di <head>).
	 *
	 * @return void
	 */
	private function boot_frontend(): void {
		( new Frontend( $this->option_manager ) )->init();
	}

	/**
	 * Inisialisasi implementasi Remove Category Base, Remove Tag
	 * Base, dan Redirect Attachments to Parent.
	 *
	 * @return void
	 */
	private function boot_url_rewriter(): void {
		( new UrlRewriter( $this->option_manager ) )->init();
	}

	/**
	 * Inisialisasi asset loading (admin/editor/frontend).
	 *
	 * @return void
	 */
	private function boot_assets(): void {
		( new Assets( $this->admin ) )->init();
	}
}

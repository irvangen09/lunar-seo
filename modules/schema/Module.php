<?php
/**
 * Module Schema - Bootstrap.
 *
 * Module hanya bertanggung jawab menginisialisasi komponen yang
 * dimilikinya. Business logic tidak ditempatkan pada bootstrap
 * (MODULE_DEVELOPMENT_GUIDE.md §6).
 *
 * Berbeda dari module General dan Sitemap, module ini TIDAK memiliki
 * Settings.php/Admin.php/Assets.php - seluruh 5 node schema (WebSite,
 * Organization, BreadcrumbList, Article/WebPage, ImageObject)
 * fully-derived otomatis dari data yang sudah ada (SiteIdentity +
 * data post native WordPress), tidak ada keputusan yang perlu
 * diserahkan ke user lewat UI pada Fase 1
 * (SCHEMA_MODULE_ARCHITECTURE.md §2 - dikonfirmasi LOCKED).
 *
 * Juga TIDAK memiliki Editor.php - sama alasannya, tidak ada field
 * baru yang perlu diisi manual per-post.
 *
 * @package Lunar\SEO\Modules\Schema
 */

namespace Lunar\SEO\Modules\Schema;

use Lunar\SEO\ModuleInterface;
use Lunar\SEO\Services\AdminMenu;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;
use Lunar\SEO\Modules\Schema\Nodes\ArticleNode;
use Lunar\SEO\Modules\Schema\Nodes\BreadcrumbListNode;
use Lunar\SEO\Modules\Schema\Nodes\ImageObjectNode;
use Lunar\SEO\Modules\Schema\Nodes\OrganizationNode;
use Lunar\SEO\Modules\Schema\Nodes\WebPageNode;
use Lunar\SEO\Modules\Schema\Nodes\WebSiteNode;
use Lunar\SEO\Modules\Schema\Services\SchemaGraphBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Module implements ModuleInterface {

	/**
	 * Slug module, dipakai untuk filter status aktif.
	 *
	 * Tidak dipakai untuk Option Manager - module Schema tidak
	 * memiliki option tersendiri (tidak ada halaman Settings, lihat
	 * docblock class ini).
	 *
	 * @var string
	 */
	private const SLUG = 'schema';

	/**
	 * Shared service Site Identity, dipakai OrganizationNode dan
	 * WebSiteNode untuk website_name/alternate_website_name/
	 * site_image_id (SCHEMA_MODULE_ARCHITECTURE.md §3).
	 *
	 * @var SiteIdentity
	 */
	private SiteIdentity $site_identity;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager - diterima
	 *                                      agar signature konstruktor seragam di
	 *                                      seluruh module (ModuleRegistry meneruskan
	 *                                      Shared Service yang sama ke semua module),
	 *                                      TIDAK dipakai module Schema saat ini.
	 * @param SiteIdentity  $site_identity  Shared service Site Identity.
	 * @param AdminMenu     $admin_menu     Shared service Admin Menu - diterima agar
	 *                                      signature konstruktor seragam (pola sama
	 *                                      dengan $option_manager di atas), TIDAK
	 *                                      dipakai module Schema saat ini (tidak
	 *                                      memiliki halaman Admin).
	 */
	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu ) {
		$this->site_identity = $site_identity;
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
		$this->boot_frontend();
	}

	/**
	 * Rakit seluruh Node, suntikkan ke SchemaGraphBuilder, lalu ke
	 * Frontend - Constructor Injection murni, tanpa Service Locator
	 * (GENERAL_MODULE_ARCHITECTURE.md §8, pola yang sama).
	 *
	 * Urutan Node di array TIDAK memengaruhi hasil akhir (setiap Node
	 * menentukan sendiri applicable/tidaknya lewat get_node(), lihat
	 * NodeInterface) - disusun mengikuti urutan tabel §0
	 * (SCHEMA_MODULE_ARCHITECTURE.md) sekadar untuk keterbacaan.
	 *
	 * @return void
	 */
	private function boot_frontend(): void {
		$image_object_node = new ImageObjectNode();

		$nodes = [
			new WebSiteNode( $this->site_identity ),
			new OrganizationNode( $this->site_identity ),
			new BreadcrumbListNode(),
			new ArticleNode( $image_object_node ),
			new WebPageNode( $image_object_node ),
		];

		$graph_builder = new SchemaGraphBuilder( $nodes );

		$frontend = new Frontend( $graph_builder );
		$frontend->init();
	}
}

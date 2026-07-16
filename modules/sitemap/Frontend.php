<?php
/**
 * Frontend.
 *
 * Orchestrator rewrite rules + intercept request + output XML
 * sitemap. Rewrite rule didaftarkan SECARA DINAMIS berdasarkan
 * toggle Include pada Settings (hanya sitemap yang aktif yang
 * mendapat rule), mengikuti pola yang sama dengan UrlRewriter.php
 * pada module General.
 *
 * @package Lunar\SEO\Modules\Sitemap
 */

namespace Lunar\SEO\Modules\Sitemap;

use Lunar\SEO\Modules\Sitemap\Providers\ProviderInterface;
use Lunar\SEO\Modules\Sitemap\Providers\HomepageProvider;
use Lunar\SEO\Modules\Sitemap\Providers\PostTypeProvider;
use Lunar\SEO\Modules\Sitemap\Providers\TaxonomyProvider;
use Lunar\SEO\Modules\Sitemap\Providers\AuthorProvider;
use Lunar\SEO\Modules\Sitemap\Providers\DateArchiveProvider;
use Lunar\SEO\Modules\Sitemap\Services\PriorityCalculator;
use Lunar\SEO\Modules\Sitemap\Services\SitemapCache;
use Lunar\SEO\Modules\Sitemap\Services\XmlBuilder;
use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	/**
	 * Slug module, dipakai untuk membaca Global Settings.
	 *
	 * @var string
	 */
	private const MODULE_SLUG = 'sitemap';

	/**
	 * Pemetaan key tipe internal ke prefix nama file URL.
	 *
	 * Key "post_tag" sengaja dipetakan ke prefix "tags" (bukan
	 * "post_tag") - mengikuti Default Sitemap URLs pada dokumen
	 * (SITEMAP_MODULE_ARCHITECTURE.md §3).
	 *
	 * @var array<string, string>
	 */
	private const CORE_TYPE_PREFIXES = [
		'homepage' => 'homepage',
		'post'     => 'post',
		'page'     => 'page',
		'category' => 'category',
		'post_tag' => 'tags',
		'authors'  => 'authors',
		'archives' => 'archives',
	];

	/**
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * @var PriorityCalculator
	 */
	private PriorityCalculator $priority_calculator;

	/**
	 * @var SitemapCache
	 */
	private SitemapCache $cache;

	/**
	 * @var XmlBuilder
	 */
	private XmlBuilder $xml_builder;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 */
	public function __construct( OptionManager $option_manager ) {
		$this->option_manager      = $option_manager;
		$this->priority_calculator = new PriorityCalculator();
		$this->cache                = new SitemapCache();
		$this->xml_builder          = new XmlBuilder();
	}

	/**
	 * Inisialisasi - hook rewrite rules, query vars, dan intercept request.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->cache->register_invalidation_hooks();

<<<<<<< HEAD
		add_filter( 'generate_rewrite_rules', [ $this, 'add_sitemap_rewrite_rules' ] );
=======
		add_action( 'init', [ $this, 'register_rewrite_rules' ] );
>>>>>>> 804fec5772c7cf8fd7fe37dc6d7a43ca83e39cac
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'maybe_output_sitemap' ] );

		// Setting Sitemap berubah -> struktur URL yang aktif bisa
		// berubah (toggle Include) -> flush rewrite rules.
		add_action( 'lunar_seo_sitemap_settings_updated', 'flush_rewrite_rules' );
	}

	/**
	 * Daftarkan query var tambahan.
	 *
	 * @param string[] $vars Query var yang sudah ada.
	 * @return string[]
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'lunar_seo_sitemap';
		$vars[] = 'lunar_seo_sitemap_page';

		return $vars;
	}

	/**
<<<<<<< HEAD
	 * Tambahkan rewrite rule sitemap index + setiap tipe yang aktif
	 * ke $wp_rewrite.
	 *
	 * Sengaja menggunakan filter `generate_rewrite_rules` (BUKAN
	 * add_rewrite_rule() di hook 'init'), mengikuti pola yang sama
	 * dengan UrlRewriter::add_taxonomy_rewrite_rules() di module
	 * General. Alasan: add_rewrite_rule() di 'init' hanya membaca
	 * Settings SEKALI per request, pada saat 'init' fire - yang
	 * terjadi SEBELUM REST API request menyimpan Settings baru.
	 * Akibatnya saat flush_rewrite_rules() dipanggil pada request
	 * yang sama (setelah save), rule yang ikut ter-flush masih versi
	 * LAMA, sehingga /post-sitemap.xml bisa 404 sampai save
	 * berikutnya. generate_rewrite_rules dieksekusi PERSIS saat
	 * regenerasi ruleset terjadi, sehingga selalu membaca Settings
	 * versi terbaru.
	 *
	 * @param \WP_Rewrite $wp_rewrite Instance WP_Rewrite.
	 * @return \WP_Rewrite
	 */
	public function add_sitemap_rewrite_rules( \WP_Rewrite $wp_rewrite ): \WP_Rewrite {
		$rules = [
			'sitemap\.xml$' => 'index.php?lunar_seo_sitemap=index',
		];

		foreach ( $this->get_active_type_prefixes() as $type => $prefix ) {
			$rules[ $prefix . '-sitemap\.xml$' ]         = 'index.php?lunar_seo_sitemap=' . $type . '&lunar_seo_sitemap_page=1';
			$rules[ $prefix . '-sitemap([0-9]+)\.xml$' ] = 'index.php?lunar_seo_sitemap=' . $type . '&lunar_seo_sitemap_page=$matches[1]';
		}

		$wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );

		return $wp_rewrite;
=======
	 * Daftarkan rewrite rule untuk sitemap index + setiap tipe yang aktif.
	 *
	 * @return void
	 */
	public function register_rewrite_rules(): void {
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?lunar_seo_sitemap=index', 'top' );

		foreach ( $this->get_active_type_prefixes() as $type => $prefix ) {
			add_rewrite_rule(
				'^' . $prefix . '-sitemap\.xml$',
				'index.php?lunar_seo_sitemap=' . $type . '&lunar_seo_sitemap_page=1',
				'top'
			);

			add_rewrite_rule(
				'^' . $prefix . '-sitemap([0-9]+)\.xml$',
				'index.php?lunar_seo_sitemap=' . $type . '&lunar_seo_sitemap_page=$matches[1]',
				'top'
			);
		}
>>>>>>> 804fec5772c7cf8fd7fe37dc6d7a43ca83e39cac
	}

	/**
	 * Tentukan tipe konten mana saja yang aktif (sesuai toggle
	 * Include di Settings), beserta prefix nama file URL-nya.
	 *
	 * @return array<string, string> Key = identifier tipe internal, Value = prefix URL.
	 */
	private function get_active_type_prefixes(): array {
		$content  = $this->option_manager->get_section( self::MODULE_SLUG, 'sitemap_content' );
		$prefixes = [];

		$toggle_map = [
			'include_homepage'     => 'homepage',
			'include_posts'        => 'post',
			'include_static_pages' => 'page',
			'include_categories'   => 'category',
			'include_tag_pages'    => 'post_tag',
			'include_author_pages' => 'authors',
			'include_archives'     => 'archives',
		];

		foreach ( $toggle_map as $setting_key => $type ) {
			if ( ! empty( $content[ $setting_key ] ) ) {
				$prefixes[ $type ] = self::CORE_TYPE_PREFIXES[ $type ];
			}
		}

		foreach ( $content['custom_post_types'] ?? [] as $slug => $enabled ) {
			if ( $enabled ) {
				$prefixes[ $slug ] = $slug;
			}
		}

		foreach ( $content['custom_taxonomies'] ?? [] as $slug => $enabled ) {
			if ( $enabled ) {
				$prefixes[ $slug ] = $slug;
			}
		}

		return $prefixes;
	}

	/**
	 * Intercept request - output XML apabila query var sitemap terdeteksi.
	 *
	 * @return void
	 */
	public function maybe_output_sitemap(): void {
		$type = get_query_var( 'lunar_seo_sitemap' );

		if ( empty( $type ) ) {
			return;
		}

		if ( 'index' === $type ) {
			$this->output_index();

			return;
		}

		$this->output_type( (string) $type );
	}

	/**
	 * Output XML sitemap index (/sitemap.xml).
	 *
	 * @return void
	 */
	private function output_index(): void {
		$content        = $this->option_manager->get_section( self::MODULE_SLUG, 'sitemap_content' );
		$links_per_page = (int) ( $content['links_per_page'] ?? 1000 );

		$sitemaps = [];

		foreach ( $this->get_active_type_prefixes() as $type => $prefix ) {
			$entries     = $this->get_entries_for_type( $type );
			$total_pages = max( 1, $this->xml_builder->count_pages( $entries, $links_per_page ) );
			$lastmod     = $this->resolve_latest_lastmod( $entries );

			for ( $page = 1; $page <= $total_pages; $page++ ) {
				$filename = $prefix . '-sitemap' . ( $page > 1 ? (string) $page : '' ) . '.xml';

				$sitemaps[] = [
					'loc'     => home_url( '/' . $filename ),
					'lastmod' => $lastmod,
				];
			}
		}

		$this->send_xml_headers();
		echo $this->xml_builder->build_sitemap_index( $sitemaps );
		exit;
	}

	/**
	 * Output XML urlset untuk satu tipe konten (dengan pagination).
	 *
	 * @param string $type Identifier tipe konten.
	 * @return void
	 */
	private function output_type( string $type ): void {
		$entries = $this->get_entries_for_type( $type );

		$content                = $this->option_manager->get_section( self::MODULE_SLUG, 'sitemap_content' );
		$links_per_page          = (int) ( $content['links_per_page'] ?? 1000 );
		$include_last_modified   = ! empty( $content['include_last_modified'] );

		$page         = max( 1, (int) get_query_var( 'lunar_seo_sitemap_page', 1 ) );
		$page_entries = $this->xml_builder->paginate_entries( $entries, $links_per_page, $page );

		$this->send_xml_headers();
		echo $this->xml_builder->build_urlset( $page_entries, $include_last_modified );
		exit;
	}

	/**
	 * Ambil entries untuk satu tipe, dari cache apabila tersedia,
	 * generate ulang via Provider apabila cache miss.
	 *
	 * @param string $type Identifier tipe konten.
	 * @return array
	 */
	private function get_entries_for_type( string $type ): array {
		$cached = $this->cache->get_entries( $type );

		if ( null !== $cached ) {
			return $cached;
		}

		$provider = $this->resolve_provider( $type );
		$entries  = null !== $provider ? $provider->get_entries() : [];

		$this->cache->set_entries( $type, $entries );

		return $entries;
	}

	/**
	 * Tentukan Provider yang sesuai untuk satu tipe konten.
	 *
	 * @param string $type Identifier tipe konten.
	 * @return ProviderInterface|null Null apabila tipe tidak dikenali.
	 */
	private function resolve_provider( string $type ): ?ProviderInterface {
		if ( 'homepage' === $type ) {
			return new HomepageProvider( $this->option_manager );
		}

		if ( 'authors' === $type ) {
			return new AuthorProvider( $this->option_manager );
		}

		if ( 'archives' === $type ) {
			return new DateArchiveProvider( $this->option_manager );
		}

		if ( 'category' === $type || 'post_tag' === $type || taxonomy_exists( $type ) ) {
			return new TaxonomyProvider( $this->option_manager, $type );
		}

		if ( 'post' === $type || 'page' === $type || post_type_exists( $type ) ) {
			return new PostTypeProvider( $this->option_manager, $this->priority_calculator, $type );
		}

		return null;
	}

	/**
	 * Cari nilai lastmod terbaru dari sekumpulan entries, dipakai
	 * sebagai lastmod sitemap index untuk tipe terkait.
	 *
	 * @param array $entries Daftar entries.
	 * @return string|null
	 */
	private function resolve_latest_lastmod( array $entries ): ?string {
		$lastmods = array_filter( array_column( $entries, 'lastmod' ) );

		if ( empty( $lastmods ) ) {
			return null;
		}

		rsort( $lastmods );

		return $lastmods[0];
	}

	/**
	 * Kirim header Content-Type XML sebelum output.
	 *
	 * @return void
	 */
	private function send_xml_headers(): void {
		header( 'Content-Type: application/xml; charset=UTF-8' );
	}
}

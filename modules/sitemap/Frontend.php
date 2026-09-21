<?php
/**
 * Frontend.
 *
 * Orchestrates rewrite rules + request interception + XML sitemap
 * output. Rewrite rules are registered DYNAMICALLY based on the
 * Include toggles in Settings (only an active sitemap type gets a
 * rule), following the same pattern as UrlRewriter.php in the General
 * module.
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

	private const MODULE_SLUG = 'sitemap';

	// The "post_tag" key is deliberately mapped to the "tags" URL
	// prefix (not "post_tag").
	private const CORE_TYPE_PREFIXES = [
		'homepage' => 'homepage',
		'post'     => 'post',
		'page'     => 'page',
		'category' => 'category',
		'post_tag' => 'tags',
		'authors'  => 'authors',
		'archives' => 'archives',
	];

	private OptionManager $option_manager;

	private PriorityCalculator $priority_calculator;

	private SitemapCache $cache;

	private XmlBuilder $xml_builder;

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager      = $option_manager;
		$this->priority_calculator = new PriorityCalculator();
		$this->cache                = new SitemapCache();
		$this->xml_builder          = new XmlBuilder();
	}

	public function init(): void {
		$this->cache->register_invalidation_hooks();

		add_filter( 'generate_rewrite_rules', [ $this, 'add_sitemap_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'maybe_output_sitemap' ] );

		// A Sitemap setting change -> the active URL structure can
		// change (an Include toggle) -> flush rewrite rules.
		add_action( 'lunar_seo_sitemap_settings_updated', 'flush_rewrite_rules' );

		// WordPress Core has had its own built-in XML Sitemap since
		// version 5.5 (/wp-sitemap.xml). Lunar SEO already produces
		// its own, more complete sitemap (/sitemap.xml — Priority,
		// Changefreq, Excluded Items, custom post type/taxonomy
		// support) — leaving both active at once produces two
		// different sitemaps that confuse search engines and risk
		// being treated as duplicate content. Disable WP Core's native
		// sitemap entirely (found through live robots.txt testing).
		add_filter( 'wp_sitemaps_enabled', '__return_false' );

		// The wp_sitemaps_enabled filter above automatically stops WP
		// Core from hooking its own "Sitemap: .../wp-sitemap.xml" line
		// (WP_Sitemaps only registers the robots_txt filter when
		// sitemaps_enabled() is true — see WP_Sitemaps::init()). Lunar
		// SEO writes its own Sitemap: line through that same filter,
		// pointing to its /sitemap.xml index.
		add_filter( 'robots_txt', [ $this, 'add_robots_sitemap_line' ], 10, 2 );
	}

	/**
	 * Adds a "Sitemap:" line to WordPress's virtual robots.txt,
	 * pointing to Lunar SEO's own Sitemap Index. Follows the exact
	 * pattern WP_Sitemaps::add_robots() uses (the `robots_txt` FILTER
	 * hook, NOT an action — WordPress's virtual robots.txt is built via
	 * apply_filters( 'robots_txt', $output, $public ) inside
	 * do_robots()).
	 */
	public function add_robots_sitemap_line( string $output, bool $public ): string {
		if ( ! $public ) {
			return $output;
		}

		return $output . "\nSitemap: " . esc_url( home_url( '/sitemap.xml' ) ) . "\n";
	}

	public function add_query_vars( array $vars ): array {
		$vars[] = 'lunar_seo_sitemap';
		$vars[] = 'lunar_seo_sitemap_page';

		return $vars;
	}

	/**
	 * Adds the sitemap index + every active type's rewrite rules to
	 * $wp_rewrite.
	 *
	 * Deliberately uses the `generate_rewrite_rules` filter (NOT
	 * add_rewrite_rule() on the 'init' hook), following the same
	 * pattern as UrlRewriter::add_taxonomy_rewrite_rules() in the
	 * General module. Reason: add_rewrite_rule() on 'init' only reads
	 * Settings ONCE per request, at the moment 'init' fires — which
	 * happens BEFORE a REST API request saves new Settings. As a
	 * result, when flush_rewrite_rules() is called within that same
	 * request (right after the save), the rules that get flushed are
	 * still the OLD version, so /post-sitemap.xml could 404 until the
	 * next save. generate_rewrite_rules runs EXACTLY when the ruleset
	 * is regenerated, so it always reads the latest Settings.
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
	}

	/**
	 * Determines which content types are active (per the Include
	 * toggles in Settings), along with their URL filename prefix.
	 *
	 * @return array<string, string> Key = internal type identifier, value = URL prefix.
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
	 * Reads entries for one type from cache if available, regenerating
	 * through a Provider on a cache miss.
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
	 * @return ProviderInterface|null Null if the type isn't recognized.
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
	 * Finds the latest lastmod value across a set of entries, used as
	 * the sitemap index's lastmod for that type.
	 */
	private function resolve_latest_lastmod( array $entries ): ?string {
		$lastmods = array_filter( array_column( $entries, 'lastmod' ) );

		if ( empty( $lastmods ) ) {
			return null;
		}

		rsort( $lastmods );

		return $lastmods[0];
	}

	private function send_xml_headers(): void {
		header( 'Content-Type: application/xml; charset=UTF-8' );
	}
}
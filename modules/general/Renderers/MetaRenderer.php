<?php
/**
 * Meta Renderer.
 *
 * Mencetak meta description dan canonical URL ke <head> via action
 * "wp_head". Robots meta TIDAK dicetak manual di sini - didaftarkan
 * lewat filter "wp_robots" (native WordPress sejak WP 5.7), agar
 * tersatu dengan sumber robots lain (misal "Discourage search
 * engines" di Settings > Reading) menjadi SATU tag akhir, bukan dua
 * tag terpisah yang berpotensi konflik.
 *
 * Setiap output di-skip sepenuhnya (bukan mencetak tag kosong)
 * apabila data tidak tersedia (ARCHITECTURE.md §10).
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

use Lunar\SEO\Modules\General\PostMetaKeys;
use Lunar\SEO\Modules\General\Services\PlaceholderResolver;
use Lunar\SEO\Modules\General\Services\DescriptionGenerator;
use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaRenderer implements RendererInterface {

	/**
	 * Slug module, dipakai untuk membaca Global Settings.
	 *
	 * @var string
	 */
	private const MODULE_SLUG = 'general';

	/**
	 * Pemetaan preset dropdown Robots (Archives/404) ke directive
	 * NEGATIF aktual. "index_follow" berarti tidak ada directive
	 * negatif sama sekali (array kosong) - "index"/"follow" tidak
	 * memiliki representasi eksplisit di robots meta tag (lihat
	 * Settings/RobotsUrl.php).
	 *
	 * @var array<string, string[]>
	 */
	private const ROBOTS_PRESET_MAP = [
		'index_follow'     => [],
		'noindex_follow'   => [ 'noindex' ],
		'noindex_nofollow' => [ 'noindex', 'nofollow' ],
	];

	/**
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * @var PlaceholderResolver
	 */
	private PlaceholderResolver $placeholder_resolver;

	/**
	 * @var DescriptionGenerator
	 */
	private DescriptionGenerator $description_generator;

	/**
	 * @param OptionManager         $option_manager         Shared service Option Manager.
	 * @param PlaceholderResolver   $placeholder_resolver   Service resolusi placeholder.
	 * @param DescriptionGenerator  $description_generator  Service fallback description.
	 */
	public function __construct(
		OptionManager $option_manager,
		PlaceholderResolver $placeholder_resolver,
		DescriptionGenerator $description_generator
	) {
		$this->option_manager        = $option_manager;
		$this->placeholder_resolver  = $placeholder_resolver;
		$this->description_generator = $description_generator;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		add_action( 'wp_head', [ $this, 'output' ], 2 );
		add_filter( 'wp_robots', [ $this, 'filter_robots' ] );
	}

	/**
	 * Cetak seluruh tag yang menjadi tanggung jawab Renderer ini.
	 *
	 * Robots TIDAK termasuk di sini - lihat filter_robots().
	 *
	 * @return void
	 */
	public function output(): void {
		$this->output_meta_description();
		$this->output_canonical();
	}

	/**
	 * Cetak meta description.
	 *
	 * @return void
	 */
	private function output_meta_description(): void {
		$description = $this->resolve_description();

		if ( '' === $description ) {
			return;
		}

		printf(
			'<meta name="description" content="%s" />' . "\n",
			esc_attr( $description )
		);
	}

	/**
	 * Resolusi meta description berdasarkan context halaman.
	 *
	 * @return string
	 */
	private function resolve_description(): string {
		if ( is_front_page() && ! is_paged() ) {
			return $this->resolve_content_description( 'homepage', [ 'title' => $this->placeholder_resolver->get_homepage_title() ] );
		}

		if ( is_singular( 'post' ) ) {
			$override = $this->get_meta_override( PostMetaKeys::DESCRIPTION );

			if ( '' !== $override ) {
				return $this->placeholder_resolver->resolve( $override, [ 'title' => get_the_title() ] );
			}

			return $this->resolve_content_description( 'post', [ 'title' => get_the_title() ] );
		}

		if ( is_singular( 'page' ) ) {
			$override = $this->get_meta_override( PostMetaKeys::DESCRIPTION );

			if ( '' !== $override ) {
				return $this->placeholder_resolver->resolve( $override, [ 'title' => get_the_title() ] );
			}

			return $this->resolve_content_description( 'page', [ 'title' => get_the_title() ] );
		}

		if ( is_category() || is_tag() ) {
			$taxonomy_type = is_category() ? 'categories' : 'tags';

			return $this->resolve_taxonomy_description(
				$taxonomy_type,
				[ 'term_title' => single_term_title( '', false ) ]
			);
		}

		// Search & 404 tidak memiliki Meta Description pada schema
		// Settings (lihat Settings/Content.php - TYPES_TITLE_ONLY).
		return '';
	}

	/**
	 * Resolusi description untuk tipe konten (homepage/post/page)
	 * dari section "content", dengan fallback auto-generate dari
	 * konten asli post apabila template kosong dan toggle aktif.
	 *
	 * @param string $content_type   Tipe konten.
	 * @param array  $context_values Placeholder kontekstual.
	 * @return string
	 */
	private function resolve_content_description( string $content_type, array $context_values ): string {
		$data = $this->option_manager->get( self::MODULE_SLUG, 'content', $content_type, [] );

		$template       = is_array( $data ) ? ( $data['meta_description'] ?? '' ) : '';
		$auto_generate  = is_array( $data ) ? ! empty( $data['auto_generate_description'] ) : false;

		if ( '' !== trim( (string) $template ) ) {
			return $this->placeholder_resolver->resolve( (string) $template, $context_values );
		}

		if ( ! $auto_generate ) {
			return '';
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		return $this->description_generator->generate( $post );
	}

	/**
	 * Resolusi description untuk taksonomi (categories/tags) dari
	 * section "categories_tags".
	 *
	 * Auto-generate TIDAK berlaku untuk archive taksonomi (tidak ada
	 * "konten asli" berupa post_content untuk archive) - hanya
	 * template placeholder yang berlaku.
	 *
	 * @param string $taxonomy_type  Tipe taksonomi.
	 * @param array  $context_values Placeholder kontekstual.
	 * @return string
	 */
	private function resolve_taxonomy_description( string $taxonomy_type, array $context_values ): string {
		$data     = $this->option_manager->get( self::MODULE_SLUG, 'categories_tags', $taxonomy_type, [] );
		$template = is_array( $data ) ? ( $data['meta_description'] ?? '' ) : '';

		if ( '' === trim( (string) $template ) ) {
			return '';
		}

		return $this->placeholder_resolver->resolve( (string) $template, $context_values );
	}

	/**
	 * Callback filter "wp_robots" (native WordPress sejak 5.7).
	 *
	 * Menyatukan directive robots dari Settings kita ke dalam array
	 * $robots yang sama dipakai WordPress core dan plugin lain,
	 * sehingga hanya SATU <meta name="robots"> yang akhirnya dicetak
	 * oleh WordPress sendiri (bukan kita cetak manual).
	 *
	 * "Discourage search engines from indexing this site"
	 * (Settings > Reading, native WordPress, option "blog_public")
	 * dihormati sebagai GLOBAL OVERRIDE - apabila aktif, plugin ini
	 * tidak menimpanya sama sekali. Ini mencegah setting SEO
	 * per-halaman secara tidak sengaja membuat situs staging/privat
	 * jadi bisa diindeks, konsisten dengan praktik SEO plugin lain
	 * (Yoast/RankMath).
	 *
	 * @param array $robots Array directive robots yang sedang dibangun.
	 * @return array
	 */
	public function filter_robots( array $robots ): array {
		if ( '0' === get_option( 'blog_public' ) ) {
			return $robots;
		}

		$directives = $this->resolve_robots_directives();

		// Hanya directive NEGATIF yang relevan bagi WordPress core -
		// "index"/"follow" (positif) tidak punya key tersendiri,
		// karena itu adalah default WordPress apabila directive
		// negatifnya tidak diaktifkan.
		$negative_directives = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

		foreach ( $negative_directives as $directive ) {
			$robots[ $directive ] = in_array( $directive, $directives, true );
		}

		return $robots;
	}

	/**
	 * Resolusi directive robots berdasarkan context halaman.
	 *
	 * @return string[]
	 */
	private function resolve_robots_directives(): array {
		if ( is_singular() ) {
			$override = $this->get_meta_override_array( PostMetaKeys::ROBOTS );

			if ( ! empty( $override ) ) {
				return $override;
			}
		}

		$robots_settings = $this->option_manager->get_section( self::MODULE_SLUG, 'robots_url' );
		$default_meta    = $robots_settings['default_robots_meta'] ?? [];

		if ( is_404() ) {
			return $this->resolve_preset( $robots_settings['not_found_robots'] ?? 'noindex_follow', $default_meta );
		}

		if ( is_category() || is_tag() ) {
			$directives    = $this->resolve_preset( $robots_settings['archives_robots'] ?? 'default', $default_meta );
			$taxonomy_type = is_category() ? 'categories' : 'tags';

			// "Show in search results" (Settings > Categories & Tags) adalah
			// setting per-tipe-taksonomi yang lebih spesifik daripada preset
			// "archives_robots" global - apabila dinonaktifkan, paksa
			// "noindex" berlaku terlepas dari preset archives_robots,
			// konsisten dengan namanya di UI ("Show in search results").
			if ( ! $this->is_taxonomy_shown_in_search_results( $taxonomy_type ) ) {
				$directives[] = 'noindex';
			}

			return array_values( array_unique( $directives ) );
		}

		if ( is_search() || is_archive() ) {
			return $this->resolve_preset( $robots_settings['archives_robots'] ?? 'default', $default_meta );
		}

		return $default_meta;
	}

	/**
	 * Baca toggle "Show in search results" untuk tipe taksonomi
	 * (categories/tags) dari section "categories_tags"
	 * (Settings/CategoriesTags.php).
	 *
	 * Default TRUE (tampil di hasil pencarian) apabila belum pernah
	 * disimpan sama sekali - sebelum admin menyentuh setting ini,
	 * tidak ada archive yang tiba-tiba di-noindex secara diam-diam.
	 *
	 * @param string $taxonomy_type "categories" atau "tags".
	 * @return bool
	 */
	private function is_taxonomy_shown_in_search_results( string $taxonomy_type ): bool {
		$data = $this->option_manager->get( self::MODULE_SLUG, 'categories_tags', $taxonomy_type, [] );

		if ( ! is_array( $data ) || ! array_key_exists( 'show_in_search_results', $data ) ) {
			return true;
		}

		return (bool) $data['show_in_search_results'];
	}

	/**
	 * Terjemahkan preset dropdown ("default"/"index_follow"/dst)
	 * menjadi daftar directive aktual.
	 *
	 * @param string   $preset       Preset yang dipilih.
	 * @param string[] $default_meta Default Robots Meta global (dipakai apabila preset = "default").
	 * @return string[]
	 */
	private function resolve_preset( string $preset, array $default_meta ): array {
		if ( 'default' === $preset ) {
			return $default_meta;
		}

		return self::ROBOTS_PRESET_MAP[ $preset ] ?? $default_meta;
	}

	/**
	 * Cetak canonical URL.
	 *
	 * Search & 404 sengaja tidak diberi canonical - keduanya bukan
	 * resource yang seharusnya diindeks/dikanonikalisasi.
	 *
	 * @return void
	 */
	private function output_canonical(): void {
		$url = $this->resolve_canonical_url();

		if ( '' === $url ) {
			return;
		}

		printf(
			'<link rel="canonical" href="%s" />' . "\n",
			esc_url( $url )
		);
	}

	/**
	 * Resolusi canonical URL berdasarkan context halaman.
	 *
	 * @return string
	 */
	private function resolve_canonical_url(): string {
		if ( is_singular() ) {
			$override = $this->get_meta_override( PostMetaKeys::CANONICAL );

			if ( '' !== $override ) {
				return $override;
			}
		}

		if ( is_front_page() && ! is_paged() ) {
			return home_url( '/' );
		}

		if ( is_singular() ) {
			$permalink = get_permalink();

			return false !== $permalink ? $permalink : '';
		}

		if ( is_category() || is_tag() ) {
			$term_link = get_term_link( get_queried_object() );

			return is_wp_error( $term_link ) ? '' : $term_link;
		}

		return '';
	}

	/**
	 * Ambil nilai override post meta berbentuk string untuk post
	 * yang sedang tampil.
	 *
	 * @param string $meta_key Meta key (lihat PostMetaKeys).
	 * @return string String kosong apabila tidak diisi.
	 */
	private function get_meta_override( string $meta_key ): string {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		$value = get_post_meta( $post_id, $meta_key, true );

		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * Ambil nilai override post meta berbentuk array (khusus Robots).
	 *
	 * @param string $meta_key Meta key (lihat PostMetaKeys).
	 * @return string[] Array kosong apabila tidak diisi.
	 */
	private function get_meta_override_array( string $meta_key ): array {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return [];
		}

		$value = get_post_meta( $post_id, $meta_key, true );

		return is_array( $value ) ? $value : [];
	}
}

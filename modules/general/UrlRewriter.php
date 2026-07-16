<?php
/**
 * URL Rewriter.
 *
 * Implementasi nyata dari 3 toggle pada Settings/RobotsUrl.php:
 * Remove Category Base, Remove Tag Base, Redirect Attachments to
 * Parent. Berbeda dari Renderers/ (yang hanya mencetak meta tag di
 * <head>), class ini mengubah struktur URL/routing WordPress itu
 * sendiri, sehingga ditempatkan sebagai komponen terpisah, bukan
 * bagian dari Frontend.php.
 *
 * Category dan Tag ditangani dengan logic yang identik secara
 * struktural (hanya beda taxonomy), sehingga digabung lewat method
 * privat bersama untuk menghindari duplikasi (CODING_STANDARD.md §2).
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UrlRewriter {

	/**
	 * Slug module, dipakai untuk membaca Global Settings.
	 *
	 * @var string
	 */
	private const MODULE_SLUG = 'general';

	/**
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 */
	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	/**
	 * Inisialisasi - hook hanya didaftarkan apabila toggle terkait
	 * aktif (ARCHITECTURE.md §10 - output/perilaku hanya dimuat
	 * apabila diperlukan).
	 *
	 * @return void
	 */
	public function init(): void {
		$settings = $this->option_manager->get_section( self::MODULE_SLUG, 'robots_url' );

		if ( ! empty( $settings['remove_category_base'] ) ) {
			add_filter( 'category_link', [ $this, 'filter_category_link' ] );
			add_filter( 'generate_rewrite_rules', [ $this, 'add_category_rewrite_rules' ] );
			add_action( 'template_redirect', [ $this, 'maybe_redirect_category_base' ] );
		}

		if ( ! empty( $settings['remove_tag_base'] ) ) {
			add_filter( 'tag_link', [ $this, 'filter_tag_link' ] );
			add_filter( 'generate_rewrite_rules', [ $this, 'add_tag_rewrite_rules' ] );
			add_action( 'template_redirect', [ $this, 'maybe_redirect_tag_base' ] );
		}

		if ( ! empty( $settings['remove_category_base'] ) || ! empty( $settings['remove_tag_base'] ) ) {
			add_filter( 'query_vars', [ $this, 'add_redirect_query_vars' ] );
		}

		if ( ! empty( $settings['redirect_attachments_to_parent'] ) ) {
			add_action( 'template_redirect', [ $this, 'redirect_attachment_to_parent' ] );
		}
	}

	// =========================================================
	// Category
	// =========================================================

	/**
	 * Hapus "/category/" dari URL category yang di-generate WordPress.
	 *
	 * @param string $link URL asli.
	 * @return string
	 */
	public function filter_category_link( string $link ): string {
		return $this->strip_taxonomy_base( $link, $this->get_taxonomy_base( 'category' ) );
	}

	/**
	 * Tambahkan rewrite rule agar URL category tanpa base tetap
	 * ter-routing benar.
	 *
	 * @param \WP_Rewrite $wp_rewrite Instance WP_Rewrite.
	 * @return \WP_Rewrite
	 */
	public function add_category_rewrite_rules( \WP_Rewrite $wp_rewrite ): \WP_Rewrite {
		return $this->add_taxonomy_rewrite_rules( $wp_rewrite, 'category', 'category_name', 'category_redirect' );
	}

	/**
	 * Redirect 301 dari URL category lama (dengan base) ke URL baru.
	 *
	 * @return void
	 */
	public function maybe_redirect_category_base(): void {
		$this->maybe_redirect_taxonomy_base( 'category_redirect', 'category' );
	}

	// =========================================================
	// Tag
	// =========================================================

	/**
	 * Hapus "/tag/" dari URL tag yang di-generate WordPress.
	 *
	 * @param string $link URL asli.
	 * @return string
	 */
	public function filter_tag_link( string $link ): string {
		return $this->strip_taxonomy_base( $link, $this->get_taxonomy_base( 'post_tag' ) );
	}

	/**
	 * Tambahkan rewrite rule agar URL tag tanpa base tetap
	 * ter-routing benar.
	 *
	 * @param \WP_Rewrite $wp_rewrite Instance WP_Rewrite.
	 * @return \WP_Rewrite
	 */
	public function add_tag_rewrite_rules( \WP_Rewrite $wp_rewrite ): \WP_Rewrite {
		return $this->add_taxonomy_rewrite_rules( $wp_rewrite, 'post_tag', 'tag', 'tag_redirect' );
	}

	/**
	 * Redirect 301 dari URL tag lama (dengan base) ke URL baru.
	 *
	 * @return void
	 */
	public function maybe_redirect_tag_base(): void {
		$this->maybe_redirect_taxonomy_base( 'tag_redirect', 'post_tag' );
	}

	// =========================================================
	// Shared helper (Category & Tag)
	// =========================================================

	/**
	 * Daftarkan query var tambahan untuk mekanisme redirect base lama.
	 *
	 * @param string[] $vars Daftar query var yang sudah ada.
	 * @return string[]
	 */
	public function add_redirect_query_vars( array $vars ): array {
		$vars[] = 'category_redirect';
		$vars[] = 'tag_redirect';

		return $vars;
	}

	/**
	 * Ambil base taxonomy (category_base/tag_base) dari WordPress,
	 * fallback ke default bawaan apabila belum diatur.
	 *
	 * @param string $taxonomy "category" atau "post_tag".
	 * @return string
	 */
	private function get_taxonomy_base( string $taxonomy ): string {
		$option_key = 'category' === $taxonomy ? 'category_base' : 'tag_base';
		$default    = 'category' === $taxonomy ? 'category' : 'tag';

		$base = get_option( $option_key );

		return $base ? trim( $base, '/' ) : $default;
	}

	/**
	 * Hapus segmen base dari URL.
	 *
	 * @param string $link URL asli.
	 * @param string $base Base yang akan dihapus (tanpa slash).
	 * @return string
	 */
	private function strip_taxonomy_base( string $link, string $base ): string {
		return str_replace( '/' . $base . '/', '/', $link );
	}

	/**
	 * Tambahkan rewrite rule untuk satu taxonomy (category/tag).
	 *
	 * Mengikuti pola yang sudah teruji dan umum dipakai untuk
	 * kebutuhan ini di ekosistem WordPress: daftar ulang seluruh
	 * term sebagai rewrite rule eksplisit, plus satu rule tambahan
	 * yang menangkap URL lama (dengan base) untuk diteruskan ke
	 * mekanisme redirect (bukan 404).
	 *
	 * @param \WP_Rewrite $wp_rewrite  Instance WP_Rewrite.
	 * @param string      $taxonomy    "category" atau "post_tag".
	 * @param string      $query_var   Query var tujuan ("category_name"/"tag").
	 * @param string      $redirect_var Query var redirect ("category_redirect"/"tag_redirect").
	 * @return \WP_Rewrite
	 */
	private function add_taxonomy_rewrite_rules( \WP_Rewrite $wp_rewrite, string $taxonomy, string $query_var, string $redirect_var ): \WP_Rewrite {
		$rewrite = [];
		$terms   = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$slug = $term->slug;

				$rewrite[ '(' . $slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?' . $query_var . '=$matches[1]&feed=$matches[2]';
				$rewrite[ '(' . $slug . ')/page/?([0-9]{1,})/?$' ]                  = 'index.php?' . $query_var . '=$matches[1]&paged=$matches[2]';
				$rewrite[ '(' . $slug . ')/?$' ]                                    = 'index.php?' . $query_var . '=$matches[1]';
			}
		}

		$base = $this->get_taxonomy_base( $taxonomy );

		// Tangkap URL lama (dengan base) agar diteruskan ke redirect,
		// bukan berakhir 404.
		$rewrite[ $base . '/(.*)$' ] = 'index.php?' . $redirect_var . '=$matches[1]';

		$wp_rewrite->rules = array_merge( $rewrite, $wp_rewrite->rules );

		return $wp_rewrite;
	}

	/**
	 * Redirect 301 dari URL base lama ke URL baru (tanpa base).
	 *
	 * @param string $redirect_query_var Query var redirect ("category_redirect"/"tag_redirect").
	 * @param string $taxonomy           "category" atau "post_tag".
	 * @return void
	 */
	private function maybe_redirect_taxonomy_base( string $redirect_query_var, string $taxonomy ): void {
		$path = get_query_var( $redirect_query_var );

		if ( empty( $path ) ) {
			return;
		}

		$path       = trim( (string) $path, '/' );
		$slug_parts = explode( '/', $path );
		$slug       = end( $slug_parts );

		$term = get_term_by( 'slug', $slug, $taxonomy );

		if ( ! $term instanceof \WP_Term ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}

		$link = get_term_link( $term );

		wp_safe_redirect( is_wp_error( $link ) ? home_url( '/' ) : $link, 301 );
		exit;
	}

	// =========================================================
	// Attachments
	// =========================================================

	/**
	 * Redirect 301 halaman attachment ke permalink induknya
	 * (post/page). Skip apabila attachment tidak memiliki induk.
	 *
	 * @return void
	 */
	public function redirect_attachment_to_parent(): void {
		if ( ! is_attachment() ) {
			return;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post || ! $post->post_parent ) {
			return;
		}

		$parent_url = get_permalink( $post->post_parent );

		if ( false !== $parent_url ) {
			wp_safe_redirect( $parent_url, 301 );
			exit;
		}
	}
}

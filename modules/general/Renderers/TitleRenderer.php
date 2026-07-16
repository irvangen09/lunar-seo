<?php
/**
 * Title Renderer.
 *
 * Meresolusi <title> tag via filter "pre_get_document_title",
 * BUKAN echo manual di wp_head - menghindari konflik dengan tema
 * dan urutan eksekusi WordPress core (lihat catatan pada
 * RendererInterface.php).
 *
 * Belum membaca override per-post (Editor.php belum meregistrasikan
 * post meta-nya) - saat ini murni berdasarkan Global Settings.
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

use Lunar\SEO\Modules\General\PostMetaKeys;
use Lunar\SEO\Modules\General\Services\TitleResolver;
use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TitleRenderer implements RendererInterface {

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
	 * @var TitleResolver
	 */
	private TitleResolver $title_resolver;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 * @param TitleResolver $title_resolver Service resolusi SEO Title.
	 */
	public function __construct( OptionManager $option_manager, TitleResolver $title_resolver ) {
		$this->option_manager = $option_manager;
		$this->title_resolver = $title_resolver;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		add_filter( 'pre_get_document_title', [ $this, 'filter_title' ], 15 );
	}

	/**
	 * Callback filter pre_get_document_title.
	 *
	 * @param string $title Title default dari WordPress.
	 * @return string
	 */
	public function filter_title( string $title ): string {
		[ $template, $context_values, $fallback_template ] = $this->resolve_context();

		if ( null === $template ) {
			// Context tidak dikenali/tidak didukung - biarkan
			// WordPress menentukan title-nya sendiri.
			return $title;
		}

		$resolved = $this->title_resolver->resolve( $template, $context_values, $fallback_template );

		return '' !== $resolved ? $resolved : $title;
	}

	/**
	 * Tentukan template, context values, dan fallback template
	 * berdasarkan halaman yang sedang diakses.
	 *
	 * Fallback template WAJIB disuplai secara eksplisit untuk context
	 * yang tidak memiliki placeholder {title} secara alami (Search,
	 * 404, Category/Tag) - fallback generik TitleResolver
	 * ("{title} {separator} {site_name}") akan menyisakan literal
	 * "{title}" yang tidak ter-resolve apabila dipakai pada context
	 * tersebut, karena context_values-nya tidak memiliki key 'title'.
	 *
	 * @return array{0: string|null, 1: array<string, string>, 2: string}
	 */
	private function resolve_context(): array {
		if ( is_front_page() && ! is_paged() ) {
			return [
				$this->get_content_field( 'homepage', 'seo_title' ),
				// {title} tetap disediakan (memakai get_homepage_title(),
				// menghormati judul static page apabila ada) untuk
				// berjaga-jaga kalau admin menulis template custom yang
				// memakai {title} secara eksplisit.
				[ 'title' => $this->title_resolver->get_homepage_title() ],
				// Fallback KHUSUS Homepage: "{site_name} {separator}
				// {tagline}" - BUKAN "{title} {separator} {site_name}"
				// generik. Alasan: pada situs yang Homepage-nya adalah
				// blog index biasa (bukan static page), {title} tidak
				// punya nilai alami yang berbeda dari {site_name},
				// sehingga fallback generik akan menghasilkan nama situs
				// tertulis dua kali (misal "Lunar WP | Lunar WP").
				// Fallback ini aman untuk kedua kasus (static page
				// maupun blog index).
				'{site_name} {separator} {tagline}',
			];
		}

		if ( is_singular( 'post' ) ) {
			$override = $this->get_meta_override( PostMetaKeys::TITLE );

			return [
				'' !== $override ? $override : $this->get_content_field( 'post', 'seo_title' ),
				[ 'title' => get_the_title() ],
				'',
			];
		}

		if ( is_singular( 'page' ) ) {
			$override = $this->get_meta_override( PostMetaKeys::TITLE );

			return [
				'' !== $override ? $override : $this->get_content_field( 'page', 'seo_title' ),
				[ 'title' => get_the_title() ],
				'',
			];
		}

		if ( is_search() ) {
			return [
				$this->get_content_field( 'search', 'seo_title' ),
				[ 'query' => get_search_query() ],
				__( 'Search results for {query}', 'lunar-seo' ) . ' {separator} {site_name}',
			];
		}

		if ( is_404() ) {
			return [
				$this->get_content_field( 'not_found', 'seo_title' ),
				[],
				__( 'Page not found', 'lunar-seo' ) . ' {separator} {site_name}',
			];
		}

		if ( is_category() || is_tag() ) {
			$taxonomy_type = is_category() ? 'categories' : 'tags';

			return [
				$this->get_taxonomy_field( $taxonomy_type, 'seo_title' ),
				[ 'term_title' => single_term_title( '', false ) ],
				'{term_title} {separator} {site_name}',
			];
		}

		return [ null, [], '' ];
	}

	/**
	 * Ambil nilai override post meta untuk post yang sedang tampil.
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
	 * Ambil field dari section "content" (homepage/post/page/search/not_found).
	 *
	 * @param string $content_type Tipe konten.
	 * @param string $field        Nama field.
	 * @return string
	 */
	private function get_content_field( string $content_type, string $field ): string {
		$data = $this->option_manager->get( self::MODULE_SLUG, 'content', $content_type, [] );

		return is_array( $data ) && isset( $data[ $field ] ) ? (string) $data[ $field ] : '';
	}

	/**
	 * Ambil field dari section "categories_tags" (categories/tags).
	 *
	 * @param string $taxonomy_type Tipe taksonomi.
	 * @param string $field         Nama field.
	 * @return string
	 */
	private function get_taxonomy_field( string $taxonomy_type, string $field ): string {
		$data = $this->option_manager->get( self::MODULE_SLUG, 'categories_tags', $taxonomy_type, [] );

		return is_array( $data ) && isset( $data[ $field ] ) ? (string) $data[ $field ] : '';
	}
}

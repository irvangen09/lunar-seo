<?php
/**
 * Open Graph Renderer.
 *
 * Mencetak og:title, og:description, og:image ke <head>. Renderer
 * ini di-skip sepenuhnya (tidak ada satupun tag dicetak) apabila
 * toggle "Enable Open Graph" nonaktif di Global Settings
 * (ARCHITECTURE.md §10).
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

use Lunar\SEO\Modules\General\Services\PlaceholderResolver;
use Lunar\SEO\Modules\General\Services\DescriptionGenerator;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OpenGraphRenderer implements RendererInterface {

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
	 * @var PlaceholderResolver
	 */
	private PlaceholderResolver $placeholder_resolver;

	/**
	 * @var DescriptionGenerator
	 */
	private DescriptionGenerator $description_generator;

	/**
	 * Shared Service untuk site_image_id (fallback og:image),
	 * SCHEMA_MODULE_ARCHITECTURE.md §3.
	 *
	 * @var SiteIdentity
	 */
	private SiteIdentity $site_identity;

	/**
	 * @param OptionManager        $option_manager         Shared service Option Manager.
	 * @param PlaceholderResolver  $placeholder_resolver   Service resolusi placeholder.
	 * @param DescriptionGenerator $description_generator  Service fallback description.
	 * @param SiteIdentity         $site_identity          Shared service Site Identity.
	 */
	public function __construct(
		OptionManager $option_manager,
		PlaceholderResolver $placeholder_resolver,
		DescriptionGenerator $description_generator,
		SiteIdentity $site_identity
	) {
		$this->option_manager        = $option_manager;
		$this->placeholder_resolver  = $placeholder_resolver;
		$this->description_generator = $description_generator;
		$this->site_identity         = $site_identity;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		add_action( 'wp_head', [ $this, 'output' ], 3 );
	}

	/**
	 * Cetak seluruh og: tag, atau skip penuh apabila toggle nonaktif.
	 *
	 * @return void
	 */
	public function output(): void {
		$social = $this->option_manager->get_section( self::MODULE_SLUG, 'social' );
		$og     = $social['open_graph'] ?? [];

		if ( empty( $og['enabled'] ) ) {
			return;
		}

		$this->output_tag( 'og:title', $this->resolve_title() );
		$this->output_tag( 'og:description', $this->resolve_description() );
		$this->output_tag( 'og:type', is_singular() ? 'article' : 'website' );
		$this->output_tag( 'og:url', $this->resolve_url() );

		$image_url = $this->resolve_image_url( (int) ( $og['image_id'] ?? 0 ) );
		$this->output_tag( 'og:image', $image_url );
	}

	/**
	 * Cetak satu og: meta tag, skip apabila value kosong.
	 *
	 * @param string $property Nama property og:.
	 * @param string $value    Nilai konten.
	 * @return void
	 */
	private function output_tag( string $property, string $value ): void {
		if ( '' === $value ) {
			return;
		}

		printf(
			'<meta property="%s" content="%s" />' . "\n",
			esc_attr( $property ),
			esc_attr( $value )
		);
	}

	/**
	 * Resolusi title halaman saat ini secara sederhana (native
	 * WordPress), tidak menggunakan template SEO Title agar og:title
	 * tetap mencerminkan judul asli konten yang dibagikan.
	 *
	 * @return string
	 */
	private function resolve_title(): string {
		if ( is_front_page() ) {
			return $this->placeholder_resolver->get_homepage_title();
		}

		if ( is_singular() ) {
			return get_the_title();
		}

		if ( is_category() || is_tag() ) {
			return single_term_title( '', false );
		}

		return '';
	}

	/**
	 * Resolusi description halaman saat ini, hanya untuk konten
	 * singular yang memiliki excerpt/konten (post/page).
	 *
	 * @return string
	 */
	private function resolve_description(): string {
		if ( ! is_singular() ) {
			return '';
		}

		$post = get_post();

		return $post instanceof \WP_Post ? $this->description_generator->generate( $post ) : '';
	}

	/**
	 * Resolusi URL halaman saat ini.
	 *
	 * @return string
	 */
	private function resolve_url(): string {
		if ( is_front_page() ) {
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
	 * Resolusi URL gambar dengan prioritas: Featured Image post saat
	 * ini -> Default Social Image (Global Settings) -> Site Image
	 * (Site Info). Skip apabila tidak ada satupun tersedia.
	 *
	 * @param int $default_social_image_id Attachment ID Default Social Image.
	 * @return string
	 */
	private function resolve_image_url( int $default_social_image_id ): string {
		if ( is_singular() && has_post_thumbnail() ) {
			$url = get_the_post_thumbnail_url( null, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		if ( $default_social_image_id > 0 ) {
			$url = wp_get_attachment_image_url( $default_social_image_id, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		$site_image_id = $this->site_identity->get_site_image_id();

		if ( $site_image_id > 0 ) {
			$url = wp_get_attachment_image_url( $site_image_id, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		return '';
	}
}

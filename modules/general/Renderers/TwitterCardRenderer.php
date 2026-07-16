<?php
/**
 * Twitter Card Renderer.
 *
 * Mencetak twitter:card, twitter:title, twitter:description,
 * twitter:image ke <head>. Renderer ini di-skip sepenuhnya apabila
 * toggle "Enable Twitter Card" nonaktif di Global Settings
 * (ARCHITECTURE.md §10).
 *
 * Logic resolusi title/description/image identik dengan
 * OpenGraphRenderer secara konsep, namun sengaja tidak digabung
 * menjadi satu class - keduanya punya sumber pengaturan (toggle +
 * image) yang independen di Global Settings (Enable Open Graph
 * terpisah dari Enable Twitter Card), sehingga tetap dipisah agar
 * salah satu bisa dinonaktifkan tanpa memengaruhi yang lain
 * (Separation of Responsibilities - ARCHITECTURE.md §22).
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

use Lunar\SEO\Modules\General\Services\PlaceholderResolver;
use Lunar\SEO\Modules\General\Services\DescriptionGenerator;
use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TwitterCardRenderer implements RendererInterface {

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
	 * @param OptionManager        $option_manager         Shared service Option Manager.
	 * @param PlaceholderResolver  $placeholder_resolver   Service resolusi placeholder.
	 * @param DescriptionGenerator $description_generator  Service fallback description.
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
		add_action( 'wp_head', [ $this, 'output' ], 4 );
	}

	/**
	 * Cetak seluruh twitter: tag, atau skip penuh apabila toggle nonaktif.
	 *
	 * @return void
	 */
	public function output(): void {
		$social  = $this->option_manager->get_section( self::MODULE_SLUG, 'social' );
		$twitter = $social['twitter_card'] ?? [];

		if ( empty( $twitter['enabled'] ) ) {
			return;
		}

		$image_url = $this->resolve_image_url( (int) ( $twitter['image_id'] ?? 0 ) );

		$this->output_tag( 'twitter:card', '' !== $image_url ? 'summary_large_image' : 'summary' );
		$this->output_tag( 'twitter:title', $this->resolve_title() );
		$this->output_tag( 'twitter:description', $this->resolve_description() );
		$this->output_tag( 'twitter:image', $image_url );
	}

	/**
	 * Cetak satu twitter: meta tag, skip apabila value kosong.
	 *
	 * @param string $name  Nama twitter: meta tag.
	 * @param string $value Nilai konten.
	 * @return void
	 */
	private function output_tag( string $name, string $value ): void {
		if ( '' === $value ) {
			return;
		}

		printf(
			'<meta name="%s" content="%s" />' . "\n",
			esc_attr( $name ),
			esc_attr( $value )
		);
	}

	/**
	 * Resolusi title halaman saat ini (native WordPress).
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
	 * Resolusi description, hanya untuk konten singular.
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
	 * Resolusi URL gambar dengan prioritas: Featured Image post saat
	 * ini -> Default Twitter Image (Global Settings) -> Site Image
	 * (Site Info).
	 *
	 * @param int $default_twitter_image_id Attachment ID Default Twitter Image.
	 * @return string
	 */
	private function resolve_image_url( int $default_twitter_image_id ): string {
		if ( is_singular() && has_post_thumbnail() ) {
			$url = get_the_post_thumbnail_url( null, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		if ( $default_twitter_image_id > 0 ) {
			$url = wp_get_attachment_image_url( $default_twitter_image_id, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		$site_image_id = (int) $this->option_manager->get( self::MODULE_SLUG, 'site_info', 'site_image_id', 0 );

		if ( $site_image_id > 0 ) {
			$url = wp_get_attachment_image_url( $site_image_id, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		return '';
	}
}

<?php
/**
 * Title Resolver.
 *
 * Bertanggung jawab meresolusi SEO Title akhir dari sebuah
 * template. Apabila template kosong, digunakan template fallback
 * default (rule-based, BUKAN AI generatif) mengikuti keputusan
 * Auto-generate Service (GENERAL_MODULE_ARCHITECTURE.md §5).
 *
 * Class ini TIDAK mengetahui dari mana template berasal (Global
 * Settings section mana, atau override per-post) - itu adalah
 * tanggung jawab pemanggil (Renderer/Editor). Single Responsibility
 * class ini murni: template + context values -> title akhir.
 *
 * @package Lunar\SEO\Modules\General\Services
 */

namespace Lunar\SEO\Modules\General\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TitleResolver {

	/**
	 * Template fallback default apabila template yang dikonfigurasi
	 * kosong. Konsisten dengan default yang tampil pada mockup
	 * (contoh: SEO Title Homepage).
	 *
	 * @var string
	 */
	private const DEFAULT_TEMPLATE = '{title} {separator} {site_name}';

	/**
	 * @var PlaceholderResolver
	 */
	private PlaceholderResolver $placeholder_resolver;

	/**
	 * @param PlaceholderResolver $placeholder_resolver Service resolusi placeholder.
	 */
	public function __construct( PlaceholderResolver $placeholder_resolver ) {
		$this->placeholder_resolver = $placeholder_resolver;
	}

	/**
	 * Resolusi SEO Title dari template.
	 *
	 * @param string $template          Template SEO Title, boleh kosong.
	 * @param array  $context_values    Placeholder kontekstual (contoh: [ 'title' => 'Judul Post' ]).
	 * @param string $fallback_template Template fallback KHUSUS context ini apabila $template
	 *                                  kosong. Wajib disuplai pemanggil untuk context yang tidak
	 *                                  memiliki placeholder {title} secara alami (Search, 404,
	 *                                  Category/Tag) - lihat TitleRenderer.php. Kosongkan untuk
	 *                                  memakai DEFAULT_TEMPLATE generik (cocok untuk Homepage/
	 *                                  Post/Page yang memang memiliki {title}).
	 * @return string
	 */
	public function resolve( string $template, array $context_values = [], string $fallback_template = '' ): string {
		if ( '' === trim( $template ) ) {
			$template = '' !== $fallback_template ? $fallback_template : self::DEFAULT_TEMPLATE;
		}

		return $this->placeholder_resolver->resolve( $template, $context_values );
	}

	/**
	 * Passthrough ke PlaceholderResolver::get_site_name().
	 *
	 * Disediakan agar TitleRenderer tidak perlu menerima
	 * PlaceholderResolver sebagai dependency terpisah - cukup lewat
	 * TitleResolver yang sudah dimilikinya.
	 *
	 * @return string
	 */
	public function get_site_name(): string {
		return $this->placeholder_resolver->get_site_name();
	}

	/**
	 * Passthrough ke PlaceholderResolver::get_homepage_title().
	 *
	 * @return string
	 */
	public function get_homepage_title(): string {
		return $this->placeholder_resolver->get_homepage_title();
	}
}

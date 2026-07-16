<?php
/**
 * Section: Site Info.
 *
 * Mengelola field: Website Name, Alternate Website Name,
 * Title Separator, Site Image.
 *
 * CATATAN PENTING: Field "Tagline" pada mockup TIDAK disimpan di
 * section ini. Tagline diproxy langsung ke setting native
 * WordPress (get_bloginfo('description') / opsi core
 * "blogdescription"), yang sudah otomatis ter-expose lewat REST
 * endpoint bawaan WordPress (/wp/v2/settings, field "description")
 * tanpa perlu kode tambahan. Ini menghindari duplikasi data antara
 * setting kita dan setting native WordPress
 * (ENGINEERING_PRINCIPLES.md #7 - WordPress Native).
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteInfo implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'site_info';

	/**
	 * Daftar karakter separator yang diizinkan, sesuai pilihan pada
	 * UI mockup Site Info. Bersifat whitelist (bukan free-text)
	 * untuk mencegah input yang tidak diharapkan (CODING_STANDARD.md §12).
	 *
	 * @var string[]
	 */
	private const ALLOWED_SEPARATORS = [ '|', '-', '—', ':', '.', '•', '*', '~', '«', '»', '/', '\\', '>', '<' ];

	/**
	 * Separator default apabila nilai yang dikirim tidak valid.
	 *
	 * @var string
	 */
	private const DEFAULT_SEPARATOR = '|';

	/**
	 * {@inheritDoc}
	 */
	public function get_section_key(): string {
		return self::SECTION_KEY;
	}

	/**
	 * {@inheritDoc}
	 */
	public function sanitize( array $input ): array {
		return [
			'website_name'           => isset( $input['website_name'] )
				? sanitize_text_field( $input['website_name'] )
				: '',
			'alternate_website_name' => isset( $input['alternate_website_name'] )
				? sanitize_text_field( $input['alternate_website_name'] )
				: '',
			'title_separator'        => $this->sanitize_separator( $input['title_separator'] ?? '' ),
			'site_image_id'          => isset( $input['site_image_id'] )
				? absint( $input['site_image_id'] )
				: 0,
		];
	}

	/**
	 * Sanitasi Title Separator terhadap whitelist karakter yang
	 * diizinkan.
	 *
	 * @param mixed $value Nilai mentah dari input.
	 * @return string
	 */
	private function sanitize_separator( $value ): string {
		if ( ! is_string( $value ) ) {
			return self::DEFAULT_SEPARATOR;
		}

		return in_array( $value, self::ALLOWED_SEPARATORS, true )
			? $value
			: self::DEFAULT_SEPARATOR;
	}
}

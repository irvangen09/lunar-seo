<?php
/**
 * Section: Excluded Items.
 *
 * Mengelola daftar kategori dan post/page yang dikecualikan dari
 * XML Sitemap meskipun toggle Include terkait aktif (sesuai
 * dokumen: "Kategori yang dimasukkan ke dalam Excluded Items tidak
 * akan muncul pada XML Sitemap meskipun opsi Include Categories
 * diaktifkan").
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ExcludedItems implements SectionInterface {

	/**
	 * Key section pada nested array option module.
	 *
	 * @var string
	 */
	private const SECTION_KEY = 'excluded_items';

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
			'excluded_categories' => $this->sanitize_id_list( $input['excluded_categories'] ?? [] ),
			'excluded_posts'      => $this->sanitize_id_list( $input['excluded_posts'] ?? [] ),
		];
	}

	/**
	 * Sanitasi daftar ID. Menerima array ID maupun string
	 * comma-separated (contoh: "110,121") agar fleksibel terhadap
	 * bentuk input dari UI (checklist ATAU text field).
	 *
	 * @param mixed $value Nilai mentah.
	 * @return int[]
	 */
	private function sanitize_id_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_map( 'trim', explode( ',', $value ) );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		$ids = array_map( 'absint', $value );
		$ids = array_filter( $ids ); // Buang 0/tidak valid.

		return array_values( array_unique( $ids ) );
	}
}

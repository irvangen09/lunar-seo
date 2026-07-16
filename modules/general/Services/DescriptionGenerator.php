<?php
/**
 * Description Generator.
 *
 * Bertanggung jawab menghasilkan Meta Description fallback secara
 * rule-based (BUKAN AI generatif) apabila admin/penulis tidak
 * mengisi Meta Description secara manual.
 *
 * Urutan fallback (GENERAL_MODULE_ARCHITECTURE.md §5.2):
 * 1. Excerpt manual WordPress (has_excerpt()).
 * 2. Paragraf pertama konten yang bermakna.
 * 3. Dipotong ±160 karakter tanpa memotong kata di tengah.
 *
 * Class ini TIDAK menggunakan PlaceholderResolver - berbeda dengan
 * Meta Description template pada Global Settings (yang mendukung
 * placeholder), fallback ini murni ekstraksi dari konten asli post,
 * bukan template. Deskripsi bersifat statis (sama untuk semua
 * pengunjung) sesuai keputusan pada §5.2 - tidak digenerate secara
 * dinamis per search query pengguna.
 *
 * @package Lunar\SEO\Modules\General\Services
 */

namespace Lunar\SEO\Modules\General\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DescriptionGenerator {

	/**
	 * Panjang maksimum meta description (karakter).
	 *
	 * @var int
	 */
	private const MAX_LENGTH = 160;

	/**
	 * Generate meta description fallback dari sebuah post.
	 *
	 * @param \WP_Post $post Post yang akan diekstrak deskripsinya.
	 * @return string
	 */
	public function generate( \WP_Post $post ): string {
		$description = $this->get_manual_excerpt( $post );

		if ( '' === $description ) {
			$description = $this->get_first_paragraph( $post );
		}

		return $this->trim_to_length( $description, self::MAX_LENGTH );
	}

	/**
	 * Ambil excerpt manual WordPress apabila tersedia.
	 *
	 * @param \WP_Post $post Post terkait.
	 * @return string
	 */
	private function get_manual_excerpt( \WP_Post $post ): string {
		if ( ! has_excerpt( $post ) ) {
			return '';
		}

		return trim( wp_strip_all_tags( $post->post_excerpt ) );
	}

	/**
	 * Ambil paragraf pertama konten yang bermakna.
	 *
	 * Shortcode dan tag HTML di-strip terlebih dahulu agar tidak
	 * ikut terpotong di tengah markup.
	 *
	 * @param \WP_Post $post Post terkait.
	 * @return string
	 */
	private function get_first_paragraph( \WP_Post $post ): string {
		$content = strip_shortcodes( $post->post_content );
		$content = wp_strip_all_tags( $content );
		$content = trim( $content );

		if ( '' === $content ) {
			return '';
		}

		$lines = preg_split( '/\r\n|\r|\n/', $content );

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' !== $line ) {
				return $line;
			}
		}

		return '';
	}

	/**
	 * Potong teks ke panjang maksimum tanpa memotong kata di tengah.
	 *
	 * @param string $text       Teks yang akan dipotong.
	 * @param int    $max_length Panjang maksimum karakter.
	 * @return string
	 */
	private function trim_to_length( string $text, int $max_length ): string {
		if ( mb_strlen( $text ) <= $max_length ) {
			return $text;
		}

		$trimmed    = mb_substr( $text, 0, $max_length );
		$last_space = strrpos( $trimmed, ' ' );

		if ( false !== $last_space ) {
			$trimmed = substr( $trimmed, 0, $last_space );
		}

		return rtrim( $trimmed ) . '…';
	}
}

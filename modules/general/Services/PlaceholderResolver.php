<?php
/**
 * Placeholder Resolver.
 *
 * Bertanggung jawab mengganti placeholder ({title}, {site_name},
 * {tagline}, {separator}, {query}, {term_title}, dst) pada template
 * SEO Title/Meta Description dengan nilai aktual.
 *
 * Placeholder global ({site_name}, {tagline}, {separator}) selalu
 * diresolusi otomatis oleh class ini sebagai satu sumber kebenaran,
 * agar tidak didup dikasi di setiap pemanggil (TitleResolver,
 * DescriptionGenerator, maupun React admin app melalui REST).
 *
 * Placeholder kontekstual ({title}, {query}, {term_title}) dikirim
 * oleh pemanggil melalui parameter $values, karena nilainya berbeda
 * tergantung context (post/page/search/archive) dan Resolver ini
 * tidak perlu mengetahui detail context tersebut.
 *
 * @package Lunar\SEO\Modules\General\Services
 */

namespace Lunar\SEO\Modules\General\Services;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlaceholderResolver {

	/**
	 * Slug module, dipakai untuk membaca Global Settings via OptionManager.
	 *
	 * @var string
	 */
	private const MODULE_SLUG = 'general';

	/**
	 * Section tempat {site_name} dan {separator} disimpan.
	 *
	 * @var string
	 */
	private const SITE_INFO_SECTION = 'site_info';

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
	 * Resolusi seluruh placeholder pada template.
	 *
	 * @param string $template Template mentah, contoh: "{title} {separator} {site_name}".
	 * @param array  $values   Placeholder kontekstual tambahan, contoh: [ 'title' => 'Judul Post' ].
	 * @return string Hasil akhir setelah placeholder diganti, whitespace berlebih dirapikan.
	 */
	public function resolve( string $template, array $values = [] ): string {
		$placeholders = array_merge( $this->get_global_placeholders(), $values );

		$search  = [];
		$replace = [];

		foreach ( $placeholders as $key => $value ) {
			$search[]  = '{' . $key . '}';
			$replace[] = (string) $value;
		}

		$resolved = str_replace( $search, $replace, $template );

		// Rapikan whitespace berlebih akibat placeholder yang resolve
		// menjadi string kosong (misal {tagline} belum diisi).
		//
		// CATATAN: ini tidak menghapus separator yang jadi menggantung
		// (contoh: "Judul | " apabila {tagline} kosong di akhir
		// template) - penanganan itu sengaja tidak dibangun sekarang
		// untuk menghindari over-engineering pada kasus tepi yang
		// jarang terjadi (template umumnya diakhiri {site_name} yang
		// selalu memiliki fallback nilai).
		return trim( preg_replace( '/\s+/', ' ', $resolved ) );
	}

	/**
	 * Resolusi nilai {site_name}: Website Name (Site Info) apabila
	 * diisi, fallback ke Site Title native WordPress apabila kosong.
	 *
	 * Method ini PUBLIC dan dipakai sebagai satu sumber kebenaran
	 * oleh Renderer manapun yang butuh nilai "nama situs" di luar
	 * konteks template placeholder biasa (misal sebagai context
	 * value {title} pada halaman Homepage) - mencegah duplikasi
	 * logic fallback yang bisa membuat nilai tidak konsisten antar
	 * Renderer (CODING_STANDARD.md §2 - DRY).
	 *
	 * @return string
	 */
	public function get_site_name(): string {
		$website_name = $this->option_manager->get(
			self::MODULE_SLUG,
			self::SITE_INFO_SECTION,
			'website_name',
			''
		);

		return '' !== $website_name ? $website_name : get_bloginfo( 'name' );
	}

	/**
	 * Resolusi nilai {title} KHUSUS untuk context Homepage.
	 *
	 * Apabila Homepage di-set sebagai static page (Settings > Reading
	 * > "A static page"), pakai judul ASLI halaman tersebut - ini
	 * bisa BERBEDA dari {site_name} (misal halaman diberi judul
	 * "Home" atau "Beranda"), sehingga template default
	 * "{title} {separator} {site_name}" tidak menghasilkan nama
	 * situs yang tertulis dua kali.
	 *
	 * Fallback ke get_site_name() HANYA apabila Homepage adalah blog
	 * posts index biasa (tidak ada static page) - dalam kasus ini
	 * memang tidak ada "judul halaman" alami yang bisa dipakai.
	 *
	 * @return string
	 */
	public function get_homepage_title(): string {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = (int) get_option( 'page_on_front' );

			if ( $front_page_id > 0 ) {
				$front_page_title = get_the_title( $front_page_id );

				if ( '' !== $front_page_title ) {
					return $front_page_title;
				}
			}
		}

		return $this->get_site_name();
	}

	/**
	 * Ambil placeholder global yang selalu tersedia di setiap context.
	 *
	 * @return array<string, string>
	 */
	private function get_global_placeholders(): array {
		$separator = $this->option_manager->get(
			self::MODULE_SLUG,
			self::SITE_INFO_SECTION,
			'title_separator',
			'|'
		);

		return [
			'site_name' => $this->get_site_name(),

			// Tagline SENGAJA dibaca langsung dari WordPress native
			// (bukan dari option module kita) - lihat keputusan pada
			// Settings/SiteInfo.php.
			'tagline'   => get_bloginfo( 'description' ),

			'separator' => $separator,
		];
	}
}

<?php
/**
 * Site Identity.
 *
 * Shared Service untuk data identitas situs (Website Name, Alternate
 * Website Name, Site Image) yang dipakai lebih dari satu module -
 * module General (Site Info, placeholder title, Open Graph/Twitter
 * image fallback) dan module Schema (Organization, WebSite).
 *
 * Dipromosikan dari module General sesuai SCHEMA_MODULE_ARCHITECTURE.md
 * §3, karena data ini terbukti dibutuhkan lebih dari satu module -
 * skenario yang sudah diantisipasi di GENERAL_MODULE_ARCHITECTURE.md
 * §2.3 (ARCHITECTURE.md §11 - Shared Services).
 *
 * @package Lunar\SEO\Services
 */

namespace Lunar\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteIdentity {

	/**
	 * Nama option penyimpanan lokasi BARU.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'lunar_seo_site_identity';

	/**
	 * Dipakai untuk Fallback Read ke lokasi LAMA
	 * (lunar_seo_general_settings.site_info), lihat get_field().
	 *
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * @param OptionManager $option_manager Dependency Injection, tanpa Service Locator
	 *                                      (GENERAL_MODULE_ARCHITECTURE.md §8, LOCKED).
	 */
	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	/**
	 * Nama situs (Website Name).
	 *
	 * Dipakai General untuk placeholder {site_name} dan Schema untuk
	 * Organization.name / WebSite.name.
	 *
	 * @return string
	 */
	public function get_website_name(): string {
		return (string) $this->get_field( 'website_name', '' );
	}

	/**
	 * Nama alternatif situs (Alternate Website Name).
	 *
	 * Dipakai Schema untuk Organization.alternateName.
	 *
	 * @return string
	 */
	public function get_alternate_website_name(): string {
		return (string) $this->get_field( 'alternate_website_name', '' );
	}

	/**
	 * ID attachment gambar situs (Site Image).
	 *
	 * Dipakai General untuk fallback og:image/twitter:image, dan
	 * Schema untuk Organization.logo (nested ImageObject).
	 *
	 * @return int
	 */
	public function get_site_image_id(): int {
		return (int) $this->get_field( 'site_image_id', 0 );
	}

	/**
	 * Simpan satu/lebih field identitas situs ke lokasi BARU.
	 *
	 * Dipanggil oleh REST handler General (Settings.php) saat user
	 * menyimpan ulang lewat UI Site Info - setelah ini, Fallback Read
	 * di get_field() otomatis tidak lagi terpakai untuk field yang
	 * baru disimpan (SCHEMA_MODULE_ARCHITECTURE.md §3.4).
	 *
	 * @param array $data Data yang sudah disanitasi oleh pemanggil.
	 * @return bool
	 */
	public function set( array $data ): bool {
		$current = get_option( self::OPTION_KEY, [] );

		if ( ! is_array( $current ) ) {
			$current = [];
		}

		// Autoload "yes" - dibaca tiap frontend request untuk render
		// meta tag/schema (sama alasan dengan pola General/Sitemap).
		return update_option( self::OPTION_KEY, array_merge( $current, $data ), true );
	}

	/**
	 * Baca field dari lokasi BARU (lunar_seo_site_identity). Apabila
	 * kosong/belum pernah diisi, fallback baca dari lokasi LAMA
	 * (lunar_seo_general_settings.site_info) - menjembatani data yang
	 * sudah diisi user sebelum SiteIdentity ada, TANPA migration
	 * routine/hook aktivasi terpisah (disepakati di percakapan: plugin
	 * masih di staging, biaya migration routine penuh belum sepadan
	 * saat ini - lihat SCHEMA_MODULE_ARCHITECTURE.md §3.4).
	 *
	 * @param string $key     Nama field.
	 * @param mixed  $default Nilai default apabila tidak ditemukan di kedua lokasi.
	 * @return mixed
	 */
	private function get_field( string $key, $default ) {
		$new = get_option( self::OPTION_KEY, [] );

		if ( is_array( $new ) && isset( $new[ $key ] ) && '' !== $new[ $key ] ) {
			return $new[ $key ];
		}

		return $this->option_manager->get( 'general', 'site_info', $key, $default );
	}
}

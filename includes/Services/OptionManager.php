<?php
/**
 * Option Manager.
 *
 * Shared Service untuk mengelola konfigurasi seluruh module secara
 * konsisten menggunakan WordPress Options API (ARCHITECTURE.md §12).
 *
 * Pola penyimpanan: 1 option per module, berisi nested array per
 * section, dengan autoload aktif karena dibaca di setiap frontend
 * request untuk keperluan render meta tag.
 *
 * @package Lunar\SEO\Services
 */

namespace Lunar\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionManager {

	/**
	 * Prefix option sesuai Plugin Blueprint §3.
	 *
	 * @var string
	 */
	private const OPTION_PREFIX = 'lunar_seo_';

	/**
	 * Cache data option per module dalam satu request, untuk
	 * menghindari pemrosesan berulang (CODING_STANDARD.md §13).
	 *
	 * @var array<string, array>
	 */
	private array $cache = [];

	/**
	 * Bangun nama option untuk sebuah module.
	 *
	 * Bersifat public agar dapat dipakai komponen lain (misal
	 * Settings.php) tanpa menduplikasi logic penamaan (DRY).
	 *
	 * @param string $module_slug Slug module, contoh: "general".
	 * @return string
	 */
	public function get_option_name( string $module_slug ): string {
		return self::OPTION_PREFIX . $module_slug . '_settings';
	}

	/**
	 * Ambil seluruh setting milik sebuah module.
	 *
	 * @param string $module_slug Slug module.
	 * @return array
	 */
	public function get_all( string $module_slug ): array {
		if ( isset( $this->cache[ $module_slug ] ) ) {
			return $this->cache[ $module_slug ];
		}

		$data = get_option( $this->get_option_name( $module_slug ), [] );

		if ( ! is_array( $data ) ) {
			$data = [];
		}

		$this->cache[ $module_slug ] = $data;

		return $data;
	}

	/**
	 * Ambil setting satu section pada sebuah module.
	 *
	 * Contoh: get_section( 'general', 'site_info' ).
	 *
	 * @param string $module_slug Slug module.
	 * @param string $section     Nama section.
	 * @return array
	 */
	public function get_section( string $module_slug, string $section ): array {
		$all = $this->get_all( $module_slug );

		return isset( $all[ $section ] ) && is_array( $all[ $section ] )
			? $all[ $section ]
			: [];
	}

	/**
	 * Ambil satu field pada section tertentu.
	 *
	 * @param string $module_slug Slug module.
	 * @param string $section     Nama section.
	 * @param string $field       Nama field.
	 * @param mixed  $default     Nilai default apabila field tidak ditemukan.
	 * @return mixed
	 */
	public function get( string $module_slug, string $section, string $field, $default = null ) {
		$section_data = $this->get_section( $module_slug, $section );

		return $section_data[ $field ] ?? $default;
	}

	/**
	 * Perbarui satu section pada module tanpa mengganggu section lain.
	 *
	 * Setiap Settings/*.php pada module hanya mengelola section-nya
	 * sendiri melalui method ini (ARCHITECTURE.md §8).
	 *
	 * @param string $module_slug Slug module.
	 * @param string $section     Nama section yang diperbarui.
	 * @param array  $data        Data baru untuk section tersebut (data harus sudah disanitasi oleh pemanggil).
	 * @return bool
	 */
	public function update_section( string $module_slug, string $section, array $data ): bool {
		$all             = $this->get_all( $module_slug );
		$all[ $section ] = $data;

		return $this->persist( $module_slug, $all );
	}

	/**
	 * Simpan seluruh data module (replace penuh).
	 *
	 * Digunakan pada kasus khusus seperti import/reset setting.
	 *
	 * @param string $module_slug Slug module.
	 * @param array  $data        Seluruh data module (data harus sudah disanitasi oleh pemanggil).
	 * @return bool
	 */
	public function update_all( string $module_slug, array $data ): bool {
		return $this->persist( $module_slug, $data );
	}

	/**
	 * Simpan data ke database dan perbarui cache request.
	 *
	 * @param string $module_slug Slug module.
	 * @param array  $data        Data lengkap yang akan disimpan.
	 * @return bool
	 */
	private function persist( string $module_slug, array $data ): bool {
		$option_name = $this->get_option_name( $module_slug );

		// Autoload "yes" karena option ini dibaca di setiap frontend
		// request untuk render meta tag (lihat GENERAL_MODULE_ARCHITECTURE.md §3.2).
		$result = update_option( $option_name, $data, true );

		$this->cache[ $module_slug ] = $data;

		return $result;
	}
}

<?php
/**
 * Settings Orchestrator.
 *
 * Bertanggung jawab meregistrasikan option module General ke
 * WordPress Settings API (ARCHITECTURE.md §12), dengan sanitasi
 * didelegasikan ke masing-masing section (SectionInterface).
 *
 * Data diakses React admin app melalui REST route KHUSUS
 * ("lunar-seo/v1/general-settings"), BUKAN endpoint generic bawaan
 * WordPress (/wp/v2/settings). Alasan: endpoint generic tersebut
 * memiliki keterbatasan dikenal untuk option bertipe object
 * bersarang (nested) - skema REST-nya kerap menyaring/reset nilai
 * sebelum sampai ke sanitize_callback, menyebabkan data tidak
 * benar-benar tersimpan meski request terlihat berhasil. REST route
 * khusus ini tetap 100% WordPress-native (memakai
 * register_rest_route() bawaan WP), hanya menghindari jalur generic
 * yang bermasalah untuk kasus data kompleks seperti ini.
 *
 * register_setting() tetap dipertahankan untuk kepatuhan terhadap
 * WordPress Options API (data tetap tersimpan di wp_options secara
 * standar, tetap bisa diakses via get_option()/WP-CLI), hanya
 * "show_in_rest" dinonaktifkan agar endpoint generic tidak menjadi
 * jalur yang bisa dipakai untuk kasus bermasalah ini.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	/**
	 * Setting group untuk register_setting().
	 *
	 * @var string
	 */
	private const OPTION_GROUP = 'lunar_seo_general';

	/**
	 * Slug module, dipakai untuk membangun nama option via OptionManager.
	 *
	 * @var string
	 */
	private const MODULE_SLUG = 'general';

	/**
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * Daftar section settings.
	 *
	 * Diisi bertahap melalui register_section() seiring setiap
	 * section (Site Info, Content, dst) dibangun. Kosong untuk
	 * saat ini (tahap kerangka).
	 *
	 * @var SectionInterface[]
	 */
	private array $sections = [];

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 */
	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;

		$this->register_sections();
	}

	/**
	 * Daftarkan seluruh section settings.
	 *
	 * @return void
	 */
	private function register_sections(): void {
		$this->register_section( new SiteInfo() );
		$this->register_section( new Content() );
		$this->register_section( new CategoriesTags() );
		$this->register_section( new Social() );
		$this->register_section( new Verification() );
		$this->register_section( new RobotsUrl() );

		// Seluruh section Global Settings telah terdaftar.
	}

	/**
	 * Daftarkan satu section ke orchestrator.
	 *
	 * @param SectionInterface $section Instance section.
	 * @return void
	 */
	private function register_section( SectionInterface $section ): void {
		$this->sections[ $section->get_section_key() ] = $section;
	}

	/**
	 * Inisialisasi - hook registrasi setting dan REST route custom.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Registrasikan option module ke WordPress Settings API.
	 *
	 * @return void
	 */
	public function register_setting(): void {
		register_setting(
			self::OPTION_GROUP,
			$this->option_manager->get_option_name( self::MODULE_SLUG ),
			[
				'type'              => 'object',
				'sanitize_callback' => [ $this, 'sanitize' ],
				// show_in_rest sengaja TIDAK diaktifkan - lihat
				// penjelasan pada docblock class ini.
				'show_in_rest'      => false,
				'default'           => [],
			]
		);
	}

	/**
	 * Registrasikan REST route khusus untuk React admin app.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'lunar-seo/v1',
			'/general-settings',
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_get_settings' ],
					'permission_callback' => [ $this, 'rest_permission_check' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'rest_update_settings' ],
					'permission_callback' => [ $this, 'rest_permission_check' ],
				],
			]
		);
	}

	/**
	 * Permission callback - hanya user dengan capability manage_options.
	 *
	 * @return bool
	 */
	public function rest_permission_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Handler GET - kembalikan seluruh setting module General.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_settings(): \WP_REST_Response {
		return new \WP_REST_Response( $this->option_manager->get_all( self::MODULE_SLUG ) );
	}

	/**
	 * Handler POST - sanitasi lalu simpan seluruh setting module General.
	 *
	 * @param \WP_REST_Request $request Request REST.
	 * @return \WP_REST_Response
	 */
	public function rest_update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$input = $request->get_json_params();
		$input = is_array( $input ) ? $input : [];

		$before = $this->option_manager->get_all( self::MODULE_SLUG );

		$sanitized = $this->sanitize( $input );

		$this->option_manager->update_all( self::MODULE_SLUG, $sanitized );

		$this->maybe_flush_rewrite_rules( $before, $sanitized );

		return new \WP_REST_Response( $sanitized );
	}

	/**
	 * Flush rewrite rules HANYA apabila toggle yang memengaruhi
	 * struktur URL (Remove Category Base/Tag Base) benar-benar
	 * berubah - flush_rewrite_rules() adalah operasi berat, tidak
	 * boleh dijalankan pada setiap penyimpanan setting
	 * (ARCHITECTURE.md §15 - Performance Strategy).
	 *
	 * @param array $before Data setting sebelum disimpan.
	 * @param array $after  Data setting setelah disanitasi.
	 * @return void
	 */
	private function maybe_flush_rewrite_rules( array $before, array $after ): void {
		$watched_keys = [ 'remove_category_base', 'remove_tag_base' ];

		$before_robots_url = $before['robots_url'] ?? [];
		$after_robots_url  = $after['robots_url'] ?? [];

		foreach ( $watched_keys as $key ) {
			$before_value = $before_robots_url[ $key ] ?? false;
			$after_value  = $after_robots_url[ $key ] ?? false;

			if ( $before_value !== $after_value ) {
				flush_rewrite_rules();
				return;
			}
		}
	}

	/**
	 * Sanitasi seluruh data option, didelegasikan per section.
	 *
	 * Section yang belum terdaftar akan diabaikan (tidak disimpan),
	 * mencegah data tidak dikenal masuk ke database.
	 *
	 * @param mixed $input Data mentah seluruh option.
	 * @return array Data tersanitasi.
	 */
	public function sanitize( $input ): array {
		$input     = is_array( $input ) ? $input : [];
		$sanitized = [];

		foreach ( $this->sections as $section_key => $section ) {
			$raw = isset( $input[ $section_key ] ) && is_array( $input[ $section_key ] )
				? $input[ $section_key ]
				: [];

			$sanitized[ $section_key ] = $section->sanitize( $raw );
		}

		return $sanitized;
	}
}

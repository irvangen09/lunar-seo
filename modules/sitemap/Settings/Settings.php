<?php
/**
 * Settings Orchestrator - Module Sitemap.
 *
 * Pola identik dengan modules/general/Settings/Settings.php - lihat
 * dokumentasi di sana untuk alasan lengkap REST route custom
 * (bukan /wp/v2/settings) dan register_setting() untuk kepatuhan
 * WordPress Options API.
 *
 * @package Lunar\SEO\Modules\Sitemap\Settings
 */

namespace Lunar\SEO\Modules\Sitemap\Settings;

use Lunar\SEO\Modules\Sitemap\Services\ContentTypeRegistry;
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
	private const OPTION_GROUP = 'lunar_seo_sitemap';

	/**
	 * Slug module, dipakai untuk membangun nama option via OptionManager.
	 *
	 * @var string
	 */
	private const MODULE_SLUG = 'sitemap';

	/**
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * @var ContentTypeRegistry
	 */
	private ContentTypeRegistry $content_type_registry;

	/**
	 * Daftar section settings.
	 *
	 * @var SectionInterface[]
	 */
	private array $sections = [];

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 */
	public function __construct( OptionManager $option_manager ) {
		$this->option_manager        = $option_manager;
		$this->content_type_registry = new ContentTypeRegistry();

		$this->register_sections();
	}

	/**
	 * Daftarkan seluruh section settings.
	 *
	 * @return void
	 */
	private function register_sections(): void {
		$this->register_section( new SitemapContent( $this->content_type_registry ) );
		$this->register_section( new ExcludedItems() );
		$this->register_section( new Priorities() );
		$this->register_section( new Changefreq() );

		// Seluruh section Sitemap Settings telah terdaftar.
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
				// penjelasan pada Settings.php module General.
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
			'/sitemap-settings',
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

		register_rest_route(
			'lunar-seo/v1',
			'/sitemap-content-types',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_get_content_types' ],
				'permission_callback' => [ $this, 'rest_permission_check' ],
			]
		);
	}

	/**
	 * Handler GET - kembalikan daftar Custom Post Type & Custom
	 * Taxonomy yang terdaftar di situs (metadata untuk Admin React
	 * app, BUKAN bagian dari data setting itu sendiri).
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_content_types(): \WP_REST_Response {
		$post_types = [];

		foreach ( $this->content_type_registry->get_custom_post_types() as $slug => $post_type_object ) {
			$post_types[] = [
				'slug'  => $slug,
				'label' => $post_type_object->label,
			];
		}

		$taxonomies = [];

		foreach ( $this->content_type_registry->get_custom_taxonomies() as $slug => $taxonomy_object ) {
			$taxonomies[] = [
				'slug'  => $slug,
				'label' => $taxonomy_object->label,
			];
		}

		return new \WP_REST_Response(
			[
				'post_types' => $post_types,
				'taxonomies' => $taxonomies,
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
	 * Handler GET - kembalikan seluruh setting module Sitemap.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_settings(): \WP_REST_Response {
		return new \WP_REST_Response( $this->option_manager->get_all( self::MODULE_SLUG ) );
	}

	/**
	 * Handler POST - sanitasi lalu simpan seluruh setting module Sitemap.
	 *
	 * @param \WP_REST_Request $request Request REST.
	 * @return \WP_REST_Response
	 */
	public function rest_update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$input = $request->get_json_params();
		$input = is_array( $input ) ? $input : [];

		$sanitized = $this->sanitize( $input );

		$this->option_manager->update_all( self::MODULE_SLUG, $sanitized );

		/**
		 * Setiap perubahan Sitemap Settings berpotensi mengubah
		 * struktur konten yang di-generate - invalidasi seluruh
		 * cache sitemap (SITEMAP_MODULE_ARCHITECTURE.md §4).
		 */
		do_action( 'lunar_seo_sitemap_settings_updated' );

		return new \WP_REST_Response( $sanitized );
	}

	/**
	 * Sanitasi seluruh data option, didelegasikan per section.
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

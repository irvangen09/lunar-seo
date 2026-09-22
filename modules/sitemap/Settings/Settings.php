<?php
/**
 * Settings Orchestrator — Module Sitemap.
 *
 * Identical pattern to modules/general/Settings/Settings.php — see
 * that file for the full reasoning behind the custom REST route
 * (not /wp/v2/settings) and why register_setting() is still kept for
 * WordPress Options API compliance.
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

	private const OPTION_GROUP = 'lunar_seo_sitemap';

	private const MODULE_SLUG = 'sitemap';

	private OptionManager $option_manager;

	private ContentTypeRegistry $content_type_registry;

	/**
	 * @var SectionInterface[]
	 */
	private array $sections = [];

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager        = $option_manager;
		$this->content_type_registry = new ContentTypeRegistry();

		$this->register_sections();
	}

	private function register_sections(): void {
		$this->register_section( new SitemapContent( $this->content_type_registry ) );
		$this->register_section( new ExcludedItems() );
		$this->register_section( new Priorities() );
		$this->register_section( new Changefreq() );
	}

	private function register_section( SectionInterface $section ): void {
		$this->sections[ $section->get_section_key() ] = $section;
	}

	public function init(): void {
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_setting(): void {
		register_setting(
			self::OPTION_GROUP,
			$this->option_manager->get_option_name( self::MODULE_SLUG ),
			[
				'type'              => 'object',
				'sanitize_callback' => [ $this, 'sanitize' ],
				// show_in_rest is DELIBERATELY not enabled — see the
				// explanation in the General module's Settings.php.
				'show_in_rest'      => false,
				'default'           => [],
			]
		);
	}

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
	 * Returns the list of registered Custom Post Types & Custom
	 * Taxonomies (metadata for the Admin React app, NOT part of the
	 * setting data itself).
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

	public function rest_permission_check(): bool {
		return current_user_can( 'manage_options' );
	}

	public function rest_get_settings(): \WP_REST_Response {
		return new \WP_REST_Response( $this->option_manager->get_all( self::MODULE_SLUG ) );
	}

	public function rest_update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$input = $request->get_json_params();
		$input = is_array( $input ) ? $input : [];

		$sanitized = $this->sanitize( $input );

		$this->option_manager->update_all( self::MODULE_SLUG, $sanitized );

		/**
		 * Any Sitemap Settings change can potentially alter the
		 * generated content's structure — invalidate every sitemap
		 * cache entry.
		 */
		do_action( 'lunar_seo_sitemap_settings_updated' );

		return new \WP_REST_Response( $sanitized );
	}

	/**
	 * A section that isn't registered is simply ignored (not saved).
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
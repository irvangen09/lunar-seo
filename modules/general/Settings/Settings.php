<?php
/**
 * Settings Orchestrator.
 *
 * Registers the General module's option with the WordPress Settings
 * API, delegating sanitization to each section (SectionInterface).
 *
 * The React admin app reads/writes data through a DEDICATED REST route
 * ("lunar-seo/v1/general-settings"), NOT WordPress's built-in generic
 * endpoint (/wp/v2/settings). Reason: that generic endpoint has a
 * known limitation with nested-object option data — its REST schema
 * often strips/resets values before they reach sanitize_callback,
 * so data doesn't actually get saved even though the request looks
 * successful. This dedicated REST route is still 100% WordPress-native
 * (uses WordPress's own register_rest_route()), it just avoids the
 * generic path that's broken for data shaped like ours.
 *
 * register_setting() is still kept, for compliance with the WordPress
 * Options API (data is still stored in wp_options the standard way,
 * still reachable via get_option()/WP-CLI) — only "show_in_rest" is
 * disabled, so the generic endpoint can't become a usable path for
 * this broken case.
 *
 * @package Lunar\SEO\Modules\General\Settings
 */

namespace Lunar\SEO\Modules\General\Settings;

use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	private const OPTION_GROUP = 'lunar_seo_general';

	private const MODULE_SLUG = 'general';

	// Site Info fields promoted to the SiteIdentity Shared Service —
	// no longer stored in lunar_seo_general_settings.site_info, though
	// their field schema/sanitization stays in SiteInfo.php (unchanged).
	// title_separator is DELIBERATELY not in this list — it stays in
	// General, not relevant to Schema.
	private const SITE_IDENTITY_FIELDS = [ 'website_name', 'alternate_website_name', 'site_image_id' ];

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	/**
	 * Filled in by register_sections() from the constructor below —
	 * never empty once this class is instantiated.
	 *
	 * @var SectionInterface[]
	 */
	private array $sections = [];

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity ) {
		$this->option_manager = $option_manager;
		$this->site_identity  = $site_identity;

		$this->register_sections();
	}

	private function register_sections(): void {
		$this->register_section( new SiteInfo() );
		$this->register_section( new Content() );
		$this->register_section( new CategoriesTags() );
		$this->register_section( new Social() );
		$this->register_section( new Verification() );
		$this->register_section( new RobotsUrl() );
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
				// explanation in this class's docblock.
				'show_in_rest'      => false,
				'default'           => [],
			]
		);
	}

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

	public function rest_permission_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * The Site Identity fields (website_name, alternate_website_name,
	 * site_image_id) are overlaid from the SiteIdentity Shared Service
	 * into the "site_info" key — the React app doesn't need to know
	 * their actual storage location was split off.
	 */
	public function rest_get_settings(): \WP_REST_Response {
		$data = $this->option_manager->get_all( self::MODULE_SLUG );

		return new \WP_REST_Response( $this->overlay_site_identity( $data ) );
	}

	public function rest_update_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$input = $request->get_json_params();
		$input = is_array( $input ) ? $input : [];

		$before = $this->option_manager->get_all( self::MODULE_SLUG );

		$sanitized = $this->sanitize( $input );

		// Site Identity fields are written to the Shared Service
		// (not to lunar_seo_general_settings) — their field
		// schema/sanitization STAYS in SiteInfo.php (unchanged), only
		// their final storage destination is split off here.
		$this->site_identity->set( $this->extract_site_identity_fields( $sanitized ) );

		foreach ( self::SITE_IDENTITY_FIELDS as $field ) {
			unset( $sanitized['site_info'][ $field ] );
		}

		$this->option_manager->update_all( self::MODULE_SLUG, $sanitized );

		$this->maybe_flush_rewrite_rules( $before, $sanitized );

		return new \WP_REST_Response( $this->overlay_site_identity( $sanitized ) );
	}

	private function extract_site_identity_fields( array $sanitized ): array {
		$site_info = $sanitized['site_info'] ?? [];

		return [
			'website_name'           => $site_info['website_name'] ?? '',
			'alternate_website_name' => $site_info['alternate_website_name'] ?? '',
			'site_image_id'          => $site_info['site_image_id'] ?? 0,
		];
	}

	/**
	 * Keeps the REST payload shape consistent whether read before or
	 * after the Site Identity field promotion — the React app
	 * (src/admin/app.js) still reads from the exact same path
	 * (settings.site_info.website_name, etc.) with no change needed.
	 */
	private function overlay_site_identity( array $data ): array {
		$data['site_info']['website_name']           = $this->site_identity->get_website_name();
		$data['site_info']['alternate_website_name']  = $this->site_identity->get_alternate_website_name();
		$data['site_info']['site_image_id']           = $this->site_identity->get_site_image_id();

		return $data;
	}

	/**
	 * Only flushes rewrite rules when a toggle that actually affects
	 * URL structure (Remove Category Base/Tag Base) really changed —
	 * flush_rewrite_rules() is a heavy operation, not something to run
	 * on every settings save.
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
	 * A section that isn't registered is simply ignored (not saved),
	 * preventing unrecognized data from reaching the database.
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
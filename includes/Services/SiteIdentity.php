<?php
/**
 * Site Identity.
 *
 * Shared Service for site identity data (Website Name, Alternate
 * Website Name, Site Image) consumed by more than one module — General
 * (Site Info, the {site_name} placeholder, Open Graph/Twitter image
 * fallback) and Schema (Organization, WebSite).
 *
 * @package Lunar\SEO\Services
 */

namespace Lunar\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SiteIdentity {

	private const OPTION_KEY = 'lunar_seo_site_identity';

	private OptionManager $option_manager;

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	public function get_website_name(): string {
		return (string) $this->get_field( 'website_name', '' );
	}

	/**
	 * Same as get_website_name(), but falls back to the site's native
	 * title (get_bloginfo('name')) when the field has never been filled
	 * in. Every consumer that needs a display-ready site name (the
	 * {site_name} placeholder, Schema's WebSite.name/Organization.name)
	 * should call this instead of re-implementing the same fallback.
	 */
	public function get_effective_website_name(): string {
		$name = $this->get_website_name();

		return '' !== $name ? $name : get_bloginfo( 'name' );
	}

	public function get_alternate_website_name(): string {
		return (string) $this->get_field( 'alternate_website_name', '' );
	}

	public function get_site_image_id(): int {
		return (int) $this->get_field( 'site_image_id', 0 );
	}

	public function set( array $data ): bool {
		$current = get_option( self::OPTION_KEY, [] );

		if ( ! is_array( $current ) ) {
			$current = [];
		}

		// autoload=yes: read on every frontend request to render meta
		// tags/schema, same rationale as the General/Sitemap options.
		return update_option( self::OPTION_KEY, array_merge( $current, $data ), true );
	}

	/**
	 * Reads a field from the NEW storage location. If it's empty/never
	 * been filled in, falls back to the OLD location
	 * (lunar_seo_general_settings.site_info) — this bridges data the
	 * user already filled in before SiteIdentity existed, without a
	 * separate migration/activation-hook process. Once Settings.php
	 * (General) saves a field here, this fallback naturally stops being
	 * used for that field.
	 */
	private function get_field( string $key, $default ) {
		$new = get_option( self::OPTION_KEY, [] );

		if ( is_array( $new ) && isset( $new[ $key ] ) && '' !== $new[ $key ] ) {
			return $new[ $key ];
		}

		return $this->option_manager->get( 'general', 'site_info', $key, $default );
	}
}
<?php
/**
 * Option Manager.
 *
 * Shared Service that manages every module's configuration
 * consistently through the WordPress Options API.
 *
 * Storage pattern: one option per module, holding a nested array per
 * section, with autoload enabled since it's read on every frontend
 * request to render meta tags.
 *
 * @package Lunar\SEO\Services
 */

namespace Lunar\SEO\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OptionManager {

	private const OPTION_PREFIX = 'lunar_seo_';

	/**
	 * Per-module option data cached within a single request, so the
	 * same option isn't fetched from the database more than once per
	 * request.
	 *
	 * @var array<string, array>
	 */
	private array $cache = [];

	/**
	 * Builds a module's option name. Public so other components (e.g.
	 * Settings.php) can reuse it without duplicating the naming logic.
	 */
	public function get_option_name( string $module_slug ): string {
		return self::OPTION_PREFIX . $module_slug . '_settings';
	}

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
	 * Example: get_section( 'general', 'site_info' ).
	 */
	public function get_section( string $module_slug, string $section ): array {
		$all = $this->get_all( $module_slug );

		return isset( $all[ $section ] ) && is_array( $all[ $section ] )
			? $all[ $section ]
			: [];
	}

	public function get( string $module_slug, string $section, string $field, $default = null ) {
		$section_data = $this->get_section( $module_slug, $section );

		return $section_data[ $field ] ?? $default;
	}

	/**
	 * Updates one section without touching any other section. Every
	 * Settings/*.php in a module only manages its own section, through
	 * this method.
	 */
	public function update_section( string $module_slug, string $section, array $data ): bool {
		$all             = $this->get_all( $module_slug );
		$all[ $section ] = $data;

		return $this->persist( $module_slug, $all );
	}

	/**
	 * Replaces a module's entire data set. Used for special cases like
	 * import/reset.
	 */
	public function update_all( string $module_slug, array $data ): bool {
		return $this->persist( $module_slug, $data );
	}

	private function persist( string $module_slug, array $data ): bool {
		$option_name = $this->get_option_name( $module_slug );

		// autoload=yes: this option is read on every frontend request
		// to render meta tags.
		$result = update_option( $option_name, $data, true );

		// The in-request cache is only updated once the write actually
		// succeeds. If update_option() fails, the DB still holds the
		// old value — leaving the cache unchanged keeps it consistent
		// with that for the rest of this request.
		if ( $result ) {
			$this->cache[ $module_slug ] = $data;
		}

		return $result;
	}
}
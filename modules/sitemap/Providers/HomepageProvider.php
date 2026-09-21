<?php
/**
 * Homepage Provider.
 *
 * Produces a single entry for the Homepage.
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HomepageProvider implements ProviderInterface {

	private const MODULE_SLUG = 'sitemap';

	private OptionManager $option_manager;

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	public function get_entries(): array {
		$priorities = $this->option_manager->get_section( self::MODULE_SLUG, 'priorities' );
		$changefreq = $this->option_manager->get_section( self::MODULE_SLUG, 'changefreq' );

		return [
			[
				'loc'        => home_url( '/' ),
				'lastmod'    => $this->resolve_lastmod(),
				'changefreq' => $changefreq['homepage'] ?? 'monthly',
				'priority'   => (float) ( $priorities['homepage'] ?? 1.0 ),
			],
		];
	}

	/**
	 * Resolves lastmod — from the static page (if the Homepage is set
	 * as one) or from the most recently modified post (blog index).
	 */
	private function resolve_lastmod(): ?string {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = (int) get_option( 'page_on_front' );

			if ( $front_page_id > 0 ) {
				$time = get_post_modified_time( 'c', true, $front_page_id );

				if ( false !== $time ) {
					return $time;
				}
			}
		}

		$recent = get_posts(
			[
				'numberposts' => 1,
				'post_status' => 'publish',
				'orderby'     => 'modified',
				'order'       => 'DESC',
			]
		);

		if ( ! empty( $recent ) ) {
			$time = get_post_modified_time( 'c', true, $recent[0] );

			return false !== $time ? $time : null;
		}

		return null;
	}
}
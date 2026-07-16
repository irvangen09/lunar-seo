<?php
/**
 * Homepage Provider.
 *
 * Menghasilkan satu entry untuk Homepage.
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HomepageProvider implements ProviderInterface {

	/**
	 * Slug module, dipakai untuk membaca Global Settings.
	 *
	 * @var string
	 */
	private const MODULE_SLUG = 'sitemap';

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
	 * {@inheritDoc}
	 */
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
	 * Resolusi lastmod - dari static page (apabila Homepage di-set
	 * sebagai static page) atau dari post terbaru (blog index).
	 *
	 * @return string|null
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

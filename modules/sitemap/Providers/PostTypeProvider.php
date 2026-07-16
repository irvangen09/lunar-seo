<?php
/**
 * Post Type Provider.
 *
 * GENERIK - satu class ini dipakai untuk Posts, Pages, MAUPUN
 * Custom Post Type apapun (termasuk WooCommerce "product" bila
 * situs memakainya), diparametrisasi lewat $post_type di
 * constructor. Tidak perlu class baru setiap ada Custom Post Type
 * baru (SITEMAP_MODULE_ARCHITECTURE.md §1).
 *
 * Automatic Priority Calculation HANYA berlaku untuk post type
 * "post" (sesuai dokumen - "Untuk Posts, Lunar SEO mendukung dua
 * mode"), Pages dan Custom Post Type lain selalu memakai nilai
 * priority statis dari Settings.
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

use Lunar\SEO\Modules\Sitemap\Services\PriorityCalculator;
use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostTypeProvider implements ProviderInterface {

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
	 * @var PriorityCalculator
	 */
	private PriorityCalculator $priority_calculator;

	/**
	 * Post type yang ditangani instance ini (contoh: "post", "page", "product").
	 *
	 * @var string
	 */
	private string $post_type;

	/**
	 * @param OptionManager       $option_manager       Shared service Option Manager.
	 * @param PriorityCalculator  $priority_calculator  Service kalkulasi Automatic Priority.
	 * @param string              $post_type            Post type yang ditangani.
	 */
	public function __construct( OptionManager $option_manager, PriorityCalculator $priority_calculator, string $post_type ) {
		$this->option_manager      = $option_manager;
		$this->priority_calculator = $priority_calculator;
		$this->post_type           = $post_type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_entries(): array {
		$excluded_items = $this->option_manager->get_section( self::MODULE_SLUG, 'excluded_items' );
		$excluded_posts = $excluded_items['excluded_posts'] ?? [];

		$posts = get_posts(
			[
				'post_type'      => $this->post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post__not_in'   => $excluded_posts,
				'no_found_rows'  => true,
			]
		);

		$priorities = $this->option_manager->get_section( self::MODULE_SLUG, 'priorities' );
		$changefreq = $this->option_manager->get_section( self::MODULE_SLUG, 'changefreq' );

		[ $priority_field, $changefreq_field ] = $this->resolve_field_keys();

		$base_priority    = (float) ( $priorities[ $priority_field ] ?? 0.5 );
		$entry_changefreq = $changefreq[ $changefreq_field ] ?? 'monthly';

		$use_auto_priority = 'post' === $this->post_type && ! empty( $priorities['auto_calculate_post_priority'] );
		$total              = count( $posts );

		$entries = [];

		foreach ( $posts as $index => $post ) {
			$priority = $base_priority;

			if ( $use_auto_priority ) {
				$priority = $this->priority_calculator->calculate(
					$index + 1,
					$total,
					(float) ( $priorities['posts'] ?? 0.8 ),
					(float) ( $priorities['minimum_post_priority'] ?? 0.2 )
				);
			}

			$lastmod = get_post_modified_time( 'c', true, $post );

			$entries[] = [
				'loc'        => get_permalink( $post ),
				'lastmod'    => false !== $lastmod ? $lastmod : null,
				'changefreq' => $entry_changefreq,
				'priority'   => $priority,
			];
		}

		return $entries;
	}

	/**
	 * Tentukan field Priority/Changefreq yang sesuai berdasarkan
	 * post type (Post/Page pakai field khusus, CPT lain pakai
	 * default bersama).
	 *
	 * @return array{0: string, 1: string}
	 */
	private function resolve_field_keys(): array {
		if ( 'post' === $this->post_type ) {
			return [ 'posts', 'posts' ];
		}

		if ( 'page' === $this->post_type ) {
			return [ 'static_pages', 'static_pages' ];
		}

		return [ 'custom_post_type_default', 'custom_post_type_default' ];
	}
}

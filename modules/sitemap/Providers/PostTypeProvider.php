<?php
/**
 * Post Type Provider.
 *
 * GENERIC — this one class is used for Posts, Pages, AND any Custom
 * Post Type (including WooCommerce's "product" if the site uses it),
 * parametrized via $post_type in the constructor. No new class is
 * needed every time a new Custom Post Type appears.
 *
 * Automatic Priority Calculation ONLY applies to the "post" post
 * type — Pages and every other Custom Post Type always use a static
 * priority value from Settings.
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

	private const MODULE_SLUG = 'sitemap';

	private OptionManager $option_manager;

	private PriorityCalculator $priority_calculator;

	// The post type this instance handles (e.g. "post", "page", "product").
	private string $post_type;

	public function __construct( OptionManager $option_manager, PriorityCalculator $priority_calculator, string $post_type ) {
		$this->option_manager      = $option_manager;
		$this->priority_calculator = $priority_calculator;
		$this->post_type           = $post_type;
	}

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
	 * Determines the matching Priority/Changefreq field for this post
	 * type (Post/Page use their own dedicated field, any other CPT
	 * uses the shared default).
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
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
 * Posts are fetched in two passes to bound peak memory on sites with
 * a large number of posts: a lightweight ID-only query first (to get
 * an accurate total and date order — needed because Automatic
 * Priority's rank is a position within the full ordered set, not
 * something a single page of results could compute on its own), then
 * hydrated in fixed-size batches. Each batch's post objects go out of
 * scope before the next batch is fetched, instead of holding every
 * post of this type in memory at once.
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

	// Number of posts hydrated into full WP_Post objects at a time.
	private const BATCH_SIZE = 500;

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

		// Pass 1: IDs only, so an accurate total and date order exist
		// before any post is fully hydrated.
		$post_ids = get_posts(
			[
				'post_type'      => $this->post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post__not_in'   => $excluded_posts,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			]
		);

		$total = count( $post_ids );

		$priorities = $this->option_manager->get_section( self::MODULE_SLUG, 'priorities' );
		$changefreq = $this->option_manager->get_section( self::MODULE_SLUG, 'changefreq' );

		[ $priority_field, $changefreq_field ] = $this->resolve_field_keys();

		$base_priority     = (float) ( $priorities[ $priority_field ] ?? 0.5 );
		$entry_changefreq  = $changefreq[ $changefreq_field ] ?? 'monthly';
		$use_auto_priority = 'post' === $this->post_type && ! empty( $priorities['auto_calculate_post_priority'] );

		$entries = [];
		$rank    = 0;

		// Pass 2: hydrate BATCH_SIZE posts at a time. Each batch's
		// WP_Post objects go out of scope before the next batch is
		// fetched, so peak memory stays bounded by BATCH_SIZE rather
		// than growing with the total post count.
		foreach ( array_chunk( $post_ids, self::BATCH_SIZE ) as $batch_ids ) {
			$batch_posts = get_posts(
				[
					'post__in'               => $batch_ids,
					'post_type'              => $this->post_type,
					'post_status'            => 'publish',
					'orderby'                => 'post__in',
					'posts_per_page'         => count( $batch_ids ),
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			foreach ( $batch_posts as $post ) {
				++$rank;

				$priority = $base_priority;

				if ( $use_auto_priority ) {
					$priority = $this->priority_calculator->calculate(
						$rank,
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
<?php
/**
 * Priority Calculator.
 *
 * Implements the Automatic Priority Calculation formula for Posts —
 * rank-based linear interpolation. The most recent post (rank 1)
 * approaches the "Posts" value (the ceiling), the oldest post
 * approaches "Minimum Post Priority" (the floor), decreasing linearly
 * by order.
 *
 * @package Lunar\SEO\Modules\Sitemap\Services
 */

namespace Lunar\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PriorityCalculator {

	/**
	 * @param int   $rank             The post's rank (1 = most recent post).
	 * @param int   $total_posts      Total posts in the relevant post type.
	 * @param float $posts_priority   The "Posts" value — the ceiling.
	 * @param float $minimum_priority The "Minimum Post Priority" value — the floor.
	 */
	public function calculate( int $rank, int $total_posts, float $posts_priority, float $minimum_priority ): float {
		if ( $total_posts <= 1 ) {
			return $posts_priority;
		}

		$priority = $minimum_priority
			+ ( ( $posts_priority - $minimum_priority ) / $total_posts )
			* ( $total_posts - $rank + 1 );

		// A safety clamp against floating-point rounding — always kept
		// within [minimum_priority, posts_priority].
		$priority = max( $minimum_priority, min( $posts_priority, $priority ) );

		return round( $priority, 1 );
	}
}
<?php
/**
 * Priority Calculator.
 *
 * Implementasi formula Automatic Priority Calculation untuk Posts
 * (SITEMAP_MODULE_ARCHITECTURE.md §5) - rank-based linear
 * interpolation. Post terbaru (rank 1) mendekati nilai "Posts"
 * (plafon), post terlama mendekati "Minimum Post Priority" (batas
 * bawah), menurun linear berdasarkan urutan.
 *
 * @package Lunar\SEO\Modules\Sitemap\Services
 */

namespace Lunar\SEO\Modules\Sitemap\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PriorityCalculator {

	/**
	 * Hitung priority otomatis berdasarkan urutan (rank) post.
	 *
	 * @param int   $rank             Urutan post (1 = post terbaru).
	 * @param int   $total_posts      Total post pada post type terkait.
	 * @param float $posts_priority   Nilai "Posts" - plafon/ceiling.
	 * @param float $minimum_priority Nilai "Minimum Post Priority" - batas bawah.
	 * @return float
	 */
	public function calculate( int $rank, int $total_posts, float $posts_priority, float $minimum_priority ): float {
		if ( $total_posts <= 1 ) {
			return $posts_priority;
		}

		$priority = $minimum_priority
			+ ( ( $posts_priority - $minimum_priority ) / $total_posts )
			* ( $total_posts - $rank + 1 );

		// Jaga-jaga terhadap pembulatan floating point - tetap
		// clamp ke rentang [minimum_priority, posts_priority].
		$priority = max( $minimum_priority, min( $posts_priority, $priority ) );

		return round( $priority, 1 );
	}
}

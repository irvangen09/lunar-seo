<?php
/**
 * Author Provider.
 *
 * Produces an entry for every user with at least one published post
 * (their author archive page).
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AuthorProvider implements ProviderInterface {

	private const MODULE_SLUG = 'sitemap';

	private OptionManager $option_manager;

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	public function get_entries(): array {
		$priorities = $this->option_manager->get_section( self::MODULE_SLUG, 'priorities' );
		$changefreq = $this->option_manager->get_section( self::MODULE_SLUG, 'changefreq' );

		$priority         = (float) ( $priorities['author_pages'] ?? 0.3 );
		$entry_changefreq = $changefreq['author_pages'] ?? 'monthly';

		$authors = get_users(
			[
				'has_published_posts' => true,
			]
		);

		$entries = [];

		foreach ( $authors as $author ) {
			$entries[] = [
				'loc'        => get_author_posts_url( $author->ID ),
				'lastmod'    => null,
				'changefreq' => $entry_changefreq,
				'priority'   => $priority,
			];
		}

		return $entries;
	}
}
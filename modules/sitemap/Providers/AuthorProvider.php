<?php
/**
 * Author Provider.
 *
 * Menghasilkan entry untuk setiap user yang memiliki minimal satu
 * post terpublish (author archive page).
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AuthorProvider implements ProviderInterface {

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

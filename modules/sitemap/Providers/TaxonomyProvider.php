<?php
/**
 * Taxonomy Provider.
 *
 * GENERIC — this one class is used for Categories, Tags, AND any
 * Custom Taxonomy (including WooCommerce's product_cat/product_tag if
 * the site uses it), parametrized via $taxonomy in the constructor.
 *
 * Excluded Categories (from the Excluded Items setting) ONLY applies
 * specifically to the "category" taxonomy, per the documented scope
 * (the setting is only named "Excluded categories" — there's no
 * "Excluded tags" or exclusion for any other taxonomy).
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TaxonomyProvider implements ProviderInterface {

	private const MODULE_SLUG = 'sitemap';

	private OptionManager $option_manager;

	// The taxonomy this instance handles (e.g. "category", "post_tag").
	private string $taxonomy;

	public function __construct( OptionManager $option_manager, string $taxonomy ) {
		$this->option_manager = $option_manager;
		$this->taxonomy       = $taxonomy;
	}

	public function get_entries(): array {
		$term_args = [
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => true,
		];

		if ( 'category' === $this->taxonomy ) {
			$excluded_items         = $this->option_manager->get_section( self::MODULE_SLUG, 'excluded_items' );
			$term_args['exclude']   = $excluded_items['excluded_categories'] ?? [];
		}

		$terms = get_terms( $term_args );

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		$priorities = $this->option_manager->get_section( self::MODULE_SLUG, 'priorities' );
		$changefreq = $this->option_manager->get_section( self::MODULE_SLUG, 'changefreq' );

		[ $priority_field, $changefreq_field ] = $this->resolve_field_keys();

		$priority         = (float) ( $priorities[ $priority_field ] ?? 0.3 );
		$entry_changefreq = $changefreq[ $changefreq_field ] ?? 'monthly';

		$entries = [];

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );

			if ( is_wp_error( $link ) ) {
				continue;
			}

			$entries[] = [
				'loc'        => $link,
				// WordPress terms have no native "modified date" like
				// posts do — lastmod is deliberately left empty.
				'lastmod'    => null,
				'changefreq' => $entry_changefreq,
				'priority'   => $priority,
			];
		}

		return $entries;
	}

	/**
	 * Determines the matching Priority/Changefreq field for this
	 * taxonomy (Category/Tag use their own dedicated field, any other
	 * taxonomy uses the shared default).
	 *
	 * @return array{0: string, 1: string}
	 */
	private function resolve_field_keys(): array {
		if ( 'category' === $this->taxonomy ) {
			return [ 'categories', 'categories' ];
		}

		if ( 'post_tag' === $this->taxonomy ) {
			return [ 'tag_pages', 'tag_pages' ];
		}

		return [ 'custom_taxonomy_default', 'custom_taxonomy_default' ];
	}
}
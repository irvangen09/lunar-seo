<?php
/**
 * Taxonomy Provider.
 *
 * GENERIK - satu class ini dipakai untuk Categories, Tags, MAUPUN
 * Custom Taxonomy apapun (termasuk WooCommerce product_cat/
 * product_tag bila situs memakainya), diparametrisasi lewat
 * $taxonomy di constructor.
 *
 * Excluded Categories (dari Settings Excluded Items) HANYA berlaku
 * spesifik untuk taxonomy "category", sesuai scope yang
 * didokumentasikan (dokumen hanya menyebut "Excluded categories",
 * tidak ada "Excluded tags" atau exclusion untuk taxonomy lain).
 *
 * @package Lunar\SEO\Modules\Sitemap\Providers
 */

namespace Lunar\SEO\Modules\Sitemap\Providers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TaxonomyProvider implements ProviderInterface {

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
	 * Taxonomy yang ditangani instance ini (contoh: "category", "post_tag").
	 *
	 * @var string
	 */
	private string $taxonomy;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 * @param string        $taxonomy       Taxonomy yang ditangani.
	 */
	public function __construct( OptionManager $option_manager, string $taxonomy ) {
		$this->option_manager = $option_manager;
		$this->taxonomy       = $taxonomy;
	}

	/**
	 * {@inheritDoc}
	 */
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
				// Term WordPress tidak memiliki "modified date" native
				// seperti post - lastmod sengaja dikosongkan.
				'lastmod'    => null,
				'changefreq' => $entry_changefreq,
				'priority'   => $priority,
			];
		}

		return $entries;
	}

	/**
	 * Tentukan field Priority/Changefreq yang sesuai berdasarkan
	 * taxonomy (Category/Tag pakai field khusus, taxonomy lain
	 * pakai default bersama).
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

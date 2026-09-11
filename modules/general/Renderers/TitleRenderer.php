<?php

namespace Lunar\SEO\Modules\General\Renderers;

use Lunar\SEO\Modules\General\PostMetaKeys;
use Lunar\SEO\Modules\General\Services\TitleResolver;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TitleRenderer implements RendererInterface {

	private const MODULE_SLUG = 'general';

	private OptionManager $option_manager;

	private TitleResolver $title_resolver;

	private SupportedPostTypes $supported_post_types;

	public function __construct( OptionManager $option_manager, TitleResolver $title_resolver, SupportedPostTypes $supported_post_types ) {
		$this->option_manager       = $option_manager;
		$this->title_resolver       = $title_resolver;
		$this->supported_post_types = $supported_post_types;
	}

	public function init(): void {
		// pre_get_document_title, not a manual echo in wp_head - avoids
		// fighting the theme and WordPress core's own title resolution.
		add_filter( 'pre_get_document_title', [ $this, 'filter_title' ], 15 );
	}

	public function filter_title( string $title ): string {
		[ $template, $context_values, $fallback_template ] = $this->resolve_context();

		if ( null === $template ) {
			return $title;
		}

		$resolved = $this->title_resolver->resolve( $template, $context_values, $fallback_template );

		return '' !== $resolved ? $resolved : $title;
	}

	// An explicit fallback template is required for contexts without a
	// natural {title} placeholder (Search, 404, Category/Tag) - the
	// resolver's generic fallback would otherwise leave a literal
	// "{title}" unresolved, since context_values has no 'title' key there.
	private function resolve_context(): array {
		if ( is_front_page() && ! is_paged() ) {
			return [
				$this->get_content_field( 'homepage', 'seo_title' ),
				// {title} is still supplied (via get_homepage_title(), which
				// honours a static page's title if set) in case an admin
				// writes a custom template that references {title} directly.
				[ 'title' => $this->title_resolver->get_homepage_title() ],
				// Homepage-specific fallback, not the generic
				// "{title} {separator} {site_name}": on a blog-index
				// homepage {title} has no natural value distinct from
				// {site_name}, so the generic fallback would print the
				// site name twice (e.g. "Lunar WP | Lunar WP").
				'{site_name} {separator} {tagline}',
			];
		}

		if ( is_singular() ) {
			$content_group = $this->supported_post_types->content_group( (string) get_post_type() );

			if ( null !== $content_group ) {
				$override = $this->get_meta_override( PostMetaKeys::TITLE );

				return [
					'' !== $override ? $override : $this->get_content_field( $content_group, 'seo_title' ),
					[ 'title' => get_the_title() ],
					'',
				];
			}
		}

		if ( is_search() ) {
			return [
				$this->get_content_field( 'search', 'seo_title' ),
				[ 'query' => get_search_query() ],
				__( 'Search results for {query}', 'lunar-seo' ) . ' {separator} {site_name}',
			];
		}

		if ( is_404() ) {
			return [
				$this->get_content_field( 'not_found', 'seo_title' ),
				[],
				__( 'Page not found', 'lunar-seo' ) . ' {separator} {site_name}',
			];
		}

		if ( is_category() || is_tag() ) {
			$taxonomy_type = is_category() ? 'categories' : 'tags';

			return [
				$this->get_taxonomy_field( $taxonomy_type, 'seo_title' ),
				[ 'term_title' => single_term_title( '', false ) ],
				'{term_title} {separator} {site_name}',
			];
		}

		return [ null, [], '' ];
	}

	private function get_meta_override( string $meta_key ): string {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		$value = get_post_meta( $post_id, $meta_key, true );

		return is_string( $value ) ? trim( $value ) : '';
	}

	private function get_content_field( string $content_type, string $field ): string {
		$data = $this->option_manager->get( self::MODULE_SLUG, 'content', $content_type, [] );

		return is_array( $data ) && isset( $data[ $field ] ) ? (string) $data[ $field ] : '';
	}

	private function get_taxonomy_field( string $taxonomy_type, string $field ): string {
		$data = $this->option_manager->get( self::MODULE_SLUG, 'categories_tags', $taxonomy_type, [] );

		return is_array( $data ) && isset( $data[ $field ] ) ? (string) $data[ $field ] : '';
	}
}
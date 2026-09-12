<?php

namespace Lunar\SEO\Modules\General\Renderers;

use Lunar\SEO\Modules\General\PostMetaKeys;
use Lunar\SEO\Modules\General\Services\PlaceholderResolver;
use Lunar\SEO\Modules\General\Services\DescriptionGenerator;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaRenderer implements RendererInterface {

	private const MODULE_SLUG = 'general';

	// "index_follow" maps to an empty array (no negative directives) -
	// "index"/"follow" have no explicit representation in a robots meta
	// tag, they're simply what applies when no negative directive is set.
	private const ROBOTS_PRESET_MAP = [
		'index_follow'     => [],
		'noindex_follow'   => [ 'noindex' ],
		'noindex_nofollow' => [ 'noindex', 'nofollow' ],
	];

	private OptionManager $option_manager;

	private PlaceholderResolver $placeholder_resolver;

	private DescriptionGenerator $description_generator;

	private SupportedPostTypes $supported_post_types;

	public function __construct(
		OptionManager $option_manager,
		PlaceholderResolver $placeholder_resolver,
		DescriptionGenerator $description_generator,
		SupportedPostTypes $supported_post_types
	) {
		$this->option_manager        = $option_manager;
		$this->placeholder_resolver  = $placeholder_resolver;
		$this->description_generator = $description_generator;
		$this->supported_post_types  = $supported_post_types;
	}

	public function init(): void {
		add_action( 'wp_head', [ $this, 'output' ], 2 );
		add_filter( 'wp_robots', [ $this, 'filter_robots' ] );
	}

	// Robots is intentionally not printed here - see filter_robots(),
	// which merges into WordPress core's own <meta name="robots"> via
	// the "wp_robots" filter instead of printing a second tag.
	public function output(): void {
		$this->output_meta_description();
		$this->output_canonical();
	}

	private function output_meta_description(): void {
		$description = $this->resolve_description();

		if ( '' === $description ) {
			return;
		}

		printf(
			'<meta name="description" content="%s" />' . "\n",
			esc_attr( $description )
		);
	}

	private function resolve_description(): string {
		if ( is_front_page() && ! is_paged() ) {
			return $this->resolve_content_description( 'homepage', [ 'title' => $this->placeholder_resolver->get_homepage_title() ] );
		}

		if ( is_singular() ) {
			$content_group = $this->supported_post_types->content_group( (string) get_post_type() );

			if ( null !== $content_group ) {
				$override = $this->get_meta_override( PostMetaKeys::DESCRIPTION );

				if ( '' !== $override ) {
					return $this->placeholder_resolver->resolve( $override, [ 'title' => get_the_title() ] );
				}

				return $this->resolve_content_description( $content_group, [ 'title' => get_the_title() ] );
			}
		}

		if ( is_category() || is_tag() ) {
			$taxonomy_type = is_category() ? 'categories' : 'tags';

			return $this->resolve_taxonomy_description(
				$taxonomy_type,
				[ 'term_title' => single_term_title( '', false ) ]
			);
		}

		// Search and 404 have no Meta Description field in Settings, so
		// they fall through to the empty-string return below.
		return '';
	}

	private function resolve_content_description( string $content_type, array $context_values ): string {
		$data = $this->option_manager->get( self::MODULE_SLUG, 'content', $content_type, [] );

		$template       = is_array( $data ) ? ( $data['meta_description'] ?? '' ) : '';
		$auto_generate  = is_array( $data ) ? ! empty( $data['auto_generate_description'] ) : false;

		if ( '' !== trim( (string) $template ) ) {
			return $this->placeholder_resolver->resolve( (string) $template, $context_values );
		}

		if ( ! $auto_generate ) {
			return '';
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		return $this->description_generator->generate( $post );
	}

	// Unlike resolve_content_description(), there's no auto-generate
	// fallback here - a taxonomy archive has no post_content to
	// generate a description from, only the template placeholder.
	private function resolve_taxonomy_description( string $taxonomy_type, array $context_values ): string {
		$data     = $this->option_manager->get( self::MODULE_SLUG, 'categories_tags', $taxonomy_type, [] );
		$template = is_array( $data ) ? ( $data['meta_description'] ?? '' ) : '';

		if ( '' === trim( (string) $template ) ) {
			return '';
		}

		return $this->placeholder_resolver->resolve( (string) $template, $context_values );
	}

	// blog_public = '0' is WordPress core's "Discourage search engines"
	// (Settings > Reading) - treated as a global override that this
	// plugin never contradicts, so per-page SEO settings can't
	// accidentally make a staging/private site indexable (same
	// precedence Yoast/RankMath use).
	public function filter_robots( array $robots ): array {
		if ( '0' === get_option( 'blog_public' ) ) {
			return $robots;
		}

		$directives = $this->resolve_robots_directives();

		// Only negative directives have a key WordPress core recognises -
		// "index"/"follow" are simply the default when their negative
		// counterpart isn't set.
		$negative_directives = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

		foreach ( $negative_directives as $directive ) {
			$robots[ $directive ] = in_array( $directive, $directives, true );
		}

		return $robots;
	}

	private function resolve_robots_directives(): array {
		if ( is_singular() ) {
			$override = $this->get_meta_override_array( PostMetaKeys::ROBOTS );

			if ( ! empty( $override ) ) {
				return $override;
			}
		}

		$robots_settings = $this->option_manager->get_section( self::MODULE_SLUG, 'robots_url' );
		$default_meta    = $robots_settings['default_robots_meta'] ?? [];

		if ( is_404() ) {
			return $this->resolve_preset( $robots_settings['not_found_robots'] ?? 'noindex_follow', $default_meta );
		}

		if ( is_category() || is_tag() ) {
			$directives    = $this->resolve_preset( $robots_settings['archives_robots'] ?? 'default', $default_meta );
			$taxonomy_type = is_category() ? 'categories' : 'tags';

			// "Show in search results" is more specific than the global
			// "archives_robots" preset - when disabled, it forces
			// "noindex" regardless of that preset.
			if ( ! $this->is_taxonomy_shown_in_search_results( $taxonomy_type ) ) {
				$directives[] = 'noindex';
			}

			return array_values( array_unique( $directives ) );
		}

		if ( is_search() || is_archive() ) {
			return $this->resolve_preset( $robots_settings['archives_robots'] ?? 'default', $default_meta );
		}

		return $default_meta;
	}

	// Defaults to true (shown) when never saved, so archives aren't
	// silently noindexed before an admin has touched this setting.
	private function is_taxonomy_shown_in_search_results( string $taxonomy_type ): bool {
		$data = $this->option_manager->get( self::MODULE_SLUG, 'categories_tags', $taxonomy_type, [] );

		if ( ! is_array( $data ) || ! array_key_exists( 'show_in_search_results', $data ) ) {
			return true;
		}

		return (bool) $data['show_in_search_results'];
	}

	private function resolve_preset( string $preset, array $default_meta ): array {
		if ( 'default' === $preset ) {
			return $default_meta;
		}

		return self::ROBOTS_PRESET_MAP[ $preset ] ?? $default_meta;
	}

	// Search and 404 intentionally get no canonical - neither is a
	// resource that should be indexed or canonicalised.
	private function output_canonical(): void {
		$url = $this->resolve_canonical_url();

		if ( '' === $url ) {
			return;
		}

		printf(
			'<link rel="canonical" href="%s" />' . "\n",
			esc_url( $url )
		);
	}

	private function resolve_canonical_url(): string {
		if ( is_singular() ) {
			$override = $this->get_meta_override( PostMetaKeys::CANONICAL );

			if ( '' !== $override ) {
				return $override;
			}
		}

		if ( is_front_page() && ! is_paged() ) {
			return home_url( '/' );
		}

		if ( is_singular() ) {
			$permalink = get_permalink();

			return false !== $permalink ? $permalink : '';
		}

		if ( is_category() || is_tag() ) {
			$term_link = get_term_link( get_queried_object() );

			return is_wp_error( $term_link ) ? '' : $term_link;
		}

		return '';
	}

	private function get_meta_override( string $meta_key ): string {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return '';
		}

		$value = get_post_meta( $post_id, $meta_key, true );

		return is_string( $value ) ? trim( $value ) : '';
	}

	private function get_meta_override_array( string $meta_key ): array {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return [];
		}

		$value = get_post_meta( $post_id, $meta_key, true );

		return is_array( $value ) ? $value : [];
	}
}
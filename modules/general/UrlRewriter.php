<?php
/**
 * URL Rewriter.
 *
 * The actual implementation behind the three toggles in
 * Settings/RobotsUrl.php: Remove Category Base, Remove Tag Base,
 * Redirect Attachments to Parent. Unlike Renderers/ (which only print
 * meta tags into <head>), this class changes WordPress's own
 * URL/routing structure, so it's kept as a separate component rather
 * than folded into Frontend.php.
 *
 * Category and Tag are handled with structurally identical logic
 * (only the taxonomy differs), so they share private helper methods
 * to avoid duplicating that logic per taxonomy.
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class UrlRewriter {

	private const MODULE_SLUG = 'general';

	private OptionManager $option_manager;

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	/**
	 * Hooks are only registered for a toggle that's actually enabled.
	 */
	public function init(): void {
		$settings = $this->option_manager->get_section( self::MODULE_SLUG, 'robots_url' );

		if ( ! empty( $settings['remove_category_base'] ) ) {
			add_filter( 'category_link', [ $this, 'filter_category_link' ] );
			add_filter( 'generate_rewrite_rules', [ $this, 'add_category_rewrite_rules' ] );
			add_action( 'template_redirect', [ $this, 'maybe_redirect_category_base' ] );
		}

		if ( ! empty( $settings['remove_tag_base'] ) ) {
			add_filter( 'tag_link', [ $this, 'filter_tag_link' ] );
			add_filter( 'generate_rewrite_rules', [ $this, 'add_tag_rewrite_rules' ] );
			add_action( 'template_redirect', [ $this, 'maybe_redirect_tag_base' ] );
		}

		if ( ! empty( $settings['remove_category_base'] ) || ! empty( $settings['remove_tag_base'] ) ) {
			add_filter( 'query_vars', [ $this, 'add_redirect_query_vars' ] );
		}

		if ( ! empty( $settings['redirect_attachments_to_parent'] ) ) {
			add_action( 'template_redirect', [ $this, 'redirect_attachment_to_parent' ] );
		}
	}

	// =========================================================
	// Category
	// =========================================================

	public function filter_category_link( string $link ): string {
		return $this->strip_taxonomy_base( $link, $this->get_taxonomy_base( 'category' ) );
	}

	public function add_category_rewrite_rules( \WP_Rewrite $wp_rewrite ): \WP_Rewrite {
		return $this->add_taxonomy_rewrite_rules( $wp_rewrite, 'category', 'category_name', 'category_redirect' );
	}

	public function maybe_redirect_category_base(): void {
		$this->maybe_redirect_taxonomy_base( 'category_redirect', 'category' );
	}

	// =========================================================
	// Tag
	// =========================================================

	public function filter_tag_link( string $link ): string {
		return $this->strip_taxonomy_base( $link, $this->get_taxonomy_base( 'post_tag' ) );
	}

	public function add_tag_rewrite_rules( \WP_Rewrite $wp_rewrite ): \WP_Rewrite {
		return $this->add_taxonomy_rewrite_rules( $wp_rewrite, 'post_tag', 'tag', 'tag_redirect' );
	}

	public function maybe_redirect_tag_base(): void {
		$this->maybe_redirect_taxonomy_base( 'tag_redirect', 'post_tag' );
	}

	// =========================================================
	// Shared helper (Category & Tag)
	// =========================================================

	public function add_redirect_query_vars( array $vars ): array {
		$vars[] = 'category_redirect';
		$vars[] = 'tag_redirect';

		return $vars;
	}

	/**
	 * Reads the taxonomy base (category_base/tag_base) from WordPress,
	 * falling back to the native default if it was never set.
	 */
	private function get_taxonomy_base( string $taxonomy ): string {
		$option_key = 'category' === $taxonomy ? 'category_base' : 'tag_base';
		$default    = 'category' === $taxonomy ? 'category' : 'tag';

		$base = get_option( $option_key );

		return $base ? trim( $base, '/' ) : $default;
	}

	/**
	 * Removes the base segment from a link.
	 *
	 * Only the FIRST "/{base}/" occurrence is removed (via strpos +
	 * substr_replace), not every occurrence a plain str_replace() would
	 * catch — a term slug that happens to match the base string
	 * elsewhere later in the path is left alone.
	 */
	private function strip_taxonomy_base( string $link, string $base ): string {
		$needle = '/' . $base . '/';
		$pos    = strpos( $link, $needle );

		if ( false === $pos ) {
			return $link;
		}

		return substr_replace( $link, '/', $pos, strlen( $needle ) );
	}

	/**
	 * Registers rewrite rules for one taxonomy (category/tag).
	 *
	 * Follows the common, well-tested pattern for this: re-register
	 * every term as an explicit rewrite rule, plus one extra rule that
	 * catches the old URL shape (with the base) and routes it to the
	 * redirect mechanism instead of a 404.
	 */
	private function add_taxonomy_rewrite_rules( \WP_Rewrite $wp_rewrite, string $taxonomy, string $query_var, string $redirect_var ): \WP_Rewrite {
		$rewrite = [];
		$terms   = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$slug = $term->slug;

				$rewrite[ '(' . $slug . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?' . $query_var . '=$matches[1]&feed=$matches[2]';
				$rewrite[ '(' . $slug . ')/page/?([0-9]{1,})/?$' ]                  = 'index.php?' . $query_var . '=$matches[1]&paged=$matches[2]';
				$rewrite[ '(' . $slug . ')/?$' ]                                    = 'index.php?' . $query_var . '=$matches[1]';
			}
		}

		$base = $this->get_taxonomy_base( $taxonomy );

		// Catches the old URL shape (with the base) so it lands on the
		// redirect handler instead of a 404.
		$rewrite[ $base . '/(.*)$' ] = 'index.php?' . $redirect_var . '=$matches[1]';

		$wp_rewrite->rules = array_merge( $rewrite, $wp_rewrite->rules );

		return $wp_rewrite;
	}

	private function maybe_redirect_taxonomy_base( string $redirect_query_var, string $taxonomy ): void {
		$path = get_query_var( $redirect_query_var );

		if ( empty( $path ) ) {
			return;
		}

		$path       = trim( (string) $path, '/' );
		$slug_parts = explode( '/', $path );
		$slug       = end( $slug_parts );

		$term = get_term_by( 'slug', $slug, $taxonomy );

		if ( ! $term instanceof \WP_Term ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}

		$link = get_term_link( $term );

		wp_safe_redirect( is_wp_error( $link ) ? home_url( '/' ) : $link, 301 );
		exit;
	}

	// =========================================================
	// Attachments
	// =========================================================

	/**
	 * Redirects an attachment page to its parent post/page permalink.
	 * Skipped if the attachment has no parent.
	 */
	public function redirect_attachment_to_parent(): void {
		if ( ! is_attachment() ) {
			return;
		}

		$post = get_post();

		if ( ! $post instanceof \WP_Post || ! $post->post_parent ) {
			return;
		}

		$parent_url = get_permalink( $post->post_parent );

		if ( false !== $parent_url ) {
			wp_safe_redirect( $parent_url, 301 );
			exit;
		}
	}
}
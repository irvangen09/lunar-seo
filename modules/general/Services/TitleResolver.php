<?php
/**
 * Title Resolver.
 *
 * Resolves the final SEO Title from a template. If the template is
 * empty, a default rule-based (NOT generative AI) fallback template is
 * used, per the Auto-generate Service decision.
 *
 * This class does NOT know where the template came from (which Global
 * Settings section, or a per-post override) — that's the caller's
 * responsibility (Renderer/Editor). Its Single Responsibility is
 * purely: template + context values -> final title.
 *
 * @package Lunar\SEO\Modules\General\Services
 */

namespace Lunar\SEO\Modules\General\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TitleResolver {

	private const DEFAULT_TEMPLATE = '{title} {separator} {site_name}';

	private PlaceholderResolver $placeholder_resolver;

	public function __construct( PlaceholderResolver $placeholder_resolver ) {
		$this->placeholder_resolver = $placeholder_resolver;
	}

	/**
	 * @param string $template          The SEO Title template, may be empty.
	 * @param array  $context_values    Contextual placeholders (e.g. [ 'title' => 'Post Title' ]).
	 * @param string $fallback_template A fallback template SPECIFIC to this context, used when
	 *                                  $template is empty. Callers must supply this for contexts
	 *                                  that don't naturally have a {title} placeholder (Search,
	 *                                  404, Category/Tag) — see TitleRenderer.php. Leave empty to
	 *                                  use the generic DEFAULT_TEMPLATE (fine for Homepage/Post/
	 *                                  Page, which do have {title}).
	 */
	public function resolve( string $template, array $context_values = [], string $fallback_template = '' ): string {
		if ( '' === trim( $template ) ) {
			$template = '' !== $fallback_template ? $fallback_template : self::DEFAULT_TEMPLATE;
		}

		return $this->placeholder_resolver->resolve( $template, $context_values );
	}

	/**
	 * Passthrough to PlaceholderResolver::get_homepage_title().
	 */
	public function get_homepage_title(): string {
		return $this->placeholder_resolver->get_homepage_title();
	}
}
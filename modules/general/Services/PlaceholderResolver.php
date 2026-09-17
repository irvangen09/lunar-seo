<?php
/**
 * Placeholder Resolver.
 *
 * Replaces placeholders ({title}, {site_name}, {tagline}, {separator},
 * {query}, {term_title}, etc.) in the SEO Title/Meta Description
 * template with their actual values.
 *
 * Global placeholders ({site_name}, {tagline}, {separator}) are always
 * resolved automatically by this class as a single source of truth, so
 * they aren't re-implemented by every caller (TitleResolver,
 * DescriptionGenerator, and the React admin app via REST).
 *
 * Contextual placeholders ({title}, {query}, {term_title}) are supplied
 * by the caller via the $values parameter, since their value depends
 * on context (post/page/search/archive) that this Resolver doesn't
 * need to know about.
 *
 * @package Lunar\SEO\Modules\General\Services
 */

namespace Lunar\SEO\Modules\General\Services;

use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlaceholderResolver {

	private const MODULE_SLUG = 'general';

	// website_name is no longer read from this section directly —
	// see SiteIdentity.
	private const SITE_INFO_SECTION = 'site_info';

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity ) {
		$this->option_manager = $option_manager;
		$this->site_identity  = $site_identity;
	}

	public function resolve( string $template, array $values = [] ): string {
		$placeholders = array_merge( $this->get_global_placeholders(), $values );

		$search  = [];
		$replace = [];

		foreach ( $placeholders as $key => $value ) {
			$search[]  = '{' . $key . '}';
			$replace[] = (string) $value;
		}

		$resolved = str_replace( $search, $replace, $template );

		// Collapse extra whitespace left behind when a placeholder
		// resolves to an empty string (e.g. {tagline} not filled in).
		//
		// NOTE: this doesn't remove a separator left dangling (e.g.
		// "Post Title | " if {tagline} is empty at the end of the
		// template) — that's deliberately not handled here, to avoid
		// over-engineering a rare edge case (templates typically end
		// with {site_name}, which always has a fallback value).
		return trim( preg_replace( '/\s+/', ' ', $resolved ) );
	}

	/**
	 * Resolves {site_name}: Website Name (Site Info) if filled in,
	 * falling back to WordPress's native Site Title if empty.
	 */
	public function get_site_name(): string {
		return $this->site_identity->get_effective_website_name();
	}

	/**
	 * Resolves {title} specifically for the Homepage context.
	 *
	 * If the Homepage is set to a static page (Settings > Reading > "A
	 * static page"), uses that page's ACTUAL title — which can DIFFER
	 * from {site_name} (e.g. the page is titled "Home"), so the default
	 * template "{title} {separator} {site_name}" doesn't end up
	 * printing the site name twice.
	 *
	 * Falls back to get_site_name() ONLY when the Homepage is the
	 * regular blog posts index (no static page) — in that case there's
	 * no natural "page title" to use.
	 */
	public function get_homepage_title(): string {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = (int) get_option( 'page_on_front' );

			if ( $front_page_id > 0 ) {
				$front_page_title = get_the_title( $front_page_id );

				if ( '' !== $front_page_title ) {
					return $front_page_title;
				}
			}
		}

		return $this->get_site_name();
	}

	private function get_global_placeholders(): array {
		$separator = $this->option_manager->get(
			self::MODULE_SLUG,
			self::SITE_INFO_SECTION,
			'title_separator',
			'|'
		);

		return [
			'site_name' => $this->get_site_name(),

			// Tagline is DELIBERATELY read directly from native
			// WordPress (not from our module's option) — see the
			// decision in Settings/SiteInfo.php.
			'tagline'   => get_bloginfo( 'description' ),

			'separator' => $separator,
		];
	}
}
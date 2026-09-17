<?php
/**
 * Open Graph Renderer.
 *
 * Prints og:title, og:description, og:image to <head>. This Renderer
 * is skipped entirely (no tag printed at all) if the "Enable Open
 * Graph" toggle is off in Global Settings.
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

use Lunar\SEO\Modules\General\Services\PlaceholderResolver;
use Lunar\SEO\Modules\General\Services\DescriptionGenerator;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class OpenGraphRenderer implements RendererInterface {

	use SocialMetaTrait;

	private const MODULE_SLUG = 'general';

	private OptionManager $option_manager;

	private PlaceholderResolver $placeholder_resolver;

	private DescriptionGenerator $description_generator;

	private SiteIdentity $site_identity;

	public function __construct(
		OptionManager $option_manager,
		PlaceholderResolver $placeholder_resolver,
		DescriptionGenerator $description_generator,
		SiteIdentity $site_identity
	) {
		$this->option_manager        = $option_manager;
		$this->placeholder_resolver  = $placeholder_resolver;
		$this->description_generator = $description_generator;
		$this->site_identity         = $site_identity;
	}

	public function init(): void {
		add_action( 'wp_head', [ $this, 'output' ], 3 );
	}

	/**
	 * Prints every og: tag, or skips entirely if the toggle is off.
	 */
	public function output(): void {
		$social = $this->option_manager->get_section( self::MODULE_SLUG, 'social' );
		$og     = $social['open_graph'] ?? [];

		if ( empty( $og['enabled'] ) ) {
			return;
		}

		$this->output_tag( 'og:title', $this->resolve_title() );
		$this->output_tag( 'og:description', $this->resolve_description() );
		$this->output_tag( 'og:type', is_singular() ? 'article' : 'website' );
		$this->output_url_tag( 'og:url', $this->resolve_url() );

		$image_url = $this->resolve_image_url( (int) ( $og['image_id'] ?? 0 ) );
		$this->output_url_tag( 'og:image', $image_url );
	}

	protected function meta_attribute(): string {
		return 'property';
	}

	/**
	 * Resolves the current page's URL — only relevant to Open Graph
	 * (og:url); Twitter Card has no equivalent tag, so this stays here
	 * rather than in the shared trait.
	 */
	private function resolve_url(): string {
		if ( is_front_page() ) {
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
}
<?php
/**
 * Twitter Card Renderer.
 *
 * Prints twitter:card, twitter:title, twitter:description,
 * twitter:image to <head>. This Renderer is skipped entirely if the
 * "Enable Twitter Card" toggle is off in Global Settings.
 *
 * Shares its title/description/image resolution logic with
 * OpenGraphRenderer via SocialMetaTrait — each class still reads its
 * own toggle and Default Image setting independently, so Open Graph
 * and Twitter Card can be enabled or disabled separately from each
 * other.
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

final class TwitterCardRenderer implements RendererInterface {

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
		add_action( 'wp_head', [ $this, 'output' ], 4 );
	}

	/**
	 * Prints every twitter: tag, or skips entirely if the toggle is off.
	 */
	public function output(): void {
		$social  = $this->option_manager->get_section( self::MODULE_SLUG, 'social' );
		$twitter = $social['twitter_card'] ?? [];

		if ( empty( $twitter['enabled'] ) ) {
			return;
		}

		$image_url = $this->resolve_image_url( (int) ( $twitter['image_id'] ?? 0 ) );

		$this->output_tag( 'twitter:card', '' !== $image_url ? 'summary_large_image' : 'summary' );
		$this->output_tag( 'twitter:title', $this->resolve_title() );
		$this->output_tag( 'twitter:description', $this->resolve_description() );
		$this->output_url_tag( 'twitter:image', $image_url );
	}

	protected function meta_attribute(): string {
		return 'name';
	}
}
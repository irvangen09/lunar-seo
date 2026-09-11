<?php

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\Modules\General\Renderers\RendererInterface;
use Lunar\SEO\Modules\General\Renderers\TitleRenderer;
use Lunar\SEO\Modules\General\Renderers\MetaRenderer;
use Lunar\SEO\Modules\General\Renderers\OpenGraphRenderer;
use Lunar\SEO\Modules\General\Renderers\TwitterCardRenderer;
use Lunar\SEO\Modules\General\Renderers\VerificationRenderer;
use Lunar\SEO\Modules\General\Services\PlaceholderResolver;
use Lunar\SEO\Modules\General\Services\TitleResolver;
use Lunar\SEO\Modules\General\Services\DescriptionGenerator;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;
use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	private SupportedPostTypes $supported_post_types;

	private array $renderers = [];

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, SupportedPostTypes $supported_post_types ) {
		$this->option_manager       = $option_manager;
		$this->site_identity        = $site_identity;
		$this->supported_post_types = $supported_post_types;

		$this->register_renderers();
	}

	private function register_renderers(): void {
		$placeholder_resolver  = new PlaceholderResolver( $this->option_manager, $this->site_identity );
		$title_resolver        = new TitleResolver( $placeholder_resolver );
		$description_generator = new DescriptionGenerator();

		$this->register_renderer(
			new TitleRenderer( $this->option_manager, $title_resolver, $this->supported_post_types )
		);

		$this->register_renderer(
			new MetaRenderer( $this->option_manager, $placeholder_resolver, $description_generator, $this->supported_post_types )
		);

		$this->register_renderer(
			new OpenGraphRenderer( $this->option_manager, $placeholder_resolver, $description_generator, $this->site_identity )
		);

		$this->register_renderer(
			new TwitterCardRenderer( $this->option_manager, $placeholder_resolver, $description_generator, $this->site_identity )
		);

		$this->register_renderer(
			new VerificationRenderer( $this->option_manager )
		);
	}

	private function register_renderer( RendererInterface $renderer ): void {
		$this->renderers[] = $renderer;
	}

	public function init(): void {
		foreach ( $this->renderers as $renderer ) {
			$renderer->init();
		}
	}
}
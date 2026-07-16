<?php
/**
 * Frontend.
 *
 * Orchestrator/composition root untuk seluruh Renderer output
 * frontend. Merakit dependency (OptionManager, Services) yang
 * dibutuhkan tiap Renderer, lalu memanggil init() masing-masing.
 *
 * Tidak menghook wp_head secara langsung di sini - setiap Renderer
 * mendaftarkan hook-nya sendiri pada titik yang sesuai (lihat
 * RendererInterface).
 *
 * @package Lunar\SEO\Modules\General
 */

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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	/**
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * @var SiteIdentity
	 */
	private SiteIdentity $site_identity;

	/**
	 * Daftar Renderer yang akan diinisialisasi.
	 *
	 * @var RendererInterface[]
	 */
	private array $renderers = [];

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 * @param SiteIdentity  $site_identity  Shared service Site Identity.
	 */
	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity ) {
		$this->option_manager = $option_manager;
		$this->site_identity  = $site_identity;

		$this->register_renderers();
	}

	/**
	 * Rakit Services dan daftarkan seluruh Renderer.
	 *
	 * Services (PlaceholderResolver, TitleResolver,
	 * DescriptionGenerator) dibangun sekali di sini dan dibagikan
	 * ke Renderer yang membutuhkannya, menghindari instansiasi
	 * berulang (CODING_STANDARD.md §13).
	 *
	 * @return void
	 */
	private function register_renderers(): void {
		$placeholder_resolver  = new PlaceholderResolver( $this->option_manager, $this->site_identity );
		$title_resolver        = new TitleResolver( $placeholder_resolver );
		$description_generator = new DescriptionGenerator();

		$this->register_renderer(
			new TitleRenderer( $this->option_manager, $title_resolver )
		);

		$this->register_renderer(
			new MetaRenderer( $this->option_manager, $placeholder_resolver, $description_generator )
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

	/**
	 * Daftarkan satu Renderer ke orchestrator.
	 *
	 * @param RendererInterface $renderer Instance Renderer.
	 * @return void
	 */
	private function register_renderer( RendererInterface $renderer ): void {
		$this->renderers[] = $renderer;
	}

	/**
	 * Inisialisasi seluruh Renderer.
	 *
	 * Masing-masing Renderer mendaftarkan hook WordPress-nya sendiri
	 * (pre_get_document_title atau wp_head) di dalam init()-nya.
	 *
	 * @return void
	 */
	public function init(): void {
		foreach ( $this->renderers as $renderer ) {
			$renderer->init();
		}
	}
}

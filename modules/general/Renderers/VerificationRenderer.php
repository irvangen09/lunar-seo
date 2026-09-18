<?php
/**
 * Verification Renderer.
 *
 * Prints site-ownership verification meta tags for Google Search
 * Console, Bing Webmaster, and Yandex Webmaster. Each platform is
 * skipped INDIVIDUALLY (not all at once) when its field is empty.
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VerificationRenderer implements RendererInterface {

	private const MODULE_SLUG = 'general';

	// Maps each platform to its provider's official verification meta
	// tag name.
	private const META_NAME_MAP = [
		'google' => 'google-site-verification',
		'bing'   => 'msvalidate.01',
		'yandex' => 'yandex-verification',
	];

	private OptionManager $option_manager;

	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	public function init(): void {
		add_action( 'wp_head', [ $this, 'output' ], 5 );
	}

	public function output(): void {
		$verification = $this->option_manager->get_section( self::MODULE_SLUG, 'verification' );

		foreach ( self::META_NAME_MAP as $platform => $meta_name ) {
			$code = $verification[ $platform ] ?? '';

			if ( '' === $code ) {
				continue;
			}

			printf(
				'<meta name="%s" content="%s" />' . "\n",
				esc_attr( $meta_name ),
				esc_attr( $code )
			);
		}
	}
}
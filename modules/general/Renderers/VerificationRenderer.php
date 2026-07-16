<?php
/**
 * Verification Renderer.
 *
 * Mencetak meta tag verifikasi kepemilikan situs untuk Google
 * Search Console, Bing Webmaster, dan Yandex Webmaster. Setiap
 * platform di-skip SECARA INDIVIDUAL (bukan semuanya sekaligus)
 * apabila field-nya kosong (ARCHITECTURE.md §10).
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VerificationRenderer implements RendererInterface {

	/**
	 * Slug module, dipakai untuk membaca Global Settings.
	 *
	 * @var string
	 */
	private const MODULE_SLUG = 'general';

	/**
	 * Pemetaan platform ke nama meta tag verifikasi resmi
	 * masing-masing penyedia layanan.
	 *
	 * @var array<string, string>
	 */
	private const META_NAME_MAP = [
		'google' => 'google-site-verification',
		'bing'   => 'msvalidate.01',
		'yandex' => 'yandex-verification',
	];

	/**
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 */
	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	/**
	 * {@inheritDoc}
	 */
	public function init(): void {
		add_action( 'wp_head', [ $this, 'output' ], 5 );
	}

	/**
	 * Cetak meta tag verifikasi untuk setiap platform yang diisi.
	 *
	 * @return void
	 */
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

<?php
/**
 * Assets.
 *
 * Loads the Sitemap module's CSS/JS. Only an Admin context exists (no
 * Editor — see Module.php) and there's no frontend asset (pure XML
 * output, not HTML/CSS/JS).
 *
 * @package Lunar\SEO\Modules\Sitemap
 */

namespace Lunar\SEO\Modules\Sitemap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	// The same Admin instance booted by Module.php — used to read the
	// ACTUAL hook_suffix (not guessed again).
	private Admin $admin;

	public function __construct( Admin $admin ) {
		$this->admin = $admin;
	}

	public function init(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin' ] );
	}

	public function enqueue_admin( string $hook_suffix ): void {
		if ( null === $this->admin->get_hook_suffix() || $hook_suffix !== $this->admin->get_hook_suffix() ) {
			return;
		}

		$asset_file = LUNAR_SEO_PATH . 'build/sitemap-admin.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			// The build hasn't been run yet (npm run build) — fail
			// gracefully.
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'lunar-seo-sitemap-admin',
			LUNAR_SEO_URL . 'build/sitemap-admin.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// NOTE: @wordpress/scripts names the CSS output differently
		// from the JS for the same entry — "build/style-sitemap-admin.css",
		// NOT "build/sitemap-admin.css" (same pattern as
		// modules/general/Assets.php).
		$style_path = LUNAR_SEO_PATH . 'build/style-sitemap-admin.css';

		if ( file_exists( $style_path ) ) {
			wp_enqueue_style(
				'lunar-seo-sitemap-admin',
				LUNAR_SEO_URL . 'build/style-sitemap-admin.css',
				[],
				$asset['version']
			);
		}
	}
}
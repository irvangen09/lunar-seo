<?php
/**
 * Post Meta Keys.
 *
 * Konstanta nama meta key override per-post, dipakai bersama oleh
 * Editor.php (registrasi) dan Renderer (pembacaan saat frontend
 * render) - satu sumber kebenaran agar tidak terjadi drift nama
 * key antara kedua sisi (CODING_STANDARD.md §2 - DRY).
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostMetaKeys {

	/**
	 * Meta key SEO Title override.
	 *
	 * @var string
	 */
	public const TITLE = '_lunar_seo_title';

	/**
	 * Meta key Meta Description override.
	 *
	 * @var string
	 */
	public const DESCRIPTION = '_lunar_seo_description';

	/**
	 * Meta key Canonical URL override.
	 *
	 * @var string
	 */
	public const CANONICAL = '_lunar_seo_canonical';

	/**
	 * Meta key Robots override.
	 *
	 * @var string
	 */
	public const ROBOTS = '_lunar_seo_robots';
}

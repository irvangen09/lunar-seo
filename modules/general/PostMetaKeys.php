<?php
/**
 * Post Meta Keys.
 *
 * Per-post override meta key names, shared by Editor.php/MetaBox.php
 * (registration/save) and the Renderers (read at frontend render) —
 * one source of truth so the two sides never drift apart.
 *
 * uninstall.php intentionally duplicates these four literal values
 * (it can't use this class — see its own note on why it stays
 * autoloader-free). If a key here ever changes, update uninstall.php
 * to match.
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PostMetaKeys {

	public const TITLE = '_lunar_seo_title';

	public const DESCRIPTION = '_lunar_seo_description';

	public const CANONICAL = '_lunar_seo_canonical';

	public const ROBOTS = '_lunar_seo_robots';
}
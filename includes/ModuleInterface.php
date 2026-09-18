<?php
/**
 * Contract every module must implement.
 *
 * Deliberately minimal (not an abstract class with many methods) to
 * avoid over-engineering.
 *
 * @package Lunar\SEO
 */

namespace Lunar\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ModuleInterface {

	/**
	 * The module's unique slug, used as its registration key and to
	 * determine its active/inactive status.
	 */
	public function get_slug(): string;

	/**
	 * Initializes the module.
	 *
	 * Called by the Module Registry ONLY when the module is active. An
	 * inactive module never has this called, so it never loads assets
	 * or hooks either.
	 */
	public function init(): void;
}
<?php
/**
 * Contract every frontend output Renderer must implement.
 *
 * The contract uses init() (not a render() called manually from a
 * single wp_head loop) because each output category has a different
 * WordPress integration point:
 * - Title -> the "pre_get_document_title" filter (must be registered
 *            BEFORE wp_head, since WordPress core prints the <title>
 *            tag at wp_head priority 1).
 * - Meta, Open Graph, Twitter Card, Verification -> the "wp_head" action.
 *
 * Each Renderer is responsible for registering its own appropriate
 * hook inside its own init() — the orchestrator (Frontend.php) doesn't
 * treat them uniformly.
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface RendererInterface {

	/**
	 * Registers this Renderer's appropriate WordPress hook.
	 *
	 * Implementations must escape output for its context
	 * (esc_attr/esc_url) and return early when the render condition
	 * isn't met (toggle off, field empty, context not relevant) —
	 * never print an empty tag.
	 */
	public function init(): void;
}
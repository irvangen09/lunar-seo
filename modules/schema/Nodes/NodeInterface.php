<?php
/**
 * Contract every Schema Node must implement.
 *
 * Deliberately minimal (one method) to avoid over-engineering,
 * following the same proven pattern as ModuleInterface/
 * RendererInterface/ProviderInterface in the General and Sitemap
 * modules.
 *
 * Each Node DECIDES FOR ITSELF whether it's applicable in the current
 * context (e.g. ArticleNode is only applicable for a post type
 * configured as its 'article' schema_node via
 * lunar_seo_supported_post_types) — SchemaGraphBuilder (the
 * orchestrator) doesn't need to know any per-type logic, it's a pure
 * aggregator.
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface NodeInterface {

	/**
	 * Produces a JSON-LD node array ready to go into the "@graph", or
	 * null if this node isn't applicable in the current context (not
	 * an empty array — a null result means the parent skips this node
	 * entirely).
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_node(): ?array;
}
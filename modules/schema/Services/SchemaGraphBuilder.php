<?php
/**
 * Schema Graph Builder.
 *
 * Thin orchestrator — a pure aggregator, it does NOT know any per-type
 * logic (whether a Node is applicable in a given context is decided by
 * the Node itself through get_node(), see NodeInterface). Same pattern
 * as General's Frontend.php, which is also a "thin orchestrator".
 *
 * @package Lunar\SEO\Modules\Schema\Services
 */

namespace Lunar\SEO\Modules\Schema\Services;

use Lunar\SEO\Modules\Schema\Nodes\NodeInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaGraphBuilder {

	/**
	 * @var NodeInterface[]
	 */
	private array $nodes;

	/**
	 * @param NodeInterface[] $nodes Every potentially-applicable Node
	 *                                (WebSite, Organization,
	 *                                BreadcrumbList, Article, WebPage)
	 *                                — order is decided by the caller.
	 */
	public function __construct( array $nodes ) {
		$this->nodes = $nodes;
	}

	/**
	 * Assembles the "@graph" from every Node applicable in the current
	 * context. A Node whose get_node() returns null is skipped
	 * entirely (not inserted as an empty entry).
	 *
	 * @return array{"@context": string, "@graph": array<int, array<string, mixed>>}
	 */
	public function build(): array {
		$graph = [];

		foreach ( $this->nodes as $node ) {
			$result = $node->get_node();

			if ( null !== $result ) {
				$graph[] = $result;
			}
		}

		return [
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		];
	}
}
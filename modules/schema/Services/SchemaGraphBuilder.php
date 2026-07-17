<?php
/**
 * Schema Graph Builder.
 *
 * Orchestrator tipis - murni agregator, TIDAK tahu logic per-type
 * (apakah suatu Node applicable di context tertentu ditentukan Node
 * itu sendiri lewat get_node(), lihat NodeInterface). Pola sama
 * dengan Frontend.php General yang jadi "orchestrator tipis"
 * (GENERAL_MODULE_ARCHITECTURE.md §6.1, SCHEMA_MODULE_ARCHITECTURE.md §5.3).
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
	 * @param NodeInterface[] $nodes Daftar seluruh Node yang mungkin applicable
	 *                                (WebSite, Organization, BreadcrumbList,
	 *                                Article, WebPage - urutan ditentukan
	 *                                pemanggil, lihat Frontend.php Tahap 2.9).
	 */
	public function __construct( array $nodes ) {
		$this->nodes = $nodes;
	}

	/**
	 * Rakit "@graph" dari seluruh Node yang applicable pada context
	 * saat ini. Node yang get_node()-nya mengembalikan null di-skip
	 * seluruhnya (bukan disisipkan sebagai entry kosong) - konsisten
	 * ARCHITECTURE.md §10 ("Output frontend hanya dimuat apabila
	 * diperlukan").
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

<?php
/**
 * Frontend Schema.
 *
 * Thin orchestrator — hooks wp_head, calls SchemaGraphBuilder, outputs
 * <script type="application/ld+json">. Same pattern as
 * Frontend.php in General/Sitemap.
 *
 * @package Lunar\SEO\Modules\Schema
 */

namespace Lunar\SEO\Modules\Schema;

use Lunar\SEO\Modules\Schema\Services\SchemaGraphBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	private SchemaGraphBuilder $graph_builder;

	public function __construct( SchemaGraphBuilder $graph_builder ) {
		$this->graph_builder = $graph_builder;
	}

	public function init(): void {
		add_action( 'wp_head', [ $this, 'render' ] );
	}

	/**
	 * If "@graph" is empty (e.g. a 404/search page with no applicable
	 * schema — WebSite/Organization are ALWAYS applicable so @graph is
	 * practically never truly empty in normal operation, this check is
	 * still kept as defense-in-depth), NO <script> is printed at all.
	 */
	public function render(): void {
		$graph = $this->graph_builder->build();

		if ( empty( $graph['@graph'] ) ) {
			return;
		}

		/*
		 * SECURITY NOTE: DO NOT add JSON_UNESCAPED_SLASHES here.
		 *
		 * By default, json_encode()/wp_json_encode() escapes every "/"
		 * character to "\/". This isn't just a stylistic choice — that
		 * escaping is what prevents the literal character sequence
		 * "</script>" from ever appearing intact inside the JSON
		 * embedded in this <script> tag. Values such as headline
		 * (ArticleNode/WebPageNode), category/page name
		 * (BreadcrumbListNode), or author name are NOT escaped for an
		 * HTML context at any earlier layer before reaching here — if
		 * one of them contains a literal "</script>" (e.g. from an
		 * Editor role, which by default has the unfiltered_html
		 * capability on a WP single-site install), this <script> tag
		 * would close prematurely, and whatever markup/script follows
		 * it would be executed by the browser as real HTML/JS (a
		 * stored XSS). JSON_UNESCAPED_UNICODE is safe to keep — it
		 * doesn't touch the "/" character.
		 */
		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $graph, JSON_UNESCAPED_UNICODE )
		);
	}
}
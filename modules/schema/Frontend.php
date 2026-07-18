<?php
/**
 * Frontend Schema.
 *
 * Orchestrator tipis - hook ke wp_head, panggil SchemaGraphBuilder,
 * output <script type="application/ld+json"> (SCHEMA_MODULE_ARCHITECTURE.md §5.4).
 * Pola sama dengan Frontend.php General/Sitemap.
 *
 * @package Lunar\SEO\Modules\Schema
 */

namespace Lunar\SEO\Modules\Schema;

use Lunar\SEO\Modules\Schema\Services\SchemaGraphBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Frontend {

	/**
	 * @var SchemaGraphBuilder
	 */
	private SchemaGraphBuilder $graph_builder;

	/**
	 * @param SchemaGraphBuilder $graph_builder Perakit @graph JSON-LD.
	 */
	public function __construct( SchemaGraphBuilder $graph_builder ) {
		$this->graph_builder = $graph_builder;
	}

	/**
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_head', [ $this, 'render' ] );
	}

	/**
	 * Output script JSON-LD.
	 *
	 * Kalau "@graph" kosong (misal halaman 404/search yang tidak
	 * relevan schema apapun - WebSite/Organization SELALU applicable
	 * jadi @graph praktis tidak akan pernah benar-benar kosong,
	 * pengecekan ini tetap dipertahankan sebagai defense-in-depth),
	 * TIDAK ADA <script> yang dicetak sama sekali - konsisten
	 * ARCHITECTURE.md §10.
	 *
	 * @return void
	 */
	public function render(): void {
		$graph = $this->graph_builder->build();

		if ( empty( $graph['@graph'] ) ) {
			return;
		}

		/*
		 * CATATAN KEAMANAN: JANGAN tambahkan JSON_UNESCAPED_SLASHES di sini.
		 *
		 * Secara default, json_encode()/wp_json_encode() meng-escape setiap
		 * karakter "/" menjadi "\/". Ini bukan sekadar gaya penulisan -
		 * escaping ini mencegah urutan karakter "</script>" muncul utuh
		 * di dalam JSON yang ditanam ke tag <script> ini. Nilai seperti
		 * headline (ArticleNode/WebPageNode), nama kategori/page
		 * (BreadcrumbListNode), atau nama author TIDAK di-escape untuk
		 * konteks HTML di layer manapun sebelum sampai sini - kalau ada
		 * yang mengandung literal "</script>" (mis. role Editor yang
		 * secara default punya capability unfiltered_html di WP
		 * single-site), tag <script> ini akan tertutup prematur dan
		 * markup/script apa pun sesudahnya akan dieksekusi browser
		 * sebagai HTML/JS nyata (stored XSS). JSON_UNESCAPED_UNICODE
		 * aman dipertahankan - tidak menyentuh karakter "/".
		 */
		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode( $graph, JSON_UNESCAPED_UNICODE )
		);
	}
}

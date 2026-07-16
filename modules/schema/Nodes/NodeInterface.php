<?php
/**
 * Kontrak yang wajib diimplementasikan setiap Schema Node.
 *
 * Sengaja dibuat minimal (satu method) untuk menghindari
 * over-engineering (ENGINEERING_PRINCIPLES.md - "Hindari abstraction
 * yang belum diperlukan"), mengikuti pola ModuleInterface/
 * RendererInterface/ProviderInterface yang sudah terbukti di module
 * General dan Sitemap.
 *
 * Setiap Node MENENTUKAN SENDIRI apakah dirinya applicable pada
 * context saat ini (misal ArticleNode hanya applicable pada
 * is_singular('post')) - SchemaGraphBuilder (orchestrator) tidak
 * perlu tahu logic per-type, murni agregator
 * (SCHEMA_MODULE_ARCHITECTURE.md §5.3).
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface NodeInterface {

	/**
	 * Hasilkan array node JSON-LD siap masuk ke dalam "@graph", atau
	 * null apabila node ini tidak applicable pada context saat ini
	 * (bukan render array kosong - SCHEMA_MODULE_ARCHITECTURE.md §5.4,
	 * konsisten dengan ARCHITECTURE.md §10 - "Output frontend hanya
	 * dimuat apabila diperlukan").
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_node(): ?array;
}

<?php
/**
 * WebSite Node.
 *
 * Sitewide - output di semua halaman (SCHEMA_MODULE_ARCHITECTURE.md §5.5).
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

use Lunar\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WebSiteNode implements NodeInterface {

	/**
	 * @var SiteIdentity
	 */
	private SiteIdentity $site_identity;

	/**
	 * @param SiteIdentity $site_identity Shared service Site Identity.
	 */
	public function __construct( SiteIdentity $site_identity ) {
		$this->site_identity = $site_identity;
	}

	/**
	 * {@inheritDoc}
	 *
	 * WebSite selalu applicable (tidak pernah null) - berbeda dari
	 * Node lain yang bergantung context (Article/BreadcrumbList),
	 * WebSite adalah node dasar yang relevan di seluruh halaman,
	 * termasuk homepage, archive, search, maupun 404.
	 */
	public function get_node(): ?array {
		return [
			'@type'     => 'WebSite',
			'@id'       => SchemaId::website(),
			'url'       => home_url( '/' ),
			'name'      => $this->get_name(),
			'publisher' => [ '@id' => SchemaId::organization() ],
		];
	}

	/**
	 * Nama situs, fallback ke Site Title WordPress apabila Website
	 * Name (Site Info) belum diisi - konsisten dengan resolusi
	 * {site_name} pada module General
	 * (GENERAL_MODULE_ARCHITECTURE.md §5.2, PlaceholderResolver::get_site_name()).
	 *
	 * @return string
	 */
	private function get_name(): string {
		$name = $this->site_identity->get_website_name();

		return '' !== $name ? $name : get_bloginfo( 'name' );
	}
}

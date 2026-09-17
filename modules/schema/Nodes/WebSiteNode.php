<?php
/**
 * WebSite Node.
 *
 * Sitewide — output on every page.
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

use Lunar\SEO\Services\SiteIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WebSiteNode implements NodeInterface {

	private SiteIdentity $site_identity;

	public function __construct( SiteIdentity $site_identity ) {
		$this->site_identity = $site_identity;
	}

	/**
	 * {@inheritDoc}
	 *
	 * WebSite is always applicable (never null) — unlike context-dependent
	 * nodes (Article/BreadcrumbList), WebSite is a base node relevant on
	 * every page, including the homepage, archives, search, and 404.
	 */
	public function get_node(): ?array {
		return [
			'@type'     => 'WebSite',
			'@id'       => SchemaId::website(),
			'url'       => home_url( '/' ),
			'name'      => $this->site_identity->get_effective_website_name(),
			'publisher' => [ '@id' => SchemaId::organization() ],
		];
	}
}
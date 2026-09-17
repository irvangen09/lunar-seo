<?php
/**
 * Organization Node.
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

final class OrganizationNode implements NodeInterface {

	private SiteIdentity $site_identity;

	public function __construct( SiteIdentity $site_identity ) {
		$this->site_identity = $site_identity;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Organization is always applicable (never null), same rationale as
	 * WebSiteNode.
	 */
	public function get_node(): ?array {
		$node = [
			'@type' => 'Organization',
			'@id'   => SchemaId::organization(),
			'name'  => $this->site_identity->get_effective_website_name(),
			'url'   => home_url( '/' ),
		];

		$alternate_name = $this->site_identity->get_alternate_website_name();

		if ( '' !== $alternate_name ) {
			$node['alternateName'] = $alternate_name;
		}

		$logo = $this->get_logo();

		if ( null !== $logo ) {
			$node['logo'] = $logo;
		}

		return $node;
	}

	/**
	 * Logo as a nested ImageObject (url, width, height). The "logo"
	 * field is skipped ENTIRELY (not rendered empty) if site_image_id
	 * hasn't been filled in, or the attachment is no longer valid (e.g.
	 * the media was deleted from the Media Library).
	 *
	 * No license/creator/creditText/copyrightNotice field at all — the
	 * logo isn't a gameplay screenshot, but the same "don't make claims
	 * that can't be verified" principle is applied consistently across
	 * the whole Schema module.
	 */
	private function get_logo(): ?array {
		$site_image_id = $this->site_identity->get_site_image_id();

		if ( $site_image_id <= 0 ) {
			return null;
		}

		$src = wp_get_attachment_image_src( $site_image_id, 'full' );

		if ( false === $src ) {
			return null;
		}

		return [
			'@type'  => 'ImageObject',
			'url'    => $src[0],
			'width'  => $src[1],
			'height' => $src[2],
		];
	}
}
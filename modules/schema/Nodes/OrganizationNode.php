<?php
/**
 * Organization Node.
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

final class OrganizationNode implements NodeInterface {

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
	 * Organization selalu applicable (tidak pernah null), sama alasan
	 * dengan WebSiteNode.
	 */
	public function get_node(): ?array {
		$node = [
			'@type' => 'Organization',
			'@id'   => SchemaId::organization(),
			'name'  => $this->get_name(),
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
	 * Nama organisasi, fallback ke Site Title WordPress - konsisten
	 * dengan WebSiteNode::get_name() (satu sumber "nama situs" yang
	 * sama dipakai kedua node ini).
	 *
	 * @return string
	 */
	private function get_name(): string {
		$name = $this->site_identity->get_website_name();

		return '' !== $name ? $name : get_bloginfo( 'name' );
	}

	/**
	 * Logo sebagai nested ImageObject (url, width, height). Field
	 * "logo" di-skip SELURUHNYA (bukan render kosong) apabila
	 * site_image_id belum diisi atau attachment sudah tidak valid
	 * (misal media sudah dihapus dari Media Library) - konsisten pola
	 * "Kondisi Skip Output" GENERAL_MODULE_ARCHITECTURE.md §6.5.
	 *
	 * Tidak ada field license/creator/creditText/copyrightNotice sama
	 * sekali, sesuai LUNAR_SEO_IMAGEOBJECT_ARCHITECTURE_BRIEF_REVISED.md §5
	 * (LOCKED di dokumen sumbernya) - logo Organization bukan
	 * screenshot gameplay, tapi prinsip "tidak membuat klaim yang
	 * tidak dapat diverifikasi" tetap berlaku secara konsisten di
	 * seluruh module Schema.
	 *
	 * @return array<string, mixed>|null
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

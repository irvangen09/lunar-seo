<?php
/**
 * Schema Id.
 *
 * Builds the "@id" string for each node. Centralized in one place
 * (instead of retyped in every Node class) because several Nodes
 * reference each other's @id (e.g. ArticleNode.publisher ->
 * Organization::id()), so the risk of a typo/inconsistency across
 * classes is real.
 *
 * Not a Node itself — this class does not implement NodeInterface, it
 * is a pure string helper and never produces a JSON-LD node of its
 * own.
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaId {

	public static function website(): string {
		return home_url( '/' ) . '#website';
	}

	public static function organization(): string {
		return home_url( '/' ) . '#organization';
	}

	public static function breadcrumb( string $permalink ): string {
		return $permalink . '#breadcrumb';
	}

	public static function article( string $permalink ): string {
		return $permalink . '#article';
	}

	public static function webpage( string $permalink ): string {
		return $permalink . '#webpage';
	}

	public static function primary_image( string $permalink ): string {
		return $permalink . '#primaryimage';
	}
}
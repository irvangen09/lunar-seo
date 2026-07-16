<?php
/**
 * Schema Id.
 *
 * Pembangun string "@id" untuk tiap node, mengikuti skema yang
 * didefinisikan di SCHEMA_MODULE_ARCHITECTURE.md §5.2. Disentralkan
 * di satu tempat (bukan diketik ulang di setiap Node class) karena
 * beberapa Node saling mereferensi @id milik Node lain (misal
 * ArticleNode.publisher -> Organization::id()), sehingga risiko typo/
 * inkonsistensi antar class jadi nyata (CODING_STANDARD.md §2 - DRY).
 *
 * Bukan Node - class ini tidak mengimplementasikan NodeInterface,
 * murni helper string, tidak menghasilkan node JSON-LD apapun.
 *
 * @package Lunar\SEO\Modules\Schema\Nodes
 */

namespace Lunar\SEO\Modules\Schema\Nodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SchemaId {

	/**
	 * @return string
	 */
	public static function website(): string {
		return home_url( '/' ) . '#website';
	}

	/**
	 * @return string
	 */
	public static function organization(): string {
		return home_url( '/' ) . '#organization';
	}

	/**
	 * @param string $permalink Permalink halaman/post saat ini.
	 * @return string
	 */
	public static function breadcrumb( string $permalink ): string {
		return $permalink . '#breadcrumb';
	}

	/**
	 * @param string $permalink Permalink post saat ini.
	 * @return string
	 */
	public static function article( string $permalink ): string {
		return $permalink . '#article';
	}

	/**
	 * @param string $permalink Permalink page saat ini.
	 * @return string
	 */
	public static function webpage( string $permalink ): string {
		return $permalink . '#webpage';
	}

	/**
	 * @param string $permalink Permalink post/page saat ini.
	 * @return string
	 */
	public static function primary_image( string $permalink ): string {
		return $permalink . '#primaryimage';
	}
}

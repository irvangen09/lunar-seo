<?php

namespace Lunar\SEO\Modules\Schema;

use Lunar\SEO\ModuleInterface;
use Lunar\SEO\Services\AdminMenu;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;
use Lunar\SEO\Services\SupportedPostTypes;
use Lunar\SEO\Modules\Schema\Nodes\ArticleNode;
use Lunar\SEO\Modules\Schema\Nodes\BreadcrumbListNode;
use Lunar\SEO\Modules\Schema\Nodes\ImageObjectNode;
use Lunar\SEO\Modules\Schema\Nodes\OrganizationNode;
use Lunar\SEO\Modules\Schema\Nodes\WebPageNode;
use Lunar\SEO\Modules\Schema\Nodes\WebSiteNode;
use Lunar\SEO\Modules\Schema\Services\SchemaGraphBuilder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This module intentionally has no Settings/Admin/Assets/Editor - all
// 5 schema nodes are fully derived from existing data (SiteIdentity +
// native WordPress post data), so there is no per-post or per-site
// decision that needs a UI.
final class Module implements ModuleInterface {

	private const SLUG = 'schema';

	private SiteIdentity $site_identity;

	private SupportedPostTypes $supported_post_types;

	// $option_manager and $admin_menu are unused here - they're still
	// accepted so ModuleRegistry can instantiate every module with the
	// same constructor signature.
	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu, SupportedPostTypes $supported_post_types ) {
		$this->site_identity        = $site_identity;
		$this->supported_post_types = $supported_post_types;
	}

	public function get_slug(): string {
		return self::SLUG;
	}

	public function init(): void {
		$this->boot_frontend();
	}

	private function boot_frontend(): void {
		$image_object_node = new ImageObjectNode();

		// Array order has no effect on the output - each node decides
		// for itself whether it's applicable via get_node().
		$nodes = [
			new WebSiteNode( $this->site_identity ),
			new OrganizationNode( $this->site_identity ),
			new BreadcrumbListNode(),
			new ArticleNode( $image_object_node, $this->supported_post_types ),
			new WebPageNode( $image_object_node, $this->supported_post_types ),
		];

		$graph_builder = new SchemaGraphBuilder( $nodes );

		$frontend = new Frontend( $graph_builder );
		$frontend->init();
	}
}
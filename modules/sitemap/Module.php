<?php

namespace Lunar\SEO\Modules\Sitemap;

use Lunar\SEO\ModuleInterface;
use Lunar\SEO\Services\AdminMenu;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;
use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// This module has no Editor.php - Sitemap has no per-post Gutenberg
// integration; content exclusion is handled via Excluded Items in
// Global Settings instead.
final class Module implements ModuleInterface {

	private const SLUG = 'sitemap';

	private OptionManager $option_manager;

	// Stored as a property (not a local variable inside boot_admin())
	// so Assets:: can reuse the same instance's hook_suffix - same
	// pattern as the General module.
	private Admin $admin;

	private AdminMenu $admin_menu;

	// $site_identity and $supported_post_types are unused here - they're
	// still accepted so ModuleRegistry can instantiate every module with
	// the same constructor signature.
	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu, SupportedPostTypes $supported_post_types ) {
		$this->option_manager = $option_manager;
		$this->admin_menu     = $admin_menu;
	}

	public function get_slug(): string {
		return self::SLUG;
	}

	public function init(): void {
		$this->boot_settings();
		$this->boot_admin();
		$this->boot_frontend();
		$this->boot_assets();
	}

	private function boot_settings(): void {
		( new Settings\Settings( $this->option_manager ) )->init();
	}

	private function boot_admin(): void {
		$this->admin = new Admin( $this->admin_menu );
		$this->admin->init();
	}

	private function boot_frontend(): void {
		( new Frontend( $this->option_manager ) )->init();
	}

	private function boot_assets(): void {
		( new Assets( $this->admin ) )->init();
	}
}
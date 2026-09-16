<?php

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\ModuleInterface;
use Lunar\SEO\Services\AdminMenu;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;
use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Module implements ModuleInterface {

	private const SLUG = 'general';

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	private AdminMenu $admin_menu;

	private SupportedPostTypes $supported_post_types;

	// Stored as a property (not a local variable inside boot_admin())
	// so Assets:: can reuse the same instance's hook_suffix.
	private Admin $admin;

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu, SupportedPostTypes $supported_post_types ) {
		$this->option_manager       = $option_manager;
		$this->site_identity        = $site_identity;
		$this->admin_menu           = $admin_menu;
		$this->supported_post_types = $supported_post_types;
	}

	public function get_slug(): string {
		return self::SLUG;
	}

	public function init(): void {
		$this->boot_settings();
		$this->boot_admin();
		$this->boot_editor();
		$this->boot_frontend();
		$this->boot_url_rewriter();
		$this->boot_assets();
	}

	private function boot_settings(): void {
		( new Settings\Settings( $this->option_manager, $this->site_identity ) )->init();
	}

	private function boot_admin(): void {
		$this->admin = new Admin( $this->admin_menu );
		$this->admin->init();
	}

	private function boot_editor(): void {
		$editor = new Editor( $this->option_manager, $this->supported_post_types );
		$editor->init();

		// Only ever registers itself where the Block Editor isn't active
		// for a supported post type (see MetaBox::should_show_on()), so
		// this never overlaps with the PluginSidebar Editor registers above.
		( new MetaBox( $editor, $this->supported_post_types ) )->init();
	}

	private function boot_frontend(): void {
		( new Frontend( $this->option_manager, $this->site_identity, $this->supported_post_types ) )->init();
	}

	private function boot_url_rewriter(): void {
		( new UrlRewriter( $this->option_manager ) )->init();
	}

	private function boot_assets(): void {
		( new Assets( $this->admin, $this->supported_post_types ) )->init();
	}
}
<?php

namespace Lunar\SEO;

use Lunar\SEO\Services\AdminMenu;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;
use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ModuleRegistry {

	private array $available_modules = [
		\Lunar\SEO\Modules\General\Module::class,
		\Lunar\SEO\Modules\Sitemap\Module::class,
		\Lunar\SEO\Modules\Schema\Module::class,
	];

	private array $active_modules = [];

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	private AdminMenu $admin_menu;

	private SupportedPostTypes $supported_post_types;

	public function __construct( OptionManager $option_manager, SiteIdentity $site_identity, AdminMenu $admin_menu, SupportedPostTypes $supported_post_types ) {
		$this->option_manager       = $option_manager;
		$this->site_identity        = $site_identity;
		$this->admin_menu           = $admin_menu;
		$this->supported_post_types = $supported_post_types;
	}

	public function register_active_modules(): void {
		foreach ( $this->available_modules as $module_class ) {
			if ( ! class_exists( $module_class ) ) {
				continue;
			}

			// Every module receives the same constructor signature, even
			// arguments a particular module doesn't use yet, so
			// instantiation here stays uniform instead of special-casing
			// per module.
			$module = new $module_class( $this->option_manager, $this->site_identity, $this->admin_menu, $this->supported_post_types );

			if ( ! $module instanceof ModuleInterface ) {
				continue;
			}

			if ( ! $this->is_module_active( $module->get_slug() ) ) {
				continue;
			}

			$module->init();

			$this->active_modules[ $module->get_slug() ] = $module;
		}
	}

	private function is_module_active( string $module_slug ): bool {
		/**
		 * Filters whether a Lunar SEO module (general, sitemap, schema)
		 * should be initialized on this request.
		 *
		 * @since Unreleased
		 *
		 * @param bool   $active      Whether the module is active. Default true.
		 * @param string $module_slug The module's slug, as returned by its
		 *                            Module::get_slug().
		 */
		return (bool) apply_filters( 'lunar_seo_module_is_active', true, $module_slug );
	}

	public function get_active_modules(): array {
		return $this->active_modules;
	}
}
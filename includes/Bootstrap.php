<?php

namespace Lunar\SEO;

use Lunar\SEO\Services\AdminMenu;
use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SiteIdentity;
use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bootstrap {

	private static ?self $instance = null;

	private OptionManager $option_manager;

	private SiteIdentity $site_identity;

	private AdminMenu $admin_menu;

	private SupportedPostTypes $supported_post_types;

	private ModuleRegistry $module_registry;

	private ?string $environment_error = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function run(): void {
		if ( ! $this->environment_check() ) {
			$this->show_environment_notice();
			return;
		}

		$this->load_textdomain();
		$this->register_shared_services();
		$this->register_modules();
	}

	private function load_textdomain(): void {
		// WordPress.org auto-loads translations for hosted plugins since
		// WP 4.6, but that doesn't apply to self-distributed plugins.
		load_plugin_textdomain( 'lunar-seo', false, dirname( LUNAR_SEO_BASENAME ) . '/languages' );
	}

	private function environment_check(): bool {
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			$this->environment_error = sprintf(
				/* translators: %s: minimum required PHP version. */
				__( 'Lunar SEO requires PHP %s or higher. Please contact your hosting provider to upgrade PHP.', 'lunar-seo' ),
				'8.0'
			);

			return false;
		}

		global $wp_version;

		// WordPress core already enforces "Requires PHP"/"Requires at
		// least" headers since 5.2+; this check is a defense-in-depth
		// fallback for load paths outside that mechanism.
		if ( isset( $wp_version ) && version_compare( $wp_version, '6.9', '<' ) ) {
			$this->environment_error = sprintf(
				/* translators: %s: minimum required WordPress version. */
				__( 'Lunar SEO requires WordPress %s or higher. Please update WordPress.', 'lunar-seo' ),
				'6.9'
			);

			return false;
		}

		return true;
	}

	private function show_environment_notice(): void {
		$message = $this->environment_error;

		add_action(
			'admin_notices',
			static function () use ( $message ) {
				printf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html( (string) $message )
				);
			}
		);
	}

	private function register_shared_services(): void {
		$this->option_manager       = new OptionManager();
		$this->site_identity        = new SiteIdentity( $this->option_manager );
		$this->admin_menu           = new AdminMenu();
		$this->supported_post_types = new SupportedPostTypes();
	}

	private function register_modules(): void {
		$this->module_registry = new ModuleRegistry(
			$this->option_manager,
			$this->site_identity,
			$this->admin_menu,
			$this->supported_post_types
		);
		$this->module_registry->register_active_modules();
	}
}
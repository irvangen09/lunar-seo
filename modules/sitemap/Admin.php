<?php
/**
 * Admin.
 *
 * Registers the admin submenu and renders the root container for the
 * React admin app. Same pattern as modules/general/Admin.php.
 *
 * Registered as a SUBMENU under the "Lunar SEO" top-level menu (its
 * slug is owned by the AdminMenu Shared Service, not by the General
 * module directly — see includes/Services/AdminMenu.php), keeping one
 * consistent navigation entry point across every module in the
 * plugin, without this module reaching into General's own class
 * directly.
 *
 * @package Lunar\SEO\Modules\Sitemap
 */

namespace Lunar\SEO\Modules\Sitemap;

use Lunar\SEO\Services\AdminMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	private AdminMenu $admin_menu;

	public const MENU_SLUG = 'lunar-seo-sitemap';

	private const ROOT_ELEMENT_ID = 'lunar-seo-sitemap-settings-root';

	private ?string $hook_suffix = null;

	public function __construct( AdminMenu $admin_menu ) {
		$this->admin_menu = $admin_menu;
	}

	/**
	 * Priority is later (20) than the top-level menu registration in
	 * General (default 10), making sure the "Lunar SEO" top-level menu
	 * is already registered before this submenu is added.
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 20 );
	}

	public function register_menu(): void {
		$this->hook_suffix = add_submenu_page(
			$this->admin_menu->get_top_level_slug(),
			__( 'Lunar SEO - Sitemap', 'lunar-seo' ),
			__( 'Sitemap', 'lunar-seo' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Just an empty root container for the React app.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div id="%s" class="lunar-settings"></div>',
			esc_attr( self::ROOT_ELEMENT_ID )
		);
	}

	public function get_hook_suffix(): ?string {
		return $this->hook_suffix;
	}
}
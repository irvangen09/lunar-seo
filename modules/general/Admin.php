<?php
/**
 * Admin.
 *
 * Registers the admin menu and renders the root container for the
 * React admin app. Settings data is read by the JS app through the
 * custom REST route (lunar-seo/v1/general-settings) — not read
 * directly by PHP here — so this class has no need for OptionManager.
 *
 * The "Lunar SEO" top-level menu slug is owned by the AdminMenu Shared
 * Service (includes/Services/AdminMenu.php), not by this class
 * directly, so other modules (Sitemap, Schema, etc.) that need to
 * register a submenu under it never have to reach into General's own
 * class.
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\Services\AdminMenu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	private const ROOT_ELEMENT_ID = 'lunar-seo-general-settings-root';

	private AdminMenu $admin_menu;

	/**
	 * The ACTUAL hook suffix returned by add_menu_page(), used by
	 * Assets.php to restrict its enqueue to this one page.
	 *
	 * Stored as the real returned value (not guessed via string
	 * concatenation), so there's no risk of a mismatch with
	 * WordPress's own hook suffix format.
	 */
	private ?string $hook_suffix = null;

	public function __construct( AdminMenu $admin_menu ) {
		$this->admin_menu = $admin_menu;
	}

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	public function register_menu(): void {
		$this->hook_suffix = add_menu_page(
			__( 'Lunar SEO', 'lunar-seo' ),
			__( 'SEO', 'lunar-seo' ),
			'manage_options',
			$this->admin_menu->get_top_level_slug(),
			[ $this, 'render_page' ],
			'dashicons-search',
			80
		);
	}

	/**
	 * Just an empty root container — the entire UI (Site Info, Content,
	 * Categories & Tags, etc.) is rendered by the React app enqueued
	 * via Assets.php.
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

	/**
	 * Null if admin_menu hasn't run yet.
	 */
	public function get_hook_suffix(): ?string {
		return $this->hook_suffix;
	}
}
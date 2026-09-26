<?php
/**
 * Plugin Name:       Lunar SEO
 * Description:       A lightweight, modular WordPress SEO plugin that follows WordPress Coding Standards.
 * Version:           1.1.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Irvan Noerfazri
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lunar-seo
 *
 * @package Lunar\SEO
 */

namespace Lunar\SEO;

// Block direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LUNAR_SEO_VERSION', '1.1.0' );
define( 'LUNAR_SEO_FILE', __FILE__ );
define( 'LUNAR_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'LUNAR_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'LUNAR_SEO_BASENAME', plugin_basename( __FILE__ ) );

$lunar_seo_autoloader = LUNAR_SEO_PATH . 'vendor/autoload.php';

if ( ! file_exists( $lunar_seo_autoloader ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Lunar SEO: Dependencies are not installed. Please run "composer install".', 'lunar-seo' )
			);
		}
	);
	return;
}

require_once $lunar_seo_autoloader;

add_action(
	'plugins_loaded',
	static function () {
		Bootstrap::instance()->run();
	}
);
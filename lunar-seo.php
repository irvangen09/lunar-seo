<?php
/**
 * Plugin Name:       Lunar SEO
 * Description:       Plugin SEO WordPress yang ringan, modular, dan mengikuti WordPress Coding Standards.
 * Version:           1.0.1
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

// Cegah akses langsung ke file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin identity constants.
 *
 * Nilai-nilai ini mengikuti Plugin Identity yang telah dikunci
 * pada PLUGIN_BLUEPRINT.md §3.
 */
define( 'LUNAR_SEO_VERSION', '1.0.1' );
define( 'LUNAR_SEO_FILE', __FILE__ );
define( 'LUNAR_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'LUNAR_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'LUNAR_SEO_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load Composer autoloader.
 *
 * Autoloader wajib ada sebelum class apapun dipanggil.
 * Jika tidak ditemukan, plugin tidak dapat berjalan dan harus
 * gagal secara aman (Fail Gracefully - CODING_STANDARD.md §11).
 */
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

/**
 * Boot plugin melalui Bootstrap.
 *
 * Bootstrap hanya bertanggung jawab menginisialisasi plugin
 * sesuai lifecycle pada ARCHITECTURE.md §21. Tidak ada business
 * logic di file ini maupun di Bootstrap.
 */
add_action(
	'plugins_loaded',
	static function () {
		Bootstrap::instance()->run();
	}
);

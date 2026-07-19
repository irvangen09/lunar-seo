<?php
/**
 * Editor.
 *
 * Registrasi post meta untuk override per-post (SEO Title, Meta
 * Description, Canonical URL, Robots). Enqueue asset editor menjadi
 * tanggung jawab Assets.php (Single Responsibility).
 *
 * Meta key diawali underscore ("_lunar_seo_...") agar otomatis
 * tersembunyi dari metabox Custom Fields bawaan WordPress (plugin
 * ini menyediakan UI sendiri lewat Gutenberg sidebar), sekaligus
 * tetap ter-expose ke REST API lewat show_in_rest agar dapat diakses
 * React app (GENERAL_MODULE_ARCHITECTURE.md §4).
 *
 * @package Lunar\SEO\Modules\General
 */

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\Services\OptionManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Editor {

	/**
	 * Post type yang didukung override per-post.
	 *
	 * Sesuai scope Content section (Settings/Content.php) yang
	 * memiliki template Post & Page - Categories/Tags dikelola
	 * lewat term (bukan post meta), sehingga tidak termasuk di sini.
	 *
	 * @var string[]
	 */
	private const SUPPORTED_POST_TYPES = [ 'post', 'page' ];

	/**
	 * Directive robots yang diizinkan (whitelist), konsisten dengan
	 * Settings/RobotsUrl.php. "index"/"follow" sengaja tidak
	 * termasuk - lihat penjelasan di sana.
	 *
	 * @var string[]
	 */
	private const ALLOWED_ROBOTS_DIRECTIVES = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

	/**
	 * Shared service Option Manager - dipakai pada tahap berikutnya
	 * untuk resolusi nilai fallback/preview.
	 *
	 * @var OptionManager
	 */
	private OptionManager $option_manager;

	/**
	 * @param OptionManager $option_manager Shared service Option Manager.
	 */
	public function __construct( OptionManager $option_manager ) {
		$this->option_manager = $option_manager;
	}

	/**
	 * Inisialisasi - hook registrasi post meta.
	 *
	 * Enqueue asset editor menjadi tanggung jawab Assets.php
	 * (Single Responsibility - satu file, satu tanggung jawab),
	 * bukan Editor.php.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_meta' ] );
	}

	/**
	 * Registrasikan seluruh post meta override untuk tiap post type
	 * yang didukung.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		foreach ( self::SUPPORTED_POST_TYPES as $post_type ) {
			$this->register_meta_for_post_type( $post_type );
		}
	}

	/**
	 * Registrasikan post meta untuk satu post type.
	 *
	 * @param string $post_type Post type target.
	 * @return void
	 */
	private function register_meta_for_post_type( string $post_type ): void {
		register_post_meta(
			$post_type,
			PostMetaKeys::TITLE,
			[
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => [ $this, 'can_edit_meta' ],
				'show_in_rest'      => true,
			]
		);

		register_post_meta(
			$post_type,
			PostMetaKeys::DESCRIPTION,
			[
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => [ $this, 'can_edit_meta' ],
				'show_in_rest'      => true,
			]
		);

		register_post_meta(
			$post_type,
			PostMetaKeys::CANONICAL,
			[
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => [ $this, 'can_edit_meta' ],
				'show_in_rest'      => true,
			]
		);

		register_post_meta(
			$post_type,
			PostMetaKeys::ROBOTS,
			[
				'type'              => 'array',
				'single'            => true,
				'default'           => [],
				'sanitize_callback' => [ $this, 'sanitize_robots_override' ],
				'auth_callback'     => [ $this, 'can_edit_meta' ],
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [ 'type' => 'string' ],
					],
				],
			]
		);
	}

	/**
	 * Sanitasi Robots override terhadap whitelist directive.
	 *
	 * @param mixed $value Nilai mentah dari REST/editor.
	 * @return string[]
	 */
	public function sanitize_robots_override( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values(
			array_intersect( array_unique( $value ), self::ALLOWED_ROBOTS_DIRECTIVES )
		);
	}

	/**
	 * Auth callback - hanya user yang boleh mengedit post
	 * bersangkutan yang boleh mengubah meta SEO-nya.
	 *
	 * @param bool   $allowed  Status izin default.
	 * @param string $meta_key Meta key yang diperiksa.
	 * @param int    $post_id  ID post terkait.
	 * @return bool
	 */
	public function can_edit_meta( bool $allowed, string $meta_key, int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}
}

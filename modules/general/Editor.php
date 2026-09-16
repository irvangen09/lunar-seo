<?php

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\Services\OptionManager;
use Lunar\SEO\Services\SupportedPostTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Editor {

	private const ALLOWED_ROBOTS_DIRECTIVES = [ 'noindex', 'nofollow', 'noarchive', 'nosnippet', 'noimageindex' ];

	private OptionManager $option_manager;

	private SupportedPostTypes $supported_post_types;

	public function __construct( OptionManager $option_manager, SupportedPostTypes $supported_post_types ) {
		$this->option_manager       = $option_manager;
		$this->supported_post_types = $supported_post_types;
	}

	public function init(): void {
		add_action( 'init', [ $this, 'register_meta' ] );
	}

	public function register_meta(): void {
		foreach ( array_keys( $this->supported_post_types->all() ) as $post_type ) {
			$this->register_meta_for_post_type( $post_type );
		}
	}

	private function register_meta_for_post_type( string $post_type ): void {
		// Keys are prefixed with an underscore (see PostMetaKeys), which
		// WordPress hides from the default Custom Fields metabox while
		// still exposing them to REST via show_in_rest below.
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

	public function sanitize_robots_override( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values(
			array_intersect( array_unique( $value ), self::ALLOWED_ROBOTS_DIRECTIVES )
		);
	}

	/**
	 * Exposes the whitelist to other consumers of the same data contract
	 * (currently MetaBox, the Classic Editor fallback) so it isn't
	 * duplicated as a separate literal array.
	 */
	public function get_allowed_robots_directives(): array {
		return self::ALLOWED_ROBOTS_DIRECTIVES;
	}

	public function can_edit_meta( bool $allowed, string $meta_key, int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}
}
<?php
/**
 * Description Generator.
 *
 * Generates a rule-based (NOT generative AI) fallback Meta Description
 * when the admin/author hasn't filled one in manually.
 *
 * Fallback order:
 * 1. WordPress's manual excerpt (has_excerpt()).
 * 2. The first meaningful paragraph of the content.
 * 3. Trimmed to ~160 characters without cutting a word in half.
 *
 * This class does NOT use PlaceholderResolver — unlike the Meta
 * Description template in Global Settings (which supports
 * placeholders), this fallback is a pure extraction from the post's
 * own content, not a template. The description is static (the same
 * for every visitor) — it isn't generated dynamically per visitor
 * search query, since there's no technical way for the server to
 * know a visitor's search query at render time.
 *
 * @package Lunar\SEO\Modules\General\Services
 */

namespace Lunar\SEO\Modules\General\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DescriptionGenerator {

	private const MAX_LENGTH = 160;

	/**
	 * Per-post-ID cache within a single request. This class's instance
	 * is shared across MetaRenderer, OpenGraphRenderer, and
	 * TwitterCardRenderer (Frontend::register_renderers()) — on a page
	 * with Meta Description + Open Graph + Twitter Card all enabled,
	 * generate() would otherwise be called for the SAME post up to 3
	 * times per request.
	 *
	 * @var array<int, string>
	 */
	private array $cache = [];

	public function generate( \WP_Post $post ): string {
		if ( isset( $this->cache[ $post->ID ] ) ) {
			return $this->cache[ $post->ID ];
		}

		$description = $this->get_manual_excerpt( $post );

		if ( '' === $description ) {
			$description = $this->get_first_paragraph( $post );
		}

		$result = $this->trim_to_length( $description, self::MAX_LENGTH );

		$this->cache[ $post->ID ] = $result;

		return $result;
	}

	private function get_manual_excerpt( \WP_Post $post ): string {
		if ( ! has_excerpt( $post ) ) {
			return '';
		}

		return trim( wp_strip_all_tags( $post->post_excerpt ) );
	}

	/**
	 * Shortcodes and HTML tags are stripped first so they can't end up
	 * cut in half by the trim below.
	 */
	private function get_first_paragraph( \WP_Post $post ): string {
		$content = strip_shortcodes( $post->post_content );
		$content = wp_strip_all_tags( $content );
		$content = trim( $content );

		if ( '' === $content ) {
			return '';
		}

		$lines = preg_split( '/\r\n|\r|\n/', $content );

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' !== $line ) {
				return $line;
			}
		}

		return '';
	}

	/**
	 * Trims text to a maximum length without cutting a word in half.
	 * Encoding is passed explicitly to mb_strlen()/mb_substr() rather
	 * than relying on the server's mb_internal_encoding() default.
	 */
	private function trim_to_length( string $text, int $max_length ): string {
		if ( mb_strlen( $text, 'UTF-8' ) <= $max_length ) {
			return $text;
		}

		$trimmed    = mb_substr( $text, 0, $max_length, 'UTF-8' );
		$last_space = strrpos( $trimmed, ' ' );

		if ( false !== $last_space ) {
			$trimmed = substr( $trimmed, 0, $last_space );
		}

		return rtrim( $trimmed ) . '…';
	}
}
<?php
/**
 * Social Meta Trait.
 *
 * Shared output/resolution logic for Open Graph and Twitter Card meta
 * tags — both surface the same title/description/image data under
 * their own tag names, so that resolution logic lives here once. Each
 * consuming class still declares its own toggle, its own Default Image
 * setting, and its own tag names independently, so Open Graph and
 * Twitter Card remain fully independent settings — this trait only
 * removes the duplication that had nothing to do with that
 * independence in the first place.
 *
 * Requires the consuming class to have $placeholder_resolver
 * (PlaceholderResolver), $description_generator (DescriptionGenerator),
 * and $site_identity (SiteIdentity) as properties, and to implement
 * meta_attribute() below.
 *
 * @package Lunar\SEO\Modules\General\Renderers
 */

namespace Lunar\SEO\Modules\General\Renderers;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait SocialMetaTrait {

	/**
	 * The meta tag attribute the consuming class uses to identify its
	 * tags — Open Graph uses "property" (og:title, og:image, ...),
	 * Twitter Card uses "name" (twitter:title, twitter:image, ...).
	 */
	abstract protected function meta_attribute(): string;

	private function output_tag( string $key, string $value ): void {
		if ( '' === $value ) {
			return;
		}

		printf(
			'<meta %s="%s" content="%s" />' . "\n",
			esc_attr( $this->meta_attribute() ),
			esc_attr( $key ),
			esc_attr( $value )
		);
	}

	/**
	 * Separate from output_tag() because a URL value needs esc_url() —
	 * not esc_attr() — for correct context-appropriate escaping.
	 */
	private function output_url_tag( string $key, string $url ): void {
		if ( '' === $url ) {
			return;
		}

		printf(
			'<meta %s="%s" content="%s" />' . "\n",
			esc_attr( $this->meta_attribute() ),
			esc_attr( $key ),
			esc_url( $url )
		);
	}

	private function resolve_title(): string {
		if ( is_front_page() ) {
			return $this->placeholder_resolver->get_homepage_title();
		}

		if ( is_singular() ) {
			return get_the_title();
		}

		if ( is_category() || is_tag() ) {
			return single_term_title( '', false );
		}

		return '';
	}

	private function resolve_description(): string {
		if ( ! is_singular() ) {
			return '';
		}

		$post = get_post();

		return $post instanceof WP_Post ? $this->description_generator->generate( $post ) : '';
	}

	/**
	 * Resolves the image URL with priority: the current post's Featured
	 * Image -> the module's own Default Image setting (Global Settings)
	 * -> the sitewide Site Image (Site Info). Empty string if none are
	 * available.
	 */
	private function resolve_image_url( int $default_image_id ): string {
		if ( is_singular() && has_post_thumbnail() ) {
			$url = get_the_post_thumbnail_url( null, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		if ( $default_image_id > 0 ) {
			$url = wp_get_attachment_image_url( $default_image_id, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		$site_image_id = $this->site_identity->get_site_image_id();

		if ( $site_image_id > 0 ) {
			$url = wp_get_attachment_image_url( $site_image_id, 'full' );

			if ( false !== $url ) {
				return $url;
			}
		}

		return '';
	}
}
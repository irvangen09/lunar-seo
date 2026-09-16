<?php

namespace Lunar\SEO\Modules\General;

use Lunar\SEO\Services\SupportedPostTypes;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classic Editor fallback for the per-post SEO fields already exposed to
 * the Block Editor via Editor::register_meta(). Only adds itself where
 * the Block Editor isn't in use for a supported post type, so the two
 * surfaces never appear on the same screen.
 */
final class MetaBox {

	private const BOX_ID = 'lunar-seo-meta-box';

	private const NONCE_ACTION = 'lunar_seo_meta_box';

	private const NONCE_FIELD = 'lunar_seo_meta_box_nonce';

	private Editor $editor;

	private SupportedPostTypes $supported_post_types;

	public function __construct( Editor $editor, SupportedPostTypes $supported_post_types ) {
		$this->editor               = $editor;
		$this->supported_post_types = $supported_post_types;
	}

	public function init(): void {
		add_action( 'add_meta_boxes', [ $this, 'register' ], 10, 2 );
		add_action( 'save_post', [ $this, 'save' ], 10, 2 );
	}

	public function register( string $post_type, WP_Post $post ): void {
		if ( ! $this->should_show_on( $post_type ) ) {
			return;
		}

		add_meta_box(
			self::BOX_ID,
			__( 'Lunar SEO', 'lunar-seo' ),
			[ $this, 'render' ],
			$post_type,
			'normal',
			'high'
		);
	}

	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$title       = get_post_meta( $post->ID, PostMetaKeys::TITLE, true );
		$description = get_post_meta( $post->ID, PostMetaKeys::DESCRIPTION, true );
		$canonical   = get_post_meta( $post->ID, PostMetaKeys::CANONICAL, true );
		$robots      = get_post_meta( $post->ID, PostMetaKeys::ROBOTS, true );
		$robots      = is_array( $robots ) ? $robots : [];
		?>
		<p>
			<label for="lunar-seo-title"><?php esc_html_e( 'SEO Title', 'lunar-seo' ); ?></label><br />
			<input
				type="text"
				id="lunar-seo-title"
				name="lunar_seo_title"
				class="widefat"
				value="<?php echo esc_attr( $title ); ?>"
			/>
		</p>
		<p>
			<label for="lunar-seo-description"><?php esc_html_e( 'Meta Description', 'lunar-seo' ); ?></label><br />
			<textarea
				id="lunar-seo-description"
				name="lunar_seo_description"
				class="widefat"
				rows="3"
			><?php echo esc_textarea( $description ); ?></textarea>
		</p>
		<p>
			<label for="lunar-seo-canonical"><?php esc_html_e( 'Canonical URL', 'lunar-seo' ); ?></label><br />
			<input
				type="url"
				id="lunar-seo-canonical"
				name="lunar_seo_canonical"
				class="widefat"
				value="<?php echo esc_attr( $canonical ); ?>"
				placeholder="<?php echo esc_attr( get_permalink( $post ) ); ?>"
			/>
		</p>
		<p><?php esc_html_e( 'Robots', 'lunar-seo' ); ?></p>
		<?php foreach ( $this->editor->get_allowed_robots_directives() as $directive ) : ?>
			<label style="display: block;">
				<input
					type="checkbox"
					name="lunar_seo_robots[]"
					value="<?php echo esc_attr( $directive ); ?>"
					<?php checked( in_array( $directive, $robots, true ) ); ?>
				/>
				<?php echo esc_html( $directive ); ?>
			</label>
		<?php endforeach; ?>
		<?php
	}

	public function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! $this->should_show_on( $post->post_type ) ) {
			return;
		}

		if ( isset( $_POST['lunar_seo_title'] ) ) {
			update_post_meta( $post_id, PostMetaKeys::TITLE, sanitize_text_field( wp_unslash( $_POST['lunar_seo_title'] ) ) );
		}

		if ( isset( $_POST['lunar_seo_description'] ) ) {
			update_post_meta( $post_id, PostMetaKeys::DESCRIPTION, sanitize_textarea_field( wp_unslash( $_POST['lunar_seo_description'] ) ) );
		}

		if ( isset( $_POST['lunar_seo_canonical'] ) ) {
			update_post_meta( $post_id, PostMetaKeys::CANONICAL, esc_url_raw( wp_unslash( $_POST['lunar_seo_canonical'] ) ) );
		}

		$robots = ( isset( $_POST['lunar_seo_robots'] ) && is_array( $_POST['lunar_seo_robots'] ) )
			? wp_unslash( $_POST['lunar_seo_robots'] )
			: [];

		update_post_meta( $post_id, PostMetaKeys::ROBOTS, $this->editor->sanitize_robots_override( $robots ) );
	}

	private function should_show_on( string $post_type ): bool {
		if ( ! $this->supported_post_types->is_supported( $post_type ) ) {
			return false;
		}

		return ! use_block_editor_for_post_type( $post_type );
	}
}
<?php
/**
 * Front-end submission form (shortcode for block themes).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Submission;

/**
 * Renders the quote submission form via [wpis_submit_form] for use in a Shortcode block.
 */
final class SubmitFormRenderer {

	/**
	 * Register shortcode.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'wpis_submit_form', array( self::class, 'render' ) );
		add_shortcode( 'wpis_repeat_badge', array( self::class, 'render_repeat_badge' ) );
	}

	/**
	 * @param array<string, string> $atts Shortcode attributes (unused).
	 * @return string
	 */
	public static function render( $atts ): string {
		$action = admin_url( 'admin-post.php' );
		$nonce  = wp_nonce_field( 'wpis_submit_quote', 'wpis_nonce', true, false );
		$pll    = '';
		if ( function_exists( 'pll_current_language' ) ) {
			$lang = pll_current_language( 'slug' );
			if ( is_string( $lang ) && '' !== $lang ) {
				$pll = '<input type="hidden" name="wpis_pll_lang" value="' . esc_attr( $lang ) . '" />';
			}
		}

		ob_start();
		?>
		<form class="wpis-submit-form" method="post" action="<?php echo esc_url( $action ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="wpis_submit_quote" />
			<?php echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo $pll; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<p class="wpis-hp-wrap" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
				<label for="wpis_hp"><?php esc_html_e( 'Leave empty', 'wpis-core' ); ?></label>
				<input type="text" name="wpis_hp" id="wpis_hp" value="" tabindex="-1" autocomplete="off" />
			</p>
			<div class="form-group">
				<label for="wpis_quote"><?php esc_html_e( 'The quote', 'wpis-core' ); ?> <span class="required">*</span></label>
				<textarea id="wpis_quote" name="wpis_quote" rows="6" placeholder="<?php echo esc_attr__( 'Paste the text here: exactly as it was written, in its original language.', 'wpis-core' ); ?>"></textarea>
				<div class="hint"><?php esc_html_e( '→ At least the text OR a screenshot is required.', 'wpis-core' ); ?></div>
			</div>
			<div class="form-group">
				<label for="wpis_screenshot"><?php esc_html_e( 'Or upload a screenshot', 'wpis-core' ); ?></label>
				<label class="upload-zone" for="wpis_screenshot">
					<span><?php esc_html_e( 'Drop a screenshot or click to choose a file.', 'wpis-core' ); ?></span>
					<input type="file" id="wpis_screenshot" name="wpis_screenshot" accept="image/*" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" />
				</label>
				<div class="hint"><?php esc_html_e( '→ We will extract the text automatically. The image is deleted after validation.', 'wpis-core' ); ?></div>
			</div>
			<div class="form-group">
				<label for="wpis_source_url"><?php esc_html_e( 'Source URL (if possible)', 'wpis-core' ); ?></label>
				<input type="url" id="wpis_source_url" name="wpis_source_url" placeholder="https://…" />
				<div class="hint"><?php esc_html_e( '→ Helps us detect the platform. Only the domain is stored, never the full URL.', 'wpis-core' ); ?></div>
			</div>
			<div class="rgpd-notice">
				<strong><?php esc_html_e( 'Privacy & data', 'wpis-core' ); ?></strong>
				<?php esc_html_e( 'Screenshots are deleted after text extraction. We never store personal identifiers (names, profile URLs or photos). Only the claim itself, the platform domain and the language are kept. Submissions are moderated before appearing on the site.', 'wpis-core' ); ?>
			</div>
			<p class="form-group">
				<label>
					<input type="checkbox" name="wpis_rgpd" value="1" required />
					<?php esc_html_e( 'I understand how my submission is used.', 'wpis-core' ); ?>
				</label>
			</p>
			<button type="submit" class="btn-primary"><?php esc_html_e( 'Submit this quote', 'wpis-core' ); ?></button>
			<span class="queue-indicator"><?php esc_html_e( 'Volunteer project — reviewed when someone can. No turnaround commitment.', 'wpis-core' ); ?></span>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Repeat count badge for query loop cards (post meta _wpis_counter).
	 *
	 * @param array<string, string> $atts Attributes.
	 * @return string
	 */
	public static function render_repeat_badge( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$n = (int) get_post_meta( $post_id, '_wpis_counter', true );
		if ( $n < 1 ) {
			$n = 0;
		}
		return '<p class="is-style-wpis-count-badge">×' . esc_html( (string) $n ) . '</p>';
	}
}

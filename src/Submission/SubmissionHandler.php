<?php
/**
 * Public quote submission via admin-post.php.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Submission;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Handles POST submissions from the front-end form.
 */
final class SubmissionHandler {

	private const ACTION = 'wpis_submit_quote';

	private const RATE_LIMIT_SEC = 300;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( self::class, 'handle' ) );
		add_action( 'template_redirect', array( self::class, 'maybe_noindex_profile' ) );
	}

	/**
	 * Process form POST.
	 *
	 * @return void
	 */
	public static function handle(): void {
		if ( ! isset( $_POST['wpis_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpis_nonce'] ) ), 'wpis_submit_quote' ) ) {
			wp_die( esc_html__( 'Invalid submission.', 'wpis-plugin' ), 403 );
		}

		if ( ! empty( $_POST['wpis_hp'] ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$ip = self::client_ip();
		if ( ! self::check_rate_limit( $ip ) ) {
			wp_die( esc_html__( 'Please wait before submitting again.', 'wpis-plugin' ), 429 );
		}

		$text = isset( $_POST['wpis_quote'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wpis_quote'] ) ) : '';
		$url  = isset( $_POST['wpis_source_url'] ) ? esc_url_raw( wp_unslash( $_POST['wpis_source_url'] ) ) : '';

		$consent = isset( $_POST['wpis_rgpd'] );
		if ( ! $consent ) {
			wp_die( esc_html__( 'Consent is required.', 'wpis-plugin' ), 400 );
		}

		$has_file = ! empty( $_FILES['wpis_screenshot']['name'] );
		if ( '' === trim( $text ) && ! $has_file ) {
			wp_die( esc_html__( 'Please enter quote text or attach a screenshot.', 'wpis-plugin' ), 400 );
		}

		if ( strlen( $text ) > 1000 ) {
			wp_die( esc_html__( 'Quote is too long.', 'wpis-plugin' ), 400 );
		}

		$user_id = get_current_user_id();

		$post_id = wp_insert_post(
			array(
				'post_type'    => QuotePostType::POST_TYPE,
				'post_status'  => 'pending',
				'post_title'   => self::title_from_text( $text ),
				'post_content' => $text,
				'post_author'  => $user_id,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_die( esc_html__( 'Could not save submission.', 'wpis-plugin' ), 500 );
		}

		$domain   = $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';
		$platform = self::guess_platform( $domain );

		update_post_meta( $post_id, '_wpis_counter', 1 );
		update_post_meta( $post_id, '_wpis_submission_source', 'form' );
		if ( $domain ) {
			update_post_meta( $post_id, '_wpis_source_domain', sanitize_text_field( $domain ) );
		}
		update_post_meta( $post_id, '_wpis_source_platform', $platform );

		if ( $has_file && ! empty( $_FILES['wpis_screenshot']['tmp_name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$aid = media_handle_upload( 'wpis_screenshot', $post_id );
			if ( ! is_wp_error( $aid ) ) {
				update_post_meta( $aid, '_wpis_temporary_upload', 1 );
			}
		}

		$token = wp_generate_password( 32, false, false );
		set_transient( 'wpis_submit_' . $token, $post_id, HOUR_IN_SECONDS );

		self::bump_rate_limit( $ip );

		/**
		 * Fires after a quote was submitted from the front-end form.
		 *
		 * @param int $post_id New quote ID.
		 */
		do_action( 'wpis_quote_submitted', $post_id );

		$url = apply_filters( 'wpis_submission_redirect_url', home_url( '/submitted/' ), $post_id );
		wp_safe_redirect( add_query_arg( 't', rawurlencode( $token ), $url ) );
		exit;
	}

	/**
	 * @param string $text Quote text.
	 * @return string
	 */
	private static function title_from_text( string $text ): string {
		$t = wp_html_excerpt( $text, 80, '…' );
		return $t ? $t : __( 'Quote submission', 'wpis-plugin' );
	}

	/**
	 * @param string|false $domain Host.
	 * @return string
	 */
	private static function guess_platform( $domain ): string {
		if ( ! $domain ) {
			return 'other';
		}
		$d = strtolower( (string) $domain );
		if ( str_contains( $d, 'mastodon' ) ) {
			return 'mastodon';
		}
		if ( str_contains( $d, 'bsky' ) ) {
			return 'bluesky';
		}
		return 'blog';
	}

	/**
	 * @return string
	 */
	private static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		return $ip;
	}

	/**
	 * @param string $ip IP.
	 * @return bool
	 */
	private static function check_rate_limit( string $ip ): bool {
		$key = 'wpis_rl_' . md5( $ip );
		return ! get_transient( $key );
	}

	/**
	 * @param string $ip IP.
	 * @return void
	 */
	private static function bump_rate_limit( string $ip ): void {
		$key = 'wpis_rl_' . md5( $ip );
		set_transient( $key, 1, self::RATE_LIMIT_SEC );
	}

	/**
	 * Noindex for profile page template (slug profile).
	 *
	 * @return void
	 */
	public static function maybe_noindex_profile(): void {
		if ( ! is_page() ) {
			return;
		}
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		if ( 'profile' === $slug || 'my-profile' === $slug ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}
	}
}

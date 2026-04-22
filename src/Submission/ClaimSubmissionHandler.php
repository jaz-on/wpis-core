<?php
/**
 * Post-submit account claim flow: let an anonymous submitter create an account
 * and attach the pending quote to that new user.
 *
 * Flow:
 * 1. SubmissionHandler stored a transient `wpis_submit_{token}` pointing at the
 *    pending quote ID and redirected to /submitted/?t={token}.
 * 2. On /submitted/, [wpis_claim_submission] renders:
 *    - a confirmation if the user is already logged in (post_author is
 *      already set by SubmissionHandler);
 *    - an inline signup form (email + password) if not logged in and the
 *      token resolves to a pending post (e.g. anonymous submit using the default owner ID).
 *    - a generic "Create an account" CTA if the token is missing or expired.
 * 3. POST to admin-post.php?action=wpis_claim_submission creates the user,
 *    signs them in, and updates `post_author` of the pending quote.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Submission;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Shortcode renderer + POST handler for the claim flow.
 */
final class ClaimSubmissionHandler {

	private const SHORTCODE = 'wpis_claim_submission';

	private const ACTION = 'wpis_claim_submission';

	/**
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( self::class, 'handle' ) );
	}

	/**
	 * Render the claim/signup block on the /submitted/ page.
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render( $atts ): string {
		unset( $atts );

		$token   = self::read_token_from_request();
		$post_id = self::token_post_id( $token );

		if ( is_user_logged_in() ) {
			return self::render_logged_in( $post_id );
		}

		$notice   = '';
		$notice_q = isset( $_GET['wpis_claim'] ) ? sanitize_key( (string) wp_unslash( $_GET['wpis_claim'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'exists' === $notice_q ) {
			$notice = __( 'An account already uses that email. Log in to attach this submission to it.', 'wpis-core' );
		} elseif ( 'invalid_email' === $notice_q ) {
			$notice = __( 'That email address does not look valid.', 'wpis-core' );
		} elseif ( 'weak_password' === $notice_q ) {
			$notice = __( 'Please pick a password with at least 8 characters.', 'wpis-core' );
		} elseif ( 'expired' === $notice_q ) {
			$notice = __( 'This claim link has expired. You can still create an account from the profile page.', 'wpis-core' );
		}

		if ( $post_id <= 0 ) {
			return self::render_generic_cta( $notice );
		}

		return self::render_signup_form( $token, $post_id, $notice );
	}

	/**
	 * @param int $post_id Post referenced by the token (0 if unknown).
	 * @return string
	 */
	private static function render_logged_in( int $post_id ): string {
		$profile_url = home_url( '/profile/' );
		$html        = '<aside class="wpis-claim wpis-claim--attached">';
		$html       .= '<p class="wpis-claim-label">' . esc_html__( 'Attached to your account', 'wpis-core' ) . '</p>';
		if ( $post_id > 0 ) {
			$html .= '<p>' . esc_html__( 'This submission is linked to your profile. You will see its status update there (pending, validated, merged, rejected).', 'wpis-core' ) . '</p>';
		} else {
			$html .= '<p>' . esc_html__( 'You are logged in, so any submission you send is already tracked from your profile.', 'wpis-core' ) . '</p>';
		}
		$html .= '<p><a class="btn-secondary" href="' . esc_url( $profile_url ) . '">' . esc_html__( 'Go to my profile', 'wpis-core' ) . '</a></p>';
		$html .= '</aside>';
		return $html;
	}

	/**
	 * @param string $notice Optional notice text.
	 * @return string
	 */
	private static function render_generic_cta( string $notice ): string {
		$profile_url = home_url( '/profile/' );
		$html        = '<aside class="wpis-claim wpis-claim--cta">';
		$html       .= '<p class="wpis-claim-label">' . esc_html__( 'Want to follow this?', 'wpis-core' ) . '</p>';
		$html       .= '<p>' . esc_html__( 'Create a free account to see the status of your submission (validated, merged, rejected) and keep track of your contributions. Your profile stays private — only you can see it.', 'wpis-core' ) . '</p>';
		if ( '' !== $notice ) {
			$html .= '<p class="wpis-claim-notice">' . esc_html( $notice ) . '</p>';
		}
		$html .= '<p><a class="btn-primary" href="' . esc_url( $profile_url ) . '">' . esc_html__( 'Create an account', 'wpis-core' ) . '</a></p>';
		$html .= '</aside>';
		return $html;
	}

	/**
	 * @param string $token   Token.
	 * @param int    $post_id Referenced post ID.
	 * @param string $notice  Optional notice text.
	 * @return string
	 */
	private static function render_signup_form( string $token, int $post_id, string $notice ): string {
		$action = esc_url( admin_url( 'admin-post.php' ) );
		$nonce  = wp_nonce_field( 'wpis_claim_submission', 'wpis_claim_nonce', true, false );

		$html  = '<aside class="wpis-claim wpis-claim--signup">';
		$html .= '<p class="wpis-claim-label">' . esc_html__( 'Want to follow this submission?', 'wpis-core' ) . '</p>';
		$html .= '<p>' . esc_html__( 'Create a free account below and this submission will be attached to your profile. You will see it move from pending to validated, merged, or rejected. Your profile stays private — only you can see it.', 'wpis-core' ) . '</p>';
		if ( '' !== $notice ) {
			$html .= '<p class="wpis-claim-notice" role="alert">' . esc_html( $notice ) . '</p>';
		}
		$html .= '<form class="wpis-claim-form" method="post" action="' . $action . '">';
		$html .= $nonce;
		$html .= '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '" />';
		$html .= '<input type="hidden" name="wpis_claim_token" value="' . esc_attr( $token ) . '" />';
		$html .= '<label class="wpis-claim-field"><span>' . esc_html__( 'Email', 'wpis-core' ) . '</span>';
		$html .= '<input type="email" name="wpis_claim_email" required autocomplete="email" /></label>';
		$html .= '<label class="wpis-claim-field"><span>' . esc_html__( 'Password (8+ characters)', 'wpis-core' ) . '</span>';
		$html .= '<input type="password" name="wpis_claim_password" minlength="8" required autocomplete="new-password" /></label>';
		$html .= '<input type="text" name="wpis_claim_hp" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-9999px;width:1px;height:1px;" aria-hidden="true" />';
		$html .= '<button type="submit" class="btn-primary">' . esc_html__( 'Create account and attach', 'wpis-core' ) . '</button>';
		$html .= '<p class="wpis-claim-aside">' . esc_html__( 'Already have an account?', 'wpis-core' ) . ' <a href="' . esc_url( wp_login_url( home_url( '/profile/' ) ) ) . '">' . esc_html__( 'Log in', 'wpis-core' ) . '</a></p>';
		$html .= '</form>';
		$html .= '</aside>';

		return $html;
	}

	/**
	 * POST handler: create user + attach pending post.
	 *
	 * @return void
	 */
	public static function handle(): void {
		if ( ! isset( $_POST['wpis_claim_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpis_claim_nonce'] ) ), 'wpis_claim_submission' ) ) {
			wp_die( esc_html__( 'Invalid claim submission.', 'wpis-core' ), 403 );
		}

		if ( ! empty( $_POST['wpis_claim_hp'] ) ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		$token   = isset( $_POST['wpis_claim_token'] ) ? sanitize_text_field( wp_unslash( $_POST['wpis_claim_token'] ) ) : '';
		$post_id = self::token_post_id( $token );
		$back    = home_url( '/submitted/' );
		$back    = '' !== $token ? add_query_arg( 't', rawurlencode( $token ), $back ) : $back;

		if ( $post_id <= 0 ) {
			wp_safe_redirect( add_query_arg( 'wpis_claim', 'expired', $back ) );
			exit;
		}

		$email = isset( $_POST['wpis_claim_email'] ) ? sanitize_email( wp_unslash( $_POST['wpis_claim_email'] ) ) : '';
		if ( '' === $email || ! is_email( $email ) ) {
			wp_safe_redirect( add_query_arg( 'wpis_claim', 'invalid_email', $back ) );
			exit;
		}

		$password = isset( $_POST['wpis_claim_password'] ) ? (string) wp_unslash( $_POST['wpis_claim_password'] ) : '';
		if ( strlen( $password ) < 8 ) {
			wp_safe_redirect( add_query_arg( 'wpis_claim', 'weak_password', $back ) );
			exit;
		}

		if ( email_exists( $email ) ) {
			wp_safe_redirect( add_query_arg( 'wpis_claim', 'exists', $back ) );
			exit;
		}

		$username = self::unique_username_from_email( $email );
		$user_id  = wp_create_user( $username, $password, $email );
		if ( is_wp_error( $user_id ) ) {
			wp_die( esc_html( $user_id->get_error_message() ), 500 );
		}

		wp_update_user(
			array(
				'ID'            => (int) $user_id,
				'display_name'  => $username,
				'nickname'      => $username,
				'first_name'    => '',
				'last_name'     => '',
				'user_url'      => '',
				'description'   => '',
			)
		);

		wp_set_current_user( (int) $user_id );
		wp_set_auth_cookie( (int) $user_id, true );

		wp_update_post(
			array(
				'ID'          => (int) $post_id,
				'post_author' => (int) $user_id,
			)
		);

		// Consume the token so it cannot be re-used.
		delete_transient( 'wpis_submit_' . $token );

		$redirect = add_query_arg( 'wpis_claim', 'attached', home_url( '/profile/' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * @return string
	 */
	private static function read_token_from_request(): string {
		$raw = isset( $_GET['t'] ) ? (string) wp_unslash( $_GET['t'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw = rawurldecode( $raw );
		return preg_match( '/^[A-Za-z0-9]{8,64}$/', $raw ) ? $raw : '';
	}

	/**
	 * @param string $token Token from the URL.
	 * @return int Post ID (0 if token invalid / expired / non-quote / already authored).
	 */
	private static function token_post_id( string $token ): int {
		if ( '' === $token ) {
			return 0;
		}
		$post_id = (int) get_transient( 'wpis_submit_' . $token );
		if ( $post_id <= 0 ) {
			return 0;
		}
		$post = get_post( $post_id );
		if ( ! $post || QuotePostType::POST_TYPE !== $post->post_type ) {
			return 0;
		}
		return (int) $post_id;
	}

	/**
	 * Generate a username that does not collide with an existing one.
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	private static function unique_username_from_email( string $email ): string {
		$base = sanitize_user( (string) strstr( $email, '@', true ), true );
		if ( '' === $base ) {
			$base = 'contributor';
		}
		$candidate = $base;
		$i         = 1;
		while ( username_exists( $candidate ) ) {
			++$i;
			$candidate = $base . $i;
			if ( $i > 999 ) {
				$candidate = $base . wp_generate_password( 4, false, false );
				break;
			}
		}
		return $candidate;
	}
}

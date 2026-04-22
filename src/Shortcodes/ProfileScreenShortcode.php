<?php
/**
 * Profile screen: dynamic contributor stats and recent submissions.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Shortcodes;

use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\User\UserStats;

/**
 * Renders [wpis_profile] for the My profile page (pairs with is-style-wpis-profile in the theme).
 */
final class ProfileScreenShortcode {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'wpis_profile', array( self::class, 'render' ) );
	}

	/**
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts ): string {
		unset( $atts );
		if ( ! post_type_exists( QuotePostType::POST_TYPE ) ) {
			return '';
		}
		if ( ! is_user_logged_in() ) {
			return self::render_logged_out();
		}

		$user  = wp_get_current_user();
		$stats = UserStats::get( (int) $user->ID );
		$val   = (int) $stats['validated'] + (int) $stats['merged'];

		$since = self::format_member_since( $user->user_registered );

		$notice = '';
		if ( isset( $_GET['wpis_claim'] ) && 'attached' === sanitize_key( (string) wp_unslash( $_GET['wpis_claim'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$notice = __( 'Welcome! Your submission is attached to your new account. You can follow its status below.', 'wpis-core' );
		}

		ob_start();
		echo '<div class="wpis-profile">';
		if ( '' !== $notice ) {
			echo '<p class="wpis-profile-notice" role="status">' . esc_html( $notice ) . '</p>';
		}
		echo '<div class="profile-header">';
		echo '<h1 class="wp-block-heading">' . esc_html__( 'Your contributions', 'wpis-core' ) . '</h1>';
		$eyeb = sprintf(
			/* translators: %s: "Month year" when the user registered */
			__( 'Private · visible only to you · member since %s', 'wpis-core' ),
			$since
		);
		echo '<p>' . esc_html( $eyeb ) . '</p>';
		echo '</div>';

		// First row: total + validated.
		echo '<div class="stats-grid" style="margin-bottom: 12px;">';
		self::echo_stat( __( 'Total submitted', 'wpis-core' ), (string) number_format_i18n( (int) $stats['total_submitted'] ), false );
		self::echo_stat( __( 'Validated', 'wpis-core' ), (string) number_format_i18n( $val ), false );
		echo '</div>';

		// Second row: rate + pending.
		echo '<div class="stats-grid" style="margin-bottom: 36px;">';
		$rate = (float) $stats['acceptance_rate'];
		self::echo_stat(
			__( 'Acceptance rate', 'wpis-core' ),
			(string) number_format_i18n( $rate, 1 ) . '<span class="suffix">%</span>',
			true
		);
		self::echo_stat( __( 'Pending', 'wpis-core' ), (string) number_format_i18n( (int) $stats['pending'] ), false );
		echo '</div>';

		self::echo_recent( (int) $user->ID );

		echo '</div>';
		return (string) ob_get_clean();
	}

	/**
	 * @param string $label         Stat label.
	 * @param string $value         Plain text or small HTML (spans) when $value_is_html.
	 * @param bool   $value_is_html When true, value may include span.suffix.
	 * @return void
	 */
	private static function echo_stat( string $label, string $value, bool $value_is_html = false ): void {
		echo '<div class="stat-card">';
		echo '<p class="label">' . esc_html( $label ) . '</p>';
		echo '<p class="value">';
		if ( $value_is_html ) {
			echo wp_kses(
				$value,
				array( 'span' => array( 'class' => true ) )
			);
		} else {
			echo esc_html( $value );
		}
		echo '</p></div>';
	}

	/**
	 * @param string $registered User registration mysql datetime.
	 * @return string
	 */
	private static function format_member_since( string $registered ): string {
		$t = mysql2date( 'U', $registered, false );
		if ( ! is_numeric( $t ) ) {
			return '';
		}
		return (string) date_i18n( 'F Y', (int) $t );
	}

	/**
	 * @return string
	 */
	private static function render_logged_out(): string {
		$profile_url  = home_url( '/profile/' );
		$login_url    = wp_login_url( $profile_url );
		$register_url = '';
		if ( get_option( 'users_can_register' ) ) {
			$register_url = wp_registration_url();
		}

		$html  = '<div class="wpis-profile wpis-profile--logged-out">';
		$html .= '<div class="wpis-login-gate">';
		$html .= '<p class="wpis-login-mark" aria-hidden="true">is</p>';
		$html .= '<h1 class="wp-block-heading">' . esc_html__( 'Your profile', 'wpis-core' ) . '</h1>';
		$html .= '<p class="sub">' . esc_html__( 'A profile is optional. Log in to track the status of your submissions (pending, validated, merged, rejected). Your profile stays private — only you can see it.', 'wpis-core' ) . '</p>';
		$html .= '<p class="wpis-login-actions">';
		$html .= '<a class="btn-primary" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Log in', 'wpis-core' ) . '</a>';
		if ( '' !== $register_url ) {
			$html .= ' <a class="btn-secondary" href="' . esc_url( $register_url ) . '">' . esc_html__( 'Create an account', 'wpis-core' ) . '</a>';
		}
		$html .= '</p>';
		$html .= '<p class="wpis-login-aside">' . esc_html__( 'You do not need an account to submit a quote. The submit form is open to everyone.', 'wpis-core' ) . ' <a href="' . esc_url( home_url( '/submit/' ) ) . '">' . esc_html__( 'Go to submit', 'wpis-core' ) . '</a></p>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * @param int $user_id User ID.
	 * @return void
	 */
	private static function echo_recent( int $user_id ): void {
		$q = new \WP_Query(
			array(
				'author'         => $user_id,
				'post_type'      => QuotePostType::POST_TYPE,
				'post_status'    => array( 'publish', 'pending', 'rejected', 'merged' ),
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		echo '<div class="submission-list">';
		echo '<h2 class="wp-block-heading">' . esc_html__( 'Your recent submissions', 'wpis-core' ) . '</h2>';

		if ( ! $q->have_posts() ) {
			echo '<p class="wp-block-paragraph" style="color: var(--muted);">' . esc_html__( 'No submissions yet.', 'wpis-core' ) . '</p>';
			echo '</div>';
			return;
		}

		while ( $q->have_posts() ) {
			$q->the_post();
			$pid   = (int) get_the_ID();
			$st    = (string) get_post_status( $pid );
			$badge = self::status_badge_markup( $pid, $st );
			$text  = get_the_title();
			$date  = get_the_date();
			$html  = '<div class="sub-item">';
			$html .= '<p class="sub-text">' . esc_html( $text ) . '</p>';
			$html .= '<div class="sub-meta-line">';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$html .= $badge;
			$html .= '<p class="sub-date">' . esc_html( $date ) . '</p>';
			$html .= '</div>';
			$html .= '</div>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $html;
		}
		wp_reset_postdata();
		echo '</div>';
	}

	/**
	 * @param int    $post_id Post ID.
	 * @param string $status  Post status.
	 * @return string HTML
	 */
	private static function status_badge_markup( int $post_id, string $status ): string {
		$counter = (int) get_post_meta( $post_id, '_wpis_counter', true );
		$label   = '';

		if ( 'publish' === $status ) {
			$class = 'status-validated';
			$label = __( 'Validated', 'wpis-core' );
		} elseif ( 'pending' === $status ) {
			$class = 'status-pending';
			$label = __( 'Pending', 'wpis-core' );
		} elseif ( 'rejected' === $status ) {
			$class = 'status-rejected';
			$label = __( 'Rejected', 'wpis-core' );
		} elseif ( 'merged' === $status ) {
			$class = 'status-merged';
			$label = __( 'Merged', 'wpis-core' );
			if ( $counter > 1 ) {
				$label = sprintf( /* translators: %d: repeat count */ __( 'Merged ×%d', 'wpis-core' ), $counter );
			}
		} else {
			$class = 'status-pending';
			$label = $status;
		}

		return '<p class="status-badge ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</p>';
	}
}

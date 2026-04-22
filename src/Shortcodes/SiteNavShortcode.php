<?php
/**
 * Site navigation + profile nav link shortcodes.
 *
 * These replace the core/navigation block in the theme header because we need
 * two things the core block does not give us out of the box:
 *   1. A dynamic last item ("My profile" when logged in, "Log in" otherwise).
 *   2. A reliable `.active` class on the current page, without relying on
 *      `aria-current="page"` or `.current-menu-item`, which behave
 *      inconsistently across block themes.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Shortcodes;

/**
 * Registers [wpis_site_nav] (whole header nav) and [wpis_profile_nav_link]
 * (single last-item link, in case you want to reuse the core nav block).
 */
final class SiteNavShortcode {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'wpis_site_nav', array( self::class, 'render_nav' ) );
		add_shortcode( 'wpis_profile_nav_link', array( self::class, 'render_profile_link' ) );
	}

	/**
	 * Render the full site nav: Feed / Explore / About / How it works / Submit /
	 * (My profile | Log in). Each link gets an `.active` class when its target
	 * URL matches the current request path.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes (ignored).
	 * @return string
	 */
	public static function render_nav( $atts ): string {
		unset( $atts );
		$items = array(
			array(
				'label' => __( 'Feed', 'wpis-core' ),
				'url'   => '/',
				'match' => array( '/', '' ),
			),
			array(
				'label' => __( 'Explore', 'wpis-core' ),
				'url'   => '/explore/',
				'match' => array( '/explore/' ),
			),
			array(
				'label' => __( 'About', 'wpis-core' ),
				'url'   => '/about/',
				'match' => array( '/about/' ),
			),
			array(
				'label' => __( 'How it works', 'wpis-core' ),
				'url'   => '/how-it-works/',
				'match' => array( '/how-it-works/' ),
			),
			array(
				'label' => __( 'Submit', 'wpis-core' ),
				'url'   => '/submit/',
				'match' => array( '/submit/', '/submitted/' ),
			),
		);

		if ( is_user_logged_in() ) {
			$items[] = array(
				'label' => __( 'My profile', 'wpis-core' ),
				'url'   => '/profile/',
				'match' => array( '/profile/' ),
			);
		} else {
			$login_url = wp_login_url( home_url( '/profile/' ) );
			$items[]   = array(
				'label' => __( 'Log in', 'wpis-core' ),
				'url'   => $login_url,
				'match' => array(),
			);
		}

		$current = self::current_path();
		$links   = '';
		foreach ( $items as $it ) {
			$is_active = in_array( $current, (array) $it['match'], true );
			$links    .= sprintf(
				'<a class="wpis-site-nav-item%1$s" href="%2$s"%3$s>%4$s</a>',
				$is_active ? ' active' : '',
				esc_url( (string) $it['url'] ),
				$is_active ? ' aria-current="page"' : '',
				esc_html( (string) $it['label'] )
			);
		}

		return '<nav class="wpis-site-nav" aria-label="' . esc_attr__( 'Main navigation', 'wpis-core' ) . '">' . $links . '</nav>';
	}

	/**
	 * Single profile/login link, for inclusion inside other navs.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes (ignored).
	 * @return string
	 */
	public static function render_profile_link( $atts ): string {
		unset( $atts );
		$current = self::current_path();
		if ( is_user_logged_in() ) {
			$active = '/profile/' === $current ? ' active' : '';
			return sprintf(
				'<a class="wpis-site-nav-item%1$s" href="/profile/"%2$s>%3$s</a>',
				$active,
				'' !== $active ? ' aria-current="page"' : '',
				esc_html__( 'My profile', 'wpis-core' )
			);
		}
		$login_url = wp_login_url( home_url( '/profile/' ) );
		return sprintf(
			'<a class="wpis-site-nav-item" href="%1$s">%2$s</a>',
			esc_url( $login_url ),
			esc_html__( 'Log in', 'wpis-core' )
		);
	}

	/**
	 * Normalized current request path (always starts and ends with /).
	 *
	 * @return string
	 */
	private static function current_path(): string {
		$req = '/';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$req = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}
		$p   = (string) wp_parse_url( $req, PHP_URL_PATH );
		if ( '' === $p ) {
			return '/';
		}
		if ( '/' !== substr( $p, -1 ) ) {
			$p .= '/';
		}
		return $p;
	}
}

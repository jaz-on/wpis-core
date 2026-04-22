<?php
/**
 * “Plugins” list row links (e.g. shortcut to Quotes).
 *
 * @package WPIS\Core\Admin
 */

namespace WPIS\Core\Admin;

/**
 * Registers plugin_action_links for WPIS Core.
 */
final class PluginListLinks {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_filter(
			'plugin_action_links_' . plugin_basename( WPIS_CORE_PLUGIN_FILE ),
			array( self::class, 'filter_action_links' )
		);
	}

	/**
	 * @param string[] $links Existing plugin row links.
	 * @return string[]
	 */
	public static function filter_action_links( array $links ): array {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $links;
		}

		$url = admin_url( 'edit.php?post_type=quote' );
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $url ),
				esc_html__( 'Quotes', 'wpis-core' )
			)
		);

		return $links;
	}
}

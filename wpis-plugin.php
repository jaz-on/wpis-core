<?php
/**
 * Plugin Name: WordPress Is… Core
 * Description: Core plugin for the "WordPress Is…" project: quotes, taxonomies, MCP integration.
 * Version: 0.1.0
 * Author: Jasonnade
 * Author URI: https://jasonrouet.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpis-plugin
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * GitHub Plugin URI: https://github.com/jaz-on/wpis-plugin
 * Primary Branch: main
 *
 * @package WPIS\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/inc/functions-api.php';

/**
 * Bail out early if WordPress is older than 6.9 (no Abilities API).
 */
if ( ! function_exists( 'wp_register_ability' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'WPIS Core requires the WordPress Abilities API (WordPress 6.9+). Please upgrade WordPress.', 'wpis-plugin' );
			echo '</p></div>';
		}
	);
	return;
}

register_activation_hook(
	__FILE__,
	static function () {
		if ( class_exists( '\WPIS\Core\Activation' ) ) {
			\WPIS\Core\Activation::activate();
		}
	}
);

if ( class_exists( '\WPIS\Core\Plugin' ) ) {
	( new \WPIS\Core\Plugin() )->register();
}

/**
 * Bail out early if MCP Adapter is not active.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-warning"><p>';
					esc_html_e( 'WPIS Core: MCP Adapter plugin is not active. Abilities will still work but will not be exposed to MCP clients.', 'wpis-plugin' );
					echo '</p></div>';
				}
			);
		}
	}
);

/**
 * Register the MCP server for the WPIS project.
 *
 * This creates a dedicated MCP server at:
 *     /wp-json/wpis/v1/wpis
 *
 * The server exposes an explicit allowlist of abilities, so new abilities
 * are only visible to AI agents once added here intentionally.
 *
 * @param \WP\MCP\Core\McpAdapter $adapter The MCP adapter instance.
 */
add_action(
	'mcp_adapter_init',
	static function ( $adapter ) {
		if ( ! $adapter || ! method_exists( $adapter, 'create_server' ) ) {
			return;
		}

		/**
		 * Filter the list of abilities exposed to the WPIS MCP server.
		 *
		 * Add new abilities to this list as the project grows.
		 *
		 * @param string[] $abilities Array of ability names (namespace/ability-name).
		 */
		$abilities = apply_filters(
			'wpis_mcp_abilities',
			array(
				'core/get-site-info',
				'core/get-environment-info',
			)
		);

		$adapter->create_server(
			'wpis-server',
			'wpis/v1',
			'wpis',
			__( 'WordPress Is… MCP', 'wpis-plugin' ),
			__( 'MCP server for the WordPress Is… project. Exposes a curated set of abilities.', 'wpis-plugin' ),
			'v1.0.0',
			array(
				\WP\MCP\Transport\HttpTransport::class,
			),
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			null,
			$abilities
		);
	}
);

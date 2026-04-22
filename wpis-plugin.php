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

if ( version_compare( PHP_VERSION, '8.2.0', '<' ) ) {
	$wpis_core_loaded = false;
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html(
				sprintf(
					/* translators: %s: current PHP version */
					__( 'WordPress Is… Core requires PHP 8.2 or newer. This site is running PHP %s.', 'wpis-plugin' ),
					PHP_VERSION
				)
			);
			echo '</p></div>';
		}
	);
} else {
	/**
	 * Prefer Composer’s vendor/autoload.php when present (local dev, CI).
	 * On hosts that only get a Git or zip copy of src/ + inc/, the runtime PSR-4 autoloader loads the same code without composer install.
	 */
	require_once __DIR__ . '/inc/autoload-runtime.php';
	wpis_plugin_load_autoloader();
	$wpis_core_loaded = true;
	require_once __DIR__ . '/inc/functions-api.php';
}

if ( $wpis_core_loaded ) {
	/**
	 * Recommend WordPress 6.9+ for Abilities API; still load CPT, taxonomies, and submission
	 * even if abilities are unavailable (avoid bailing before Plugin registers).
	 */
	global $wp_version;
	if ( version_compare( (string) $wp_version, '6.9', '<' ) ) {
		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-warning"><p>';
				esc_html_e( 'WPIS Core targets WordPress 6.9+ for the Abilities API. Quotes and moderation features still load; upgrade WordPress for full MCP/abilities support.', 'wpis-plugin' );
				echo '</p></div>';
			}
		);
	}

	// Autoload on activation: Git Updater and wp-admin may include this file without a prior full bootstrap; mirror load_autoloader() here.
	register_activation_hook(
		__FILE__,
		static function () {
			if ( version_compare( PHP_VERSION, '8.2.0', '<' ) ) {
				return;
			}
			require_once __DIR__ . '/inc/autoload-runtime.php';
			wpis_plugin_load_autoloader();
			if ( class_exists( '\WPIS\Core\Activation' ) ) {
				\WPIS\Core\Activation::activate();
			}
		}
	);

	( new \WPIS\Core\Plugin() )->register();

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
}

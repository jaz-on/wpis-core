<?php
/**
 * PSR-4 autoloader for WPIS\Core when Composer’s vendor/ is not deployed.
 *
 * Maps WPIS\Core\ → src/ (same as composer.json autoload).
 *
 * @package WPIS\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register spl_autoload for the plugin’s own classes (no external packages required).
 *
 * @return void
 */
function wpis_core_register_psr4_autoload(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;

	$base_dir = dirname( __DIR__ ) . '/src/';

	spl_autoload_register(
		static function ( string $class_name ) use ( $base_dir ): void {
			$prefix = 'WPIS\\Core\\';
			$len    = strlen( $prefix );
			if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
				return;
			}
			$relative = str_replace( '\\', '/', substr( $class_name, $len ) );
			$file     = $base_dir . $relative . '.php';
			if ( is_readable( $file ) ) {
				require $file;
			}
		}
	);
}

/**
 * Load Composer autoload if present, otherwise the runtime PSR-4 loader.
 *
 * @return bool True if a loader is available.
 */
function wpis_core_load_autoloader(): bool {
	if ( is_readable( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
		require_once dirname( __DIR__ ) . '/vendor/autoload.php';
		return true;
	}
	wpis_core_register_psr4_autoload();
	return true;
}

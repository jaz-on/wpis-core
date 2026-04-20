<?php
/**
 * Runs upgrades when the plugin files update without reactivation.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core;

/**
 * Schema version sync for existing installs.
 */
final class Upgrade {

	private const OPTION_KEY = 'wpis_core_schema_version';

	/**
	 * Hook upgrade checks after CPT/taxonomies register.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'maybe_upgrade' ), 20 );
	}

	/**
	 * Seed terms and flush rewrites once per schema bump.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$current = (string) get_option( self::OPTION_KEY, '' );
		if ( version_compare( $current, Activation::SCHEMA_VERSION, '>=' ) ) {
			return;
		}

		Activation::seed_default_terms();
		update_option( self::OPTION_KEY, Activation::SCHEMA_VERSION, true );

		if ( get_option( 'permalink_structure' ) ) {
			flush_rewrite_rules( false );
		}
	}
}

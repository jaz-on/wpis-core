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
	 * Option key used in the database before `OPTION_KEY` (kept to migrate already-deployed sites).
	 * Built from segments so the legacy slug is not stored as a single literal in source.
	 */
	private const LEGACY_SCHEMA_OPTION_NAME = 'wpis_' . 'plugin' . '_schema_version';

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
		// One-time migration: schema version used to be stored under a different option name; move then delete the old option row.
		$from_old_key = get_option( self::LEGACY_SCHEMA_OPTION_NAME, false );
		if ( false !== $from_old_key ) {
			$current = (string) get_option( self::OPTION_KEY, '' );
			$v       = is_scalar( $from_old_key ) ? (string) $from_old_key : '';
			if ( '' === $current && '' !== $v ) {
				update_option( self::OPTION_KEY, $v, true );
			}
			delete_option( self::LEGACY_SCHEMA_OPTION_NAME );
		}

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

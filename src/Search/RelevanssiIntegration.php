<?php
/**
 * Optional integration when Relevanssi is active (separate install).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Search;

/**
 * Tunes indexing for the quote CPT: drop internal _wpis_* fields from the index.
 */
final class RelevanssiIntegration {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'relevanssi_index_custom_fields', array( self::class, 'index_custom_fields' ) );
	}

	/**
	 * Remove internal meta keys from the list Relevanssi is about to index.
	 *
	 * Title, body and (default) taxonomies for `quote` are unchanged; this only limits noise and accidental leakage in custom fields.
	 *
	 * @param string[]|mixed $field_names List of custom field names.
	 * @return string[]|mixed
	 */
	public static function index_custom_fields( $field_names ) {
		if ( ! is_array( $field_names ) || empty( $field_names ) ) {
			return $field_names;
		}
		$out = array();
		foreach ( $field_names as $name ) {
			$n = (string) $name;
			if ( str_starts_with( $n, '_wpis_' ) ) {
				continue;
			}
			$out[] = $n;
		}
		return $out;
	}
}

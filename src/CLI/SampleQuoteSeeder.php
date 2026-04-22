<?php
/**
 * Single entry for curated sample quotes (same dataset; tracks with _wpis_demo_seed).
 * Erase removes legacy starter-tagged posts too.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\CLI;

/**
 * Sample quote import and removal for admin and WP-CLI.
 */
final class SampleQuoteSeeder {

	/**
	 * Insert sample quotes from the shared dataset.
	 *
	 * @param int $count Target number of posts (0 = all rows).
	 * @return int Number of posts created.
	 */
	public static function seed( int $count = 24 ): int {
		return DemoSeeder::seed( $count );
	}

	/**
	 * Delete posts seeded as starter or demo sample quotes.
	 *
	 * @return int Number of posts deleted.
	 */
	public static function erase(): int {
		return StarterSeeder::erase() + DemoSeeder::erase();
	}
}

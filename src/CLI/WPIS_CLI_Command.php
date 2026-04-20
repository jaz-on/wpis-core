<?php
/**
 * WP-CLI commands for WPIS.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\CLI;

use WPIS\Core\Dedup\DuplicateFinder;
use WPIS\Core\Merge\QuoteMerger;
use WPIS\Core\PostTypes\QuotePostType;

/**
 * WP-CLI `wp wpis` subcommands.
 */
final class WPIS_CLI_Command {

	/**
	 * Merge a source quote into a target quote.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : Source post ID.
	 *
	 * <target>
	 * : Target post ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpis merge 12 34
	 *
	 * @param array<int, string> $args Positional args.
	 * @return void
	 */
	public function merge( array $args ): void {
		$source = isset( $args[0] ) ? (int) $args[0] : 0;
		$target = isset( $args[1] ) ? (int) $args[1] : 0;
		$result = QuoteMerger::merge( $source, $target );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		\WP_CLI::success( sprintf( 'Merged quote %d into %d.', $source, $target ) );
	}

	/**
	 * Unmerge a merged quote.
	 *
	 * ## OPTIONS
	 *
	 * <quote_id>
	 * : Merged quote ID.
	 *
	 * @param array<int, string> $args Positional args.
	 * @return void
	 */
	public function unmerge( array $args ): void {
		$id     = isset( $args[0] ) ? (int) $args[0] : 0;
		$result = QuoteMerger::unmerge( $id );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		\WP_CLI::success( sprintf( 'Unmerged quote %d.', $id ) );
	}

	/**
	 * Show plugin health: WordPress version, quote CPT, counts, Abilities API.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpis doctor
	 *
	 * @return void
	 */
	public function doctor(): void {
		global $wp_version;
		\WP_CLI::log( 'WordPress: ' . ( is_string( $wp_version ) ? $wp_version : 'unknown' ) );
		\WP_CLI::log( 'wp_register_ability: ' . ( function_exists( 'wp_register_ability' ) ? 'yes' : 'no' ) );
		$cpt = post_type_exists( QuotePostType::POST_TYPE ) ? 'yes' : 'no';
		\WP_CLI::log( 'quote CPT registered: ' . $cpt );
		$counts = wp_count_posts( QuotePostType::POST_TYPE );
		$pub    = isset( $counts->publish ) ? (int) $counts->publish : 0;
		$pend   = isset( $counts->pending ) ? (int) $counts->pending : 0;
		\WP_CLI::log( sprintf( 'quotes publish=%d pending=%d', $pub, $pend ) );
		\WP_CLI::success( 'WPIS doctor complete.' );
	}

	/**
	 * Seed demo quotes (flagged with _wpis_demo_seed). Use --erase to remove them.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<n>]
	 * : How many quotes to create (max dataset size). Default: all (24).
	 *
	 * [--erase]
	 * : Delete previously seeded demo quotes only.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wpis seed_demo
	 *     wp wpis seed_demo --count=12
	 *     wp wpis seed_demo --erase
	 *
	 * @param array<int, string>   $args Positional args.
	 * @param array<string, mixed> $assoc_args Flags.
	 * @return void
	 */
	public function seed_demo( array $args, array $assoc_args ): void {
		unset( $args );
		if ( ! empty( $assoc_args['erase'] ) ) {
			$n = DemoSeeder::erase();
			\WP_CLI::success( sprintf( 'Removed %d demo quotes.', $n ) );
			return;
		}
		$count = isset( $assoc_args['count'] ) ? max( 0, (int) $assoc_args['count'] ) : 0;
		$n     = DemoSeeder::seed( $count );
		\WP_CLI::success( sprintf( 'Created %d demo quotes.', $n ) );
	}

	/**
	 * List potential duplicates for a quote’s text (or by ID).
	 *
	 * ## OPTIONS
	 *
	 * <quote_id>
	 * : Quote post ID to load text from.
	 *
	 * @param array<int, string> $args Positional args.
	 * @return void
	 */
	public function find_duplicates( array $args ): void {
		$id   = isset( $args[0] ) ? (int) $args[0] : 0;
		$post = get_post( $id );
		if ( ! $post || QuotePostType::POST_TYPE !== $post->post_type ) {
			\WP_CLI::error( 'Invalid quote ID.' );
		}
		$rows = DuplicateFinder::find( $post->post_content, 'en', 50 );
		if ( empty( $rows ) ) {
			\WP_CLI::log( 'No duplicates found above threshold.' );
			return;
		}
		foreach ( $rows as $row ) {
			\WP_CLI::log(
				sprintf(
					'%d | score %s | %s',
					$row['quote_id'],
					(string) $row['score'],
					$row['text_preview']
				)
			);
		}
	}
}

<?php
/**
 * WP-CLI commands for WPIS.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\CLI;

use WPIS\Core\Dedup\DuplicateFinder;
use WPIS\Core\Merge\QuoteMerger;

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
		if ( ! $post ) {
			\WP_CLI::error( 'Invalid post ID.' );
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

<?php
/**
 * Public API wrappers for themes and external code.
 *
 * @package WPIS\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Merge one quote into another.
 *
 * @param int $source_id Source post ID.
 * @param int $target_id Target post ID.
 * @return true|\WP_Error
 */
function wpis_merge_quote( int $source_id, int $target_id ) {
	return \WPIS\Core\Merge\QuoteMerger::merge( $source_id, $target_id );
}

/**
 * Restore a merged quote.
 *
 * @param int $quote_id Quote ID (merged status).
 * @return true|\WP_Error
 */
function wpis_unmerge_quote( int $quote_id ) {
	return \WPIS\Core\Merge\QuoteMerger::unmerge( $quote_id );
}

/**
 * Ranked list of similar quotes.
 *
 * @param string $text      Needle text.
 * @param string $lang      Language code.
 * @param int    $threshold Minimum score 0–100.
 * @return array<int, array{quote_id: int, score: float, text_preview: string}>
 */
function wpis_find_potential_duplicates( string $text, string $lang = 'en', int $threshold = 70 ): array {
	return \WPIS\Core\Dedup\DuplicateFinder::find( $text, $lang, $threshold );
}

/**
 * Stats for a contributor.
 *
 * @param int $user_id User ID.
 * @return array<string, int|float>
 */
function wpis_get_user_stats( int $user_id ): array {
	return \WPIS\Core\User\UserStats::get( $user_id );
}

/**
 * Submit a quote candidate (bot, extension, CLI): deduplicate or create pending quote.
 *
 * @param array<string, mixed> $args See \WPIS\Core\Submission\QuoteCandidateSubmitter::submit().
 * @return array{result: string, post_id?: int, error?: string}
 */
function wpis_submit_quote_candidate( array $args ): array {
	return \WPIS\Core\Submission\QuoteCandidateSubmitter::submit( $args );
}

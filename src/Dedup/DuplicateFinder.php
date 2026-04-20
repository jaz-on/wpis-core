<?php
/**
 * Heuristic duplicate detection for quotes.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Dedup;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Find similar existing quotes by normalized string comparison.
 */
final class DuplicateFinder {

	/**
	 * Find potential duplicate quotes ranked by similarity score (0–100).
	 *
	 * Second pass (embeddings) can be added via `wpis_find_potential_duplicates_semantic` filter: not implemented here.
	 *
	 * @param string $text      Text to compare.
	 * @param string $lang      Language code (reserved for future use).
	 * @param int    $threshold Minimum score 0–100 to include.
	 * @return array<int, array{quote_id: int, score: float, text_preview: string}>
	 */
	public static function find( string $text, string $lang = 'en', int $threshold = 70 ): array {
		unset( $lang );

		$normalized = self::normalize( $text );
		if ( '' === $normalized ) {
			return array();
		}

		$q = new \WP_Query(
			array(
				'post_type'      => QuotePostType::POST_TYPE,
				'post_status'    => array( 'publish', 'pending' ),
				'posts_per_page' => 200,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		$ranked = array();

		foreach ( $q->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$candidate = self::normalize( $post->post_content );
			if ( '' === $candidate ) {
				continue;
			}

			similar_text( $normalized, $candidate, $pct );

			/**
			 * Filters or replaces semantic duplicate scoring (embeddings, etc.).
			 *
			 * @param float  $pct       Similarity 0–100 from similar_text.
			 * @param string $normalized Normalized needle.
			 * @param string $candidate  Normalized haystack.
			 * @param int    $post_id   Candidate post ID.
			 */
			$pct = (float) apply_filters( 'wpis_find_potential_duplicates_semantic', $pct, $normalized, $candidate, $post->ID );

			if ( $pct < $threshold ) {
				continue;
			}

			$ranked[] = array(
				'quote_id'     => (int) $post->ID,
				'score'        => round( $pct, 2 ),
				'text_preview' => wp_html_excerpt( $post->post_content, 40, '…' ),
			);
		}

		usort(
			$ranked,
			static function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return $ranked;
	}

	/**
	 * @param string $text Raw text.
	 * @return string
	 */
	public static function normalize( string $text ): string {
		$t = strtolower( wp_strip_all_tags( $text ) );
		$t = preg_replace( '/[^\p{L}\p{N}\s]/u', '', $t );
		$t = preg_replace( '/\s+/u', ' ', $t );
		return trim( (string) $t );
	}
}

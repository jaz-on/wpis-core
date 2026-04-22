<?php
/**
 * Demo quote seeding for staging and design QA.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\CLI;

use WPIS\Core\Activation;
use WPIS\Core\Constants;
use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Submission\QuoteDefaultOwner;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * Creates and removes demo quotes (meta flag _wpis_demo_seed).
 */
final class DemoSeeder {

	private const DEMO_META = '_wpis_demo_seed';

	/**
	 * Delete all demo quotes.
	 *
	 * @return int Number of posts deleted.
	 */
	public static function erase(): int {
		$ids = get_posts(
			array(
				'post_type'      => QuotePostType::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::DEMO_META,
						'value' => '1',
					),
				),
			)
		);
		$n   = 0;
		foreach ( $ids as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				++$n;
			}
		}
		return $n;
	}

	/**
	 * Insert demo quotes with taxonomies, meta, and opposing pairs.
	 *
	 * @param int $count Target number of posts (rounded to dataset size).
	 * @return int Number of posts created.
	 */
	public static function seed( int $count = 24 ): int {
		Activation::seed_default_terms();

		$dataset = QuoteSampleDataset::get_rows();
		if ( $count > 0 && $count < count( $dataset ) ) {
			$dataset = array_slice( $dataset, 0, $count );
		}

		$ids    = array();
		$author = QuoteDefaultOwner::get_user_id();
		foreach ( $dataset as $row ) {
			$post_id = wp_insert_post(
				array(
					'post_type'    => QuotePostType::POST_TYPE,
					'post_status'  => 'publish',
					'post_title'   => self::title_from_content( (string) $row['content'] ),
					'post_content' => $row['content'],
					'post_author'  => $author,
				),
				true
			);
			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}
			update_post_meta( (int) $post_id, self::DEMO_META, 1 );
			update_post_meta( (int) $post_id, '_wpis_counter', (int) $row['counter'] );
			update_post_meta( (int) $post_id, '_wpis_submission_source', 'form' );
			$platform = (string) $row['platform'];
			if ( ! in_array( $platform, Constants::SOURCE_PLATFORMS, true ) ) {
				$platform = 'blog';
			}
			update_post_meta( (int) $post_id, '_wpis_source_platform', $platform );
			update_post_meta( (int) $post_id, '_wpis_source_domain', sanitize_text_field( (string) $row['domain'] ) );

			wp_set_object_terms( (int) $post_id, (string) $row['sentiment'], SentimentTaxonomy::TAXONOMY, false );
			wp_set_object_terms( (int) $post_id, (string) $row['claim'], ClaimTypeTaxonomy::TAXONOMY, false );

			if ( ! empty( $row['editorial'] ) ) {
				$editorial = wp_kses(
					(string) $row['editorial'],
					array(
						'em'     => array(),
						'strong' => array(),
						'a'      => array( 'href' => array() ),
					)
				);
				update_post_meta( (int) $post_id, '_wpis_editorial_note', $editorial );
			}

			$ids[] = (int) $post_id;
		}

		$pairs = array(
			array( 0, 1 ),
			array( 2, 3 ),
			array( 4, 5 ),
			array( 6, 7 ),
			array( 8, 9 ),
			array( 10, 11 ),
		);
		foreach ( $pairs as $pair ) {
			$a = $ids[ $pair[0] ] ?? 0;
			$b = $ids[ $pair[1] ] ?? 0;
			if ( $a && $b ) {
				update_post_meta( $a, '_wpis_opposing_quote_id', $b );
				update_post_meta( $b, '_wpis_opposing_quote_id', $a );
			}
		}

		return count( $ids );
	}

	/**
	 * @param string $text Content.
	 * @return string
	 */
	private static function title_from_content( string $text ): string {
		$t = wp_html_excerpt( $text, 72, '…' );
		return $t ? $t : 'Quote';
	}
}

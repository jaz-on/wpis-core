<?php
/**
 * Tests for AbilitiesRegistry stats output.
 *
 * @package WPIS\Core\Tests
 */

namespace WPIS\Core\Tests;

use WPIS\Core\Abilities\AbilitiesRegistry;
use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * @covers \WPIS\Core\Abilities\AbilitiesRegistry
 */
class AbilitiesRegistryStatsTest extends \WP_UnitTestCase {

	public function test_ability_stats_returns_shape_and_counts_by_status(): void {
		$this->factory->post->create(
			array(
				'post_type'   => QuotePostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);
		$this->factory->post->create(
			array(
				'post_type'   => QuotePostType::POST_TYPE,
				'post_status' => 'pending',
			)
		);

		$stats = AbilitiesRegistry::ability_stats();

		$this->assertArrayHasKey( 'total_quotes', $stats );
		$this->assertArrayHasKey( 'by_status', $stats );
		$this->assertArrayHasKey( 'by_sentiment', $stats );
		$this->assertArrayHasKey( 'by_claim_type', $stats );
		$this->assertArrayHasKey( 'by_language', $stats );
		$this->assertIsInt( $stats['total_quotes'] );
		$this->assertIsArray( $stats['by_status'] );
		$this->assertGreaterThanOrEqual( 1, $stats['by_status']['publish'] );
		$this->assertGreaterThanOrEqual( 1, $stats['by_status']['pending'] );
	}

	public function test_ability_stats_aggregates_sentiment_and_claim_taxonomies(): void {
		$pid = $this->factory->post->create(
			array(
				'post_type'   => QuotePostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);
		wp_set_object_terms( $pid, 'positive', SentimentTaxonomy::TAXONOMY, false );
		wp_set_object_terms( $pid, 'security', ClaimTypeTaxonomy::TAXONOMY, false );

		$stats = AbilitiesRegistry::ability_stats();

		$this->assertArrayHasKey( 'positive', $stats['by_sentiment'] );
		$this->assertGreaterThanOrEqual( 1, $stats['by_sentiment']['positive'] );
		$this->assertArrayHasKey( 'security', $stats['by_claim_type'] );
		$this->assertGreaterThanOrEqual( 1, $stats['by_claim_type']['security'] );
	}

	public function test_ability_stats_includes_by_language_all_without_polylang(): void {
		$stats = AbilitiesRegistry::ability_stats();
		$this->assertArrayHasKey( 'by_language', $stats );
		$this->assertArrayHasKey( '_all', $stats['by_language'] );
		$this->assertSame( (int) $stats['total_quotes'], (int) $stats['by_language']['_all'] );
	}
}

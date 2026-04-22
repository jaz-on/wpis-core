<?php
/**
 * Tests for CPT, taxonomies, meta and statuses registration.
 *
 * @package WPIS\Core\Tests
 */

namespace WPIS\Core\Tests;

use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * @coversNothing
 */
class QuoteRegistrationTest extends \WP_UnitTestCase {

	public function test_quote_post_type_is_registered(): void {
		$this->assertTrue( post_type_exists( QuotePostType::POST_TYPE ) );
	}

	public function test_sentiment_taxonomy_is_registered(): void {
		$this->assertTrue( taxonomy_exists( SentimentTaxonomy::TAXONOMY ) );
		$tax = get_taxonomy( SentimentTaxonomy::TAXONOMY );
		$this->assertFalse( $tax->hierarchical );
	}

	public function test_claim_type_taxonomy_is_registered(): void {
		$this->assertTrue( taxonomy_exists( ClaimTypeTaxonomy::TAXONOMY ) );
		$tax = get_taxonomy( ClaimTypeTaxonomy::TAXONOMY );
		$this->assertTrue( $tax->hierarchical );
	}

	public function test_custom_post_statuses_are_registered(): void {
		$statuses = get_post_stati( array(), 'objects' );
		$this->assertArrayHasKey( 'rejected', $statuses );
		$this->assertArrayHasKey( 'merged', $statuses );
	}

	public function test_quote_meta_is_registered(): void {
		$registered = get_registered_meta_keys( 'post', QuotePostType::POST_TYPE );
		$this->assertIsArray( $registered );
		$keys = array(
			'_wpis_counter',
			'_wpis_source_domain',
			'_wpis_source_platform',
			'_wpis_parent_id',
			'_wpis_rejection_reason',
			'_wpis_moderated_at',
			'_wpis_ai_snapshot',
			'_wpis_submission_source',
			'_wpis_source_language',
			'_wpis_original_text',
			'_wpis_opposing_quote_id',
			'_wpis_editorial_note',
		);
		foreach ( $keys as $key ) {
			$this->assertArrayHasKey( $key, $registered, "Meta {$key} should be registered." );
		}
	}
}

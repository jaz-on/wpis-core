<?php
/**
 * Tests for quote merge and validation.
 *
 * @package WPIS\Core\Tests
 */

namespace WPIS\Core\Tests;

use WPIS\Core\Merge\QuoteMerger;
use WPIS\Core\PostTypes\QuotePostType;

/**
 * @covers \WPIS\Core\Merge\QuoteMerger
 */
class QuoteMergerTest extends \WP_UnitTestCase {

	public function test_merge_rejects_same_source_and_target(): void {
		$pid = $this->factory->post->create(
			array(
				'post_type'   => QuotePostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);
		$r = QuoteMerger::merge( $pid, $pid );
		$this->assertWPError( $r );
	}

	public function test_merge_rejects_invalid_post_type(): void {
		$wrong = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$quote = $this->factory->post->create(
			array(
				'post_type'   => QuotePostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);
		$this->assertWPError( QuoteMerger::merge( $wrong, $quote ) );
		$this->assertWPError( QuoteMerger::merge( $quote, $wrong ) );
	}

	public function test_merge_combines_counters_and_marks_source_merged(): void {
		$source = $this->factory->post->create(
			array(
				'post_type'   => QuotePostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);
		$target = $this->factory->post->create(
			array(
				'post_type'   => QuotePostType::POST_TYPE,
				'post_status' => 'publish',
			)
		);
		update_post_meta( $source, '_wpis_counter', 2 );
		update_post_meta( $target, '_wpis_counter', 3 );

		$r = QuoteMerger::merge( (int) $source, (int) $target );
		$this->assertTrue( $r );
		$this->assertSame( 'merged', get_post_status( $source ) );
		$this->assertSame( 5, (int) get_post_meta( $target, '_wpis_counter', true ) );
		$this->assertSame( (int) $target, (int) get_post_meta( $source, '_wpis_parent_id', true ) );
	}
}

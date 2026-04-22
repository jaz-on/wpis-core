<?php
/**
 * Tests for programmatic quote candidates.
 *
 * @package WPIS\Core\Tests
 */

namespace WPIS\Core\Tests;

use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Submission\QuoteCandidateSubmitter;

/**
 * @covers \WPIS\Core\Submission\QuoteCandidateSubmitter
 */
class QuoteCandidateSubmitterTest extends \WP_UnitTestCase {

	public function test_submit_skips_empty_text(): void {
		$r = wpis_submit_quote_candidate(
			array(
				'text'              => '   ',
				'submission_source' => 'bot-mastodon',
				'source_platform'   => 'mastodon',
			)
		);
		$this->assertSame( QuoteCandidateSubmitter::RESULT_SKIPPED_EMPTY, $r['result'] );
	}

	public function test_submit_validates_submission_source(): void {
		$r = wpis_submit_quote_candidate(
			array(
				'text'              => 'WordPress is valid test',
				'submission_source' => 'not-a-real-source',
				'source_platform'   => 'mastodon',
			)
		);
		$this->assertSame( QuoteCandidateSubmitter::RESULT_ERROR_VALIDATION, $r['result'] );
	}

	public function test_submit_validates_source_platform(): void {
		$r = wpis_submit_quote_candidate(
			array(
				'text'              => 'WordPress is valid test',
				'submission_source' => 'bot-mastodon',
				'source_platform'   => 'not-a-platform',
			)
		);
		$this->assertSame( QuoteCandidateSubmitter::RESULT_ERROR_VALIDATION, $r['result'] );
	}

	public function test_submit_creates_pending_quote(): void {
		$text = 'WordPress is PHPUnit candidate ' . wp_generate_password( 12, false, false );
		$r    = wpis_submit_quote_candidate(
			array(
				'text'              => $text,
				'submission_source' => 'bot-mastodon',
				'source_platform'   => 'mastodon',
			)
		);
		$this->assertSame( QuoteCandidateSubmitter::RESULT_CREATED, $r['result'] );
		$this->assertArrayHasKey( 'post_id', $r );
		$post = get_post( (int) $r['post_id'] );
		$this->assertInstanceOf( \WP_Post::class, $post );
		$this->assertSame( QuotePostType::POST_TYPE, $post->post_type );
		$this->assertSame( 'pending', $post->post_status );
		$this->assertSame( wpis_default_quote_owner_user_id(), (int) $post->post_author );
		$this->assertSame( 'bot-mastodon', get_post_meta( $post->ID, '_wpis_submission_source', true ) );
		$this->assertSame( 'mastodon', get_post_meta( $post->ID, '_wpis_source_platform', true ) );
	}

	public function test_submit_stores_source_meta_for_non_english(): void {
		$text = 'WordPress is PHPUnit fr ' . wp_generate_password( 8, false, false );
		$r    = wpis_submit_quote_candidate(
			array(
				'text'              => $text,
				'submission_source' => 'bot-mastodon',
				'source_platform'   => 'mastodon',
				'lang'              => 'fr',
				'source_language'   => 'fr',
			)
		);
		$this->assertSame( QuoteCandidateSubmitter::RESULT_CREATED, $r['result'] );
		$post_id = (int) $r['post_id'];
		$this->assertSame( 'fr', get_post_meta( $post_id, '_wpis_source_language', true ) );
		$this->assertSame( $text, get_post_meta( $post_id, '_wpis_original_text', true ) );
		$post = get_post( $post_id );
		$this->assertStringContainsString( 'WordPress is PHPUnit', (string) $post->post_content );
	}
}

<?php
/**
 * Tests for public submission handler registration.
 *
 * @package WPIS\Core\Tests
 */

namespace WPIS\Core\Tests;

use WPIS\Core\Submission\SubmissionHandler;

/**
 * @covers \WPIS\Core\Submission\SubmissionHandler
 */
class SubmissionHandlerTest extends \WP_UnitTestCase {

	public function test_submission_action_hooks_are_registered(): void {
		$this->assertIsInt( has_action( 'admin_post_wpis_submit_quote', array( SubmissionHandler::class, 'handle' ) ) );
		$this->assertIsInt( has_action( 'admin_post_nopriv_wpis_submit_quote', array( SubmissionHandler::class, 'handle' ) ) );
	}

	public function test_template_redirect_hook_for_profile_noindex(): void {
		$this->assertIsInt( has_action( 'template_redirect', array( SubmissionHandler::class, 'maybe_noindex_profile' ) ) );
	}
}

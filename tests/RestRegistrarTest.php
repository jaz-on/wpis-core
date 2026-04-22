<?php
/**
 * Tests for custom REST routes.
 *
 * @package WPIS\Core\Tests
 */

namespace WPIS\Core\Tests;

/**
 * @covers \WPIS\Core\REST\RestRegistrar
 */
class RestRegistrarTest extends \WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		do_action( 'rest_api_init' );
	}

	public function test_my_stats_forbidden_when_logged_out(): void {
		wp_set_current_user( 0 );
		$request  = new \WP_REST_Request( 'GET', '/wpis/v1/my-stats' );
		$response = rest_get_server()->dispatch( $request );
		$code     = $response->get_status();
		$this->assertNotSame( 200, $code );
		$this->assertContains( $code, array( 401, 403 ), 'REST should not return stats to anonymous user.' );
	}

	public function test_my_stats_ok_when_logged_in(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$request  = new \WP_REST_Request( 'GET', '/wpis/v1/my-stats' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
	}
}

<?php
/**
 * REST API routes for WPIS.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\REST;

use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\User\UserStats;

/**
 * Registers REST routes.
 */
final class RestRegistrar {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * @return void
	 */
	public static function routes(): void {
		register_rest_route(
			'wpis/v1',
			'/my-stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'my_stats' ),
				'permission_callback' => array( self::class, 'logged_in' ),
			)
		);

		register_rest_route(
			'wpis/v1',
			'/my-quotes',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'my_quotes' ),
				'permission_callback' => array( self::class, 'logged_in' ),
			)
		);
	}

	/**
	 * @return bool|\WP_Error
	 */
	public static function logged_in() {
		return is_user_logged_in();
	}

	/**
	 * @return \WP_REST_Response
	 */
	public static function my_stats(): \WP_REST_Response {
		$uid = get_current_user_id();
		return new \WP_REST_Response( UserStats::get( $uid ) );
	}

	/**
	 * @return \WP_REST_Response
	 */
	public static function my_quotes(): \WP_REST_Response {
		$uid = get_current_user_id();
		$q   = new \WP_Query(
			array(
				'post_type'      => QuotePostType::POST_TYPE,
				'post_status'    => 'any',
				'author'         => $uid,
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$out = array();
		foreach ( $q->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			$out[] = array(
				'id'      => $post->ID,
				'status'  => $post->post_status,
				'excerpt' => wp_html_excerpt( $post->post_content, 120, '…' ),
				'date'    => $post->post_date,
			);
		}

		return new \WP_REST_Response( $out );
	}
}

<?php
/**
 * Propagate quote post status changes to Polylang siblings (group-level moderation).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Sync;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Mirrors publish / pending / rejected / merged across translation groups.
 */
final class GroupStatusSync {

	/**
	 * @var bool
	 */
	private static $running = false;

	/**
	 * Statuses that must stay aligned across translations.
	 */
	private const SYNC_STATUSES = array( 'publish', 'pending', 'rejected', 'merged' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'transition_post_status', array( self::class, 'on_transition' ), 10, 3 );
	}

	/**
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post.
	 * @return void
	 */
	public static function on_transition( string $new_status, string $old_status, $post ): void {
		if ( self::$running ) {
			return;
		}

		if ( ! $post instanceof \WP_Post || QuotePostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( $new_status === $old_status ) {
			return;
		}

		if ( ! in_array( $new_status, self::SYNC_STATUSES, true ) ) {
			return;
		}

		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return;
		}

		$map = pll_get_post_translations( $post->ID );
		if ( ! is_array( $map ) || count( $map ) < 2 ) {
			return;
		}

		self::$running = true;

		foreach ( $map as $pid ) {
			$pid = (int) $pid;
			if ( $pid === (int) $post->ID ) {
				continue;
			}
			$other = get_post( $pid );
			if ( ! $other ) {
				continue;
			}
			if ( $new_status === $other->post_status ) {
				continue;
			}
			wp_update_post(
				array(
					'ID'          => $pid,
					'post_status' => $new_status,
				)
			);
		}

		self::$running = false;
	}
}

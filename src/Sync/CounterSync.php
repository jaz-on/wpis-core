<?php
/**
 * Keep _wpis_counter aligned across Polylang translation groups.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Sync;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Propagates counter updates to sibling translations.
 */
final class CounterSync {

	/**
	 * @var bool
	 */
	private static $running = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'updated_postmeta', array( self::class, 'on_meta_update' ), 10, 4 );
	}

	/**
	 * When _wpis_counter changes, copy to other posts in the Polylang group.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value New value.
	 * @return void
	 */
	public static function on_meta_update( int $meta_id, int $post_id, string $meta_key, $meta_value ): void {
		unset( $meta_id );

		if ( self::$running ) {
			return;
		}

		if ( '_wpis_counter' !== $meta_key ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || QuotePostType::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return;
		}

		$new_val = (int) $meta_value;
		if ( $new_val < 1 ) {
			$new_val = 1;
		}

		$map = pll_get_post_translations( $post_id );
		if ( ! is_array( $map ) || count( $map ) < 2 ) {
			return;
		}

		self::$running = true;

		foreach ( $map as $pid ) {
			$pid = (int) $pid;
			if ( $pid === $post_id ) {
				continue;
			}
			$current = (int) get_post_meta( $pid, '_wpis_counter', true );
			if ( $current === $new_val ) {
				continue;
			}
			update_post_meta( $pid, '_wpis_counter', $new_val );
		}

		self::$running = false;
	}
}

<?php
/**
 * Merge and unmerge quote posts (Polylang-aware when available).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Merge;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Quote merge / unmerge operations.
 */
final class QuoteMerger {

	/**
	 * Merge source quote into target (and translation pairs when Polylang is active).
	 *
	 * @param int $source_id Source post ID.
	 * @param int $target_id Target post ID.
	 * @return true|\WP_Error
	 */
	public static function merge( int $source_id, int $target_id ) {
		if ( $source_id === $target_id ) {
			return new \WP_Error( 'wpis_merge_same', __( 'Source and target cannot be the same quote.', 'wpis-plugin' ) );
		}

		$source = get_post( $source_id );
		$target = get_post( $target_id );

		if ( ! $source || QuotePostType::POST_TYPE !== $source->post_type ) {
			return new \WP_Error( 'wpis_merge_source', __( 'Invalid source quote.', 'wpis-plugin' ) );
		}
		if ( ! $target || QuotePostType::POST_TYPE !== $target->post_type ) {
			return new \WP_Error( 'wpis_merge_target', __( 'Invalid target quote.', 'wpis-plugin' ) );
		}

		self::reparent_merged_children( $source_id, $target_id );

		if ( function_exists( 'pll_get_post_translations' ) ) {
			$st = pll_get_post_translations( $source_id );
			$tt = pll_get_post_translations( $target_id );
			if ( is_array( $st ) && is_array( $tt ) && ! empty( $st ) && ! empty( $tt ) ) {
				$result = self::merge_with_polylang( $source_id, $target_id );
			} else {
				$result = self::merge_single( $source_id, $target_id );
			}
		} else {
			$result = self::merge_single( $source_id, $target_id );
		}

		if ( true === $result ) {
			/**
			 * Fires after a quote has been merged into another.
			 *
			 * @param int $source_id Source post ID.
			 * @param int $target_id Target post ID.
			 */
			do_action( 'wpis_quote_merged', $source_id, $target_id );
		}

		return $result;
	}

	/**
	 * Single-language merge.
	 *
	 * @param int $source_id Source ID.
	 * @param int $target_id Target ID.
	 * @return true|\WP_Error
	 */
	private static function merge_single( int $source_id, int $target_id ) {
		$source_counter = self::get_counter( $source_id );
		$target_counter = self::get_counter( $target_id );

		update_post_meta( $target_id, '_wpis_counter', $target_counter + $source_counter );

		$updated = wp_update_post(
			array(
				'ID'          => $source_id,
				'post_status' => 'merged',
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		update_post_meta( $source_id, '_wpis_parent_id', $target_id );

		return true;
	}

	/**
	 * Merge translation groups pairwise (same language → same language).
	 *
	 * @param int $source_id Any post in the source group.
	 * @param int $target_id Any post in the target group.
	 * @return true|\WP_Error
	 */
	private static function merge_with_polylang( int $source_id, int $target_id ) {
		$source_map = pll_get_post_translations( $source_id );
		$target_map = pll_get_post_translations( $target_id );

		if ( ! is_array( $source_map ) ) {
			$source_map = array();
		}
		if ( ! is_array( $target_map ) ) {
			$target_map = array();
		}

		if ( empty( $source_map ) ) {
			$lang       = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $source_id ) : '';
			$source_map = $lang ? array( $lang => $source_id ) : array( 'default' => $source_id );
		}
		if ( empty( $target_map ) ) {
			$lang       = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $target_id ) : '';
			$target_map = $lang ? array( $lang => $target_id ) : array( 'default' => $target_id );
		}

		$default_target = $target_id;

		foreach ( $source_map as $lang => $sid ) {
			$sid = (int) $sid;
			$tid = isset( $target_map[ $lang ] ) ? (int) $target_map[ $lang ] : (int) $default_target;
			if ( $sid <= 0 || $tid <= 0 ) {
				continue;
			}

			$source_counter = self::get_counter( $sid );
			$target_counter = self::get_counter( $tid );

			update_post_meta( $tid, '_wpis_counter', $target_counter + $source_counter );

			$updated = wp_update_post(
				array(
					'ID'          => $sid,
					'post_status' => 'merged',
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			update_post_meta( $sid, '_wpis_parent_id', $tid );
		}

		return true;
	}

	/**
	 * Re-parent quotes that pointed to the source as merge parent.
	 *
	 * @param int $source_id Former parent.
	 * @param int $target_id New parent.
	 * @return void
	 */
	private static function reparent_merged_children( int $source_id, int $target_id ): void {
		$q = new \WP_Query(
			array(
				'post_type'      => QuotePostType::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_wpis_parent_id',
				'meta_value'     => $source_id,
			)
		);

		foreach ( $q->posts as $child_id ) {
			update_post_meta( (int) $child_id, '_wpis_parent_id', $target_id );
		}
	}

	/**
	 * Unmerge a merged quote back to published.
	 *
	 * @param int $quote_id Merged quote ID.
	 * @return true|\WP_Error
	 */
	public static function unmerge( int $quote_id ) {
		$post = get_post( $quote_id );
		if ( ! $post || QuotePostType::POST_TYPE !== $post->post_type ) {
			return new \WP_Error( 'wpis_unmerge_post', __( 'Invalid quote.', 'wpis-plugin' ) );
		}

		if ( 'merged' !== $post->post_status ) {
			return new \WP_Error( 'wpis_unmerge_status', __( 'Quote is not in merged status.', 'wpis-plugin' ) );
		}

		$parent_id = (int) get_post_meta( $quote_id, '_wpis_parent_id', true );
		if ( $parent_id <= 0 ) {
			return new \WP_Error( 'wpis_unmerge_parent', __( 'Missing parent reference.', 'wpis-plugin' ) );
		}

		$source_counter = self::get_counter( $quote_id );
		$parent_counter = self::get_counter( $parent_id );

		$new_parent = max( 0, $parent_counter - $source_counter );
		if ( $new_parent < 1 ) {
			$new_parent = 1;
		}
		update_post_meta( $parent_id, '_wpis_counter', $new_parent );

		delete_post_meta( $quote_id, '_wpis_parent_id' );

		$updated = wp_update_post(
			array(
				'ID'          => $quote_id,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		/**
		 * Fires after a merged quote was restored.
		 *
		 * @param int $quote_id Quote ID.
		 * @param int $parent_id Former parent ID.
		 */
		do_action( 'wpis_quote_unmerged', $quote_id, $parent_id );

		return true;
	}

	/**
	 * @param int $post_id Post ID.
	 * @return int
	 */
	private static function get_counter( int $post_id ): int {
		$c = (int) get_post_meta( $post_id, '_wpis_counter', true );
		return $c > 0 ? $c : 1;
	}
}

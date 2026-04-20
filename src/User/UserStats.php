<?php
/**
 * Contributor statistics.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\User;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Stats for a WordPress user’s quote submissions.
 */
final class UserStats {

	/**
	 * @param int $user_id User ID.
	 * @return array{total_submitted: int, validated: int, pending: int, rejected: int, merged: int, acceptance_rate: float}
	 */
	public static function get( int $user_id ): array {
		$counts = array(
			'publish'  => 0,
			'pending'  => 0,
			'rejected' => 0,
			'merged'   => 0,
		);

		foreach ( array_keys( $counts ) as $status ) {
			$q                 = new \WP_Query(
				array(
					'post_type'      => QuotePostType::POST_TYPE,
					'post_status'    => $status,
					'author'         => $user_id,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			$counts[ $status ] = count( $q->posts );
		}

		$total     = array_sum( $counts );
		$validated = $counts['publish'] + $counts['merged'];
		$rate      = $total > 0 ? round( 100 * ( $validated / $total ), 1 ) : 0.0;

		return array(
			'total_submitted' => $total,
			'validated'       => $counts['publish'],
			'pending'         => $counts['pending'],
			'rejected'        => $counts['rejected'],
			'merged'          => $counts['merged'],
			'acceptance_rate' => $rate,
		);
	}
}

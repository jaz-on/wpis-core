<?php
/**
 * Resolves the WordPress user ID used when no real contributor is known (seed data, bots, anonymous form).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Submission;

/**
 * Default `post_author` for quotes and seeded content.
 */
final class QuoteDefaultOwner {

	/**
	 * User ID to attribute quotes and sample content to when the submitter is not a logged-in user.
	 *
	 * Default: `1` when that user exists, else the first administrator, else `0`.
	 *
	 * @return int
	 */
	public static function get_user_id(): int {
		$from_filter = (int) apply_filters( 'wpis_default_quote_owner_user_id', 0 );
		if ( $from_filter > 0 && get_userdata( $from_filter ) ) {
			return $from_filter;
		}

		if ( get_userdata( 1 ) ) {
			return 1;
		}

		$ids = get_users(
			array(
				'role'    => 'administrator',
				'number'  => 1,
				'orderby' => 'ID',
				'order'   => 'ASC',
				'fields'  => 'ID',
			)
		);
		if ( is_array( $ids ) && array() !== $ids && isset( $ids[0] ) ) {
			return (int) $ids[0];
		}

		return 0;
	}
}

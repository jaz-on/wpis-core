<?php
/**
 * Custom post statuses for the quote CPT.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core;

/**
 * Registers rejected and merged statuses (pending and publish are core).
 */
final class PostStatuses {

	/**
	 * Register custom statuses for quotes.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_post_status(
			'rejected',
			array(
				'label'                     => _x( 'Rejected', 'post status', 'wpis-core' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of posts */
				'label_count'               => _n_noop(
					'Rejected <span class="count">(%s)</span>',
					'Rejected <span class="count">(%s)</span>',
					'wpis-core'
				),
			)
		);

		register_post_status(
			'merged',
			array(
				'label'                     => _x( 'Merged', 'post status', 'wpis-core' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of posts */
				'label_count'               => _n_noop(
					'Merged <span class="count">(%s)</span>',
					'Merged <span class="count">(%s)</span>',
					'wpis-core'
				),
			)
		);
	}
}

<?php
/**
 * Deletes stale temporary screenshot uploads.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Submission;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Daily cron for media cleanup.
 */
final class CronCleanup {

	public const CRON_HOOK = 'wpis_cleanup_temporary_uploads';

	/**
	 * @return void
	 */
	public static function register(): void {
		add_action( self::CRON_HOOK, array( self::class, 'run' ) );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * @return void
	 */
	public static function run(): void {
		$q = new \WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 50,
				'meta_key'       => '_wpis_temporary_upload',
				'meta_value'     => '1',
				'date_query'     => array(
					array(
						'before'    => '7 days ago',
						'inclusive' => true,
					),
				),
				'fields'         => 'ids',
			)
		);

		foreach ( $q->posts as $aid ) {
			$parent = (int) wp_get_post_parent_id( $aid );
			if ( ! $parent ) {
				wp_delete_attachment( (int) $aid, true );
				continue;
			}
			$p = get_post( $parent );
			if ( ! $p || QuotePostType::POST_TYPE !== $p->post_type ) {
				wp_delete_attachment( (int) $aid, true );
				continue;
			}
			if ( in_array( $p->post_status, array( 'pending', 'rejected' ), true ) ) {
				wp_delete_attachment( (int) $aid, true );
			}
		}
	}
}

<?php
/**
 * Quote post meta registration (REST-enabled).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Meta;

use WPIS\Core\Constants;
use WPIS\Core\PostTypes\QuotePostType;

/**
 * Registers protected post meta for quotes.
 */
final class QuoteMeta {

	/**
	 * Register all quote meta keys.
	 *
	 * @return void
	 */
	public static function register(): void {
		$auth = array( self::class, 'auth_edit_post' );

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_counter',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 0,
					),
				),
				'sanitize_callback' => 'absint',
				'auth_callback'     => $auth,
				'default'           => 1,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_source_domain',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_source_platform',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( self::class, 'sanitize_platform' ),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_parent_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_rejection_reason',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( self::class, 'sanitize_rejection_reason' ),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_moderated_at',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_ai_snapshot',
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
				),
				'sanitize_callback' => array( self::class, 'sanitize_ai_snapshot' ),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_submission_source',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( self::class, 'sanitize_submission_source' ),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_source_language',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_original_text',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_opposing_quote_id',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			QuotePostType::POST_TYPE,
			'_wpis_editorial_note',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => $auth,
			)
		);
	}

	/**
	 * Only users who can edit the quote see or change meta via REST.
	 *
	 * @param bool   $allowed Whether to allow.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id Post ID.
	 * @return bool
	 */
	public static function auth_edit_post( bool $allowed, string $meta_key, int $post_id ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		unset( $allowed, $meta_key );
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Sanitize platform slug against the allow list.
	 *
	 * @param string $value Raw platform slug.
	 * @return string
	 */
	public static function sanitize_platform( $value ): string {
		$value = sanitize_key( (string) $value );
		if ( ! in_array( $value, Constants::SOURCE_PLATFORMS, true ) ) {
			return 'other';
		}
		return $value;
	}

	/**
	 * Sanitize rejection reason against the allow list.
	 *
	 * @param string $value Raw reason.
	 * @return string
	 */
	public static function sanitize_rejection_reason( $value ): string {
		$value = sanitize_key( (string) $value );
		if ( ! in_array( $value, Constants::REJECTION_REASONS, true ) ) {
			return 'other';
		}
		return $value;
	}

	/**
	 * Sanitize submission source against the allow list.
	 *
	 * @param string $value Raw submission source.
	 * @return string
	 */
	public static function sanitize_submission_source( $value ): string {
		$value = sanitize_key( (string) $value );
		if ( ! in_array( $value, Constants::SUBMISSION_SOURCES, true ) ) {
			return 'form';
		}
		return $value;
	}

	/**
	 * Normalize AI snapshot to an array for storage.
	 *
	 * @param mixed $value Snapshot (array/object).
	 * @return array<string, mixed>
	 */
	public static function sanitize_ai_snapshot( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return $value;
	}
}

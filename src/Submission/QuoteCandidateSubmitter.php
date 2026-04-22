<?php
/**
 * Programmatic quote candidates (bots, CLI, integrations): dedup, pending post, meta.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Submission;

use WPIS\Core\Constants;
use WPIS\Core\PostTypes\QuotePostType;

/**
 * Shared ingestion path for non-form submissions.
 */
final class QuoteCandidateSubmitter {

	public const RESULT_CREATED          = 'created';
	public const RESULT_BUMPED           = 'bumped';
	public const RESULT_SKIPPED_EMPTY    = 'skipped_empty';
	public const RESULT_SKIPPED_LONG     = 'skipped_too_long';
	public const RESULT_ERROR_INSERT     = 'error_insert';
	public const RESULT_ERROR_VALIDATION = 'error_validation';

	private const BODY_MAX_CHARS = 1000;

	/**
	 * Create a pending quote or bump an existing duplicate’s counter.
	 *
	 * @param array<string, mixed> $args {
	 *     Arguments.
	 *
	 *     @type string $text              Quote body.
	 *     @type string $submission_source One of Constants::SUBMISSION_SOURCES.
	 *     @type string $source_platform   One of Constants::SOURCE_PLATFORMS.
	 *     @type string $lang                Dedup language hint. Default en.
	 *     @type int    $dedup_threshold     0-100. Default 70.
	 *     @type string $source_url          Optional canonical URL.
	 *     @type string $polylang_slug      Optional Polylang language slug.
	 *     @type string $source_language     Optional BCP-47 or short code. With `text`, used for the EN pivot; stored when not English.
	 * }
	 * @return array{result: string, post_id?: int, error?: string}
	 */
	public static function submit( array $args ): array {
		if ( ! function_exists( 'wpis_find_potential_duplicates' ) ) {
			return array(
				'result' => self::RESULT_ERROR_INSERT,
				'error'  => 'wpis_find_potential_duplicates unavailable',
			);
		}

		$text              = isset( $args['text'] ) ? (string) $args['text'] : '';
		$submission_source = isset( $args['submission_source'] ) ? sanitize_key( (string) $args['submission_source'] ) : '';
		$source_platform   = isset( $args['source_platform'] ) ? sanitize_key( (string) $args['source_platform'] ) : '';
		$lang              = isset( $args['lang'] ) ? sanitize_key( (string) $args['lang'] ) : 'en';
		$dedup_threshold   = isset( $args['dedup_threshold'] ) ? max( 0, min( 100, (int) $args['dedup_threshold'] ) ) : 70;
		$source_url        = isset( $args['source_url'] ) ? esc_url_raw( (string) $args['source_url'] ) : '';
		$polylang_slug     = isset( $args['polylang_slug'] ) ? sanitize_key( (string) $args['polylang_slug'] ) : '';
		$source_language   = isset( $args['source_language'] ) && '' !== (string) $args['source_language']
			? sanitize_key( (string) $args['source_language'] )
			: $lang;

		if ( ! in_array( $submission_source, Constants::SUBMISSION_SOURCES, true ) ) {
			return array(
				'result' => self::RESULT_ERROR_VALIDATION,
				'error'  => 'invalid submission_source',
			);
		}
		if ( ! in_array( $source_platform, Constants::SOURCE_PLATFORMS, true ) ) {
			return array(
				'result' => self::RESULT_ERROR_VALIDATION,
				'error'  => 'invalid source_platform',
			);
		}

		$text = trim( preg_replace( '/\s+/u', ' ', $text ) ?? '' );
		if ( '' === $text ) {
			return array( 'result' => self::RESULT_SKIPPED_EMPTY );
		}

		$raw_len = function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
		if ( $raw_len > self::BODY_MAX_CHARS ) {
			return array( 'result' => self::RESULT_SKIPPED_LONG );
		}

		$context = array(
			'submission_source' => $submission_source,
			'source_platform'   => $source_platform,
			'source_url'        => $source_url,
		);
		$body    = $text;
		if ( ! self::is_english_source( $source_language ) ) {
			/**
			 * Return English body text for a non-English candidate. Default: unchanged (no paid API in core).
			 *
			 * @param string               $text         Trimmed source text.
			 * @param string               $source_lang  Normalized key (e.g. fr, de).
			 * @param array<string, mixed> $context     submission_source, source_platform, source_url.
			 */
			$body = (string) apply_filters( 'wpis_bot_translate_to_english', $text, $source_language, $context );
		}
		$body = trim( preg_replace( '/\s+/u', ' ', $body ) ?? '' );
		if ( '' === $body ) {
			return array( 'result' => self::RESULT_SKIPPED_EMPTY );
		}

		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $body, 'UTF-8' ) : strlen( $body );
		if ( $len > self::BODY_MAX_CHARS ) {
			return array( 'result' => self::RESULT_SKIPPED_LONG );
		}

		$dupes = wpis_find_potential_duplicates( $text, $lang, $dedup_threshold );
		if ( array() !== $dupes && isset( $dupes[0]['quote_id'], $dupes[0]['score'] ) && (float) $dupes[0]['score'] >= (float) $dedup_threshold ) {
			$qid     = (int) $dupes[0]['quote_id'];
			$current = (int) get_post_meta( $qid, '_wpis_counter', true );
			if ( $current < 1 ) {
				$current = 1;
			}
			update_post_meta( $qid, '_wpis_counter', $current + 1 );
			return array(
				'result'  => self::RESULT_BUMPED,
				'post_id' => $qid,
			);
		}

		$title = self::title_from_text( $body );
		$body  = self::truncate_body( $body, self::BODY_MAX_CHARS );

		$post_id = wp_insert_post(
			array(
				'post_type'    => QuotePostType::POST_TYPE,
				'post_status'  => 'pending',
				'post_title'   => $title,
				'post_content' => $body,
				'post_author'  => QuoteDefaultOwner::get_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			$msg = is_wp_error( $post_id ) ? $post_id->get_error_message() : 'insert failed';
			return array(
				'result' => self::RESULT_ERROR_INSERT,
				'error'  => $msg,
			);
		}

		$post_id = (int) $post_id;

		update_post_meta( $post_id, '_wpis_counter', 1 );
		update_post_meta( $post_id, '_wpis_submission_source', $submission_source );
		update_post_meta( $post_id, '_wpis_source_platform', $source_platform );

		if ( $source_url ) {
			$domain = wp_parse_url( $source_url, PHP_URL_HOST );
			if ( is_string( $domain ) && '' !== $domain ) {
				update_post_meta( $post_id, '_wpis_source_domain', sanitize_text_field( $domain ) );
			}
		}

		if ( '' !== $polylang_slug && function_exists( 'pll_languages_list' ) && function_exists( 'pll_set_post_language' ) ) {
			$valid = pll_languages_list( array( 'fields' => 'slug' ) );
			if ( is_array( $valid ) && in_array( $polylang_slug, $valid, true ) ) {
				pll_set_post_language( $post_id, $polylang_slug );
			}
		}

		if ( ! self::is_english_source( $source_language ) ) {
			update_post_meta( $post_id, '_wpis_source_language', $source_language );
			update_post_meta( $post_id, '_wpis_original_text', $text );
		}

		/**
		 * Fires after a programmatic quote candidate was stored as pending.
		 *
		 * @param int $post_id New quote ID.
		 */
		do_action( 'wpis_quote_submitted', $post_id );

		return array(
			'result'  => self::RESULT_CREATED,
			'post_id' => $post_id,
		);
	}

	/**
	 * BCP-47 / short code: treat "en" and tags starting with "en-" as English.
	 *
	 * @param string $code Sanitized key or BCP-47 style tag.
	 * @return bool
	 */
	private static function is_english_source( string $code ): bool {
		if ( '' === $code ) {
			return true;
		}
		$c = str_replace( '_', '-', strtolower( $code ) );
		if ( 'en' === $c ) {
			return true;
		}
		return str_starts_with( $c, 'en-' );
	}

	/**
	 * @param string $text Raw body.
	 * @param int    $max  Max length.
	 * @return string
	 */
	private static function truncate_body( string $text, int $max ): string {
		$text = trim( $text );
		if ( $max < 1 ) {
			return '';
		}
		if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
			if ( mb_strlen( $text, 'UTF-8' ) <= $max ) {
				return $text;
			}
			return mb_substr( $text, 0, $max, 'UTF-8' );
		}
		if ( strlen( $text ) <= $max ) {
			return $text;
		}
		return substr( $text, 0, $max );
	}

	/**
	 * @param string $text Quote text.
	 * @return string
	 */
	private static function title_from_text( string $text ): string {
		$t = wp_html_excerpt( $text, 80, '…' );
		return '' !== $t ? $t : __( 'Quote submission', 'wpis-core' );
	}
}

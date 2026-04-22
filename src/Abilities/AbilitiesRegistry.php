<?php
/**
 * WordPress Abilities API registration for WPIS.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Abilities;

use WPIS\Core\Dedup\DuplicateFinder;
use WPIS\Core\Merge\QuoteMerger;
use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Submission\QuoteDefaultOwner;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * Registers wpis/* abilities and MCP allowlist entries.
 */
final class AbilitiesRegistry {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_action( 'wp_abilities_api_init', array( self::class, 'register_abilities' ) );
		add_filter( 'wpis_mcp_abilities', array( self::class, 'mcp_allowlist' ) );
	}

	/**
	 * @param string[] $abilities Ability names.
	 * @return string[]
	 */
	public static function mcp_allowlist( array $abilities ): array {
		$extra = array(
			'wpis/quote-create',
			'wpis/quote-update',
			'wpis/quote-merge',
			'wpis/quote-find-duplicates',
			'wpis/stats-summary',
		);
		return array_merge( $abilities, $extra );
	}

	/**
	 * @return void
	 */
	public static function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'wpis/quote-create',
			array(
				'label'               => __( 'Create quote', 'wpis-core' ),
				'description'         => __( 'Create a new quote submission.', 'wpis-core' ),
				'category'            => 'wpis',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'text'            => array( 'type' => 'string' ),
						'language'        => array( 'type' => 'string' ),
						'source_url'      => array( 'type' => 'string' ),
						'source_platform' => array( 'type' => 'string' ),
						'status'          => array( 'type' => 'string' ),
					),
					'required'   => array( 'text', 'language' ),
				),
				'execute_callback'    => array( self::class, 'ability_create' ),
				'permission_callback' => array( self::class, 'can_create_posts' ),
			)
		);

		wp_register_ability(
			'wpis/quote-update',
			array(
				'label'               => __( 'Update quote', 'wpis-core' ),
				'description'         => __( 'Update quote content, status or terms.', 'wpis-core' ),
				'category'            => 'wpis',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'quote_id'   => array( 'type' => 'integer' ),
						'text'       => array( 'type' => 'string' ),
						'status'     => array( 'type' => 'string' ),
						'sentiment'  => array( 'type' => 'string' ),
						'claim_type' => array( 'type' => 'string' ),
					),
					'required'   => array( 'quote_id' ),
				),
				'execute_callback'    => array( self::class, 'ability_update' ),
				'permission_callback' => array( self::class, 'can_edit_quote_input' ),
			)
		);

		wp_register_ability(
			'wpis/quote-merge',
			array(
				'label'               => __( 'Merge quotes', 'wpis-core' ),
				'description'         => __( 'Merge a source quote into a target quote.', 'wpis-core' ),
				'category'            => 'wpis',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'source_id' => array( 'type' => 'integer' ),
						'target_id' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'source_id', 'target_id' ),
				),
				'execute_callback'    => array( self::class, 'ability_merge' ),
				'permission_callback' => array( self::class, 'can_manage' ),
			)
		);

		wp_register_ability(
			'wpis/quote-find-duplicates',
			array(
				'label'               => __( 'Find duplicate quotes', 'wpis-core' ),
				'description'         => __( 'Rank similar existing quotes by string similarity.', 'wpis-core' ),
				'category'            => 'wpis',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'text'      => array( 'type' => 'string' ),
						'language'  => array( 'type' => 'string' ),
						'threshold' => array( 'type' => 'integer' ),
					),
					'required'   => array( 'text' ),
				),
				'execute_callback'    => array( self::class, 'ability_duplicates' ),
				'permission_callback' => array( self::class, 'can_edit_posts' ),
			)
		);

		wp_register_ability(
			'wpis/stats-summary',
			array(
				'label'               => __( 'WPIS stats summary', 'wpis-core' ),
				'description'         => __( 'Aggregate quote counts by status, sentiment and claim type.', 'wpis-core' ),
				'category'            => 'wpis',
				'input_schema'        => array(
					'type' => 'object',
				),
				'execute_callback'    => array( self::class, 'ability_stats' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @return bool
	 */
	public static function can_create_posts(): bool {
		return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
	}

	/**
	 * @param array<string, mixed> $input Input payload.
	 * @return bool
	 */
	public static function can_edit_quote_input( array $input ): bool {
		$id = isset( $input['quote_id'] ) ? (int) $input['quote_id'] : 0;
		return $id > 0 && current_user_can( 'edit_post', $id );
	}

	/**
	 * @return bool
	 */
	public static function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * @return bool
	 */
	public static function can_edit_posts(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function ability_create( array $input ) {
		$text = isset( $input['text'] ) ? (string) $input['text'] : '';
		if ( '' === trim( $text ) ) {
			return new \WP_Error( 'wpis_empty', __( 'Text required.', 'wpis-core' ) );
		}
		$status = isset( $input['status'] ) ? (string) $input['status'] : 'pending';
		if ( ! in_array( $status, array( 'pending', 'publish', 'draft' ), true ) ) {
			$status = 'pending';
		}
		$uid = get_current_user_id();
		if ( 0 === $uid ) {
			$uid = QuoteDefaultOwner::get_user_id();
		}
		$pid = wp_insert_post(
			array(
				'post_type'    => QuotePostType::POST_TYPE,
				'post_status'  => $status,
				'post_title'   => wp_html_excerpt( $text, 80, '…' ),
				'post_content' => $text,
				'post_author'  => $uid,
			),
			true
		);
		if ( is_wp_error( $pid ) ) {
			return $pid;
		}
		update_post_meta( $pid, '_wpis_counter', 1 );
		update_post_meta( $pid, '_wpis_submission_source', 'form' );
		return array(
			'quote_id' => (int) $pid,
			'status'   => $status,
			'url'      => get_permalink( $pid ),
		);
	}

	/**
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function ability_update( array $input ) {
		$id = isset( $input['quote_id'] ) ? (int) $input['quote_id'] : 0;
		if ( $id <= 0 ) {
			return new \WP_Error( 'wpis_bad_id', __( 'Bad quote ID.', 'wpis-core' ) );
		}
		$fields = array();
		if ( isset( $input['text'] ) ) {
			wp_update_post(
				array(
					'ID'           => $id,
					'post_content' => (string) $input['text'],
				)
			);
			$fields[] = 'text';
		}
		if ( isset( $input['status'] ) ) {
			wp_update_post(
				array(
					'ID'          => $id,
					'post_status' => (string) $input['status'],
				)
			);
			$fields[] = 'status';
		}
		if ( ! empty( $input['sentiment'] ) ) {
			wp_set_object_terms( $id, (string) $input['sentiment'], SentimentTaxonomy::TAXONOMY );
			$fields[] = 'sentiment';
		}
		if ( ! empty( $input['claim_type'] ) ) {
			wp_set_object_terms( $id, (string) $input['claim_type'], ClaimTypeTaxonomy::TAXONOMY );
			$fields[] = 'claim_type';
		}
		return array(
			'quote_id'       => $id,
			'updated_fields' => $fields,
		);
	}

	/**
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function ability_merge( array $input ) {
		$s = isset( $input['source_id'] ) ? (int) $input['source_id'] : 0;
		$t = isset( $input['target_id'] ) ? (int) $input['target_id'] : 0;
		$r = QuoteMerger::merge( $s, $t );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		return array(
			'target_id'   => $t,
			'new_counter' => (int) get_post_meta( $t, '_wpis_counter', true ),
		);
	}

	/**
	 * @param array<string, mixed> $input Input.
	 * @return array<int, array<string, mixed>>
	 */
	public static function ability_duplicates( array $input ) {
		$text = isset( $input['text'] ) ? (string) $input['text'] : '';
		$lang = isset( $input['language'] ) ? (string) $input['language'] : 'en';
		$thr  = isset( $input['threshold'] ) ? (int) $input['threshold'] : 70;
		return DuplicateFinder::find( $text, $lang, $thr );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function ability_stats(): array {
		$statuses = self::countable_post_statuses();
		$by       = self::count_quotes_by_status( $statuses );
		$total    = array_sum( $by );

		return array(
			'total_quotes'  => $total,
			'by_status'     => $by,
			'by_sentiment'  => self::count_quotes_by_taxonomy( SentimentTaxonomy::TAXONOMY, $statuses ),
			'by_claim_type' => self::count_quotes_by_taxonomy( ClaimTypeTaxonomy::TAXONOMY, $statuses ),
			'by_language'   => self::count_quotes_by_language( $statuses, $total ),
		);
	}

	/**
	 * Post statuses that participate in aggregate stats.
	 *
	 * @return string[]
	 */
	private static function countable_post_statuses(): array {
		return array( 'publish', 'pending', 'rejected', 'merged' );
	}

	/**
	 * @param string[] $statuses Post statuses.
	 * @return array<string, int> Keys are status slugs.
	 */
	private static function count_quotes_by_status( array $statuses ): array {
		$by = array();
		foreach ( $statuses as $st ) {
			$q         = new \WP_Query(
				array(
					'post_type'      => QuotePostType::POST_TYPE,
					'post_status'    => $st,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			$by[ $st ] = count( $q->posts );
		}
		return $by;
	}

	/**
	 * Count quotes per term slug for a taxonomy (all given statuses).
	 *
	 * @param string   $taxonomy  Registered taxonomy.
	 * @param string[] $statuses  Post status slugs.
	 * @return array<string, int> Keys are term slugs.
	 */
	private static function count_quotes_by_taxonomy( string $taxonomy, array $statuses ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}
		$out = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$query              = new \WP_Query(
				array(
					'post_type'      => QuotePostType::POST_TYPE,
					'post_status'    => $statuses,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => $taxonomy,
							'field'    => 'term_id',
							'terms'    => (int) $term->term_id,
						),
					),
				)
			);
			$out[ $term->slug ] = count( $query->posts );
		}
		return $out;
	}

	/**
	 * When Polylang is active, count quotes per language slug; otherwise one bucket for the site.
	 *
	 * @param string[] $statuses Post status slugs.
	 * @param int      $total    Total quote count (used when Polylang is off).
	 * @return array<string, int>
	 */
	private static function count_quotes_by_language( array $statuses, int $total ): array {
		if ( ! function_exists( 'pll_languages_list' ) ) {
			return array( '_all' => $total );
		}
		$langs = pll_languages_list( array( 'fields' => 'slug' ) );
		if ( ! is_array( $langs ) || array() === $langs ) {
			return array( '_all' => $total );
		}
		$out = array();
		foreach ( $langs as $slug ) {
			$query                 = new \WP_Query(
				array(
					'post_type'      => QuotePostType::POST_TYPE,
					'post_status'    => $statuses,
					'lang'           => $slug,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			$out[ (string) $slug ] = count( $query->posts );
		}
		return $out;
	}
}

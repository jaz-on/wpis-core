<?php
/**
 * Shortcodes for public screens (home stats, explore grids).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Shortcodes;

use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * Registers [wpis_site_stats], [wpis_explore_claim_types], [wpis_explore_platforms].
 */
final class PublicScreenShortcodes {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'wpis_site_stats', array( self::class, 'render_site_stats' ) );
		add_shortcode( 'wpis_explore_claim_types', array( self::class, 'render_explore_claim_types' ) );
		add_shortcode( 'wpis_explore_platforms', array( self::class, 'render_explore_platforms' ) );
	}

	/**
	 * Render four hero metrics for the home seed.
	 *
	 * @param array<string, string> $atts Shortcode atts.
	 * @return string
	 */
	public static function render_site_stats( $atts ): string {
		unset( $atts );
		if ( ! post_type_exists( QuotePostType::POST_TYPE ) ) {
			return '';
		}
		$pub    = (int) wp_count_posts( QuotePostType::POST_TYPE )->publish;
		$pend   = (int) wp_count_posts( QuotePostType::POST_TYPE )->pending;
		$plat_n = self::count_distinct_source_platforms();
		$lang_n = self::count_site_languages();

		$items = array(
			array(
				'n'     => $pub,
				'label' => 'quotes collected',
			),
			array(
				'n'     => $plat_n,
				'label' => 'platforms sourced',
			),
			array(
				'n'     => $lang_n,
				'label' => 'languages',
			),
			array(
				'n'     => $pend,
				'label' => 'pending moderation',
			),
		);

		$out = '<div class="wpis-hero-stats-inner">';
		foreach ( $items as $row ) {
			$n    = (int) $row['n'];
			$out .= '<p class="wp-block-paragraph"><strong>' . esc_html( (string) number_format_i18n( $n ) ) . '</strong>' . esc_html( $row['label'] ) . '</p>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	 * Count distinct _wpis_source_platform values on published quotes.
	 *
	 * @return int
	 */
	private static function count_distinct_source_platforms(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$val = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT pm.meta_value) FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s AND p.post_status = 'publish'
				AND pm.meta_key = '_wpis_source_platform' AND pm.meta_value != ''",
				QuotePostType::POST_TYPE
			)
		);
		return max( 0, (int) $val );
	}

	/**
	 * Polylang language count, or 1.
	 *
	 * @return int
	 */
	private static function count_site_languages(): int {
		if ( function_exists( 'pll_languages_list' ) ) {
			$list = pll_languages_list( array( 'fields' => 'slug' ) );
			if ( is_array( $list ) && count( $list ) > 0 ) {
				return count( $list );
			}
		}
		return 1;
	}

	/**
	 * Render a card for each top-level claim_type term.
	 *
	 * @param array<string, string> $atts Shortcode atts.
	 * @return string
	 */
	public static function render_explore_claim_types( $atts ): string {
		unset( $atts );
		$terms = get_terms(
			array(
				'taxonomy'   => ClaimTypeTaxonomy::TAXONOMY,
				'hide_empty' => false,
				'parent'     => 0,
				'orderby'    => 'name',
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$out = '';
		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$out .= self::render_claim_type_card( $term );
		}
		return $out;
	}

	/**
	 * Build HTML for one claim-type card (counts and sentiment bar).
	 *
	 * @param \WP_Term $term Claim type term.
	 * @return string
	 */
	private static function render_claim_type_card( \WP_Term $term ): string {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			$link = '#';
		}
		$total = (int) $term->count;
		$pos   = self::count_in_term_by_sentiment( (int) $term->term_id, 'positive' );
		$neg   = self::count_in_term_by_sentiment( (int) $term->term_id, 'negative' );
		$mix   = self::count_in_term_by_sentiment( (int) $term->term_id, 'mixed' );
		$bar   = self::bar_percent_triplet( $pos, $neg, $mix );

		$desc = term_description( $term );
		if ( ! is_string( $desc ) || '' === trim( wp_strip_all_tags( $desc ) ) ) {
			$desc = self::default_claim_blurb( (string) $term->slug );
		} else {
			$desc = wp_kses_post( $desc );
		}

		$html  = '<div class="wp-block-group is-style-wpis-tax-card">';
		$html .= '<div class="wp-block-group is-style-wpis-tax-card-head" style="display: flex; flex-wrap: nowrap; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 10px;">';
		$html .= '<h3 class="wp-block-heading" style="font-size: 22px; font-weight: 600; letter-spacing: -0.01em; line-height: 1.15; margin: 0;">';
		$html .= '<a href="' . esc_url( (string) $link ) . '">' . esc_html( $term->name ) . '</a>';
		$html .= '</h3>';
		$html .= '<p class="is-style-wpis-tax-count wp-block-paragraph">' . esc_html( sprintf( /* translators: %s: number */ _n( '%s quote', '%s quotes', $total, 'wpis-core' ), number_format_i18n( $total ) ) ) . '</p>';
		$html .= '</div>';
		$html .= '<p class="is-style-wpis-tax-desc wp-block-paragraph" style="font-size: 14px; line-height: 1.5; color: var(--muted); margin: 0 0 14px;">' . $desc . '</p>';

		$html .= self::render_sentiment_bar_columns( $bar['neg_w'], $bar['pos_w'], $bar['mix_w'] );
		$html .= '<div class="wpis-tax-breakdown is-layout-flex wp-block-group" style="display: flex; flex-wrap: wrap; gap: 12px; font-family: var(--wp--preset--font-family--jetbrains-mono); font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin: 0;">';
		$html .= '<p class="wp-block-paragraph" style="margin: 0; display: flex; align-items: center; gap: 4px;"><span class="wpis-dot neg" aria-hidden="true"></span>' . esc_html( sprintf( /* translators: %d */ __( '%d critical', 'wpis-core' ), $neg ) ) . '</p>';
		$html .= '<p class="wp-block-paragraph" style="margin: 0; display: flex; align-items: center; gap: 4px;"><span class="wpis-dot pos" aria-hidden="true"></span>' . esc_html( sprintf( /* translators: %d */ __( '%d supportive', 'wpis-core' ), $pos ) ) . '</p>';
		$html .= '<p class="wp-block-paragraph" style="margin: 0; display: flex; align-items: center; gap: 4px;"><span class="wpis-dot mix" aria-hidden="true"></span>' . esc_html( sprintf( /* translators: %d */ __( '%d mixed', 'wpis-core' ), $mix ) ) . '</p>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * @param int    $term_id Term ID.
	 * @param string $sent    Sentiment slug.
	 * @return int
	 */
	private static function count_in_term_by_sentiment( int $term_id, string $sent ): int {
		$q = new \WP_Query(
			array(
				'post_type'              => QuotePostType::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => true,
				'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'relation' => 'AND',
					array(
						'taxonomy' => ClaimTypeTaxonomy::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => $term_id,
					),
					array(
						'taxonomy' => SentimentTaxonomy::TAXONOMY,
						'field'    => 'slug',
						'terms'    => $sent,
					),
				),
			)
		);
		return (int) $q->found_posts;
	}

	/**
	 * Turn three counts into safe column widths.
	 *
	 * @param int $pos Count for the positive segment.
	 * @param int $neg Count for the negative segment.
	 * @param int $mix Count for the mixed segment.
	 * @return array{neg_w: int, pos_w: int, mix_w: int}
	 */
	private static function bar_percent_triplet( int $pos, int $neg, int $mix ): array {
		$t = $pos + $neg + $mix;
		if ( $t < 1 ) {
			return array(
				'neg_w' => 33,
				'pos_w' => 34,
				'mix_w' => 33,
			);
		}
		$neg_w = (int) round( 100 * $neg / $t );
		$pos_w = (int) round( 100 * $pos / $t );
		$mix_w = 100 - $neg_w - $pos_w;
		if ( $mix_w < 0 ) {
			$mix_w = 0;
			$pos_w = 100 - $neg_w;
		}
		if ( $neg_w < 1 && $neg > 0 ) {
			$neg_w = 1;
		}
		return array(
			'neg_w' => $neg_w,
			'pos_w' => $pos_w,
			'mix_w' => $mix_w,
		);
	}

	/**
	 * @param int $w_neg Column width 0-100.
	 * @param int $w_pos Column width 0-100.
	 * @param int $w_mix Column width 0-100.
	 * @return string
	 */
	private static function render_sentiment_bar_columns( int $w_neg, int $w_pos, int $w_mix ): string {
		$html  = '<div class="wpis-tax-bar-columns wp-block-columns" style="display: flex; flex-direction: row; width: 100%; margin: 0 0 8px; border: 1px solid var(--line); overflow: hidden; gap: 0;">';
		$html .= '<div style="flex: 0 0 ' . (int) $w_neg . '%; padding: 0; min-width: 0;">';
		$html .= '<div class="wp-block-group wpis-tax-bar-seg neg" style="min-height: 6px; height: 100%; background: var(--wp--preset--color--negative); box-sizing: border-box;"></div></div>';
		$html .= '<div style="flex: 0 0 ' . (int) $w_pos . '%; padding: 0; min-width: 0;">';
		$html .= '<div class="wp-block-group wpis-tax-bar-seg pos" style="min-height: 6px; height: 100%; background: var(--wp--preset--color--positive); box-sizing: border-box;"></div></div>';
		$html .= '<div style="flex: 0 0 ' . (int) $w_mix . '%; padding: 0; min-width: 0;">';
		$html .= '<div class="wp-block-group wpis-tax-bar-seg mix" style="min-height: 6px; height: 100%; background: var(--wp--preset--color--mixed); box-sizing: border-box;"></div></div>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * @param string $slug Claim type slug.
	 * @return string
	 */
	private static function default_claim_blurb( string $slug ): string {
		$map = array(
			'performance'        => 'Speed, weight and responsiveness. Often about hosting setup as much as WordPress itself.',
			'security'           => 'How safe, exposed or resilient WordPress is. Mostly about the plugin ecosystem, not core.',
			'ease-of-use'        => 'Who is WordPress for? The tension between accessibility for beginners and power for devs.',
			'community'          => 'The people, WordCamps and the ecosystem of contributors. Often the most emotional category.',
			'ecosystem'          => 'Plugins, themes and integrations. The breadth vs quality debate lives here.',
			'business-viability' => 'Can you build a real business on WordPress? Scale, pricing and long-term bets.',
			'accessibility'      => 'A smaller count with high stakes. Standards, training and the reality of third-party code.',
			'modernity'          => 'Is WordPress keeping up? The block editor, headless and “old stack” arguments.',
		);
		if ( isset( $map[ $slug ] ) ) {
			return esc_html( $map[ $slug ] );
		}
		return '';
	}

	/**
	 * Render a row per source platform for published quotes.
	 *
	 * @param array<string, string> $atts Shortcode atts.
	 * @return string
	 */
	public static function render_explore_platforms( $atts ): string {
		unset( $atts );
		global $wpdb;
		$archive = get_post_type_archive_link( QuotePostType::POST_TYPE );
		if ( ! is_string( $archive ) || '' === $archive ) {
			$archive = home_url( '/' );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQLPlaceholders
		$sql = "SELECT pm.meta_value AS platform, COUNT(*) AS c
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE p.post_type = %s AND p.post_status = 'publish'
			AND pm.meta_key = '_wpis_source_platform' AND pm.meta_value != ''
			GROUP BY pm.meta_value
			ORDER BY c DESC";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, QuotePostType::POST_TYPE ), ARRAY_A );
		if ( ! is_array( $rows ) || count( $rows ) === 0 ) {
			return '<p class="wp-block-paragraph" style="color: var(--muted);">' . esc_html__( 'No platform data yet. Publish quotes with a source platform.', 'wpis-core' ) . '</p>';
		}
		$out = '';
		foreach ( $rows as $row ) {
			$slug = (string) ( $row['platform'] ?? '' );
			$c    = (int) ( $row['c'] ?? 0 );
			if ( '' === $slug ) {
				continue;
			}
			$label = self::platform_label( $slug );
			$out  .= '<div class="wp-block-group is-style-wpis-platform-card is-layout-flex" style="display: flex; flex-wrap: nowrap; justify-content: space-between; align-items: baseline;">';
			$out  .= '<h4 class="wp-block-heading" style="font-size: 15px; font-weight: 500; margin: 0;">';
			$out  .= '<a href="' . esc_url( $archive ) . '">' . esc_html( $label ) . '</a>';
			$out  .= '</h4>';
			$out  .= '<p class="is-style-wpis-platform-count wp-block-paragraph" style="margin: 0;">' . esc_html( number_format_i18n( $c ) ) . '</p>';
			$out  .= '</div>';
		}
		return $out;
	}

	/**
	 * @param string $slug Platform slug (Constants::SOURCE_PLATFORMS).
	 * @return string
	 */
	private static function platform_label( string $slug ): string {
		$map = array(
			'mastodon' => 'Mastodon',
			'bluesky'  => 'Bluesky',
			'linkedin' => 'LinkedIn',
			'youtube'  => 'YouTube',
			'reddit'   => 'Reddit',
			'blog'     => 'Blog',
			'x'        => 'X',
			'hn'       => 'HN',
			'other'    => 'Other',
		);
		return $map[ $slug ] ?? $slug;
	}
}

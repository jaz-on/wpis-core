<?php
/**
 * Feed controls (filter selects + sort tabs + load more) for the home feed.
 *
 * The shortcodes render accessible markup only; the theme enqueues
 * `assets/js/wpis-feed.js` to wire client-side filtering, sorting, and
 * the "load more" progressive disclosure. When JavaScript is disabled all
 * cards remain visible and the controls degrade to a no-op.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Shortcodes;

use WPIS\Core\Constants;
use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * Registers [wpis_feed_controls] and [wpis_load_more].
 */
final class FeedControlsShortcode {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'wpis_feed_controls', array( self::class, 'render_controls' ) );
		add_shortcode( 'wpis_load_more', array( self::class, 'render_load_more' ) );
	}

	/**
	 * Feed header + sort tabs + 3 filter selects.
	 *
	 * @param array<string, string>|string $atts Shortcode atts.
	 * @return string
	 */
	public static function render_controls( $atts ): string {
		$atts = shortcode_atts(
			array(
				'title' => __( 'The feed', 'wpis-core' ),
			),
			(array) $atts,
			'wpis_feed_controls'
		);

		$sort_tabs = array(
			'recent'   => __( 'Recent', 'wpis-core' ),
			'repeated' => __( 'Most repeated', 'wpis-core' ),
			'random'   => __( 'Random', 'wpis-core' ),
		);
		$sort_html = '';
		$i         = 0;
		foreach ( $sort_tabs as $slug => $label ) {
			$is_active = 0 === $i;
			$sort_html .= sprintf(
				'<button type="button" class="wpis-feed-sort-btn%1$s" data-sort="%2$s" aria-pressed="%3$s">%4$s</button>',
				$is_active ? ' is-active' : '',
				esc_attr( $slug ),
				$is_active ? 'true' : 'false',
				esc_html( $label )
			);
			++$i;
		}

		$sentiment_counts = self::taxonomy_counts( SentimentTaxonomy::TAXONOMY );
		$claim_counts     = self::taxonomy_counts( ClaimTypeTaxonomy::TAXONOMY );
		$platform_counts  = self::platform_counts();

		$selects = self::select( 'sentiment', __( 'All sentiments', 'wpis-core' ), $sentiment_counts )
			. self::select( 'claim', __( 'All claim types', 'wpis-core' ), $claim_counts )
			. self::select( 'platform', __( 'All platforms', 'wpis-core' ), $platform_counts );

		return '<div class="wpis-feed-controls" data-wpis-feed-controls>'
			. '<div class="wpis-feed-header">'
			. '<p class="wpis-feed-title">' . esc_html( (string) $atts['title'] )
			. ' <button type="button" class="wpis-filter-show-toggle" aria-expanded="true" data-wpis-toggle-filters>' . esc_html__( 'Hide filters', 'wpis-core' ) . '</button>'
			. '</p>'
			. '<div class="wpis-feed-sort" role="tablist" aria-label="' . esc_attr__( 'Sort feed', 'wpis-core' ) . '">' . $sort_html . '</div>'
			. '</div>'
			. '<div class="wpis-filter-selects" data-open="true">' . $selects . '</div>'
			. '<p class="wpis-no-results-msg" role="status" aria-live="polite" hidden>' . esc_html__( 'No quotes match these filters yet.', 'wpis-core' ) . '</p>'
			. '</div>';
	}

	/**
	 * Load-more button that reveals hidden feed cards progressively.
	 *
	 * @param array<string, string>|string $atts Shortcode atts.
	 * @return string
	 */
	public static function render_load_more( $atts ): string {
		$atts = shortcode_atts(
			array(
				'step' => 10,
			),
			(array) $atts,
			'wpis_load_more'
		);
		$step = max( 1, (int) $atts['step'] );

		return '<button type="button" class="wpis-load-more-btn" data-wpis-load-more data-step="' . esc_attr( (string) $step ) . '">'
			. esc_html__( 'Load more', 'wpis-core' )
			. ' <span class="wpis-load-more-count">(+' . esc_html( (string) $step ) . ')</span>'
			. '</button>';
	}

	/* ------------------------------------------------------------------ */

	/**
	 * @param string                      $id       Short id (sentiment|claim|platform).
	 * @param string                      $all      "All" placeholder label.
	 * @param array<int, array{0:string,1:string,2:int}> $options Array of [slug, label, count].
	 * @return string
	 */
	private static function select( string $id, string $all, array $options ): string {
		$opts = '<option value="">' . esc_html( $all ) . '</option>';
		foreach ( $options as $opt ) {
			[ $slug, $label, $count ] = $opt;
			if ( $count <= 0 ) {
				continue;
			}
			$opts .= sprintf(
				'<option value="%1$s">%2$s (%3$s)</option>',
				esc_attr( $slug ),
				esc_html( $label ),
				esc_html( number_format_i18n( $count ) )
			);
		}
		return '<div class="wpis-filter-select-wrap">'
			. '<label class="screen-reader-text" for="wpis-filter-' . esc_attr( $id ) . '">' . esc_html( $all ) . '</label>'
			. '<select class="wpis-filter-select" id="wpis-filter-' . esc_attr( $id ) . '" data-wpis-filter="' . esc_attr( $id ) . '">'
			. $opts
			. '</select></div>';
	}

	/**
	 * Quote counts by taxonomy term slug, ordered by count desc.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int, array{0:string,1:string,2:int}>
	 */
	private static function taxonomy_counts( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return array();
		}
		$out = array();
		foreach ( $terms as $t ) {
			if ( ! $t instanceof \WP_Term ) {
				continue;
			}
			$out[] = array( (string) $t->slug, (string) $t->name, (int) $t->count );
		}
		return $out;
	}

	/**
	 * Quote counts by source_platform meta value, ordered by count desc.
	 *
	 * @return array<int, array{0:string,1:string,2:int}>
	 */
	private static function platform_counts(): array {
		global $wpdb;
		$post_type = QuotePostType::POST_TYPE;
		$sql       = $wpdb->prepare(
			"SELECT pm.meta_value AS slug, COUNT(DISTINCT pm.post_id) AS n
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key = %s
			   AND p.post_type = %s
			   AND p.post_status = %s
			 GROUP BY pm.meta_value
			 ORDER BY n DESC",
			'_wpis_source_platform',
			$post_type,
			'publish'
		);
		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! is_array( $rows ) ) {
			return array();
		}
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
		$out = array();
		foreach ( $rows as $row ) {
			$slug = (string) ( $row['slug'] ?? '' );
			$n    = (int) ( $row['n'] ?? 0 );
			if ( '' === $slug || ! in_array( $slug, Constants::SOURCE_PLATFORMS, true ) ) {
				continue;
			}
			$label = $map[ $slug ] ?? ucfirst( $slug );
			$out[] = array( $slug, $label, $n );
		}
		return $out;
	}
}

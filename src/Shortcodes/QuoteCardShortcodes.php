<?php
/**
 * Quote card / detail meta shortcodes used by the feed template part and the
 * single-quote template.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Shortcodes;

use WPIS\Core\Constants;
use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * Registers [wpis_quote_footer], [wpis_quote_meta], [wpis_quote_date],
 * [wpis_quote_detail_meta], [wpis_quote_spread_stats], [wpis_quote_opposing].
 */
final class QuoteCardShortcodes {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'wpis_quote_footer', array( self::class, 'render_footer' ) );
		add_shortcode( 'wpis_quote_meta', array( self::class, 'render_meta' ) );
		add_shortcode( 'wpis_quote_date', array( self::class, 'render_date' ) );
		add_shortcode( 'wpis_quote_detail_meta', array( self::class, 'render_detail_meta' ) );
		add_shortcode( 'wpis_quote_spread_stats', array( self::class, 'render_spread_stats' ) );
		add_shortcode( 'wpis_quote_opposing', array( self::class, 'render_opposing' ) );
	}

	/**
	 * Feed card footer: claim tag + count badge only (mockup parity). Platforms,
	 * languages and date are intentionally omitted from the feed card.
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_footer( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$claim_tags = get_the_term_list( $post_id, ClaimTypeTaxonomy::TAXONOMY, '', '</span><span class="wpis-claim-tag is-style-wpis-claim-tag">', '' );
		if ( is_string( $claim_tags ) && '' !== $claim_tags ) {
			$claim_tags = '<span class="wpis-claim-tag is-style-wpis-claim-tag">' . $claim_tags . '</span>';
			$claim_tags = preg_replace( '#<a[^>]*>([^<]*)</a>#i', '$1', (string) $claim_tags );
		} else {
			$claim_tags = '';
		}

		$count = (int) get_post_meta( $post_id, '_wpis_counter', true );
		if ( $count < 0 ) {
			$count = 0;
		}
		$count_badge = '<span class="wpis-count-badge is-style-wpis-count-badge">×' . esc_html( (string) $count ) . '</span>';

		return $claim_tags . $count_badge;
	}

	/**
	 * Inline platforms + langs only (for custom layouts that keep claim-tag
	 * and count-badge as separate blocks).
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_meta( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		return self::platforms_span( $post_id ) . self::langs_span( $post_id );
	}

	/**
	 * Short date (JetBrains Mono, "APR 20" style).
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_date( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		return self::date_span( $post_id );
	}

	/**
	 * Single-quote detail meta row (claim + sentiment + count).
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_detail_meta( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$sentiment = self::first_term_name( $post_id, SentimentTaxonomy::TAXONOMY );
		$claims    = self::terms_csv( $post_id, ClaimTypeTaxonomy::TAXONOMY );
		$count     = (int) get_post_meta( $post_id, '_wpis_counter', true );

		$parts = array();
		if ( '' !== $claims ) {
			$parts[] = '<span class="is-style-wpis-claim-tag">' . esc_html( $claims ) . '</span>';
		}
		if ( '' !== $sentiment ) {
			$parts[] = '<span class="dot">·</span><span>' . esc_html( self::sentiment_label( $sentiment ) ) . '</span>';
		}
		if ( $count > 0 ) {
			$parts[] = '<span class="dot">·</span><span>×' . esc_html( (string) $count ) . '</span>';
		}

		if ( empty( $parts ) ) {
			return '';
		}

		return '<div class="wpis-detail-claim-meta">' . implode( '', $parts ) . '</div>';
	}

	/**
	 * Spread stats grid for the single-quote template.
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_spread_stats( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$count       = max( 0, (int) get_post_meta( $post_id, '_wpis_counter', true ) );
		$platforms   = self::post_platform_labels( $post_id );
		$platform_n  = count( $platforms );
		$langs       = self::post_langs( $post_id );
		$lang_n      = count( $langs );
		$first_seen  = get_post_time( 'M d', false, $post_id, false );
		$first_label = is_string( $first_seen ) ? strtoupper( $first_seen ) : '';

		$grid = '<div class="wpis-spread-grid">';
		$grid .= self::spread_item( number_format_i18n( $count ), __( 'total sightings', 'wpis-core' ) );
		$grid .= self::spread_item( number_format_i18n( $platform_n ), __( 'platforms', 'wpis-core' ) );
		$grid .= self::spread_item( number_format_i18n( $lang_n ), __( 'languages', 'wpis-core' ) );
		$grid .= self::spread_item( $first_label, __( 'first seen', 'wpis-core' ) );
		$grid .= '</div>';

		return '<section class="wpis-spread-section"><p class="wpis-section-title">' . esc_html__( 'Where it spread', 'wpis-core' ) . '</p>' . $grid . '</section>';
	}

	/**
	 * Opposing quote block for the single-quote template. Reads
	 * `_wpis_opposing_quote_id` and renders the matching quote's excerpt.
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_opposing( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$opp_id = (int) get_post_meta( $post_id, '_wpis_opposing_quote_id', true );
		if ( $opp_id <= 0 ) {
			return '';
		}
		$opp = get_post( $opp_id );
		if ( ! $opp || 'publish' !== $opp->post_status || QuotePostType::POST_TYPE !== $opp->post_type ) {
			return '';
		}

		$claims  = self::terms_csv( $opp_id, ClaimTypeTaxonomy::TAXONOMY );
		$count   = (int) get_post_meta( $opp_id, '_wpis_counter', true );
		$excerpt = wp_strip_all_tags( (string) $opp->post_content );
		$excerpt = wp_html_excerpt( $excerpt, 200, '…' );

		$meta = '';
		if ( '' !== $claims ) {
			$meta .= '<span>' . esc_html( $claims ) . '</span>';
		}
		if ( $count > 0 ) {
			$meta .= '<span>·</span><span>×' . esc_html( (string) $count ) . '</span>';
		}
		$meta .= '<a class="see-more" href="' . esc_url( (string) get_permalink( $opp_id ) ) . '">' . esc_html__( 'Read more →', 'wpis-core' ) . '</a>';

		return '<aside class="wpis-against-block">'
			. '<p class="wpis-against-label">' . esc_html__( 'The opposing take', 'wpis-core' ) . '</p>'
			. '<p class="wpis-against-quote">' . esc_html( $excerpt ) . '</p>'
			. '<p class="wpis-against-meta">' . $meta . '</p>'
			. '</aside>';
	}

	/* ----------------------------------------------------------------------
	 * Helpers
	 * -------------------------------------------------------------------- */

	/**
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function platforms_span( int $post_id ): string {
		$labels = self::post_platform_labels( $post_id );
		if ( empty( $labels ) ) {
			return '';
		}
		return '<span class="wpis-quote-platforms">' . esc_html( implode( ' · ', $labels ) ) . '</span>';
	}

	/**
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function langs_span( int $post_id ): string {
		$langs = self::post_langs( $post_id );
		if ( empty( $langs ) ) {
			return '';
		}
		$out = '<span class="wpis-quote-langs">';
		foreach ( $langs as $lang ) {
			$out .= '<span class="wpis-lang-tag">' . esc_html( strtoupper( $lang ) ) . '</span>';
		}
		$out .= '</span>';
		return $out;
	}

	/**
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function date_span( int $post_id ): string {
		$d = get_post_time( 'M d', false, $post_id, false );
		if ( ! is_string( $d ) || '' === $d ) {
			return '';
		}
		return '<span class="wpis-quote-date">' . esc_html( strtoupper( $d ) ) . '</span>';
	}

	/**
	 * @param int $post_id Post ID.
	 * @return list<string>
	 */
	private static function post_platform_labels( int $post_id ): array {
		$slugs = (array) get_post_meta( $post_id, '_wpis_source_platform', false );
		$slugs = array_unique( array_filter( array_map( 'strval', $slugs ) ) );
		if ( empty( $slugs ) ) {
			$single = (string) get_post_meta( $post_id, '_wpis_source_platform', true );
			if ( '' !== $single ) {
				$slugs = array( $single );
			}
		}
		$out = array();
		foreach ( $slugs as $slug ) {
			if ( in_array( $slug, Constants::SOURCE_PLATFORMS, true ) ) {
				$out[] = self::platform_label( $slug );
			}
		}
		return $out;
	}

	/**
	 * @param int $post_id Post ID.
	 * @return list<string>
	 */
	private static function post_langs( int $post_id ): array {
		$langs = array();
		if ( function_exists( 'pll_get_post_language' ) ) {
			$lang = pll_get_post_language( $post_id, 'slug' );
			if ( is_string( $lang ) && '' !== $lang ) {
				$langs[] = $lang;
			}
		}
		$meta = (string) get_post_meta( $post_id, '_wpis_source_language', true );
		if ( '' !== $meta ) {
			$langs[] = $meta;
		}
		$langs = array_unique( array_filter( $langs ) );
		return array_values( $langs );
	}

	/**
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return string
	 */
	private static function first_term_name( int $post_id, string $taxonomy ): string {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return '';
		}
		return (string) $terms[0]->slug;
	}

	/**
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return string
	 */
	private static function terms_csv( int $post_id, string $taxonomy ): string {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return '';
		}
		$names = array();
		foreach ( $terms as $t ) {
			$names[] = (string) $t->name;
		}
		return implode( ', ', $names );
	}

	/**
	 * @param string $slug Platform slug.
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
		return $map[ $slug ] ?? ucfirst( $slug );
	}

	/**
	 * @param string $slug Sentiment slug.
	 * @return string
	 */
	private static function sentiment_label( string $slug ): string {
		$map = array(
			'positive' => __( 'Positive', 'wpis-core' ),
			'negative' => __( 'Negative', 'wpis-core' ),
			'mixed'    => __( 'Mixed', 'wpis-core' ),
			'neutral'  => __( 'Neutral', 'wpis-core' ),
		);
		return $map[ $slug ] ?? ucfirst( $slug );
	}

	/**
	 * @param string $big   Headline number.
	 * @param string $label Label.
	 * @return string
	 */
	private static function spread_item( string $big, string $label ): string {
		return '<div class="wpis-spread-item"><span class="big-num">' . esc_html( '' === $big ? '—' : $big ) . '</span><span class="small-label">' . esc_html( $label ) . '</span></div>';
	}
}

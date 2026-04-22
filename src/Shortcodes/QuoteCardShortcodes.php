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
		add_shortcode( 'wpis_quote_breadcrumb', array( self::class, 'render_breadcrumb' ) );
		add_shortcode( 'wpis_quote_detail_meta', array( self::class, 'render_detail_meta' ) );
		add_shortcode( 'wpis_quote_full', array( self::class, 'render_full' ) );
		add_shortcode( 'wpis_quote_spread_stats', array( self::class, 'render_spread_stats' ) );
		add_shortcode( 'wpis_quote_opposing', array( self::class, 'render_opposing' ) );
		add_shortcode( 'wpis_quote_variants', array( self::class, 'render_variants' ) );
		add_shortcode( 'wpis_quote_editorial_note', array( self::class, 'render_editorial_note' ) );
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
		$count_label = sprintf(
			/* translators: %d: number of times this claim has been repeated across platforms */
			_n( 'repeated %d time', 'repeated %d times', $count, 'wpis-core' ),
			$count
		);
		$count_badge = sprintf(
			'<span class="wpis-count-badge is-style-wpis-count-badge" aria-label="%1$s"><span aria-hidden="true">&times;%2$s</span></span>',
			esc_attr( $count_label ),
			esc_html( (string) $count )
		);

		return $claim_tags . ' ' . $count_badge;
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

		$claims = self::terms_csv( $post_id, ClaimTypeTaxonomy::TAXONOMY );
		$count  = max( 1, (int) get_post_meta( $post_id, '_wpis_counter', true ) );
		/* translators: %1$d: number of submissions; %2$s: localized month + year (e.g. "March 2026"). */
		$since = get_post_time( 'F Y', false, $post_id, true );
		$since = is_string( $since ) ? $since : '';

		$parts = array();
		if ( '' !== $claims ) {
			$parts[] = '<span class="is-style-wpis-claim-tag">' . esc_html( $claims ) . '</span>';
		}
		if ( '' !== $since ) {
			$parts[] = '<span class="dot">·</span><span>' . sprintf(
				/* translators: %1$d: submissions count; %2$s: month and year (e.g. March 2026). */
				esc_html( _n( 'Submitted %1$d time since %2$s', 'Submitted %1$d times since %2$s', $count, 'wpis-core' ) ),
				(int) $count,
				esc_html( $since )
			) . '</span>';
		}

		if ( empty( $parts ) ) {
			return '';
		}

		return '<div class="wpis-detail-claim-meta">' . implode( '', $parts ) . '</div>';
	}

	/**
	 * Full-quote H1 for the single-quote template. Uses the post content so we
	 * keep the entire sentence even when the post_title is truncated, and wraps
	 * the first " is " in a span for the italic accent.
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_full( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}
		$text = wp_strip_all_tags( (string) $post->post_content );
		$text = trim( preg_replace( '/\s+/', ' ', $text ) ?? '' );
		if ( '' === $text ) {
			$text = (string) get_the_title( $post );
		}
		$safe = esc_html( $text );
		$safe = preg_replace( '/\bis\b/u', '<span class="is-word">is</span>', $safe, 1 );
		if ( ! is_string( $safe ) || '' === $safe ) {
			$safe = esc_html( $text );
		}
		return '<h1 class="wp-block-heading is-style-wpis-detail-quote wpis-detail-quote">' . $safe . '</h1>';
	}

	/**
	 * Breadcrumb for single-quote: Feed / {first claim term} / This quote.
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_breadcrumb( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$feed_url   = home_url( '/' );
		$feed_label = __( 'Feed', 'wpis-core' );
		$this_label = __( 'This quote', 'wpis-core' );

		$terms = get_the_terms( $post_id, ClaimTypeTaxonomy::TAXONOMY );
		$parts = array(
			'<a href="' . esc_url( $feed_url ) . '">' . esc_html( $feed_label ) . '</a>',
		);
		if ( is_array( $terms ) && ! empty( $terms ) ) {
			$term     = $terms[0];
			$term_url = (string) get_term_link( $term );
			if ( '' !== $term_url && ! is_wp_error( $term_url ) ) {
				$parts[] = '<a href="' . esc_url( $term_url ) . '">' . esc_html( (string) $term->name ) . '</a>';
			}
		}
		$parts[] = '<span>' . esc_html( $this_label ) . '</span>';

		return '<nav class="wpis-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'wpis-core' ) . '">' . implode( '<span class="sep">/</span>', $parts ) . '</nav>';
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

		$count      = max( 0, (int) get_post_meta( $post_id, '_wpis_counter', true ) );
		$platforms  = self::post_platform_labels( $post_id );
		$platform_n = count( $platforms );
		$langs      = self::post_langs( $post_id );
		$lang_n     = count( $langs );
		$variants_n = self::variants_count( $post_id );

		$grid  = '<div class="wpis-spread-grid">';
		$grid .= self::spread_item( number_format_i18n( $count ), __( 'total submissions', 'wpis-core' ) );
		$grid .= self::spread_item( number_format_i18n( $platform_n ), __( 'platforms', 'wpis-core' ) );
		$grid .= self::spread_item( number_format_i18n( $lang_n ), __( 'languages', 'wpis-core' ) );
		$grid .= self::spread_item( number_format_i18n( $variants_n ), __( 'variants merged', 'wpis-core' ) );
		$grid .= '</div>';

		return '<section class="wpis-spread-section"><p class="wpis-section-title">' . esc_html__( 'How this claim spreads', 'wpis-core' ) . '</p>' . $grid . '</section>';
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

		$claims    = self::terms_csv( $opp_id, ClaimTypeTaxonomy::TAXONOMY );
		$count     = (int) get_post_meta( $opp_id, '_wpis_counter', true );
		$platforms = self::post_platform_labels( $opp_id );
		$excerpt   = wp_strip_all_tags( (string) $opp->post_content );
		$excerpt   = wp_html_excerpt( $excerpt, 200, '…' );

		$meta = '';
		if ( '' !== $claims ) {
			$meta .= '<span class="wpis-claim-tag is-style-wpis-claim-tag">' . esc_html( $claims ) . '</span>';
		}
		if ( $count > 0 ) {
			$count_label = sprintf(
				/* translators: %d: number of times this claim has been repeated across platforms */
				_n( 'repeated %d time', 'repeated %d times', $count, 'wpis-core' ),
				$count
			);
			$meta .= sprintf(
				' <span class="wpis-count-badge is-style-wpis-count-badge" aria-label="%1$s"><span aria-hidden="true">&times;%2$s</span></span>',
				esc_attr( $count_label ),
				esc_html( (string) $count )
			);
		}
		if ( ! empty( $platforms ) ) {
			$meta .= '<span>' . esc_html(
				sprintf(
					/* translators: %s: comma-separated platform list. */
					__( 'Seen on %s', 'wpis-core' ),
					implode( ', ', $platforms )
				)
			) . '</span>';
		}
		$meta .= '<a class="see-more" href="' . esc_url( (string) get_permalink( $opp_id ) ) . '">' . esc_html__( 'See the full opposing view →', 'wpis-core' ) . '</a>';

		return '<aside class="wpis-against-block">'
			. '<p class="wpis-against-label">' . esc_html__( 'Someone disagrees', 'wpis-core' ) . '</p>'
			. '<p class="wpis-against-quote">' . esc_html( $excerpt ) . '</p>'
			. '<p class="wpis-against-meta">' . $meta . '</p>'
			. '</aside>';
	}

	/**
	 * Variants list: other quotes sharing the same claim term (excluding current).
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_variants( $atts ): string {
		$atts    = shortcode_atts(
			array(
				'title' => __( 'A few of the variants', 'wpis-core' ),
				'limit' => '4',
			),
			(array) $atts,
			'wpis_quote_variants'
		);
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$siblings = self::fetch_variants( (int) $post_id, max( 1, (int) $atts['limit'] ) );
		if ( empty( $siblings ) ) {
			return '';
		}

		$lines = '';
		foreach ( $siblings as $sibling ) {
			$text      = wp_strip_all_tags( (string) $sibling->post_content );
			$text      = wp_html_excerpt( $text, 180, '…' );
			$platforms = self::post_platform_labels( (int) $sibling->ID );
			$langs     = array_map( 'strtoupper', self::post_langs( (int) $sibling->ID ) );
			$year      = get_post_time( 'Y', false, $sibling, false );

			$meta_parts = array();
			if ( ! empty( $langs ) ) {
				$meta_parts[] = esc_html( (string) $langs[0] );
			}
			if ( ! empty( $platforms ) ) {
				$meta_parts[] = esc_html( (string) $platforms[0] );
			}
			if ( is_string( $year ) && '' !== $year ) {
				$meta_parts[] = esc_html( $year );
			}
			$lines .= '<a class="wpis-variant-line" href="' . esc_url( (string) get_permalink( $sibling ) ) . '">'
				. '<p class="v-text">' . esc_html( $text ) . '</p>'
				. '<p class="v-meta">' . implode( ' · ', $meta_parts ) . '</p>'
				. '</a>';
		}

		return '<section class="wpis-variants-compact">'
			. '<p class="wpis-section-title">' . esc_html( (string) $atts['title'] ) . '</p>'
			. $lines
			. '</section>';
	}

	/**
	 * Editor note block, powered by the `_wpis_editor_note` post meta. Renders
	 * nothing when the meta is empty so authors opt in per quote.
	 *
	 * @param array<string, string>|string $atts Shortcode atts (ignored).
	 * @return string
	 */
	public static function render_editorial_note( $atts ): string {
		unset( $atts );
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$note = (string) get_post_meta( $post_id, '_wpis_editorial_note', true );
		if ( '' === trim( $note ) ) {
			return '';
		}
		$allowed = array(
			'em'     => array(),
			'strong' => array(),
			'a'      => array( 'href' => array() ),
			'br'     => array(),
		);
		$safe    = wp_kses( $note, $allowed );
		return '<aside class="wpis-editorial-note">'
			. '<p class="note-label">' . esc_html__( 'A note from the editor', 'wpis-core' ) . '</p>'
			. '<p>' . $safe . '</p>'
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

	/**
	 * Other quotes sharing the first claim term (excluding current).
	 *
	 * @param int $post_id Current post ID.
	 * @param int $limit   Max number of siblings to return.
	 * @return array<int, \WP_Post>
	 */
	private static function fetch_variants( int $post_id, int $limit = 4 ): array {
		$terms = get_the_terms( $post_id, ClaimTypeTaxonomy::TAXONOMY );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return array();
		}
		$siblings = get_posts(
			array(
				'post_type'        => QuotePostType::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => $limit,
				'post__not_in'     => array( $post_id ),
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => false,
				'tax_query'        => array(
					array(
						'taxonomy' => ClaimTypeTaxonomy::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => (int) $terms[0]->term_id,
					),
				),
			)
		);
		return is_array( $siblings ) ? $siblings : array();
	}

	/**
	 * Total number of other quotes sharing the current quote's first claim term.
	 *
	 * @param int $post_id Current post ID.
	 * @return int
	 */
	private static function variants_count( int $post_id ): int {
		$terms = get_the_terms( $post_id, ClaimTypeTaxonomy::TAXONOMY );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return 0;
		}
		$query = new \WP_Query(
			array(
				'post_type'              => QuotePostType::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'post__not_in'           => array( $post_id ),
				'tax_query'              => array(
					array(
						'taxonomy' => ClaimTypeTaxonomy::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => (int) $terms[0]->term_id,
					),
				),
			)
		);
		return (int) $query->found_posts;
	}
}

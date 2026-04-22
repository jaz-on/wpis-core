<?php
/**
 * Subcategory chip navigation for taxonomy archives and search.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Shortcodes;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Registers [wpis_subcat_nav] and [wpis_subcat_chips_for_post].
 */
final class SubcatNavShortcode {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( 'wpis_subcat_nav', array( self::class, 'render' ) );
		add_shortcode( 'wpis_subcat_chips_for_post', array( self::class, 'render_for_post' ) );
	}

	/**
	 * Render a scrollable chip row for a taxonomy. When a term is queried
	 * (e.g. taxonomy-claim_type archive), children of that term take priority;
	 * otherwise the top-level terms are shown.
	 *
	 * Attributes:
	 *   taxonomy — defaults to `claim_type`.
	 *   label    — leading label; defaults to "Narrow down".
	 *
	 * @param array<string, string>|string $atts Shortcode atts.
	 * @return string
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'taxonomy' => 'claim_type',
				'label'    => __( 'Narrow down', 'wpis-core' ),
			),
			(array) $atts,
			'wpis_subcat_nav'
		);

		$taxonomy = (string) $atts['taxonomy'];
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return '';
		}

		$current = get_queried_object();
		$current_term_id = 0;
		if ( $current instanceof \WP_Term && $current->taxonomy === $taxonomy ) {
			$current_term_id = (int) $current->term_id;
		}

		$parent_id = 0;
		if ( $current_term_id ) {
			$children = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'parent'     => $current_term_id,
					'hide_empty' => false,
				)
			);
			if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
				$parent_id = $current_term_id;
			}
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'parent'     => $parent_id,
				'hide_empty' => false,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$total        = 0;
		$parent_label = __( 'All', 'wpis-core' );
		if ( $parent_id ) {
			$parent_term = get_term( $parent_id, $taxonomy );
			if ( $parent_term instanceof \WP_Term ) {
				$parent_label = $parent_term->name;
				$total        = (int) $parent_term->count;
			}
		} else {
			foreach ( $terms as $t ) {
				if ( $t instanceof \WP_Term ) {
					$total += (int) $t->count;
				}
			}
		}

		$html  = '<p class="wpis-subcat-label">' . esc_html( (string) $atts['label'] ) . '</p>';
		$html .= '<nav class="wpis-subcat-nav" aria-label="' . esc_attr__( 'Narrow down', 'wpis-core' ) . '">';

		$all_url = $parent_id
			? (string) get_term_link( (int) $parent_id, $taxonomy )
			: (string) get_post_type_archive_link( QuotePostType::POST_TYPE );
		$all_active = ( 0 === $current_term_id ) || ( $parent_id && $parent_id === $current_term_id );
		$html .= self::chip( $all_url, $parent_label, $total, (bool) $all_active );

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$html .= self::chip( (string) $link, (string) $term->name, (int) $term->count, $current_term_id === (int) $term->term_id );
		}

		$html .= '</nav>';
		return '<div class="wpis-subcat-wrap">' . $html . '</div>';
	}

	/**
	 * Render claim-type chips for the current post (used by single-quote).
	 *
	 * @param array<string, string>|string $atts Shortcode atts.
	 * @return string
	 */
	public static function render_for_post( $atts ): string {
		$atts = shortcode_atts(
			array(
				'taxonomy' => 'claim_type',
			),
			(array) $atts,
			'wpis_subcat_chips_for_post'
		);

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}
		$terms = get_the_terms( $post_id, (string) $atts['taxonomy'] );
		if ( ! is_array( $terms ) || is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		$html = '<nav class="wpis-subcat-nav" aria-label="' . esc_attr__( 'Related claim types', 'wpis-core' ) . '">';
		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$html .= self::chip( (string) $link, (string) $term->name, (int) $term->count, false );
		}
		$html .= '</nav>';
		return $html;
	}

	/**
	 * @param string $url    Term link.
	 * @param string $label  Term name.
	 * @param int    $count  Term count.
	 * @param bool   $active Whether the chip marks the current term.
	 * @return string
	 */
	private static function chip( string $url, string $label, int $count, bool $active ): string {
		$class = 'wpis-subcat-chip' . ( $active ? ' active' : '' );
		return sprintf(
			'<a class="%1$s" href="%2$s"%3$s>%4$s<span class="wpis-subcat-chip-count">%5$s</span></a>',
			esc_attr( $class ),
			esc_url( $url ),
			$active ? ' aria-current="page"' : '',
			esc_html( $label ),
			esc_html( number_format_i18n( $count ) )
		);
	}
}

<?php
/**
 * Public REST endpoint for AJAX “load more” on quote feeds.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\REST;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Serves HTML fragments matching theme quote cards (keep in sync with wpis_theme_apply_quote_feed_args).
 */
final class QuoteFeedEndpoint {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * @return void
	 */
	public static function routes(): void {
		register_rest_route(
			'wpis/v1',
			'/quote-feed',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'serve' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'page'       => array(
						'default'           => 1,
						'sanitize_callback' => static function ( $v ) {
							return max( 1, (int) $v );
						},
					),
					'per_page'   => array(
						'default'           => 10,
						'sanitize_callback' => static function ( $v ) {
							return min( 50, max( 1, (int) $v ) );
						},
					),
					'wpis_sort'  => array(
						'default' => 'date',
					),
					'wpis_order' => array(
						'default' => 'DESC',
					),
					'sentiment'  => array(
						'default' => '',
					),
					'claim_type' => array(
						'default' => '',
					),
					'lang'       => array(
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function serve( \WP_REST_Request $request ) {
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$sort     = sanitize_key( (string) $request->get_param( 'wpis_sort' ) );
		if ( ! in_array( $sort, array( 'date', 'counter' ), true ) ) {
			$sort = 'date';
		}
		$ord = strtoupper( (string) $request->get_param( 'wpis_order' ) );
		if ( ! in_array( $ord, array( 'ASC', 'DESC' ), true ) ) {
			$ord = 'DESC';
		}

		$args = array(
			'post_type'           => QuotePostType::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => $page,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);

		if ( 'counter' === $sort ) {
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_wpis_counter';
			$args['order']    = $ord;
		} else {
			$args['orderby'] = 'date';
			$args['order']   = $ord;
		}

		$tax_query = array();
		$sent_slug = sanitize_title( (string) $request->get_param( 'sentiment' ) );
		if ( '' !== $sent_slug ) {
			$tax_query[] = array(
				'taxonomy' => 'sentiment',
				'field'    => 'slug',
				'terms'    => $sent_slug,
			);
		}
		$claim_slug = sanitize_title( (string) $request->get_param( 'claim_type' ) );
		if ( '' !== $claim_slug ) {
			$tax_query[] = array(
				'taxonomy' => 'claim_type',
				'field'    => 'slug',
				'terms'    => $claim_slug,
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		$lang = sanitize_key( (string) $request->get_param( 'lang' ) );
		if ( '' !== $lang && function_exists( 'pll_languages_list' ) ) {
			$list = pll_languages_list( array( 'fields' => 'slug' ) );
			if ( is_array( $list ) && in_array( $lang, $list, true ) ) {
				$args['lang'] = $lang;
			}
		}

		$q = new \WP_Query( $args );

		$html = '';
		foreach ( $q->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$html .= self::render_card( $post );
			}
		}

		return new \WP_REST_Response(
			array(
				'page'        => $page,
				'per_page'    => $per_page,
				'total'       => (int) $q->found_posts,
				'total_pages' => max( 1, (int) $q->max_num_pages ),
				'html'        => $html,
			)
		);
	}

	/**
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private static function render_card( \WP_Post $post ): string {
		$title   = get_the_title( $post );
		$link    = get_permalink( $post );
		$excerpt = wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 35, '…' );
		$read    = __( 'Read', 'wpis-plugin' );

		$ct_line = self::term_line( $post, 'claim_type' );
		$st_line = self::term_line( $post, 'sentiment' );
		$meta    = array_filter( array( $ct_line, $st_line ) );
		$meta_s  = ! empty( $meta ) ? implode( ' · ', $meta ) : '';

		$inner  = '<h3 class="wp-block-post-title has-large-font-size"><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></h3>';
		$inner .= '<div class="wp-block-post-excerpt has-small-font-size"><p>' . esc_html( $excerpt ) . ' <a href="' . esc_url( $link ) . '">' . esc_html( $read ) . '</a></p></div>';
		if ( '' !== $meta_s ) {
			$inner .= '<div class="wp-block-group has-small-font-size" style="display:flex;flex-wrap:wrap;gap:0.5rem">' . $meta_s . '</div>';
		}

		return '<div class="wp-block-group wpis-quote-card" style="border-bottom-color:var(--wp--preset--color--ink);border-bottom-width:1px;border-bottom-style:solid;padding-top:1.25rem;padding-bottom:1.25rem">' . $inner . '</div>';
	}

	/**
	 * @param \WP_Post $post Post.
	 * @param string   $tax Taxonomy slug.
	 * @return string Linked term names or empty.
	 */
	private static function term_line( \WP_Post $post, string $tax ): string {
		$terms = get_the_terms( $post, $tax );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return '';
		}
		$parts = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}
			$url     = get_term_link( $term );
			$parts[] = is_wp_error( $url )
				? esc_html( $term->name )
				: '<a href="' . esc_url( $url ) . '">' . esc_html( $term->name ) . '</a>';
		}
		return implode( ' · ', $parts );
	}
}

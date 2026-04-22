<?php
/**
 * Public REST endpoint for AJAX “load more” on quote feeds.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\REST;

use WPIS\Core\Constants;
use WPIS\Core\PostTypes\QuotePostType;

/**
 * Serves HTML fragments aligned with theme selectors: `.wpis-quote-card` and `wpis-sent-*` sentiment stripes
 * (see wpis-theme `assets/css/wpis-global.css`).
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
					'platform'   => array(
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

		$plat = sanitize_key( (string) $request->get_param( 'platform' ) );
		if ( '' !== $plat && in_array( $plat, Constants::SOURCE_PLATFORMS, true ) ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_wpis_source_platform',
					'value' => $plat,
				),
			);
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
		$link    = get_permalink( $post );
		$body    = wp_strip_all_tags( (string) $post->post_content );
		$excerpt = wp_trim_words( $body, 40, '…' );
		$read    = __( 'Read', 'wpis-core' );

		$sent = 'neutral';
		$st   = get_the_terms( $post, 'sentiment' );
		if ( is_array( $st ) && ! empty( $st ) && $st[0] instanceof \WP_Term ) {
			$slug = $st[0]->slug;
			if ( in_array( $slug, array( 'positive', 'negative', 'neutral', 'mixed' ), true ) ) {
				$sent = $slug;
			}
		}

		$ct_terms = get_the_terms( $post, 'claim_type' );
		$claim    = '';
		if ( is_array( $ct_terms ) && ! empty( $ct_terms ) && $ct_terms[0] instanceof \WP_Term ) {
			$claim = $ct_terms[0]->name;
		}

		$counter = (int) get_post_meta( $post->ID, '_wpis_counter', true );
		if ( $counter < 1 ) {
			$counter = 1;
		}
		$plat = (string) get_post_meta( $post->ID, '_wpis_source_platform', true );

		$inner  = '<p class="wpis-quote-card__text"><a class="wpis-quote-card__link" href="' . esc_url( $link ) . '">' . esc_html( $excerpt ) . '</a></p>';
		$inner .= '<div class="wpis-quote-card__footer">';
		if ( '' !== $claim ) {
			$inner .= '<span class="wpis-quote-card__claim">' . esc_html( $claim ) . '</span>';
		}
		$inner .= '<span class="wpis-quote-card__badge" aria-label="' . esc_attr__( 'Echo count', 'wpis-core' ) . '">×' . esc_html( (string) $counter ) . '</span>';
		if ( '' !== $plat ) {
			$inner .= '<span class="wpis-quote-card__plat">' . esc_html( strtoupper( $plat ) ) . '</span>';
		}
		$inner .= '<a class="wpis-quote-card__read" href="' . esc_url( $link ) . '">' . esc_html( $read ) . '</a>';
		$inner .= '</div>';

		return '<article class="wpis-quote-card wpis-sent-' . esc_attr( $sent ) . ' wp-block-post">' . $inner . '</article>';
	}
}

<?php
/**
 * Sentiment taxonomy (flat).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Taxonomies;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Registers sentiment taxonomy for quotes.
 */
final class SentimentTaxonomy {

	public const TAXONOMY = 'sentiment';

	/**
	 * Register taxonomy.
	 *
	 * @return void
	 */
	public static function register(): void {
		$labels = array(
			'name'                       => _x( 'Sentiments', 'taxonomy general name', 'wpis-core' ),
			'singular_name'              => _x( 'Sentiment', 'taxonomy singular name', 'wpis-core' ),
			'search_items'               => __( 'Search Sentiments', 'wpis-core' ),
			'popular_items'              => __( 'Popular Sentiments', 'wpis-core' ),
			'all_items'                  => __( 'All Sentiments', 'wpis-core' ),
			'edit_item'                  => __( 'Edit Sentiment', 'wpis-core' ),
			'update_item'                => __( 'Update Sentiment', 'wpis-core' ),
			'add_new_item'               => __( 'Add New Sentiment', 'wpis-core' ),
			'new_item_name'              => __( 'New Sentiment Name', 'wpis-core' ),
			'separate_items_with_commas' => __( 'Separate sentiments with commas', 'wpis-core' ),
			'add_or_remove_items'        => __( 'Add or remove sentiments', 'wpis-core' ),
			'choose_from_most_used'      => __( 'Choose from the most used sentiments', 'wpis-core' ),
			'not_found'                  => __( 'No sentiments found.', 'wpis-core' ),
			'menu_name'                  => __( 'Sentiments', 'wpis-core' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => false,
			'show_in_rest'      => true,
			'rest_base'         => 'sentiments',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'sentiment' ),
		);

		register_taxonomy( self::TAXONOMY, QuotePostType::POST_TYPE, $args );
	}
}

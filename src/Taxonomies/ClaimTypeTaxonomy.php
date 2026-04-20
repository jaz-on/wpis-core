<?php
/**
 * Claim type taxonomy (hierarchical).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Taxonomies;

use WPIS\Core\PostTypes\QuotePostType;

/**
 * Registers claim_type taxonomy for quotes.
 */
final class ClaimTypeTaxonomy {

	public const TAXONOMY = 'claim_type';

	/**
	 * Register taxonomy.
	 *
	 * @return void
	 */
	public static function register(): void {
		$labels = array(
			'name'                  => _x( 'Claim types', 'taxonomy general name', 'wpis-core' ),
			'singular_name'         => _x( 'Claim type', 'taxonomy singular name', 'wpis-core' ),
			'search_items'          => __( 'Search Claim Types', 'wpis-core' ),
			'all_items'             => __( 'All Claim Types', 'wpis-core' ),
			'parent_item'           => __( 'Parent Claim Type', 'wpis-core' ),
			'parent_item_colon'     => __( 'Parent Claim Type:', 'wpis-core' ),
			'edit_item'             => __( 'Edit Claim Type', 'wpis-core' ),
			'update_item'           => __( 'Update Claim Type', 'wpis-core' ),
			'add_new_item'          => __( 'Add New Claim Type', 'wpis-core' ),
			'new_item_name'         => __( 'New Claim Type Name', 'wpis-core' ),
			'menu_name'             => __( 'Claim Types', 'wpis-core' ),
			'not_found'             => __( 'No claim types found.', 'wpis-core' ),
			'no_terms'              => __( 'No claim types', 'wpis-core' ),
			'filter_by_item'        => __( 'Filter by claim type', 'wpis-core' ),
			'items_list_navigation' => __( 'Claim types list navigation', 'wpis-core' ),
			'items_list'            => __( 'Claim types list', 'wpis-core' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'claim-types',
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'claim' ),
		);

		register_taxonomy( self::TAXONOMY, QuotePostType::POST_TYPE, $args );
	}
}

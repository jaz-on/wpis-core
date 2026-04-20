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
			'name'                  => _x( 'Claim types', 'taxonomy general name', 'wpis-plugin' ),
			'singular_name'         => _x( 'Claim type', 'taxonomy singular name', 'wpis-plugin' ),
			'search_items'          => __( 'Search Claim Types', 'wpis-plugin' ),
			'all_items'             => __( 'All Claim Types', 'wpis-plugin' ),
			'parent_item'           => __( 'Parent Claim Type', 'wpis-plugin' ),
			'parent_item_colon'     => __( 'Parent Claim Type:', 'wpis-plugin' ),
			'edit_item'             => __( 'Edit Claim Type', 'wpis-plugin' ),
			'update_item'           => __( 'Update Claim Type', 'wpis-plugin' ),
			'add_new_item'          => __( 'Add New Claim Type', 'wpis-plugin' ),
			'new_item_name'         => __( 'New Claim Type Name', 'wpis-plugin' ),
			'menu_name'             => __( 'Claim Types', 'wpis-plugin' ),
			'not_found'             => __( 'No claim types found.', 'wpis-plugin' ),
			'no_terms'              => __( 'No claim types', 'wpis-plugin' ),
			'filter_by_item'        => __( 'Filter by claim type', 'wpis-plugin' ),
			'items_list_navigation' => __( 'Claim types list navigation', 'wpis-plugin' ),
			'items_list'            => __( 'Claim types list', 'wpis-plugin' ),
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

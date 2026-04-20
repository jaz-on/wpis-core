<?php
/**
 * Quote custom post type.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\PostTypes;

/**
 * Registers the quote CPT.
 */
final class QuotePostType {

	public const POST_TYPE = 'quote';

	/**
	 * Register post type.
	 *
	 * @return void
	 */
	public static function register(): void {
		$labels = array(
			'name'                  => _x( 'Quotes', 'post type general name', 'wpis-core' ),
			'singular_name'         => _x( 'Quote', 'post type singular name', 'wpis-core' ),
			'menu_name'             => _x( 'Quotes', 'admin menu', 'wpis-core' ),
			'name_admin_bar'        => _x( 'Quote', 'add new on admin bar', 'wpis-core' ),
			'add_new'               => _x( 'Add New', 'quote', 'wpis-core' ),
			'add_new_item'          => __( 'Add New Quote', 'wpis-core' ),
			'new_item'              => __( 'New Quote', 'wpis-core' ),
			'edit_item'             => __( 'Edit Quote', 'wpis-core' ),
			'view_item'             => __( 'View Quote', 'wpis-core' ),
			'all_items'             => __( 'All Quotes', 'wpis-core' ),
			'search_items'          => __( 'Search Quotes', 'wpis-core' ),
			'not_found'             => __( 'No quotes found.', 'wpis-core' ),
			'not_found_in_trash'    => __( 'No quotes found in Trash.', 'wpis-core' ),
			'archives'              => _x( 'Quote archives', 'The post type archive label used in nav menus', 'wpis-core' ),
			'filter_items_list'     => _x( 'Filter quotes list', 'Screen reader text for the filter links', 'wpis-core' ),
			'items_list_navigation' => _x( 'Quotes list navigation', 'Screen reader text for the pagination', 'wpis-core' ),
			'items_list'            => _x( 'Quotes list', 'Screen reader text for the items list', 'wpis-core' ),
		);

		$args = array(
			'labels'                => $labels,
			'description'           => __( 'Statements about WordPress collected for WordPress Is…', 'wpis-core' ),
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'quote' ),
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'has_archive'           => true,
			'hierarchical'          => false,
			'menu_position'         => 20,
			'menu_icon'             => 'dashicons-format-quote',
			'supports'              => array( 'title', 'editor', 'author', 'custom-fields', 'revisions' ),
			'show_in_rest'          => true,
			'rest_base'             => 'quotes',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		);

		register_post_type( self::POST_TYPE, $args );
	}
}

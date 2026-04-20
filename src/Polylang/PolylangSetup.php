<?php
/**
 * Polylang integration (post types, taxonomies).
 *
 * @package WPIS\Core
 */

namespace WPIS\Core\Polylang;

use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * Registers translatable objects with Polylang.
 */
final class PolylangSetup {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'pll_get_post_types', array( self::class, 'post_types' ), 10, 2 );
		add_filter( 'pll_get_taxonomies', array( self::class, 'taxonomies' ), 10, 2 );
	}

	/**
	 * @param string[] $types   Post types.
	 * @param bool     $settings Whether options UI.
	 * @return string[]
	 */
	public static function post_types( array $types, $settings ): array {
		unset( $settings );
		$types[ QuotePostType::POST_TYPE ] = QuotePostType::POST_TYPE;
		return $types;
	}

	/**
	 * @param string[] $taxonomies Taxonomies.
	 * @param bool     $settings   Whether settings UI.
	 * @return string[]
	 */
	public static function taxonomies( array $taxonomies, $settings ): array {
		unset( $settings );
		$taxonomies[ SentimentTaxonomy::TAXONOMY ] = SentimentTaxonomy::TAXONOMY;
		$taxonomies[ ClaimTypeTaxonomy::TAXONOMY ] = ClaimTypeTaxonomy::TAXONOMY;
		return $taxonomies;
	}
}

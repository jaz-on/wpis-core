<?php
/**
 * Plugin activation: flush rewrites and seed default terms.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core;

use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;

/**
 * Activation handler.
 */
final class Activation {

	public const SCHEMA_VERSION = '0.1.0';

	private const OPTION_KEY = 'wpis_plugin_schema_version';

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		PostStatuses::register();
		QuotePostType::register();
		SentimentTaxonomy::register();
		ClaimTypeTaxonomy::register();

		self::seed_default_terms();
		update_option( self::OPTION_KEY, self::SCHEMA_VERSION, true );

		flush_rewrite_rules( false );
	}

	/**
	 * Insert default taxonomy terms if missing (idempotent).
	 *
	 * @return void
	 */
	public static function seed_default_terms(): void {
		$sentiments = array(
			'positive' => __( 'Positive', 'wpis-plugin' ),
			'negative' => __( 'Negative', 'wpis-plugin' ),
			'neutral'  => __( 'Neutral', 'wpis-plugin' ),
			'mixed'    => __( 'Mixed', 'wpis-plugin' ),
		);

		foreach ( $sentiments as $slug => $name ) {
			if ( ! term_exists( $slug, SentimentTaxonomy::TAXONOMY ) ) {
				wp_insert_term( $name, SentimentTaxonomy::TAXONOMY, array( 'slug' => $slug ) );
			}
		}

		$claims = array(
			'performance'        => __( 'Performance', 'wpis-plugin' ),
			'security'           => __( 'Security', 'wpis-plugin' ),
			'ease-of-use'        => __( 'Ease of use', 'wpis-plugin' ),
			'community'          => __( 'Community', 'wpis-plugin' ),
			'ecosystem'          => __( 'Ecosystem', 'wpis-plugin' ),
			'business-viability' => __( 'Business viability', 'wpis-plugin' ),
			'accessibility'      => __( 'Accessibility', 'wpis-plugin' ),
			'modernity'          => __( 'Modernity', 'wpis-plugin' ),
		);

		foreach ( $claims as $slug => $name ) {
			if ( ! term_exists( $slug, ClaimTypeTaxonomy::TAXONOMY ) ) {
				wp_insert_term( $name, ClaimTypeTaxonomy::TAXONOMY, array( 'slug' => $slug ) );
			}
		}
	}
}

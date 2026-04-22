<?php
/**
 * Main plugin bootstrap.
 *
 * @package WPIS\Core
 */

namespace WPIS\Core;

use WPIS\Core\Admin\PluginListLinks;
use WPIS\Core\Admin\QuoteAdminColumns;
use WPIS\Core\Meta\QuoteMeta;
use WPIS\Core\PostTypes\QuotePostType;
use WPIS\Core\Taxonomies\ClaimTypeTaxonomy;
use WPIS\Core\Taxonomies\SentimentTaxonomy;
use WPIS\Core\Sync\CounterSync;
use WPIS\Core\Sync\GroupStatusSync;
use WPIS\Core\Upgrade;

/**
 * Plugin singleton.
 */
final class Plugin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_content' ), 0 );
		CounterSync::register();
		GroupStatusSync::register();
		Upgrade::register();
		$this->register_cli();
		$this->register_front_features();
		add_action( 'init', array( $this, 'register_admin' ), 30 );
		add_action( 'plugins_loaded', array( $this, 'register_relevanssi' ), 20 );
	}

	/**
	 * Post types, taxonomies, meta, statuses.
	 *
	 * @return void
	 */
	public function register_content(): void {
		PostStatuses::register();
		QuotePostType::register();
		SentimentTaxonomy::register();
		ClaimTypeTaxonomy::register();
		QuoteMeta::register();
	}

	/**
	 * Admin-only UI.
	 *
	 * @return void
	 */
	public function register_admin(): void {
		if ( ! is_admin() ) {
			return;
		}
		PluginListLinks::register();
		QuoteAdminColumns::register();
	}

	/**
	 * WP-CLI commands.
	 *
	 * @return void
	 */
	private function register_cli(): void {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}
		\WP_CLI::add_command(
			'wpis',
			\WPIS\Core\CLI\WPIS_CLI_Command::class
		);
	}

	/**
	 * Submission, REST, Polylang, abilities.
	 *
	 * @return void
	 */
	private function register_front_features(): void {
		\WPIS\Core\Shortcodes\PublicScreenShortcodes::register();
		\WPIS\Core\Submission\SubmissionHandler::register();
		\WPIS\Core\Submission\SubmitFormRenderer::register();
		\WPIS\Core\Submission\CronCleanup::register();
		\WPIS\Core\REST\RestRegistrar::register();
		\WPIS\Core\REST\QuoteFeedEndpoint::register();
		\WPIS\Core\Polylang\PolylangSetup::register();
		\WPIS\Core\Abilities\AbilitiesRegistry::register();
	}

	/**
	 * Third-party: Relevanssi (optional plugin).
	 *
	 * @return void
	 */
	public function register_relevanssi(): void {
		if ( ! defined( 'RELEVANSSI_VERSION' ) ) {
			return;
		}
		\WPIS\Core\Search\RelevanssiIntegration::register();
	}
}

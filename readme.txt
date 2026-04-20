=== WordPress Is… Core ===
Contributors: jaz_on
Tags: mcp, abilities-api, ai, wordpress-is, quotes
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Core plugin for the "WordPress Is…" project — quotes, taxonomies, meta, and MCP integration.

== Description ==

WordPress Is… Core powers the community archive: a `quote` custom post type, sentiment and claim-type taxonomies, REST-ready post meta, and the MCP server for AI tooling.

Features include:

* `quote` custom post type (REST-enabled, archive at `/quote/`)
* Taxonomies: `sentiment` (flat), `claim_type` (hierarchical) with default terms on activation
* Custom statuses: `rejected`, `merged` (plus core `pending`, `publish`, etc.)
* Post meta: counter, source fields, moderation fields, AI snapshot, opposing quote link, editorial note (see code)
* Admin list columns: counter, platform, submission source; sort by counter; filter by submission source
* MCP server at `/wp-json/wpis/v1/wpis` with an ability allowlist and `wpis_mcp_abilities` filter

== Requirements ==

* WordPress 6.9 or higher (Abilities API)
* PHP 8.3 or higher
* MCP Adapter plugin (recommended for MCP exposure; optional for other features)

== Installation ==

1. From the **`jaz-on/wpis-plugin`** repo, deploy the plugin directory as **`wpis-core`** under `/wp-content/plugins/` (folder name must match the plugin bootstrap).
2. Activate the plugin through the "Plugins" menu in WordPress
3. Visit Settings > Permalinks and save once if archives 404
4. (Optional) Ensure MCP Adapter is active for MCP discovery

== Development ==

* `composer install` — PHP dependencies and PHPCS/PHPUnit
* `composer lint` — PHPCS
* `composer test` — PHPUnit (requires `WP_TESTS_DIR`; install WordPress test lib via `bin/install-wp-tests.sh` from the WP developer docs or wp-cli scaffold)

== Changelog ==

= 0.2.0 =
* Chantier 1: `quote` CPT, taxonomies, meta, custom statuses, admin list enhancements
* Composer PSR-4 autoload (`WPIS\Core\`), PHPCS, PHPUnit scaffold
* MCP registration unchanged; version bump

= 0.1.0 =
* Initial release — MCP server registration and core abilities allowlist

== Links ==

* Project lead: https://jasonrouet.com

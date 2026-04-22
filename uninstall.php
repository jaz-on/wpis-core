<?php
/**
 * Uninstall handler for WordPress Is… (wpis-core).
 *
 * Runs when the plugin is deleted (not deactivated) via the WordPress admin.
 *
 * @package WPIS\Core
 */

// Prevent direct access.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * No persistent data to clean up yet.
 * Future versions will remove:
 * - Custom post types and their content (opt-in via setting).
 * - Custom taxonomies and their terms (opt-in via setting).
 * - Plugin options.
 */

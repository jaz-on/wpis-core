<?php
/**
 * PHPUnit bootstrap for wpis-plugin (WordPress test suite).
 *
 * Set WP_TESTS_DIR to your wordpress-tests-lib path (from bin/install-wp-tests.sh).
 *
 * @package WPIS\Core\Tests
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

$GLOBALS['wp_tests_options'] = array(
	'active_plugins' => array( basename( dirname( __DIR__ ) ) . '/wpis-plugin.php' ),
);

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	echo 'WP_TESTS_DIR is not set. Install the WordPress test suite, e.g.:' . PHP_EOL;
	echo '  bash bin/install-wp-tests.sh wordpress_test root "" localhost latest' . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load this plugin.
 */
function _wpis_plugin_manually_load_plugin(): void {
	require dirname( __DIR__ ) . '/wpis-plugin.php';
}

tests_add_filter( 'muplugins_loaded', '_wpis_plugin_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

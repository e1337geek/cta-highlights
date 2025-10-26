<?php
/**
 * PHPUnit Bootstrap File for CTA Highlights Plugin
 *
 * This file is loaded before running PHPUnit tests. It sets up the WordPress
 * test environment and loads necessary dependencies.
 *
 * @package CTAHighlights\Tests
 */

// Composer autoloader
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Load PHPUnit Polyfills
require_once dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// WordPress tests directory
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom config to wp-tests-config.php
$_ENV['WP_TESTS_DB_NAME'] = getenv( 'WP_TESTS_DB_NAME' ) ?: 'wordpress_test';
$_ENV['WP_TESTS_DB_USER'] = getenv( 'WP_TESTS_DB_USER' ) ?: 'root';
$_ENV['WP_TESTS_DB_PASSWORD'] = getenv( 'WP_TESTS_DB_PASSWORD' ) ?: 'password';
$_ENV['WP_TESTS_DB_HOST'] = getenv( 'WP_TESTS_DB_HOST' ) ?: 'localhost';
$_ENV['WP_TESTS_TABLE_PREFIX'] = getenv( 'WP_TESTS_TABLE_PREFIX' ) ?: 'wptests_';

// Load WordPress test suite
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress tests library at: $_tests_dir\n";
	echo "Run: npm run test:setup\n";
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin before the tests run
 */
function _manually_load_plugin() {
	require dirname( __DIR__, 2 ) . '/cta-highlights.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load test helpers
require_once __DIR__ . '/helpers.php';

// Load test factories
require_once __DIR__ . '/Factories/CTAFactory.php';
require_once __DIR__ . '/Factories/PostFactory.php';
require_once __DIR__ . '/Factories/UserFactory.php';
require_once __DIR__ . '/Factories/TemplateFactory.php';

// Load test traits
require_once __DIR__ . '/Traits/CreatesDatabase.php';
require_once __DIR__ . '/Traits/CreatesTemplates.php';
require_once __DIR__ . '/Traits/CreatesShortcodes.php';
require_once __DIR__ . '/Traits/AssertsHTML.php';

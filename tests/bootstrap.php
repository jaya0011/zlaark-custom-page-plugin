<?php
/**
 * PHPUnit bootstrap file for Custom Page Builder tests
 *
 * @package Custom_Page_Builder
 */

// Define test environment
define('CUSTOM_PAGE_BUILDER_TESTING', true);

// WordPress test environment setup
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    require dirname(dirname(__FILE__)) . '/custom-page-builder.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load test utilities
require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/Factories/PageFactory.php';
require_once __DIR__ . '/Factories/SectionFactory.php';
require_once __DIR__ . '/Mocks/MockSecureImageHandler.php';
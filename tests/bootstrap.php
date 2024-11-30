<?php
/**
 * PHPUnit bootstrap file
 */

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Anna polku wp-content/plugins/[plugin-directory]/tests/bootstrap.php
define('WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php');

// Lataa WordPress test suite
require_once $_tests_dir . '/includes/functions.php';

/**
 * Lataa teema
 */
function _manually_load_theme() {
    switch_theme('tonys-theme');
}
tests_add_filter('muplugins_loaded', '_manually_load_theme');

// Käynnistä WordPress test suite
require $_tests_dir . '/includes/bootstrap.php';

<?php
/**
 * PHPUnit bootstrap: loads the WordPress test suite (wp-phpunit) and this plugin.
 *
 * @package DCTech\PropertyLeads
 */

$dctech_plugin_dir = dirname( __DIR__ );

require_once $dctech_plugin_dir . '/vendor/autoload.php';

$dctech_wp_phpunit = getenv( 'WP_PHPUNIT__DIR' ) ? getenv( 'WP_PHPUNIT__DIR' ) : $dctech_plugin_dir . '/vendor/wp-phpunit/wp-phpunit';

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $dctech_plugin_dir . '/vendor/yoast/phpunit-polyfills' );

require_once $dctech_wp_phpunit . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $dctech_plugin_dir ) {
		require $dctech_plugin_dir . '/dctech-property-leads.php';
	}
);

require $dctech_wp_phpunit . '/includes/bootstrap.php';

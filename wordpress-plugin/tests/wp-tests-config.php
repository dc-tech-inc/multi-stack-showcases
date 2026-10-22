<?php
/**
 * Config for the WordPress test suite; everything comes from the environment (see .env.example).
 * WARNING: the suite DROPS all tables in DB_NAME. Never point it at a real site database.
 *
 * @package DCTech\PropertyLeads
 */

define( 'ABSPATH', rtrim( getenv( 'WP_CORE_DIR' ) ? getenv( 'WP_CORE_DIR' ) : '/tmp/wordpress', '/' ) . '/' );
define( 'WP_DEBUG', true );

define( 'DB_NAME', getenv( 'WP_TEST_DB_NAME' ) ? getenv( 'WP_TEST_DB_NAME' ) : 'wordpress_test' );
define( 'DB_USER', getenv( 'WP_TEST_DB_USER' ) ? getenv( 'WP_TEST_DB_USER' ) : 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TEST_DB_PASSWORD' ) ? getenv( 'WP_TEST_DB_PASSWORD' ) : '' );
define( 'DB_HOST', getenv( 'WP_TEST_DB_HOST' ) ? getenv( 'WP_TEST_DB_HOST' ) : '127.0.0.1' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );

$table_prefix = 'wptests_';

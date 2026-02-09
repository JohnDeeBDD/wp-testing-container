<?php
/**
 * Bootstrap WordPress inside Codeception's wpunit suite.
 *
 * This file is executed once before any test in this suite runs.
 * It loads the full WordPress environment so tests can use WP functions
 * (get_option, wp_insert_post, register_post_type, etc.).
 */

// Path to the WordPress installation inside the container.
$wp_root = getenv('WP_ROOT') ?: '/var/www/html';

// Safety check: make sure WP is actually installed.
$wp_load = $wp_root . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
    throw new RuntimeException(
        "WordPress not found at {$wp_load}. Run bin/test-setup first."
    );
}

// Prevent WordPress from sending headers during tests.
define( 'WP_USE_THEMES', false );

// Load WordPress.
require_once $wp_load;

echo "WordPress " . get_bloginfo( 'version' ) . " loaded from {$wp_root}\n";

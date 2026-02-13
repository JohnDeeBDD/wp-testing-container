<?php
/**
 * Plugin Name: My Plugin
 * Description: A sample WordPress plugin used to demonstrate the testing container.
 * Version:     1.0.0
 * Author:      Developer
 * Text Domain: my-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return a greeting string.
 *
 * @param string $name Name to greet.
 * @return string
 */
function my_plugin_greet( string $name ): string {
    return "Hello, {$name}!";
}

/**
 * Register a custom post type called "book".
 */
function my_plugin_register_book_cpt(): void {
    register_post_type( 'book', [
        'public'  => true,
        'label'   => 'Books',
        'supports' => [ 'title', 'editor', 'thumbnail' ],
    ] );
}
add_action( 'init', 'my_plugin_register_book_cpt' );

/**
 * Render the [HELLO] shortcode.
 *
 * @return string
 */
function my_plugin_hello_shortcode(): string {
    return 'HELLO WORLD';
}
add_shortcode( 'HELLO', 'my_plugin_hello_shortcode' );

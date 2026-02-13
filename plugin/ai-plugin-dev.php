<?php
/**
 * Plugin Name: AI Plugin Dev
 * Plugin URI: https://aiplugin.dev
 * Description: A WordPress plugin builder with single command execution
 * Version: 5.0.0.0.0
 * Author: General Chicken 
 * License: Copyright (C) 2025. THIS IS NOT FREE SOFTWARE.
 */

namespace AIPluginDev;

if (!defined('ABSPATH')) {
    exit;
}
error_log("ai-plugin-dev.php loaded.");
require_once __DIR__ . '/vendor/autoload.php';

GlobalConstants::enable();

$restAPIMetaFields = new \AIPluginDev\RestAPIMetaFields();
$restAPIMetaFields->register_hooks();

$aiPluginCPT = new \AIPluginDev\AIPluginCPT();
$aiPluginCPT->register();



\add_action('rest_api_init', [new BuildAPI, 'register_rest_route']);
\add_action('rest_api_init', [new NextBuildAPI, 'register_rest_route']);

register_activation_hook(__FILE__, function() {
    $aiPluginCPT = new \AIPluginDev\AIPluginCPT();
    $aiPluginCPT->register();
    flush_rewrite_rules();
});

$BuildPluginActionButton = new BuildButtonActionHandler;
$BuildPluginActionButton->enable();

\add_action('widgets_init', function() {
        Widget_NewAIPluginButton::register_widget();
});

\add_action('widgets_init', function() {
    Widget_MyAI_Plugins::register_widget();
});

//TestMode::enable_test_mode();

function expose_hidden_data_key() {
    register_post_meta( 'ai-plugin', '_cacbot_action_enabled_build_plugin', array(
        'type'         => 'string',
        'single'       => true,
        'show_in_rest' => true,   // 👈 this exposes it in the REST API
        'auth_callback' => function() {
            return true;  // adjust permissions as needed
        }
    ) );
}
add_action( 'init', '\AIPluginDev\expose_hidden_data_key' );

FrontendData::enable();

\add_action('wp_enqueue_scripts', function () {
    \wp_enqueue_script(
        'ai-plugin-dev-main',
        \plugin_dir_url(__FILE__) . 'src/js/ai-plugin-dev.js',
        [],
        (string) (\filemtime(\plugin_dir_path(__FILE__) . 'src/js/ai-plugin-dev.js') ?: '1'),
        true
    );
});

\add_action('admin_enqueue_scripts', function () {
    \wp_enqueue_script(
        'ai-plugin-dev-main',
        \plugin_dir_url(__FILE__) . 'src/js/ai-plugin-dev.js',
        [],
        (string) (\filemtime(\plugin_dir_path(__FILE__) . 'src/js/ai-plugin-dev.js') ?: '1'),
        true
    );
});

\add_action('init', function() {
    register_post_meta('ai-plugin', 'ai-plugin-version', [
        'show_in_rest' => true,
        'single'       => true,
        'type'         => 'string',
        'auth_callback' => function() {
            return current_user_can('edit_posts');
        },
    ]);
});

ActionButton::enable();

FrontendViewBuilder::enable_header();

<?php
/**
 * Plugin Name: {ai_plugin_title}
 * Plugin URI: https://aiplugin.dev/ai-plugin/{ai_plugin_title}
 * Description: 
 * Version: 1
 * Author: {ai_plugin_author}
 * License: Copyright (C) AI_PLUGIN_DEV 2026. ALL RIGHTS RESERVED. THIS IS NOT FREE SOFTWARE.
 */

namespace {ai_plugin_slug};

/*
Example require:
require_once plugin_dir_path(__FILE__) . 'src/ExampleClass.php';
*/

//JavaScript goes in src/js/{ai_plugin_slug}.js:
\add_action( 'wp_enqueue_scripts', '\{ai_plugin_slug}\wp_enqueue_scripts' );

function wp_enqueue_scripts(){
    \wp_enqueue_script(
        '{ai_plugin_slug}',
        \plugin_dir_url(dirname(__FILE__)) . 'src/js/{ai_plugin_slug}.js',
        ['wp-api'],
        false, 
        true
    );
}

require_once plugin_dir_path(__FILE__) . "plugin-update-checker/plugin-update-checker.php";
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://aiplugin.dev/wp-content/aiplugins/{ai_plugin_slug}_details.json',
	__FILE__, //Full path to the main plugin file or functions.php.
	'{ai_plugin_slug}'
);
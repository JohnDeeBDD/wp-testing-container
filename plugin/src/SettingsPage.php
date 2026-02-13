<?php

namespace AIPluginDev;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsPage {
    
    public function __construct() {
        \add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    /**
     * Add the settings page to the WordPress admin menu
     */
    public function add_admin_menu(): void {
        \add_options_page(
            'AI Plugin Dev Settings',           // Page title
            'AI Plugin Dev',                    // Menu title
            'manage_options',                   // Capability
            'ai-plugin-dev-settings',          // Menu slug
            [$this, 'render_settings_page']    // Callback function
        );
    }
    
    /**
     * Render the settings page content
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1>AI Plugin Dev Settings</h1>
            <p>Configure your AI Plugin Dev settings below.</p>
            
            <form method="post" action="options.php">
                <?php
                \settings_fields('ai_plugin_dev_settings');
                \do_settings_sections('ai_plugin_dev_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Plugin Status</th>
                        <td>
                            <p>AI Plugin Dev is active and ready to use.</p>
                        </td>
                    </tr>
                </table>
                <?php \submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
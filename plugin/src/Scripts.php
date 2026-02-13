<?php

namespace AIPluginDev;

/**
 * Scripts - Handles admin script enqueuing for ai-plugin meta box
 *
 * Business Value: Efficient script loading only in admin meta box context
 */
class Scripts
{
    /**
     * Enqueue admin meta box scripts
     */
    public function enqueue_admin_scripts(): void
    {
        // Only enqueue in admin context
        if (!\is_admin()) {
            return;
        }
        
        // Only enqueue on ai-plugin post edit screens
        if (!$this->should_load_admin_scripts()) {
            return;
        }
        
        // Get the compiled admin JavaScript file path
        $admin_js_path = \plugin_dir_path(__FILE__) . 'ai-plugin-dev-admin/ai-plugin-dev-admin.js';
        $admin_js_url = \plugin_dir_url(__FILE__) . 'ai-plugin-dev-admin/ai-plugin-dev-admin.js';
        
        // Check if compiled admin JS exists
        if (!\file_exists($admin_js_path)) {
            \error_log('AI Plugin Dev: Compiled admin JS not found at: ' . $admin_js_path);
            return;
        }
        
        \wp_enqueue_script(
            'ai-plugin-dev-admin',
            $admin_js_url,
            ['jquery', 'wp-api'],
            (string) (\filemtime($admin_js_path) ?: '1'), // Use file modification time for cache busting
            true
        );
        
        // Get current post ID if available
        $post_id = \get_the_ID();
        if (!$post_id && isset($_GET['post'])) {
            $post_id = (int) $_GET['post'];
        }
        
        // Create nonce for REST API and admin actions
        $rest_nonce = \wp_create_nonce('wp_rest');
        $admin_nonce = \wp_create_nonce('ai_plugin_dev_admin');
        
        \wp_localize_script('ai-plugin-dev-admin', 'AIPluginDev', [
            'rest_nonce' => $rest_nonce,
            'admin_nonce' => $admin_nonce,
            'post_id' => $post_id,
            'ajax_url' => \admin_url('admin-ajax.php'),
            'rest_url' => \rest_url('ai-plugin-dev/v1/'),
            'is_admin' => true,
            'is_edit_screen' => $this->is_ai_plugin_edit_screen(),
            'post_type' => \get_post_type($post_id ?: 0)
        ]);
        
        // Add some debugging info in development
        if (\defined('WP_DEBUG') && WP_DEBUG) {
            \error_log('AI Plugin Dev: Admin scripts enqueued for post ID: ' . $post_id);
        }
    }
    
    /**
     * Check if current context should load admin scripts
     */
    public function should_load_admin_scripts(): bool
    {
        // Only load in admin
        if (!\is_admin()) {
            return false;
        }
        
        // Don't load during AJAX requests (unless it's our AJAX)
        if (\wp_doing_ajax() && !$this->is_our_ajax_request()) {
            return false;
        }
        
        // Don't load during cron
        if (\wp_doing_cron()) {
            return false;
        }
        
        // Only load on ai-plugin edit screens
        if (!$this->is_ai_plugin_edit_screen()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if we're on an ai-plugin post edit screen
     */
    private function is_ai_plugin_edit_screen(): bool
    {
        global $pagenow, $post;
        
        // Check if we're on post.php or post-new.php
        if (!in_array($pagenow, ['post.php', 'post-new.php'])) {
            return false;
        }
        
        // Check post type from current post
        if ($post && $post->post_type === 'ai-plugin') {
            return true;
        }
        
        // Check post type from GET parameter
        if (isset($_GET['post_type']) && $_GET['post_type'] === 'ai-plugin') {
            return true;
        }
        
        // Check post type from post ID in GET parameter
        if (isset($_GET['post'])) {
            $post_id = (int) $_GET['post'];
            $post_type = \get_post_type($post_id);
            if ($post_type === 'ai-plugin') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if this is our AJAX request
     */
    private function is_our_ajax_request(): bool
    {
        if (!isset($_POST['action'])) {
            return false;
        }
        
        $our_actions = [
            'ai_plugin_dev_build',
            'ai_plugin_dev_get_status',
            'ai_plugin_dev_save_meta'
        ];
        
        return in_array($_POST['action'], $our_actions);
    }
}
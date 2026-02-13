<?php

namespace AIPluginDev;

/**
 * RestAPIMetaFields - Handles REST API meta field registration for ai-plugin post type
 * 
 * Business Value: Exposes plugin meta data through WordPress REST API for frontend consumption
 */
class RestAPIMetaFields
{
    /**
     * Register WordPress hooks
     */
    public function register_hooks(): void
    {
        \add_action('rest_api_init', [$this, 'register_meta_fields']);
    }
    
    /**
     * Register meta fields for REST API exposure
     */
    public function register_meta_fields(): void
    {
        // Register agent_status meta field for ai-plugin post type
        \register_rest_field(
            'ai-plugin',                           // Post type
            'agent_status',                        // Field name in REST response
            [
                'get_callback'    => [$this, 'get_agent_status'],
                'update_callback' => [$this, 'update_agent_status'],
                'schema'          => [
                    'description' => \__('Current status of the AI plugin build process', 'ai-plugin-dev'),
                    'type'        => 'string',
                   // 'enum'        => AgentStatusValues::get_status_values(),
                    'context'     => ['view', 'edit'],
                    'readonly'    => false,
                ],
            ]
        );

        // Register build_started meta field for ai-plugin post type
        \register_rest_field(
            'ai-plugin',                           // Post type
            'build_started',                       // Field name in REST response
            [
                'get_callback'    => [$this, 'get_build_started'],
                'update_callback' => null,         // Read-only field
                'schema'          => [
                    'description' => \__('Unix timestamp when the build process started', 'ai-plugin-dev'),
                    'type'        => 'integer',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ]
        );

        // Register build_stopped meta field for ai-plugin post type
        \register_rest_field(
            'ai-plugin',                           // Post type
            'build_stopped',                       // Field name in REST response
            [
                'get_callback'    => [$this, 'get_build_stopped'],
                'update_callback' => null,         // Read-only field
                'schema'          => [
                    'description' => \__('Unix timestamp when the build process stopped', 'ai-plugin-dev'),
                    'type'        => 'integer',
                    'context'     => ['view', 'edit'],
                    'readonly'    => true,
                ],
            ]
        );

        // Register ai-plugin-version meta field for ai-plugin post type
        \register_rest_field(
            'ai-plugin',                           // Post type
            'ai-plugin-version',                   // Field name in REST response
            [
                'get_callback'    => [$this, 'get_plugin_version'],
                'update_callback' => [$this, 'update_plugin_version'],
                'schema'          => [
                    'description' => \__('Version number of the AI plugin', 'ai-plugin-dev'),
                    'type'        => 'string',
                    'context'     => ['view', 'edit'],
                    'readonly'    => false,
                ],
            ]
        );
    }
    
    /**
     * Get callback for agent_status meta field
     *
     * @param array $object The post object array
     * @param string $field_name The field name being requested
     * @param \WP_REST_Request $request The REST request object
     * @return string The agent status value
     */
    public function get_agent_status($object, string $field_name, \WP_REST_Request $request): string
    {
        $post_id = $object['id'];
        
        // Check permissions - user must be able to edit the post
        if (!\current_user_can('edit_post', $post_id)) {
            return '';
        }
        
        // Get agent_status from post meta
        $agent_status = \get_post_meta($post_id, 'agent_status', true);
        
        // Sanitize and validate the status value
        $valid_statuses = AgentStatusValues::get_status_values();
        
        // If empty or invalid, return 'idle' as default
        if (empty($agent_status) || !in_array($agent_status, $valid_statuses, true)) {
            return 'idle';
        }
        
        return \sanitize_text_field($agent_status);
    }
    
    /**
     * Get callback for build_started meta field
     *
     * @param array $object The post object array
     * @param string $field_name The field name being requested
     * @param \WP_REST_Request $request The REST request object
     * @return int|null The build started timestamp or null if not set
     */
    public function get_build_started($object, string $field_name, \WP_REST_Request $request): ?int
    {
        $post_id = $object['id'];
        
        // Check permissions - user must be able to edit the post
        if (!\current_user_can('edit_post', $post_id)) {
            return null;
        }
        
        // Get build_started from post meta
        $build_started = \get_post_meta($post_id, 'build_started', true);
        
        // Return as integer if valid, null otherwise
        if (empty($build_started) || !is_numeric($build_started)) {
            return null;
        }
        
        return (int) $build_started;
    }
    
    /**
     * Get callback for build_stopped meta field
     *
     * @param array $object The post object array
     * @param string $field_name The field name being requested
     * @param \WP_REST_Request $request The REST request object
     * @return int|null The build stopped timestamp or null if not set
     */
    public function get_build_stopped($object, string $field_name, \WP_REST_Request $request): ?int
    {
        $post_id = $object['id'];
        
        // Check permissions - user must be able to edit the post
        if (!\current_user_can('edit_post', $post_id)) {
            return null;
        }
        
        // Get build_stopped from post meta
        $build_stopped = \get_post_meta($post_id, 'build_stopped', true);
        
        // Return as integer if valid, null otherwise
        if (empty($build_stopped) || !is_numeric($build_stopped)) {
            return null;
        }
        
        return (int) $build_stopped;
    }
    
    /**
     * Get callback for ai-plugin-version meta field
     *
     * @param array $object The post object array
     * @param string $field_name The field name being requested
     * @param \WP_REST_Request $request The REST request object
     * @return string|null The plugin version or null if not set
     */
    public function get_plugin_version($object, string $field_name, \WP_REST_Request $request): ?string
    {
        $post_id = $object['id'];
        
        // Check permissions - user must be able to edit the post
        if (!\current_user_can('edit_post', $post_id)) {
            return null;
        }
        
        // Get plugin version using the Version class
        $version = Version::get_version($post_id);
        
        // Return the version as a string
        return $version ? \sanitize_text_field($version) : null;
    }
    
    /**
     * Update callback for agent_status meta field
     *
     * @param string $value The new agent status value
     * @param \WP_Post $object The post object
     * @param string $field_name The field name being updated
     * @param \WP_REST_Request $request The REST request object
     * @return bool True on success, false on failure
     */
    public function update_agent_status($value, \WP_Post $object, string $field_name, \WP_REST_Request $request): bool
    {
        $post_id = $object->ID;
        
        // Check permissions - user must be able to edit the post
        if (!\current_user_can('edit_post', $post_id)) {
            return false;
        }
        
        // Validate the status value
        $valid_statuses = AgentStatusValues::get_status_values();
        if (!in_array($value, $valid_statuses, true)) {
            return false;
        }
        
        // Sanitize the value
        $sanitized_value = \sanitize_text_field($value);
        
        // Update the post meta
        $result = \update_post_meta($post_id, 'agent_status', $sanitized_value);
        
        // If status is being set to 'building', also set build_started timestamp
        if ($sanitized_value === 'building') {
            \update_post_meta($post_id, 'build_started', time());
            // Clear any previous build_stopped timestamp
            \delete_post_meta($post_id, 'build_stopped');
        }
        
        // If status is being set to 'done' or 'error', set build_stopped timestamp
        if (in_array($sanitized_value, ['done', 'error'], true)) {
            \update_post_meta($post_id, 'build_stopped', time());
        }
        
        return $result !== false;
    }
    
    /**
     * Update callback for ai-plugin-version meta field
     *
     * @param string $value The new plugin version value
     * @param \WP_Post $object The post object
     * @param string $field_name The field name being updated
     * @param \WP_REST_Request $request The REST request object
     * @return bool True on success, false on failure
     */
    public function update_plugin_version($value, \WP_Post $object, string $field_name, \WP_REST_Request $request): bool
    {
        $post_id = $object->ID;
        
        // Check permissions - user must be able to edit the post
        if (!\current_user_can('edit_post', $post_id)) {
            return false;
        }
        
        // Sanitize the value
        $sanitized_value = \sanitize_text_field($value);
        
        // Update the post meta using the Version class method
        $result = \update_post_meta($post_id, 'ai-plugin-version', $sanitized_value);
        
        return $result !== false;
    }
}
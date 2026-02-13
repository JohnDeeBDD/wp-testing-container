<?php

namespace AIPluginDev;

class AgentStatusValues{

    public static function get_status_values(){
        return ["idle", "done", "building", "error", "ready", "unpublished"];
    }

    public static function get_post_status($post_id){
        // First check if the WordPress post is published
        $wp_post_status = get_post_status($post_id);
        $is_published = in_array($wp_post_status, ['publish', 'private']);
        
        // If post is not published, return "unpublished" regardless of agent_status
        if (!$is_published) {
            return 'unpublished';
        }
        
        // Get the status from post meta
        $status = get_post_meta($post_id, 'agent_status', true);
        
        // If no data exists, return "ready"
        if (empty($status)) {
            return 'ready';
        }
        
        // Validate that the status is one of the allowed values
        $valid_statuses = self::get_status_values();
        if (in_array($status, $valid_statuses)) {
            return $status;
        }
        
        // If status is invalid, return "ready" as fallback
        return 'ready';
    }
}
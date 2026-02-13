<?php

namespace AIPluginDev;

class ActionButton{

    public static function enable() {
        
        \add_action("wp", [__CLASS__, 'register_action_button']);
    }

    public static function register_action_button() {
        
        if ( !is_singular() ) {return;}
        $post_id = get_queried_object_id();
        $action_enabled = \get_post_meta($post_id, '_cacbot_action_enabled_build_plugin', true);       
        if ( $action_enabled ) {
            /**
            * Register the "Action" button via ai_style theme's action button API
            *
            * This button displays a hammer icon and triggers the hammerAction() JS callback
            * when clicked, which shows an alert with "Hammer clicked".
            */
            if (defined('AI_STYLE_COMMENT_FORM_BUTTONS_FILTER_NAME')) {
                // ACTION_BUTTON_FILTER_NAME is defined by the ai_style theme. 
        
                \add_filter(AI_STYLE_COMMENT_FORM_BUTTONS_FILTER_NAME, function(array $buttons) {
                    $buttons[] = array(
                        'name'     => 'hammer-action',
                        'content'  => __('Action', 'ai-plugin-dev'),
                        'icon'     => 'dashicons-hammer',
                        'callback' => 'hammerAction',
                        'priority' => 20,
                    );
                    return $buttons;       
                });
            }else{
                error_log("⚠️ Warning: ACTION_BUTTON_FILTER_NAME is not defined. Cannot register action button.");
            }


        }
    }
}
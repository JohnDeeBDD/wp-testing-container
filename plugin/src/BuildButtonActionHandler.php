<?php

namespace AIPluginDev;

class BuildButtonActionHandler{


    public static function enable(){
        \add_action( 'wp_insert_comment',  [self::class, 'handle_comment_post'], 5, 2);
    }

    public static function handle_comment_post($comment_id, $Comment)
    {
        if (str_ends_with($Comment->comment_content, "[ai-plugin-dev-build-plugin-action-button-pressed]")) {
            global $doNotPassGo;
            $doNotPassGo = true; //This flag aborts the Cacbot comment processing as a question. 
            \wp_update_post(
                [
                    'ID'            => $Comment->comment_post_ID,
                    'comment_status'=> 'closed',
                ]
            );
            $current_user_ID = get_current_user_id();
            $anchor_post_ID = \Cacbot\LinkedPost::get_anchor_post_id($Comment->comment_post_ID);
            \update_post_meta($anchor_post_ID, "ai-plugin-dev-agent-status", "building");
            \AIPluginDev\OriginalSpec::handle_original_spec_submission($anchor_post_ID, $Comment->comment_post_ID);
        }
    }

}
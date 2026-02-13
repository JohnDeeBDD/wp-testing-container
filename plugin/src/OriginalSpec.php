<?php

namespace AIPluginDev;

class OriginalSpec {

    //The original spec will be stored as a Cacbot linked post_id
    public static function get_spec($post_id){
        $spec = \get_post_meta($post_id, AI_PLUGIN_DEV_ORIGINAL_SPEC, true);
        if (empty($spec)) {
            $spec = "Paste specification into comment box and click 'Build Plugin'.";
        }else{
            $spec = "<a href = '?p=$spec'>Click to view</a>";
        }
        return $spec;
    }

    public static function handle_original_spec_submission($anchor_post_ID, $comment_post_ID){
        if (empty(\get_post_meta($anchor_post_ID, AI_PLUGIN_DEV_ORIGINAL_SPEC, true))) {
            \update_post_meta($anchor_post_ID, AI_PLUGIN_DEV_ORIGINAL_SPEC, $comment_post_ID);
        }
    }



}
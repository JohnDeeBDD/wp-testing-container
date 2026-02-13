<?php

namespace AIPluginDev;

class FrontendData{

    public static function enable(){

        \add_filter( 'cacbot_api_response_data_filters', ['\AIPluginDev\FrontendData', 'cacbot_api_response_data_filters' ]);

    }

    public static function cacbot_api_response_data_filters( $data ) {
        $data['content'] = \apply_filters('the_content', get_post_field('post_content', $data['post_id']));
        $data['title'] = \get_post_field('post_title', $data['post_id']);
        //$data[AI_PLUGIN_DEV_PLUGIN_DESCRIPTION] = \get_post_meta( $data['post_id'] , AI_PLUGIN_DEV_PLUGIN_DESCRIPTION, true);
        $data[AI_PLUGIN_DEV_PLUGIN_VERSION] = \get_post_meta( $data['post_id'] , AI_PLUGIN_DEV_PLUGIN_VERSION, true);
        $data[CACBOT_ACTION_ENABLED_BUILD_PLUGIN] = \get_post_meta( $data['post_id'] , CACBOT_ACTION_ENABLED_BUILD_PLUGIN, true);
        
        $data[AI_PLUGIN_DEV_AGENT_STATUS] = \get_post_meta( $data['post_id'] , AI_PLUGIN_DEV_AGENT_STATUS, true);
        return $data;
    }

}
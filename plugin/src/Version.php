<?php

namespace AIPluginDev;

class Version{

    public static string $version_key = "ai-plugin-version";

    public static function get_version($post_id){
        $version = get_post_meta($post_id, self::$version_key, true);
        if(!$version){
            $version = "1";
            update_post_meta($post_id, self::$version_key, $version);
        }
        return $version;
    }

    public static function increment_version($post_id){
        $version = self::get_version($post_id);
        $new_version = implode('.', array_map('intval', explode('.', $version))) + 1;
        update_post_meta($post_id, self::$version_key, $new_version);
        return $new_version;
    }       

}
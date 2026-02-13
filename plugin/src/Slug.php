<?php

namespace AIPluginDev;

class Slug{

    public static function get_AI_Plugin_slug(int $post_id): string {

        return "aiplugin" . $post_id;

    }


    public static function get_plugin_directory(int $post_id): string {
        $dir = plugin_dir_path(dirname(dirname(__FILE__))) . self::get_AI_Plugin_slug($post_id);
        error_log("Slug::get_plugin_directory() returns $dir");
        return $dir;
    }


    public static function get_plugin_file_path(int $post_id): string {
        return self::get_plugin_directory($post_id) . "/" . self::get_AI_Plugin_slug($post_id) . ".php";
    }

}
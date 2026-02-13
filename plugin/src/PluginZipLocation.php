<?php

namespace AIPluginDev;

class PluginZipLocation{

    public static function get_zip_location($post_id){
        $site_url = \get_site_url();
        if($site_url === "http://localhost"){
            return "/var/www/html/wp-content/plugins/aiplugin" . $post_id . "/aiplugin" . $post_id . ".zip";
        }
        if($site_url === "https://aiplugin.dev"){
            return "/var/www/aiplugin.dev/wp-content/aiplugins/aiplugin" . $post_id . ".zip";
        }
        return null;
    }

}
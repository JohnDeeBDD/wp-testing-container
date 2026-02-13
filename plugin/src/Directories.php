<?php

namespace AIPluginDev;

class Directories {

    public static function get_aiplugins_directory(): string {  
        if(get_site_url() === "http://localhost"){
            return '/var/www/html/wp-content/aiplugins/';
        } else {
            return '/var/www/aiplugin.dev/wp-content/aiplugins/';  
        }
    }

}
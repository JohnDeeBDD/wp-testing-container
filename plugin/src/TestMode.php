<?php

namespace AIPluginDev;

class TestMode{

    public static string $metadata_key = "ai-plugin-test-mode";

    public static function is_test_mode($post_id){

        if ( \metadata_exists( "post", $post_id, self::$metadata_key ) ){
            $value = \get_post_meta ($post_id, self::$metadata_key, true );
            if($value){
                return true;
            }
        }
        return false;
    }

    public static function enable_test_mode(){

        \add_action("template_redirect", function(){
            if(isset($_GET['set_test_mode'])){
                if(\is_singular("ai-plugin")){
                    $post_id = \get_the_ID();
                    \update_post_meta($post_id, self::$metadata_key, true);
                }
            }
        });
    }
        
}
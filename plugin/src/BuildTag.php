<?php

namespace AIPluginDev;

class BuildTag{

    public static function add_exclusive_build_tag_to_linked_post($post_id){
        // Validate post ID
        if (!$post_id || !is_numeric($post_id)) {
            return false;
        }
        
        // Get the post
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        // Get the post author
        $author_id = $post->post_author;
        
        // Define the tag name
        $tag_name = 'BUILDING';
        
        // Get the tag object
        $tag = get_term_by('name', $tag_name, 'post_tag');
        if (!$tag) {
            // Create the tag if it doesn't exist
            $tag_result = wp_insert_term($tag_name, 'post_tag');
            if (is_wp_error($tag_result)) {
                return false;
            }
            $tag_id = $tag_result['term_id'];
        } else {
            $tag_id = $tag->term_id;
        }
        
        // Find all posts by the same author that have the "BUILDING" tag
        $posts_with_building_tag = get_posts(array(
            'author' => $author_id,
            'post_status' => 'any',
            'numberposts' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'post_tag',
                    'field' => 'name',
                    'terms' => $tag_name
                )
            ),
            'exclude' => array($post_id) // Exclude the current post
        ));
        
        // Remove the "BUILDING" tag from all other posts by the same author
        foreach ($posts_with_building_tag as $other_post) {
            wp_remove_object_terms($other_post->ID, $tag_id, 'post_tag');
        }
        
        // Add the "BUILDING" tag to the current post
        $result = wp_set_object_terms($post_id, $tag_id, 'post_tag', true);
        
        // Return true if successful, false otherwise
        return !is_wp_error($result);
    }

}
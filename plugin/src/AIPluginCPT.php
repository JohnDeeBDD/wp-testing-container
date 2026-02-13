<?php

namespace AIPluginDev;

/**
 * AIPluginCPT - Registers and manages the ai-plugin custom post type
 * 
 * Business Value: Serves as the central hub for all plugin build requests
 */
class AIPluginCPT
{
    /**
     * Register the custom post type and initialize functionality
     */
    public function register(): void
    {
        \add_action('init', [$this, 'register_post_type']);
        \add_action('init', [$this, 'register_taxonomies']);
        \add_filter('manage_ai-plugin_posts_columns', [$this, 'customize_columns']);
        \add_action('manage_ai-plugin_posts_custom_column', [$this, 'populate_custom_columns'], 10, 2);

        \add_action("template_redirect", function () {
            global $post;
            if (\is_user_logged_in()) {

                if (\is_singular()) {
                    if (
                        \is_singular("ai-plugin")
                    ) {
                        self::wp_enqueue_scripts();
                    }
                }
            }
        });
    }
        
    public function wp_enqueue_scripts(){
        /*

        \wp_register_script(
            "ai-plugin-dev",
            \plugin_dir_url(dirname(__FILE__)) . 'dist/ai-plugin-dev-admin.js',
            ['wp-api'],
            false, 
            true
        );
        
        \wp_enqueue_script("ai-plugin-dev");
        */
    }

    /**
     * Register the ai-plugin custom post type
     */
    public function register_post_type(): void
    {

        $labels = [
            'name'                  => \_x('AI Plugins', 'Post type general name', 'ai-plugin-dev'),
            'singular_name'         => \_x('AI Plugin', 'Post type singular name', 'ai-plugin-dev'),
            'menu_name'             => \_x('AI Plugins', 'Admin Menu text', 'ai-plugin-dev'),
            'name_admin_bar'        => \_x('AI Plugin', 'Add New on Toolbar', 'ai-plugin-dev'),
            'add_new'               => \__('Add New', 'ai-plugin-dev'),
            'add_new_item'          => \__('Add New AI Plugin', 'ai-plugin-dev'),
            'new_item'              => \__('New AI Plugin', 'ai-plugin-dev'),
            'edit_item'             => \__('Edit AI Plugin', 'ai-plugin-dev'),
            'view_item'             => \__('View AI Plugin', 'ai-plugin-dev'),
            'all_items'             => \__('All AI Plugins', 'ai-plugin-dev'),
            'search_items'          => \__('Search AI Plugins', 'ai-plugin-dev'),
            'parent_item_colon'     => \__('Parent AI Plugins:', 'ai-plugin-dev'),
            'not_found'             => \__('No AI plugins found.', 'ai-plugin-dev'),
            'not_found_in_trash'    => \__('No AI plugins found in Trash.', 'ai-plugin-dev'),
            'featured_image'        => \_x('AI Plugin Cover Image', 'Overrides the "Featured Image" phrase', 'ai-plugin-dev'),
            'set_featured_image'    => \_x('Set cover image', 'Overrides the "Set featured image" phrase', 'ai-plugin-dev'),
            'remove_featured_image' => \_x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'ai-plugin-dev'),
            'use_featured_image'    => \_x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'ai-plugin-dev'),
            'archives'              => \_x('AI Plugin archives', 'The post type archive label', 'ai-plugin-dev'),
            'insert_into_item'      => \_x('Insert into AI plugin', 'Overrides the "Insert into post" phrase', 'ai-plugin-dev'),
            'uploaded_to_this_item' => \_x('Uploaded to this AI plugin', 'Overrides the "Uploaded to this post" phrase', 'ai-plugin-dev'),
            'filter_items_list'     => \_x('Filter AI plugins list', 'Screen reader text for the filter links', 'ai-plugin-dev'),
            'items_list_navigation' => \_x('AI plugins list navigation', 'Screen reader text for the pagination', 'ai-plugin-dev'),
            'items_list'            => \_x('AI plugins list', 'Screen reader text for the items list', 'ai-plugin-dev'),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'ai-plugin'],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => true,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-admin-plugins',
            'supports'           => ['title', 'editor', 'custom-fields', 'revisions', 'comments', 'page-attributes'],
            'show_in_rest'       => true,
            'rest_base'          => 'ai-plugins',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        ];

        \register_post_type('ai-plugin', $args);
    }

    /**
     * Register taxonomies for the ai-plugin post type
     */
    public function register_taxonomies(): void
    {
        // Register custom hierarchical categories for ai-plugin
        $category_labels = [
            'name'              => _x('AI Plugin Categories', 'taxonomy general name', 'ai-plugin-dev'),
            'singular_name'     => _x('AI Plugin Category', 'taxonomy singular name', 'ai-plugin-dev'),
            'search_items'      => __('Search Categories', 'ai-plugin-dev'),
            'all_items'         => __('All Categories', 'ai-plugin-dev'),
            'parent_item'       => __('Parent Category', 'ai-plugin-dev'),
            'parent_item_colon' => __('Parent Category:', 'ai-plugin-dev'),
            'edit_item'         => __('Edit Category', 'ai-plugin-dev'),
            'update_item'       => __('Update Category', 'ai-plugin-dev'),
            'add_new_item'      => __('Add New Category', 'ai-plugin-dev'),
            'new_item_name'     => __('New Category Name', 'ai-plugin-dev'),
            'menu_name'         => __('Categories', 'ai-plugin-dev'),
        ];

        $category_args = [
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'ai-plugin-category'],
            'show_in_rest'      => true,
            'rest_base'         => 'ai-plugin-categories',
        ];

        \register_taxonomy('ai-plugin-category', ['ai-plugin'], $category_args);

        // Register custom non-hierarchical tags for ai-plugin
        $tag_labels = [
            'name'                       => _x('AI Plugin Tags', 'taxonomy general name', 'ai-plugin-dev'),
            'singular_name'              => _x('AI Plugin Tag', 'taxonomy singular name', 'ai-plugin-dev'),
            'search_items'               => __('Search Tags', 'ai-plugin-dev'),
            'popular_items'              => __('Popular Tags', 'ai-plugin-dev'),
            'all_items'                  => __('All Tags', 'ai-plugin-dev'),
            'edit_item'                  => __('Edit Tag', 'ai-plugin-dev'),
            'update_item'                => __('Update Tag', 'ai-plugin-dev'),
            'add_new_item'               => __('Add New Tag', 'ai-plugin-dev'),
            'new_item_name'              => __('New Tag Name', 'ai-plugin-dev'),
            'separate_items_with_commas' => __('Separate tags with commas', 'ai-plugin-dev'),
            'add_or_remove_items'        => __('Add or remove tags', 'ai-plugin-dev'),
            'choose_from_most_used'      => __('Choose from the most used tags', 'ai-plugin-dev'),
            'not_found'                  => __('No tags found.', 'ai-plugin-dev'),
            'menu_name'                  => __('Tags', 'ai-plugin-dev'),
        ];

        $tag_args = [
            'hierarchical'      => false,
            'labels'            => $tag_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'ai-plugin-tag'],
            'show_in_rest'      => true,
            'rest_base'         => 'ai-plugin-tags',
        ];

        \register_taxonomy('ai-plugin-tag', ['ai-plugin'], $tag_args);
    }

    /**
     * Customize the columns displayed in the admin list table
     *
     * @param array $columns The default columns
     * @return array The customized columns
     */
    public function customize_columns(array $columns): array
    {
        // Remove unwanted columns
        unset($columns['comments']);
        unset($columns['date']);
        
        // Reorder columns to show: Title, Parent, Author, Categories, Tags
        $new_columns = [];
        
        // Keep checkbox and title
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        if (isset($columns['title'])) {
            $new_columns['title'] = $columns['title'];
        }
        
        // Add parent column for hierarchy
        $new_columns['parent'] = __('Parent', 'ai-plugin-dev');
        
        // Add author column
        $new_columns['author'] = __('Author', 'ai-plugin-dev');
        
        // Add custom taxonomy columns
        $new_columns['taxonomy-ai-plugin-category'] = __('Categories', 'ai-plugin-dev');
        $new_columns['taxonomy-ai-plugin-tag'] = __('Tags', 'ai-plugin-dev');
        
        return $new_columns;
    }

    /**
     * Populate custom column content
     *
     * @param string $column The column name
     * @param int $post_id The post ID
     */
    public function populate_custom_columns(string $column, int $post_id): void
    {
        switch ($column) {
            case 'parent':
                $parent_id = \wp_get_post_parent_id($post_id);
                if ($parent_id) {
                    $parent_title = \get_the_title($parent_id);
                    $parent_link = \get_edit_post_link($parent_id);
                    if ($parent_link) {
                        echo '<a href="' . \esc_url($parent_link) . '">' . \esc_html($parent_title) . '</a>';
                    } else {
                        echo \esc_html($parent_title);
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'author':
                $author_id = \get_post_field('post_author', $post_id);
                $author = \get_userdata($author_id);
                if ($author) {
                    echo \esc_html($author->display_name);
                }
                break;
        }
    }
}
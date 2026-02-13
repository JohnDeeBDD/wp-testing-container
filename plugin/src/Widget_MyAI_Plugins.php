<?php

namespace AIPluginDev;

class Widget_MyAI_Plugins extends \WP_Widget {

    public function __construct() {
        parent::__construct(
            'my_ai_plugins_widget', // Base ID
            'My AI Plugins', // Name
            array(
                'description' => 'Display a list of the user\'s AI plugins',
                'classname' => 'my-ai-plugins-widget'
            )
        );
    }

    /**
     * Register the widget
     */
    public static function register_widget() {
        \register_widget(__CLASS__);
    }

    /**
     * Front-end display of widget
     */
    public function widget($args, $instance) {
        // Check if user is logged in - if not, render nothing
        if (!\is_user_logged_in()) {
            return;
        }

        echo $args['before_widget'];
        
        // Display title if set in widget settings
        if (!empty($instance['title'])) {
            echo $args['before_title'] . \apply_filters('widget_title', $instance['title']) . $args['after_title'];
        } else {
            // Default title
            echo $args['before_title'] . 'Your A.I. Plugins' . $args['after_title'];
        }
        
        // Display the plugin list
        echo $this->render_plugin_list();
        
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Your A.I. Plugins';
        ?>
        <p>
            <label for="<?php echo \esc_attr($this->get_field_id('title')); ?>"><?php \_e('Title:', 'ai-plugin-dev'); ?></label>
            <input class="widefat" id="<?php echo \esc_attr($this->get_field_id('title')); ?>" name="<?php echo \esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo \esc_attr($title); ?>">
        </p>
        <p>
            <em><?php \_e('This widget displays a list of the current user\'s AI plugins. Only visible to logged-in users.', 'ai-plugin-dev'); ?></em>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? \sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }

    /**
     * Render the plugin list
     */
    private function render_plugin_list() {
        // Get current user ID
        $current_user_id = \get_current_user_id();
        
        // Query for ai-plugin posts by current user
        $query_args = array(
            'post_type' => 'ai-plugin',
            'author' => $current_user_id,
            'posts_per_page' => 50,
            'post_status' => array('publish', 'private', 'draft'),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $ai_plugins = \get_posts($query_args);
        
        // If no plugins found, show the message
        if (empty($ai_plugins)) {
            return '<p>Click \'New AI Plugin\' to begin.</p>';
        }
        
        // Build the unordered list
        $html = '<ul>';
        //var_dump($ai_plugins);die();
        foreach ($ai_plugins as $plugin) {
            //$permalink = \get_permalink($plugin->ID);
            $title = !empty($plugin->post_title) ? $plugin->post_title : 'Untitled Plugin';
            if(\get_post_type($plugin->ID) === 'ai-plugin') {
                $html .= '<li><a href="/?p=' . $plugin->ID . '">' . \esc_html($title) . '</a></li>';
            } 
            //$html .= '<li><a href="' . \esc_url($permalink) . '">' . \esc_html($title) . '</a></li>';
        }
        $html .= '</ul>';
        
        return $html;
    }
}
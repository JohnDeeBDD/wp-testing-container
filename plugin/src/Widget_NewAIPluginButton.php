<?php
/**
 * Widget for creating new AI plugins
 * 
 * This widget displays a button that allows logged-in users to create new AI plugins.
 * It handles the entire plugin creation workflow including:
 * - Rendering the button UI
 * - Creating WordPress posts
 * - Setting up plugin directories and files
 * - Generating initial plugin packages
 * 
 * @package AIPluginDev
 */

namespace AIPluginDev;

class Widget_NewAIPluginButton extends \WP_Widget {

    // ============================================================================
    // WIDGET INITIALIZATION
    // ============================================================================

    /**
     * Constructor - Initialize the widget
     */
    public function __construct() {
        parent::__construct(
            'new_ai_plugin_button_widget',
            'New AI Plugin Button',
            [
                'description' => 'A button widget to create new AI plugins',
                'classname' => 'new-ai-plugin-button-widget'
            ]
        );
        
        $this->init_hooks();
    }

    /**
     * Register the widget with WordPress
     */
    public static function register_widget() {
        \register_widget(__CLASS__);
    }

    /**
     * Initialize WordPress hooks for styles and form handling
     */
    private function init_hooks() {
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_button_styles']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_button_styles']);
        
        if (isset($_POST['new_ai_plugin_action']) && $_POST['new_ai_plugin_action'] === 'create') {
            \add_action('init', [$this, 'doNewAIPluginButtonPress']);
        }
    }

    /**
     * Enqueue CSS styles for the button
     */
    public function enqueue_button_styles() {
        $css_path = \plugin_dir_path(__FILE__) . 'new-ai-plugin-button.css';
        $css_url = \plugin_dir_url(__FILE__) . 'new-ai-plugin-button.css';
        
        if (\file_exists($css_path)) {
            \wp_enqueue_style(
                'new-ai-plugin-button-styles',
                $css_url,
                [],
                (string) (\filemtime($css_path) ?: '1'),
                'all'
            );
        }
    }

    // ============================================================================
    // WIDGET INTERFACE (WordPress Widget API)
    // ============================================================================

    /**
     * Front-end display of widget
     * 
     * @param array $args Widget arguments
     * @param array $instance Saved values from database
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . \apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        echo $this->render_button();
        
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form in WordPress admin
     * 
     * @param array $instance Previously saved values from database
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo \esc_attr($this->get_field_id('title')); ?>">
                <?php \_e('Title:', 'ai-plugin-dev'); ?>
            </label>
            <input 
                class="widefat" 
                id="<?php echo \esc_attr($this->get_field_id('title')); ?>" 
                name="<?php echo \esc_attr($this->get_field_name('title')); ?>" 
                type="text" 
                value="<?php echo \esc_attr($title); ?>"
            >
        </p>
        <p>
            <em><?php \_e('This widget displays a button to create new AI plugins. Users must be logged in to create plugins.', 'ai-plugin-dev'); ?></em>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved
     * 
     * @param array $new_instance Values just sent to be saved
     * @param array $old_instance Previously saved values from database
     * @return array Updated safe values to be saved
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? \sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }

    // ============================================================================
    // BUTTON RENDERING
    // ============================================================================

    /**
     * Render the "New AI Plugin" button
     * 
     * For logged-out users: Shows a button that redirects to login
     * For logged-in users: Shows a form with nonce security to create a plugin
     * 
     * @return string HTML for the button form
     */
    private function render_button() {
        if (!\is_user_logged_in()) {
            return $this->render_login_button();
        }
        
        return $this->render_create_button();
    }

    /**
     * Render button for non-logged-in users (redirects to login)
     * 
     * @return string HTML for login redirect button
     */
    private function render_login_button() {
        $current_url = $this->get_current_url();
        $login_url = \wp_login_url($current_url);
        
        $html = '<form method="get" action="' . \esc_url($login_url) . '" style="display: inline-block;">';
        $html .= '<button type="submit" id="ai-plugin-dev-new-plugin-button" class="button button-primary">New AI Plugin</button>';
        $html .= '</form>';
        
        return $html;
    }

    /**
     * Render button for logged-in users (creates plugin)
     * 
     * @return string HTML for plugin creation button
     */
    private function render_create_button() {
        $html = '<form method="post" style="display: inline-block;">';
        $html .= \wp_nonce_field('new_ai_plugin_nonce', 'new_ai_plugin_nonce_field', true, false);
        $html .= '<input type="hidden" name="new_ai_plugin_action" value="create" />';
        $html .= '<button type="submit" id="ai-plugin-dev-new-plugin-button" class="button button-primary">New AI Plugin</button>';
        $html .= '</form>';
        
        return $html;
    }

    /**
     * Get the current page URL for redirect purposes
     * 
     * @return string Current page URL
     */
    private function get_current_url() {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    // ============================================================================
    // PLUGIN CREATION WORKFLOW
    // ============================================================================

    /**
     * Handle the button press to create a new AI plugin
     * 
     * This is the main entry point for plugin creation. It:
     * 1. Validates user authentication
     * 2. Verifies security nonce
     * 3. Creates WordPress post
     * 4. Sets up meta fields
     * 5. Creates production files
     * 6. Redirects to the new plugin
     */
    public function doNewAIPluginButtonPress() {
        // Validate user is logged in
        if (!\is_user_logged_in()) {
            $this->redirect_to_login();
            return;
        }
        
        // Verify security nonce
        if (!$this->verify_nonce()) {
            \wp_die('Security check failed');
        }
        
        // Create the plugin post
        $post_id = $this->create_plugin_post();
        if (!$post_id) {
            return;
        }
        
        // Set up plugin meta fields
        $this->setup_plugin_meta($post_id);
        
        // Create production files and redirect
        $this->finalize_plugin_creation($post_id);
    }

    /**
     * Redirect non-logged-in users to login page
     */
    private function redirect_to_login() {
        $current_url = $this->get_current_url();
        $login_url = \wp_login_url($current_url);
        \wp_redirect($login_url);
        exit;
    }

    /**
     * Verify the security nonce
     * 
     * @return bool True if nonce is valid
     */
    private function verify_nonce() {
        return isset($_POST['new_ai_plugin_nonce_field']) &&
               \wp_verify_nonce($_POST['new_ai_plugin_nonce_field'], 'new_ai_plugin_nonce');
    }

    /**
     * Create the WordPress post for the new AI plugin
     * 
     * @return int|false Post ID on success, false on failure
     */
    private function create_plugin_post() {
        $random_title = substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
        
        $post_id = \wp_insert_post([
            'post_title'   => $random_title,
            'post_type'    => 'ai-plugin',
            'post_status'  => 'private',
            'post_content' => 'This is your AI Plugin.'
        ]);
        
        if (is_wp_error($post_id)) {
            error_log("Error creating AI Plugin post: " . $post_id->get_error_message());
            \wp_die('Error creating AI Plugin post. Please try again.');
            return false;
        }
        
        return $post_id;
    }

    /**
     * Set up meta fields for the new plugin post
     * 
     * @param int $post_id The post ID
     */
    private function setup_plugin_meta($post_id) {
        \update_post_meta($post_id, "_cacbot_conversation", "1");
        \update_post_meta($post_id, "_cacbot_anchor_post", "1");
        \update_post_meta($post_id, "_cacbot_action_enabled_build_plugin", "1");
        \update_post_meta($post_id, "_cacbot_system_instructions", SystemInstructions::get_instructions());
        \update_post_meta($post_id, "ai-plugin-description", "This is a new AI Plugin .");
        \update_post_meta($post_id, "_cacbot_comment_form_placeholder_text", 
            "Enter a question and click the arrow to chat with the Developer. Enter an instruction and click 'Action', to make the Developer do something. Click 'New' to clear the chat.");
    }

    /**
     * Finalize plugin creation and redirect to the new plugin
     * 
     * @param int $post_id The post ID
     */
    private function finalize_plugin_creation($post_id) {
        $post_url = \get_permalink($post_id);
        
        if (!$post_url) {
            error_log("Error getting permalink for post ID: " . $post_id);
            \wp_die('Post created successfully but could not redirect. Please check your posts.');
            return;
        }
        
        $current_user = wp_get_current_user();
        $display_name = $current_user->display_name;
        
        self::do_create_production_files($post_id, $display_name);
        
        \wp_redirect($post_url);
        exit;
    }

    // ============================================================================
    // PRODUCTION FILE CREATION
    // ============================================================================

    /**
     * Create production files for the new plugin
     * 
     * This creates the plugin directory structure, copies template files,
     * and sets up the initial plugin package.
     * 
     * @param int $post_id The post ID
     * @param string $display_name The user's display name
     */
    public static function do_create_production_files($post_id, $display_name) {
        $ai_plugins_directory = Directories::get_aiplugins_directory();
        $plugin_slug = "aiplugin" . $post_id;
        $directory = $ai_plugins_directory . $plugin_slug;

        // Create plugin directory
        self::create_plugin_directory($directory);
        
        // Set directory permissions
        self::set_directory_group($directory);
        
        // Copy template files
        self::copy_template_files($directory);
        
        // Configure plugin files with actual values
        self::configure_plugin_files($directory, $post_id, $display_name);
        
        // Create initial version zip
        self::do_make_and_move_version_1_zip($post_id);
    }

    /**
     * Create the plugin directory if it doesn't exist
     * 
     * @param string $directory Directory path
     */
    private static function create_plugin_directory($directory) {
        if (is_dir($directory)) {
            error_log("Directory already exists: $directory");
            return;
        }
        
        if (!mkdir($directory, 0775, true)) {
            $error_msg = 'Failed to create directory: ' . $directory;
            $error_msg .= ' - Error: ' . error_get_last()['message'];
            error_log($error_msg);
            wp_die($error_msg);
        }
        
        error_log("Directory created successfully: $directory");
    }

    /**
     * Set the directory group permissions
     *
     * @param string $directory Directory path
     */
    private static function set_directory_group($directory) {
        $group = 'ai-builds';
        exec("chgrp $group " . escapeshellarg($directory), $output, $return_var);
        
        if ($return_var === 0) {
            error_log("Group successfully changed to $group for: $directory");
        } else {
            error_log("Failed to change group for: $directory");
        }
    }

    /**
     * Set the file ownership to ubuntu user
     *
     * @param string $path File path
     */
    private static function set_ubuntu_ownership($path) {
        $user = 'ubuntu';
        exec("sudo chown $user " . escapeshellarg($path), $output, $return_var);
        
        if ($return_var === 0) {
            error_log("Ownership successfully changed to $user for: $path");
        } else {
            error_log("Failed to change ownership to $user for: $path. Output: " . implode("\n", $output));
        }
    }

    /**
     * Copy template files to the new plugin directory
     * 
     * @param string $directory Destination directory
     */
    private static function copy_template_files($directory) {
        $template_directory = plugin_dir_path(__FILE__) . 'template';
        $source = realpath($template_directory);
        $destination = realpath($directory);
        
        self::recurse_copy($source, $destination);
    }

    /**
     * Configure plugin files by replacing placeholders with actual values
     * 
     * @param string $directory Plugin directory
     * @param int $post_id Post ID
     * @param string $display_name User's display name
     */
    private static function configure_plugin_files($directory, $post_id, $display_name) {
        $plugin_slug = 'aiplugin' . $post_id;
        
        $placeholders = [
            '{ai_plugin_title}' => 'AI Plugin ' . $post_id,
            '{ai_plugin_slug}' => $plugin_slug,
            '{ai_plugin_author}' => $display_name,
        ];
        
        // Update index.php with placeholders
        self::update_index_file($directory, $post_id, $placeholders);
        
        // Update info.json with plugin details
        self::update_info_json($directory, $post_id, $display_name, $plugin_slug);
    }

    /**
     * Update the index.php file with actual values and rename it
     * 
     * @param string $directory Plugin directory
     * @param int $post_id Post ID
     * @param array $placeholders Placeholder replacements
     */
    private static function update_index_file($directory, $post_id, $placeholders) {
        $index_file = $directory . '/index.php';
        
        if (!file_exists($index_file)) {
            error_log("Index file does not exist: $index_file");
            return;
        }
        
        // Replace placeholders
        $content = file_get_contents($index_file);
        $new_content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
        file_put_contents($index_file, $new_content);
        error_log("Replaced placeholders in file: $index_file");
        
        // Set ownership for the updated file
        self::set_ubuntu_ownership($index_file);
        
        // Rename to plugin-specific name
        $new_index_file = $directory . '/aiplugin' . $post_id . '.php';
        if (rename($index_file, $new_index_file)) {
            error_log("Renamed index.php to aiplugin" . $post_id . ".php successfully.");
            // Set ownership for the renamed file
            self::set_ubuntu_ownership($new_index_file);
        } else {
            error_log("Failed to rename index.php to aiplugin" . $post_id . ".php.");
        }
    }

    /**
     * Update the info.json file with plugin details and rename it
     * 
     * @param string $directory Plugin directory
     * @param int $post_id Post ID
     * @param string $display_name User's display name
     * @param string $plugin_slug Plugin slug
     */
    private static function update_info_json($directory, $post_id, $display_name, $plugin_slug) {
        $info_file = $directory . '/info.json';
        
        if (!file_exists($info_file)) {
            error_log("Info.json file does not exist: $info_file");
            return;
        }
        
        // Load and update JSON
        $json_content = file_get_contents($info_file);
        $info_data = json_decode($json_content, true);
        
        if ($info_data === null) {
            error_log("Failed to decode JSON from info.json file for post ID: $post_id");
            return;
        }
        
        // Update values
        $info_data['name'] = 'AI Plugin ' . $post_id;
        $info_data['slug'] = $plugin_slug;
        $info_data['author'] = $display_name;
        $info_data['download_url'] = 'https://aiplugin.dev/wp-content/aiplugins/' . $plugin_slug . '.zip';
        $info_data['last_updated'] = date('Y-m-d H:i:s');
        
        // Save updated JSON
        $updated_json = json_encode($info_data, JSON_PRETTY_PRINT);
        if (file_put_contents($info_file, $updated_json) !== false) {
            error_log("Updated info.json file successfully for post ID: $post_id");
            // Set ownership for the updated file
            self::set_ubuntu_ownership($info_file);
        } else {
            error_log("Failed to update info.json file for post ID: $post_id");
        }
        
        // Rename to plugin-specific name
        $new_info_file = $directory . '/' . $plugin_slug . '_details.json';
        if (rename($info_file, $new_info_file)) {
            error_log("Renamed info.json to " . $plugin_slug . "_details.json successfully.");
            // Set ownership for the renamed file
            self::set_ubuntu_ownership($new_info_file);
        } else {
            error_log("Failed to rename info.json to " . $plugin_slug . "_details.json.");
        }
    }

    // ============================================================================
    // ZIP PACKAGE CREATION
    // ============================================================================

    /**
     * Create version 1 zip package for the plugin
     *
     * @param int $post_id Post ID
     */
    private static function do_make_and_move_version_1_zip($post_id) {
        $plugin_slug = "aiplugin" . $post_id;
        $ai_plugins_directory = Directories::get_aiplugins_directory();
        
        // Create nested directory structure for zip
        $zip_source_dir = $ai_plugins_directory . $plugin_slug . "/" . $plugin_slug;
        self::create_zip_directory_structure($zip_source_dir);
        
        // Copy files into zip structure
        self::copy_files_for_zip($ai_plugins_directory, $plugin_slug, $zip_source_dir);
        
        // Create the zip file
        $zip_success = self::create_zip_file($ai_plugins_directory, $plugin_slug);
        
        // Copy details JSON to root
        self::copy_details_json($ai_plugins_directory, $plugin_slug);
        
        // Clean up main plugin directory
        $main_plugin_dir = $ai_plugins_directory . $plugin_slug;
        self::cleanup_temp_build_directory($main_plugin_dir);
        
        // Set ownership for final files (after cleanup)
        $zip_file_path = $ai_plugins_directory . $plugin_slug . ".zip";
        $details_file_path = $ai_plugins_directory . $plugin_slug . "_details.json";
        self::set_ubuntu_ownership($zip_file_path);
        self::set_ubuntu_ownership($details_file_path);
    }

    /**
     * Create directory structure for zip package
     *
     * @param string $zip_source_dir Base directory for zip contents
     */
    private static function create_zip_directory_structure($zip_source_dir) {
        mkdir($zip_source_dir, 0775, true);
        mkdir($zip_source_dir . "/src", 0775, true);
        mkdir($zip_source_dir . "/plugin-update-checker", 0775, true);
        
        // Set group ownership for all created directories
        self::set_directory_group($zip_source_dir);
        self::set_directory_group($zip_source_dir . "/src");
        self::set_directory_group($zip_source_dir . "/plugin-update-checker");
    }

    /**
     * Copy necessary files into the zip directory structure
     *
     * @param string $ai_plugins_directory Base plugins directory
     * @param string $plugin_slug Plugin slug
     * @param string $zip_source_dir Zip source directory
     */
    private static function copy_files_for_zip($ai_plugins_directory, $plugin_slug, $zip_source_dir) {
        // Copy main plugin file
        $source_php_file = $ai_plugins_directory . $plugin_slug . "/" . $plugin_slug . ".php";
        $dest_php_file = $zip_source_dir . "/" . $plugin_slug . ".php";
        
        if (file_exists($source_php_file)) {
            copy($source_php_file, $dest_php_file);
            self::set_ubuntu_ownership($dest_php_file);
        } else {
            error_log("Source PHP file not found: " . $source_php_file);
        }
        
        // Copy plugin-update-checker directory
        $source_puc_dir = $ai_plugins_directory . $plugin_slug . "/plugin-update-checker";
        $dest_puc_dir = $zip_source_dir . "/plugin-update-checker";
        
        if (is_dir($source_puc_dir)) {
            self::recurse_copy($source_puc_dir, $dest_puc_dir);
            // Set ownership recursively for all copied files
            self::set_ubuntu_ownership_recursive($dest_puc_dir);
        } else {
            error_log("Plugin-update-checker directory not found: " . $source_puc_dir);
        }
    }

    /**
     * Create the zip file using shell command
     *
     * @param string $ai_plugins_directory Base plugins directory
     * @param string $plugin_slug Plugin slug
     * @return bool True on success, false on failure
     */
    private static function create_zip_file($ai_plugins_directory, $plugin_slug) {
        $zip_file_path = $ai_plugins_directory . $plugin_slug . ".zip";
        $zip_command = "cd " . $ai_plugins_directory . $plugin_slug . " && zip -r ../" . $plugin_slug . ".zip " . $plugin_slug;
        
        error_log("DEBUG: About to execute zip command: " . $zip_command);
        exec($zip_command, $output, $return_var);
        error_log("DEBUG: Zip command return code: " . $return_var . ", Output: " . implode("\n", $output));
        
        if ($return_var !== 0) {
            error_log("Zip creation failed for " . $plugin_slug . ". Command: " . $zip_command . ". Output: " . implode("\n", $output));
            return false;
        } else {
            error_log("Zip created successfully: " . $zip_file_path);
            
            // Set group ownership for the zip file
            $group = 'ai-builds';
            exec("chgrp $group " . escapeshellarg($zip_file_path), $chgrp_output, $chgrp_return);
            
            if ($chgrp_return === 0) {
                error_log("Group successfully changed to $group for zip file: $zip_file_path");
            } else {
                error_log("Failed to change group for zip file: $zip_file_path");
            }
            
            return true;
        }
    }

    /**
     * Copy the details JSON file to the root plugins directory
     *
     * @param string $ai_plugins_directory Base plugins directory
     * @param string $plugin_slug Plugin slug
     */
    private static function copy_details_json($ai_plugins_directory, $plugin_slug) {
        $source_details_file = $ai_plugins_directory . $plugin_slug . "/" . $plugin_slug . "_details.json";
        $dest_details_file = $ai_plugins_directory . $plugin_slug . "_details.json";
        
        if (!file_exists($source_details_file)) {
            error_log("Details JSON file not found: " . $source_details_file);
            return;
        }
        
        if (copy($source_details_file, $dest_details_file)) {
            error_log("Details JSON file copied successfully: " . $source_details_file . " to " . $dest_details_file);
            
            // Set group ownership for the details JSON file
            $group = 'ai-builds';
            exec("chgrp $group " . escapeshellarg($dest_details_file), $chgrp_output, $chgrp_return);
            
            if ($chgrp_return === 0) {
                error_log("Group successfully changed to $group for details JSON: $dest_details_file");
            } else {
                error_log("Failed to change group for details JSON: $dest_details_file");
            }
        } else {
            error_log("Failed to copy details JSON file: " . $source_details_file . " to " . $dest_details_file);
        }
    }

    // ============================================================================
    // UTILITY METHODS
    // ============================================================================

    /**
     * Recursively delete a directory and all its contents
     *
     * @param string $dir Directory path to delete
     * @return bool True on success, false on failure
     */
    private static function recurse_delete($dir) {
        if (!file_exists($dir)) {
            error_log("Directory does not exist, nothing to delete: $dir");
            return true;
        }
        
        if (!is_dir($dir)) {
            error_log("Path is not a directory: $dir");
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                self::recurse_delete($path);
            } else {
                if (!unlink($path)) {
                    error_log("Failed to delete file: $path");
                } else {
                    error_log("Deleted file: $path");
                }
            }
        }
        
        if (rmdir($dir)) {
            error_log("Deleted directory: $dir");
            return true;
        } else {
            error_log("Failed to delete directory: $dir");
            return false;
        }
    }

    /**
     * Clean up main plugin directory after zip creation
     *
     * @param string $main_plugin_dir The main plugin directory to remove
     */
    private static function cleanup_temp_build_directory($main_plugin_dir) {
        error_log("Starting cleanup of main plugin directory: $main_plugin_dir");
        
        if (self::recurse_delete($main_plugin_dir)) {
            error_log("Successfully cleaned up main plugin directory: $main_plugin_dir");
        } else {
            error_log("Warning: Failed to fully clean up main plugin directory: $main_plugin_dir");
        }
    }

    /**
     * Recursively copy files and directories
     *
     * @param string $src Source directory
     * @param string $dst Destination directory
     */
    private static function recurse_copy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $src_path = $src . '/' . $file;
            $dst_path = $dst . '/' . $file;
            
            if (is_dir($src_path)) {
                self::recurse_copy($src_path, $dst_path);
            } else {
                if (!copy($src_path, $dst_path)) {
                    error_log("Failed to copy file: " . $src_path . " to " . $dst_path);
                } else {
                    error_log("Copied file: " . $src_path . " to " . $dst_path);
                    // Set ownership for each copied file
                    self::set_ubuntu_ownership($dst_path);
                }
            }
        }
        
        closedir($dir);
    }

    /**
     * Set the file ownership to ubuntu user recursively
     *
     * @param string $path Directory or file path
     */
    private static function set_ubuntu_ownership_recursive($path) {
        $user = 'ubuntu';
        exec("sudo chown -R $user " . escapeshellarg($path), $output, $return_var);
        
        if ($return_var === 0) {
            error_log("Ownership successfully changed recursively to $user for: $path");
        } else {
            error_log("Failed to change ownership recursively to $user for: $path. Output: " . implode("\n", $output));
        }
    }
}

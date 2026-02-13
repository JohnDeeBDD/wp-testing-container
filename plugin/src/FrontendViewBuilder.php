<?php

namespace AIPluginDev;

/**
 * Class FrontendViewBuilder
 * 
 * Responsible for building frontend views and HTML content for AI Plugin development interface.
 * Generates plugin information tables with download functionality.
 * 
 * @package AIPluginDev
 */
class FrontendViewBuilder{


    public static function enable_header(){
        add_filter('the_content', function ($content) {
            if (is_singular('ai-plugin') && in_the_loop() && is_main_query()) {
                global $post;
                $post_id = $post->ID;
                $html = \AIPluginDev\FrontendViewBuilder::get_header_content($post_id);
                return $html . $content;
            }
        return $content;
        });
    }

    /**
     * CSS class names used throughout the component
     */
    private const CSS_CLASSES = [
        'CONTAINER' => 'ai-plugin-info-container',
        'TABLE' => 'ai-plugin-info-table',
        'DOWNLOAD_BTN' => 'ai-plugin-download-btn',
        'DOWNLOAD_STATUS' => 'ai-plugin-download-status',
        'VERSION_UPDATED' => 'version-updated'
    ];

    /**
     * Default values for plugin information
     */
    private const DEFAULTS = [
        'DESCRIPTION' => 'No description available',
        'DOWNLOAD_PATH' => '/wp-content/aiplugins/aiplugin%d.zip'
    ];

    /**
     * HTML element IDs for dynamic content updates
     */
    private const ELEMENT_IDS = [
        'TITLE_DISPLAY' => 'post-title-display',
        'VERSION_DISPLAY' => 'ai-plugin-version-display',
        'DESCRIPTION_DISPLAY' => 'ai-plugin-description-display',
        'ORIGINAL_SPEC_DISPLAY' => 'ai-plugin-original-spec-display'
    ];

    /**
     * Generate complete header content with plugin information table
     *
     * @param int $post_id The WordPress post ID for the plugin
     * @return string Complete HTML content for the plugin header
     */
    public static function get_header_content($post_id)
    {
        $plugin_data = self::get_plugin_data($post_id);
        
        $html = self::build_container_start();
        $html .= self::get_css_styles();
        $html .= self::build_plugin_info_table($plugin_data, $post_id);
        $html .= self::build_container_end();

        return $html;
    }

    /**
     * Extract plugin data from WordPress post and meta fields
     *
     * @param int $post_id The WordPress post ID
     * @return array Plugin data including title, description, and version
     */
    private static function get_plugin_data($post_id)
    {
        $post = get_post($post_id);
        
        $plugin_description = get_post_meta($post_id, 'ai-plugin-description', true);
        if (empty($plugin_description)) {
            $plugin_description = self::DEFAULTS['DESCRIPTION'];
        }

        return [
            'title' => $post->post_title,
            'description' => $plugin_description,
            'version' => Version::get_version($post_id),
            'original_spec' => OriginalSpec::get_spec($post_id)
        ];
    }

    /**
     * Build the opening container div
     *
     * @return string HTML for container start
     */
    private static function build_container_start()
    {
        return '<div class="' . self::CSS_CLASSES['CONTAINER'] . '">';
    }

    /**
     * Build the closing container div
     *
     * @return string HTML for container end
     */
    private static function build_container_end()
    {
        return '</div>';
    }

    /**
     * Generate CSS styles for the plugin information display
     *
     * @return string CSS styles wrapped in <style> tags
     */
    private static function get_css_styles()
    {
        return '<style>
            .' . self::CSS_CLASSES['CONTAINER'] . ' {
                margin: 20px 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            
            .' . self::CSS_CLASSES['TABLE'] . ' {
                width: 100%;
                max-width: 100%;
                border-collapse: collapse;
                background-color: #1a1a1a;
                color: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
                table-layout: fixed;
                box-sizing: border-box;
            }
            
            .' . self::CSS_CLASSES['TABLE'] . ' th,
            .' . self::CSS_CLASSES['TABLE'] . ' td {
                padding: 15px;
                text-align: left;
                border-bottom: 1px solid #333;
            }
            
            .' . self::CSS_CLASSES['TABLE'] . ' th {
                background-color: #2d2d2d;
                font-weight: 600;
                color: #e0e0e0;
            }
            
            .' . self::CSS_CLASSES['TABLE'] . ' tr:last-child td {
                border-bottom: none;
            }
            
            .' . self::CSS_CLASSES['TABLE'] . ' tr:hover {
                background-color: #252525;
            }
            
            .' . self::CSS_CLASSES['DOWNLOAD_BTN'] . ' {
                background-color: #007cba;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                transition: background-color 0.2s ease;
                text-decoration: none;
                display: inline-block;
            }
            
            .' . self::CSS_CLASSES['DOWNLOAD_BTN'] . ':hover {
                background-color: #005a87;
                color: white;
                text-decoration: none;
            }
            
            .' . self::CSS_CLASSES['DOWNLOAD_BTN'] . ':disabled,
            .' . self::CSS_CLASSES['DOWNLOAD_BTN'] . '.disabled {
                background-color: #666;
                cursor: not-allowed;
                opacity: 0.6;
            }
            
            .' . self::CSS_CLASSES['DOWNLOAD_STATUS'] . ' {
                color: #ccc;
                font-style: italic;
                font-size: 13px;
            }
            
            .' . self::CSS_CLASSES['DOWNLOAD_STATUS'] . '.building {
                color: #ffa500;
            }
            
            .' . self::CSS_CLASSES['DOWNLOAD_STATUS'] . '.error {
                color: #ff6b6b;
            }
            
            .' . self::CSS_CLASSES['VERSION_UPDATED'] . ' {
                background-color: #4CAF50 !important;
                color: white !important;
                padding: 2px 6px !important;
                border-radius: 3px !important;
                transition: all 0.3s ease !important;
                animation: versionPulse 2s ease-in-out !important;
            }
            
            @keyframes versionPulse {
                0% { background-color: #4CAF50; }
                50% { background-color: #66BB6A; }
                100% { background-color: transparent; color: inherit; }
            }
        </style>';
    }

    /**
     * Build the complete plugin information table
     *
     * @param array $plugin_data Plugin data array
     * @param int $post_id The WordPress post ID
     * @return string HTML table with plugin information
     */
    private static function build_plugin_info_table($plugin_data, $post_id)
    {
        $html = '<table class="' . self::CSS_CLASSES['TABLE'] . '">';
        $html .= self::build_table_header();
        $html .= self::build_table_body($plugin_data, $post_id);
        $html .= '</table>';
        
        return $html;
    }

    /**
     * Build table header
     *
     * @return string HTML for table header
     */
    private static function build_table_header()
    {
        return '<thead>
            <tr><th colspan="2">AI Plugin Information</th></tr>
        </thead>';
    }

    /**
     * Build table body with all plugin information rows
     *
     * @param array $plugin_data Plugin data array
     * @param int $post_id The WordPress post ID
     * @return string HTML for table body
     */
    private static function build_table_body($plugin_data, $post_id)
    {
        $html = '<tbody>';
        $html .= self::build_plugin_name_row($plugin_data['title']);
        $html .= self::build_plugin_version_row($plugin_data['version']);
        $html .= self::build_original_spec_row($plugin_data['original_spec']);
        $html .= self::build_associated_posts_row($post_id);
        $html .= self::build_download_row($post_id);
        $html .= '</tbody>';
        
        return $html;
    }

    /**
     * Build plugin name table row
     *
     * @param string $plugin_title The plugin title
     * @return string HTML for plugin name row
     */
    private static function build_plugin_name_row($plugin_title)
    {
        return '<tr>
            <td><strong>Plugin Name</strong></td>
            <td><span id="' . self::ELEMENT_IDS['TITLE_DISPLAY'] . '">' . esc_html($plugin_title) . '</span></td>
        </tr>';
    }

    /**
     * Build plugin version table row
     *
     * @param string $plugin_version The plugin version
     * @return string HTML for plugin version row
     */
    private static function build_plugin_version_row($plugin_version)
    {
        return '<tr>
            <td><strong>Version</strong></td>
            <td><span id="' . self::ELEMENT_IDS['VERSION_DISPLAY'] . '" data-field="ai-plugin-version">' . esc_html($plugin_version) . '</span></td>
        </tr>';
    }

    /**
     * Build original specification table row
     *
     * @param string $original_spec The original specification
     * @return string HTML for original spec row
     */
    private static function build_original_spec_row($original_spec)
    {
        return '<tr>
            <td><strong>Original Specification</strong></td>
            <td><span id="' . self::ELEMENT_IDS['ORIGINAL_SPEC_DISPLAY'] . '">' . $original_spec . '</span></td>
        </tr>';
    }

    /**
     * Build associated posts table row
     *
     * @param int $post_id The WordPress post ID
     * @return string HTML for associated posts row
     */
    private static function build_associated_posts_row($post_id)
    {
        $user_id = get_current_user_id();
        $associated_posts = new AssociatedPosts($post_id, $user_id);

        return '<tr>
            <td><strong>Tasks</strong></td>
            <td>' . $associated_posts->get_HTML() . '</td>
        </tr>';
    }

    /**
     * Build download button table row
     *
     * @param int $post_id The WordPress post ID
     * @return string HTML for download row
     */
    private static function build_download_row($post_id)
    {
        return '<tr>
            <td><strong>Download</strong></td>
            <td>' . self::get_download_button_html($post_id) . '</td>
        </tr>';
    }

    /**
     * Generate download button HTML - always shows active download button
     *
     * @param int $post_id The post ID
     * @return string HTML for the download button
     */
    private static function get_download_button_html($post_id)
    {
        $download_url = self::get_download_url($post_id);

        return '<a href="' . esc_url($download_url) . '" class="' . self::CSS_CLASSES['DOWNLOAD_BTN'] . '" target="_blank" rel="noopener">Download Plugin</a>';
    }

    /**
     * Generate download URL from post ID
     *
     * @param int $post_id The post ID
     * @return string Download URL for the plugin zip file
     */
    private static function get_download_url($post_id)
    {
        return sprintf(self::DEFAULTS['DOWNLOAD_PATH'], $post_id);
    }
}
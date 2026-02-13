<?php

namespace AIPluginDev;

/**
 * Class AssociatedPosts
 *
 * Manages and retrieves associated posts for a given anchor post and user.
 *
 * NOTE: This class only works after the WordPress "init" action has been fired.
 *
 * @package AIPluginDev
 * @since 1.0.0
 */
class AssociatedPosts {

    /**
     * The ID of the anchor post.
     *
     * @var int
     */
    private int $anchor_post_id;

    /**
     * The ID of the user.
     *
     * @var int
     */
    private int $user_id;

    /**
     * Constructor for AssociatedPosts.
     *
     * Initializes the class with an anchor post ID and user ID.
     *
     * @param int $anchor_post_id The ID of the anchor post.
     * @param int $user_id        The ID of the user.
     */
    public function __construct(int $anchor_post_id, int $user_id) {
        $this->anchor_post_id = $anchor_post_id;
        $this->user_id = $user_id;
    }

    /**
     * Retrieves associated posts for the anchor post and user.
     *
     * Delegates to the Cacbot\AssociatedLinkedPosts class to fetch
     * the associated posts based on the anchor post ID and user ID.
     *
     * @return mixed The associated posts data returned by Cacbot\AssociatedLinkedPosts::get().
     */
    public function get_associated_posts() {
        return \Cacbot\AssociatedLinkedPosts::get($this->anchor_post_id, $this->user_id);
    }


    public function get_HTML() {
        $associated_posts = $this->get_associated_posts();
        if (empty($associated_posts)) {
            return '<p>No associated posts found.</p>';
        }

        $limit = 5;
        $total = count($associated_posts);
        $needs_expand = $total > $limit;
        $unique_id = 'assoc-posts-' . $this->anchor_post_id;

        $html = '<style>
            .assoc-posts-hidden { display: none; }
            .assoc-posts-expand-link { color: #007cba; cursor: pointer; font-size: 13px; margin-top: 4px; display: inline-block; }
            .assoc-posts-expand-link:hover + .assoc-posts-hidden,
            .assoc-posts-hidden:hover { display: block; }
            .assoc-posts-expand-link:hover { text-decoration: underline; }
        </style>';

        $html .= '<ul style="margin:0; padding-left:18px;">';
        foreach ($associated_posts as $index => $post_id) {
            $post_title = get_the_title($post_id);
            $post_link = get_permalink($post_id);
            if ($needs_expand && $index >= $limit) {
                break;
            }
            $html .= "<li><a href='" . esc_url($post_link) . "'>" . esc_html($post_title) . "</a></li>";
        }
        $html .= '</ul>';

        if ($needs_expand) {
            $remaining = $total - $limit;
            $html .= '<span class="assoc-posts-expand-link" onclick="document.getElementById(\'' . esc_attr($unique_id) . '\').style.display=\'block\'; this.style.display=\'none\';">Click to expand (' . $remaining . ' more)</span>';
            $html .= '<ul id="' . esc_attr($unique_id) . '" class="assoc-posts-hidden" style="margin:0; padding-left:18px;">';
            foreach ($associated_posts as $index => $post_id) {
                if ($index < $limit) {
                    continue;
                }
                $post_title = get_the_title($post_id);
                $post_link = get_permalink($post_id);
                $html .= "<li><a href='" . esc_url($post_link) . "'>" . esc_html($post_title) . "</a></li>";
            }
            $html .= '</ul>';
        }

        return $html;
    }   

}
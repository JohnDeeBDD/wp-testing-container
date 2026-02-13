<?php

namespace AIPluginDev;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class NextBuildAPI{

    // REST API Constants
    const REST_NAMESPACE = 'ai-plugin-dev/v1';
    const REST_ENDPOINT = 'next-build';

    // Post Type Constants
    const POST_TYPE_AI_PLUGIN = 'ai-plugin';

    // Meta Key Constants
    const META_AGENT_STATUS = 'ai-plugin-dev-agent-status';
    const META_AI_PLUGIN_VERSION = 'ai-plugin-version';

    // Status Constants
    const STATUS_BUILDING = 'building';

    // Tag Constants
    const TAG_BUILDING = 'BUILDING';

    // Query Constants
    const SINGLE_POST_LIMIT = 1;

    // Message Constants
    const MSG_NO_BUILDING_POSTS = 'No ai-plugin posts found with status "building"';
    const BUTTON_PRESSED_MARKER = '[ai-plugin-dev-build-plugin-action-button-pressed]';

    public function register_rest_route(): void
    {
        \register_rest_route(
            self::REST_NAMESPACE,
            self::REST_ENDPOINT,
            [
                'methods'             => ['GET', 'POST'],
                'callback'            => [$this, 'handle_request'],
                //'permission_callback' => [$this, 'check_permissions'],
                'permission_callback' => function() { return true; },
            ]
        );
    }

    /**
     * @param \WP_REST_Request<array<string, mixed>> $request
     * @return bool|\WP_Error
     */
    public function check_permissions(\WP_REST_Request $request)
    {
        if (!\current_user_can('edit_posts')) {
            return new WP_Error(
                'rest_forbidden',
                'You do not have permission to access this endpoint.',
                ['status' => 403]
            );
        }

        return true;
    }

    /**
     * @param \WP_REST_Request<array<string, mixed>> $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_request(\WP_REST_Request $request)
    {
        $args = [
            'post_type' => self::POST_TYPE_AI_PLUGIN,
            'post_status' => ['publish', 'private'],
            'posts_per_page' => self::SINGLE_POST_LIMIT,
            'orderby' => 'date',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => self::META_AGENT_STATUS,
                    'value' => self::STATUS_BUILDING,
                    'compare' => '='
                ]
            ]
        ];

        $query = new \WP_Query($args);

        if (!$query->have_posts()) {
            return new WP_REST_Response([
                'post_id' => null,
                'post_author' => null,
                'post_title' => null,
                'version' => null,
                'message' => self::MSG_NO_BUILDING_POSTS
            ], 200);
        }

        $post = $query->posts[0];

        // Get author data with null-safety fallback
        $author = \get_userdata($post->post_author);
        $author_name = $author ? $author->display_name : 'Unknown';
        $author_id = $author ? $author->ID : (int) $post->post_author;

        // Verify Cacbot dependencies are available
        if (!class_exists('\Cacbot\AnchorPost') || !method_exists('\Cacbot\AnchorPost', 'filter_for_linked_post_id')) {
            return new WP_Error(
                'dependency_unavailable',
                'Required dependency Cacbot\AnchorPost is not available.',
                ['status' => 500]
            );
        }

        if (!class_exists('\Cacbot\CommentsAsText') || !method_exists('\Cacbot\CommentsAsText', 'get_comments_as_text')) {
            return new WP_Error(
                'dependency_unavailable',
                'Required dependency Cacbot\CommentsAsText is not available.',
                ['status' => 500]
            );
        }

        try {
            $linked_post_id = \Cacbot\AnchorPost::filter_for_linked_post_id($post->ID, $author_id);
            $conversation_content = $this->get_comments_from_linked_post_as_transcript($linked_post_id);
        } catch (\Throwable $e) {
            return new WP_Error(
                'transcript_generation_failed',
                'Failed to generate conversation transcript: ' . $e->getMessage(),
                ['status' => 500]
            );
        }

        // The version returned is the canonical version the builder should build to
        $version = intval(\get_post_meta($post->ID, self::META_AI_PLUGIN_VERSION, true)) + 1;

        return new WP_REST_Response([
            'post_id' => $post->ID,
            'linked_post_id' => $linked_post_id,
            'post_author' => $author_name,
            'post_author_id' => $author_id,
            'conversation_transcript' => $conversation_content,
            'post_title' => $post->post_title,
            'version' => $version,
        ], 200);
    }

    private function get_comments_from_linked_post_as_transcript(int $linked_post_id): string
    {
        $comments_as_text = \Cacbot\CommentsAsText::get_comments_as_text($linked_post_id);
        return str_replace(self::BUTTON_PRESSED_MARKER, '', $comments_as_text);
    }
}

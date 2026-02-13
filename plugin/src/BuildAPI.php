<?php
namespace AIPluginDev;

use WP_Error;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class BuildAPI
{
    public const NAMESPACE = 'ai-plugin-dev/v1';
    public const ROUTE     = '/build';
    public const CPT       = 'ai-plugin';

    public function register_rest_route(): void
    {
        // Build endpoint
        \register_rest_route(
            self::NAMESPACE,
            self::ROUTE,
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'handle_request'],
                'args'                => $this->args_schema(),
                'permission_callback' => [$this, 'check_permissions'],
            ]
        );
        
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function args_schema(): array
    {
        return [
            'post_id' => [ 
                'description' => 'Existing post ID of an ai-plugin post to build.',
                'type'        => 'integer',
                'required'    => false,
                'validate_callback' => static function ($value): bool {
                    return $value === null || (\is_numeric($value) && (int) $value > 0);
                },
            ]
        ];
    }

    /**
     * Check permissions for the build endpoint
     *
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    /**
     * @param \WP_REST_Request<array<string, mixed>> $request
     * @return bool|\WP_Error
     */
    public function check_permissions(\WP_REST_Request $request)
    {
        // Verify nonce
        $nonce = $request->get_header('X-WP-Nonce');
        if (!\wp_verify_nonce($nonce ?: '', 'wp_rest')) {
            return new WP_Error(
                'rest_cookie_invalid_nonce',
                'Cookie check failed',
                ['status' => 403]
            );
        }

        // Check if user is logged in
        if (!\is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                'You are not currently logged in.',
                ['status' => 401]
            );
        }

        return true;
    }

    /**
     * @param WP_REST_Request<array<string, mixed>> $request
     * @return WP_REST_Response|WP_Error
     */
    /**
     * @param \WP_REST_Request<array<string, mixed>> $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_request(\WP_REST_Request $request)
    {
        $post_id_raw = $request->get_param('post_id');
        
        // Check if post_id is provided
        if (empty($post_id_raw)) {
            return new \WP_Error(
                'missing_post_id',
                'Post ID is required.',
                ['status' => 400]
            );
        }
        
        // Ensure post_id is an integer
        $post_id = is_numeric($post_id_raw) ? (int) $post_id_raw : 0;
        if ($post_id <= 0) {
            return new \WP_Error(
                'invalid_post_id',
                'Invalid post ID provided.',
                ['status' => 400]
            );
        }
        
        // Check if the post exists
        $post = \get_post($post_id);
        if (!$post instanceof \WP_Post) {
            return new \WP_Error(
                'post_not_found',
                'Post not found.',
                ['status' => 404]
            );
        }
        
        // Check if the post is of the correct custom post type
        if ($post->post_type !== self::CPT) {
            return new WP_Error(
                'invalid_post_type',
                'Post must be of type ' . self::CPT . '.',
                ['status' => 400]
            );
        }
        
        // Check if current user can edit this post
        if (!\current_user_can('edit_post', $post_id)) {
            return new WP_Error(
                'insufficient_permissions',
                'You do not have permission to edit this post.',
                ['status' => 403]
            );
        }
       
        \update_post_meta($post_id, "kali_status", time());
    
            try {

                
                return new \WP_REST_Response([
                    'success' => true,
                    'message' => 'Build request processed successfully.',
                    'post_id' => $post_id,
                    'agent' => "kali",
                    'pid' => 666
                ], 200);
            } catch (\RuntimeException $e) {
                return new \WP_Error(
                    'agent_start_failed',
                    'Failed to start agent: ' . $e->getMessage(),
                    ['status' => 500]
                );
            }
    }


}

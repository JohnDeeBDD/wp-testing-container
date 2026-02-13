<?php
/**
 * Task Path Documentation
 *
 * This file documents the complete task execution path from WordPress to local system
 * and back, including the generation of linked_post_id JSON files.
 */

/**
 * TASK EXECUTION FLOW
 * ===================
 *
 * 1. TASK INITIATION (WordPress Production Site)
 *    - Task starts as input on production WordPress site
 *    - Custom post type "ai-plugins" is created with post_id
 *    - DEPRECATED: Building tag is assigned to post
 *    - Post meta fields are set:
 *      * build_started (timestamp)
 *      * agent_status ("building")
 *      * ai-plugin-version (incremented)
 *
 * 2. API RESPONSE GENERATION (NextBuildAPI.php)
 *    - next-build-api calculates next build task
 *    - Cacbot\AnchorPost::filter_for_linked_post_id() creates linked post
 *    - API response emitted with:
 *      * post_id (original post)
 *      * linked_post_id (newly created linked post for comments)
 *      * post_author, post_author_id
 *      * post_content, post_title
 *      * version, build_started, test_mode, command
 *
 * 3. TASK POLLING & EXECUTION (CLI System)
 *    - PHP CLI app (cli.php) polls next-build-api API for pending tasks
 *    - When task is received, PluginCreator.php:
 *      * Creates plugin directory structure
 *      * Generates plugin files
 *      * Creates linked_post_id_{ID}.json file with API response data
 *    - Task is then executed via roo-bridge VSCode extension
 *
 * 4. TASK PROCESSING (Roo-Bridge Extension)
 *    - roo-bridge VSCode extension performs task using Roo-Veterinary AI
 *    - Task output is captured in /captures directory as markdown
 *    - Plugin files are generated/modified
 *
 * 5. POST-BUILD PROCESSING (after-build.php)
 *    - after-build.php is executed automatically after task completion
 *    - Reads existing linked_post_id_{ID}.json file (does NOT create it)
 *    - Performs production build:
 *      * Updates plugin metadata
 *      * Creates zip packages
 *      * Uploads to remote server
 *      * Updates WordPress post content and meta
 *
 * 6. COMPLETION & REPORTING
 *    - Build state transitions to COMPLETED
 *    - Task completion status is tracked
 *    - Comments are posted to linked_post_id (not original post_id)
 *    - VS Code is terminated when all tasks complete
 */

/**
 * LINKED_POST_ID JSON FILE GENERATION
 * ===================================
 *
 * CREATED BY: PluginCreator.php (during step 3)
 * LOCATION: /var/www/html/wp-content/plugins/aiplugin{ID}/linked_post_id_{LINKED_ID}.json
 *
 * STRUCTURE:
 * {
 *   "api_response": {
 *     "post_id": 2397,
 *     "linked_post_id": "2398",
 *     "post_author": "Author Name",
 *     "post_author_id": 123,
 *     "post_content": "...",
 *     "post_title": "...",
 *     "version": 1,
 *     "build_started": 1234567890,
 *     "test_mode": false,
 *     "command": "..."
 *   },
 *   "metadata": {
 *     "created_at": "2024-01-03T10:44:00+00:00",
 *     "api_endpoint": "https://api.example.com/endpoint",
 *     "cli_version": 1,
 *     "plugin_slug": "aiplugin2397",
 *     "filename": "linked_post_id_2398.json",
 *     "linked_post_id": "2398"
 *   }
 * }
 *
 * PURPOSE: Enables comment posting to linked post instead of original post
 * CONSUMED BY: roo-bridge TypeScript LinkedPostIdService and resolver utilities
 */

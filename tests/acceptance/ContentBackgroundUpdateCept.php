<?php

$I = new AcceptanceTester($scenario);

$I->wantTo('Test that post content updates automatically when backend data changes via polling mechanism');

// =============================================================================
// SETUP: Create AI Plugin and Extract Post ID
// =============================================================================

$I->comment("Setting up test environment");

// Navigate to WordPress dashboard and login
$I->amOnUrl("http://localhost");
$I->loginAsAdmin();
$I->amOnPage("/");

// Create new AI Plugin
$I->comment("Creating new AI Plugin");
$I->click("#ai-plugin-dev-new-plugin-button");
$I->waitForElement(".ai-plugin-info-container");
$I->see("AI Plugin Information");

// Extract post ID from current page
$post_id = $I->executeJS("
    var postId = null;
    
    // Method 1: URL pattern matching
    var url = window.location.href;
    var urlMatches = url.match(/postid-(\d+)/);
    if (urlMatches) {
        postId = urlMatches[1];
    }
    
    // Method 2: WordPress admin bar edit link
    if (!postId) {
        var editLink = document.querySelector('#wp-admin-bar-edit a');
        if (editLink) {
            var editMatches = editLink.href.match(/post=(\d+)/);
            if (editMatches) {
                postId = editMatches[1];
            }
        }
    }
    
    // Method 3: Body class inspection
    if (!postId) {
        var bodyMatches = document.body.className.match(/postid-(\d+)/);
        if (bodyMatches) {
            postId = bodyMatches[1];
        }
    }
    
    return postId;
");

$I->assertNotEmpty($post_id, "Post ID must be found to continue test");
$I->comment("Using Post ID: {$post_id}");

// =============================================================================
// BASELINE: Capture initial content state
// =============================================================================

$I->comment("Capturing initial content state");

$initial_content = $I->executeJS("
    var element = document.querySelector('#post-content-display, .post-content, .ai-plugin-content');
    return element ? element.textContent.trim() : null;
");

$I->comment("Initial content: " . ($initial_content ?: 'Not found'));

// =============================================================================
// CORE TEST: Content Update via REST API and Polling Verification
// =============================================================================

$I->comment("Testing content update via REST API and polling mechanism");

// Configuration
$app_password = "EkF8hcmJRl70ZGdKxjW8Hjgf"; // From acceptance.suite.yml
$polling_wait_time = 7; // Seconds to wait for polling mechanism
$new_content = "UPDATED CONTENT Test at " . date('H:i:s') . " This should appear via polling";

$I->comment("Updating content to: {$new_content}");

// Update content via REST API
$api_response = $I->executeJS("
    return fetch('/wp-json/wp/v2/ai-plugins/{$post_id}', {
        method: 'POST',
        headers: {
            'Authorization': 'Basic ' + btoa('Codeception:{$app_password}'),
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.wpApiSettings ? window.wpApiSettings.nonce : ''
        },
        body: JSON.stringify({ content: '{$new_content}' })
    }).then(response => response.json()).then(data => {
        return { success: true, data: data };
    }).catch(error => {
        return { success: false, error: error.message };
    });
");

$I->assertTrue($api_response['success'], "API update should succeed");
$I->comment("API update completed successfully");

// Wait for polling mechanism to detect the change
$I->comment("Waiting {$polling_wait_time} seconds for polling mechanism to update UI...");
$I->wait($polling_wait_time);

// Verify content was updated in the UI
$updated_content = $I->executeJS("
    var element = document.querySelector('#post-content-display, .post-content, .ai-plugin-content');
    if (!element) {
        throw new Error('Content display element not found');
    }
    
    // Get text content and decode HTML entities
    var content = element.textContent || element.innerText || '';
    content = content.trim();
    
    // Decode common HTML entities
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    return tempDiv.textContent || tempDiv.innerText || content;
");

$I->comment("Expected content: {$new_content}");
$I->comment("Actual content: {$updated_content}");

// CRITICAL ASSERTION: This will fail if polling mechanism is not working
$I->assertEquals(
    $new_content,
    $updated_content,
    "Content should be updated in the UI via polling mechanism. If this fails, the background polling is not working correctly."
);

// =============================================================================
// ADDITIONAL VERIFICATION: Test with different content
// =============================================================================

$I->comment("Testing second content update to ensure polling is consistently working");

$second_content = "SECOND UPDATE Test at " . date('H:i:s') . " Verifying consistent polling";

// Update via API again
$I->executeJS("
    fetch('/wp-json/wp/v2/ai-plugins/{$post_id}', {
        method: 'POST',
        headers: {
            'Authorization': 'Basic ' + btoa('Codeception:{$app_password}'),
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.wpApiSettings ? window.wpApiSettings.nonce : ''
        },
        body: JSON.stringify({ content: '{$second_content}' })
    });
");

// Wait for polling again
$I->wait($polling_wait_time);

// Verify second update
$final_content = $I->executeJS("
    var element = document.querySelector('#post-content-display, .post-content, .ai-plugin-content');
    if (!element) return null;
    
    // Get text content and decode HTML entities
    var content = element.textContent || element.innerText || '';
    content = content.trim();
    
    // Decode common HTML entities
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;
    return tempDiv.textContent || tempDiv.innerText || content;
");

$I->assertEquals(
    $second_content,
    $final_content,
    "Second content update should also work via polling mechanism"
);

$I->comment("✅ Content background update polling mechanism is working correctly!");

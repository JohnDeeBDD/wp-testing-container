<?php

$I = new AcceptanceTester($scenario);

$I->wantTo('Test initial plugin creation and download functionality 🚀');

// =============================================================================
// SETUP: Initial navigation and plugin creation
// =============================================================================

$I->comment("Setting up test environment for plugin creation and download");
$I->amOnUrl("http://localhost");
$I->loginAsAdmin();
$I->amOnPage("/wp-admin/");
$I->amOnPage("/");

$I->makeScreenshot("01-initial-dashboard");
$I->comment("Initial dashboard state 📸 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/01-initial-dashboard.png' target='_blank'>available here</a>");

// =============================================================================
// PLUGIN CREATION: Click New AI Plugin button and create plugin
// =============================================================================

$I->comment("🎯 Creating new AI Plugin by clicking the New AI Plugin button");
$I->expect("The AI Plugin creation button to be clickable and create a new plugin");
$I->click("#ai-plugin-dev-new-plugin-button");
$I->waitForElement(".ai-plugin-info-container");
$I->see("AI Plugin Information");

$I->makeScreenshot("02-plugin-created-and-loaded");
$I->comment("AI Plugin created and loaded 🎯 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/02-plugin-created-and-loaded.png' target='_blank'>available here</a>");

// =============================================================================
// POST ID EXTRACTION: Get the post ID for API calls
// =============================================================================

$I->comment("🔍 Extracting post_id from current page using JavaScript");
$post_id = $I->executeJS("
    // Try multiple methods to get the post ID
    var postId = null;
    
    // Method 1: Check URL for post ID pattern
    var url = window.location.href;
    var matches = url.match(/postid-(\d+)/);
    if (matches) {
        postId = matches[1];
    }
    
    // Method 2: Check for WordPress admin bar edit link
    if (!postId) {
        var editLink = document.querySelector('#wp-admin-bar-edit a');
        if (editLink) {
            var href = editLink.href;
            var editMatches = href.match(/post=(\d+)/);
            if (editMatches) {
                postId = editMatches[1];
            }
        }
    }
    
    // Method 3: Check body class for postid
    if (!postId) {
        var bodyClass = document.body.className;
        var bodyMatches = bodyClass.match(/postid-(\d+)/);
        if (bodyMatches) {
            postId = bodyMatches[1];
        }
    }
    
    return postId;
");

$I->expect("Post ID to be extracted successfully");
$I->assertNotEmpty($post_id, "Post ID should be found for the created plugin");

$I->comment("✅ AI Plugin CPT created successfully!");
$I->comment("📋 Post ID: {$post_id}");

// =============================================================================
// INITIAL STATE: Capture initial plugin state
// =============================================================================

$I->comment("📊 Capturing initial plugin state before build process");

$initial_title = $I->executeJS("
    var titleElement = document.querySelector('#post-title-display');
    return titleElement ? titleElement.textContent.trim() : null;
");

$initial_version = $I->executeJS("
    var versionElement = document.querySelector('#ai-plugin-version-display');
    return versionElement ? versionElement.textContent.trim() : null;
");

$I->comment("📋 Initial Plugin Values:");
$I->comment("   Title: {$initial_title}");
$I->comment("   Version: {$initial_version}");

$I->makeScreenshot("03-initial-plugin-state");
$I->comment("Initial plugin state captured 📊 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/03-initial-plugin-state.png' target='_blank'>screenshot available here</a>");

// =============================================================================
// DOWNLOAD TEST: Attempt to download the plugin
// =============================================================================

$I->comment("📥 Testing plugin download functionality");

// Check if download button exists
$I->expect("Download button should be present after plugin creation");
$I->seeElement(".ai-plugin-download-btn");

// Get download button details
$download_info = $I->executeJS("
    var downloadBtn = document.querySelector('.ai-plugin-download-btn');
    if (downloadBtn) {
        return {
            exists: true,
            href: downloadBtn.href,
            text: downloadBtn.textContent.trim(),
            disabled: downloadBtn.disabled || downloadBtn.classList.contains('disabled')
        };
    }
    return { exists: false };
");

$I->comment("Download button info: " . json_encode($download_info));

$I->expect("Download button should have proper attributes");
$I->assertTrue($download_info['exists'], "Download button should exist");
$I->assertEquals("Download Plugin", $download_info['text'], "Download button should have correct text");
$I->assertNotEmpty($download_info['href'], "Download button should have a valid href");

$I->makeScreenshot("06-download-button-ready");
$I->comment("Download button ready 📥 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/06-download-button-ready.png' target='_blank'>screenshot available here</a>");

// =============================================================================
// DOWNLOAD ATTEMPT: Click download button and verify
// =============================================================================

$I->comment("🎯 Attempting to download the plugin");

// Click the download button
$I->click(".ai-plugin-download-btn");

// Wait a moment for download to initiate
$I->wait(3);

$I->makeScreenshot("07-after-download-click");
$I->comment("After download button click 🎯 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/07-after-download-click.png' target='_blank'>screenshot available here</a>");

// =============================================================================
// DOWNLOAD VERIFICATION: Check if download was successful
// =============================================================================

$I->comment("🔍 Verifying download functionality");

// Check if we can access the download URL directly
$download_url_test = $I->executeJS("
    var downloadBtn = document.querySelector('.ai-plugin-download-btn');
    if (downloadBtn && downloadBtn.href) {
        // Try to fetch the download URL to see if it's accessible
        return fetch(downloadBtn.href, { method: 'HEAD' })
            .then(response => {
                return {
                    success: true,
                    status: response.status,
                    headers: {
                        'content-type': response.headers.get('content-type'),
                        'content-disposition': response.headers.get('content-disposition')
                    }
                };
            })
            .catch(error => {
                return { success: false, error: error.message };
            });
    }
    return { success: false, error: 'No download URL found' };
");

$I->comment("Download URL test result: " . json_encode($download_url_test));

// =============================================================================
// FINAL VERIFICATION: Test results and assertions
// =============================================================================

$I->comment("📋 Final verification of plugin creation and download test");

// Verify that the plugin was created successfully
$I->expect("Plugin should be created with valid post ID");
$I->assertNotEmpty($post_id, "Post ID should be valid");

// Verify that download button exists and is functional
$I->expect("Download functionality should be available");
$I->assertTrue($download_info['exists'], "Download button should exist");
$I->assertFalse($download_info['disabled'], "Download button should not be disabled");

// Check if download URL is accessible (this may fail if zip generation is not working)
if ($download_url_test['success']) {
    $I->comment("✅ Download URL is accessible");
    $I->assertTrue($download_url_test['success'], "Download URL should be accessible");
    
    // Check for appropriate content type (should be zip file)
    if (isset($download_url_test['headers']['content-type'])) {
        $content_type = $download_url_test['headers']['content-type'];
        $I->comment("Download content type: {$content_type}");
        
        // Verify it's a zip file
        $is_zip = strpos($content_type, 'zip') !== false || strpos($content_type, 'application/octet-stream') !== false;
        $I->assertTrue($is_zip, "Download should be a zip file, got: {$content_type}");
    }
} else {
    $I->comment("❌ Download URL test failed: " . ($download_url_test['error'] ?? 'Unknown error'));
    $I->fail("Download functionality is not working: " . ($download_url_test['error'] ?? 'Unknown error'));
}

$I->makeScreenshot("08-download-verification");
$I->comment("Download verification complete 📋 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/08-download-verification.png' target='_blank'>screenshot available here</a>");
$I->comment("✅ Initial plugin creation and download test completed successfully!");

$I->comment("Now we will test that the version 1 of the plugin has been created correctly.");
$I->amGoingTo("Check the file system for zipped plugin and json file.");
$I->expect("The zipped plugin to exist in the /wp-content/aiplugins directory.");

// =============================================================================
// FILE SYSTEM VERIFICATION: Check for zip and JSON files
// =============================================================================

$I->comment("🔍 Verifying file system for plugin files");

// Define expected file paths
$zip_filename = "aiplugin{$post_id}.zip";
$json_filename = "aiplugin{$post_id}_details.json";
$zip_path = "/var/www/html/wp-content/aiplugins/{$zip_filename}";
$json_path = "/var/www/html/wp-content/aiplugins/{$json_filename}";

$I->comment("Expected files:");
$I->comment("  ZIP: {$zip_path}");
$I->comment("  JSON: {$json_path}");

// Check if ZIP file exists
$I->expect("ZIP file should exist after plugin creation");
$zip_exists = file_exists($zip_path);
$I->assertTrue($zip_exists, "ZIP file should exist at: {$zip_path}");
$I->comment("✅ ZIP file found: {$zip_filename}");

// Check if JSON details file exists
$I->expect("JSON details file should exist after plugin creation");
$json_exists = file_exists($json_path);
$I->assertTrue($json_exists, "JSON details file should exist at: {$json_path}");
$I->comment("✅ JSON details file found: {$json_filename}");

$I->makeScreenshot("09-files-verified");
$I->comment("File system verification complete 🔍 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/09-files-verified.png' target='_blank'>screenshot available here</a>");

// =============================================================================
// JSON CONTENT VALIDATION: Verify JSON structure and content
// =============================================================================

$I->comment("📋 Validating JSON details file content");

// Read and parse JSON file
$json_content = file_get_contents($json_path);
$I->assertNotEmpty($json_content, "JSON file should not be empty");

$plugin_details = json_decode($json_content, true);
$I->assertNotNull($plugin_details, "JSON should be valid and parseable");

$I->comment("JSON content loaded successfully");

// Validate required fields exist
$required_fields = ['name', 'slug', 'author', 'version', 'download_url', 'requires', 'tested', 'requires_php', 'last_updated'];

foreach ($required_fields as $field) {
    $I->assertArrayHasKey($field, $plugin_details, "JSON should contain '{$field}' field");
}

$I->comment("✅ All required JSON fields are present");

// Validate specific field values
$I->expect("Plugin name should not be empty");
$I->assertNotEmpty($plugin_details['name'], "Plugin name should not be empty");

$I->expect("Plugin slug should match expected pattern");
$expected_slug = "aiplugin{$post_id}";
$I->assertEquals($expected_slug, $plugin_details['slug'], "Plugin slug should be '{$expected_slug}'");

$I->expect("Plugin version should be set");
$I->assertNotEmpty($plugin_details['version'], "Plugin version should not be empty");

$I->expect("Download URL should be properly formatted");
$expected_download_url = "https://aiplugin.dev/wp-content/aiplugins/{$zip_filename}";
$I->assertEquals($expected_download_url, $plugin_details['download_url'], "Download URL should match expected format");

$I->expect("WordPress requirements should be set");
$I->assertNotEmpty($plugin_details['requires'], "WordPress requires version should not be empty");
$I->assertNotEmpty($plugin_details['tested'], "WordPress tested version should not be empty");
$I->assertNotEmpty($plugin_details['requires_php'], "PHP requires version should not be empty");

$I->expect("Last updated timestamp should be set");
$I->assertNotEmpty($plugin_details['last_updated'], "Last updated timestamp should not be empty");

$I->comment("📋 JSON field validation results:");
$I->comment("  Name: {$plugin_details['name']}");
$I->comment("  Slug: {$plugin_details['slug']}");
$I->comment("  Author: '{$plugin_details['author']}'");
$I->comment("  Version: {$plugin_details['version']}");
$I->comment("  Download URL: {$plugin_details['download_url']}");
$I->comment("  Requires WP: {$plugin_details['requires']}");
$I->comment("  Tested WP: {$plugin_details['tested']}");
$I->comment("  Requires PHP: {$plugin_details['requires_php']}");
$I->comment("  Last Updated: {$plugin_details['last_updated']}");

// =============================================================================
// AUTHOR FIELD VALIDATION: This should fail if author is missing
// =============================================================================

$I->comment("🚨 Testing author field validation");

$I->expect("Author field should not be empty");
use Codeception\Configuration;
$config = Configuration::config();

$suiteConfig = Configuration::suiteSettings('acceptance', $config);

$author_value = $suiteConfig['modules']['config']['WPWebDriver']['adminUsername'];

$I->assertNotEmpty($plugin_details['author'], "Author field should not be empty, got: '{$plugin_details['author']}'");
$I->assertEquals($author_value, $plugin_details['author'], "Author field should match expected value: '{$author_value}'");
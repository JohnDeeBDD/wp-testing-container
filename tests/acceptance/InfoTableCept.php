<?php

$I = new AcceptanceTester($scenario);

$I->wantTo('Test that UI updates automatically when backend data changes via polling mechanism 🔄');

// 📸 Initial state screenshot
$I->amOnUrl("http://localhost");
$I->loginAsAdmin();
$I->amOnPage("/");
$I->makeScreenshot("01-initial-dashboard");
$I->comment("Initial dashboard state 📸 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/01-initial-dashboard.png' target='_blank'>available here</a>");

// 🎯 Click the AI Plugin creation button (this automatically creates the plugin)
$I->expect("The AI Plugin creation button to be clickable and create a new plugin");
$I->click("#ai-plugin-dev-new-plugin-button");
$I->waitForElement(".ai-plugin-info-container");
$I->see("AI Plugin Information");

$I->makeScreenshot("02-plugin-created-and-loaded");
$I->comment("AI Plugin created and loaded 🎯 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/02-plugin-created-and-loaded.png' target='_blank'>available here</a>");

// 🔍 Extract post_id from current URL using JavaScript
$I->comment("Extracting post_id from current page using JavaScript 🔍");
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
$I->assertNotEmpty($post_id, "Post ID should be found");

$I->comment("✅ AI Plugin CPT created successfully!");
$I->comment("📋 Post ID: {$post_id}");

// 📊 Capture initial UI values before any updates
$I->comment("Capturing initial UI values before backend updates 📊");

$initial_title = $I->executeJS("
    var titleElement = document.querySelector('#post-title-display');
    return titleElement ? titleElement.textContent.trim() : null;
");

$initial_version = $I->executeJS("
    var versionElement = document.querySelector('#ai-plugin-version-display');
    return versionElement ? versionElement.textContent.trim() : null;
");

$I->comment("📋 Initial UI Values:");
$I->comment("   Title: {$initial_title}");
$I->comment("   Version: {$initial_version}");

$I->makeScreenshot("03-initial-ui-values");
$I->comment("Initial UI values captured 📊 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/03-initial-ui-values.png' target='_blank'>screenshot available here</a>");

// 🔄 Test 1: Update Plugin Title via WordPress REST API
$I->comment("🔄 TEST 1: Updating plugin title via WordPress REST API");

$new_title = "Updated Test Plugin Title " . date('H:i:s');
$I->comment("Updating title to: {$new_title}");

// Get application password from config
$app_password = $I->grabFromCurrentUrl(); // This gets current URL, we need the config
$app_password = "EkF8hcmJRl70ZGdKxjW8Hjgf"; // From acceptance.suite.yml

// Update title via cURL using WordPress REST API
$curl_command = "curl -X POST 'http://localhost/wp-json/wp/v2/ai-plugins/{$post_id}' " .
    "-H 'Authorization: Basic " . base64_encode("Codeception:{$app_password}") . "' " .
    "-H 'Content-Type: application/json' " .
    "-d '{\"title\":\"" . addslashes($new_title) . "\"}'";

$I->comment("Executing cURL command to update title");
$I->comment("Command: {$curl_command}");

// Execute the cURL command (we'll use a simpler approach with executeJS to make the API call)
$api_response = $I->executeJS("
    return fetch('/wp-json/wp/v2/ai-plugins/{$post_id}', {
        method: 'POST',
        headers: {
            'Authorization': 'Basic ' + btoa('Codeception:{$app_password}'),
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.wpApiSettings ? window.wpApiSettings.nonce : ''
        },
        body: JSON.stringify({
            title: '{$new_title}'
        })
    }).then(response => response.json()).then(data => {
        return { success: true, data: data };
    }).catch(error => {
        return { success: false, error: error.message };
    });
");

$I->comment("API Response for title update: " . json_encode($api_response));

$I->makeScreenshot("04-after-title-api-call");
$I->comment("After title API call 🔄 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/04-after-title-api-call.png' target='_blank'>screenshot available here</a>");

// Wait for polling mechanism to pick up the change (cacbot polls every 5 seconds)
$I->comment("Waiting 7 seconds for polling mechanism to detect title change...");
$I->wait(7);

// Check if title updated in UI
$updated_title = $I->executeJS("
    var titleElement = document.querySelector('#post-title-display');
    return titleElement ? titleElement.textContent.trim() : null;
");

$I->comment("Updated title in UI: {$updated_title}");
$I->expect("Title should be updated in the UI after polling");
$I->assertEquals($new_title, $updated_title, "Title should match the updated value");

$I->makeScreenshot("05-title-updated-in-ui");
$I->comment("Title updated in UI ✅ <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/05-title-updated-in-ui.png' target='_blank'>screenshot available here</a>");

// 🔄 Test 2: Update Plugin Version via REST API
$I->comment("🔄 TEST 2: Updating plugin version via REST API");

$new_version = "2.1." . time();
$I->comment("Updating version to: {$new_version}");

// Update version via JavaScript fetch API
$version_api_response = $I->executeJS("
    return fetch('/wp-json/wp/v2/ai-plugins/{$post_id}', {
        method: 'POST',
        headers: {
            'Authorization': 'Basic ' + btoa('Codeception:{$app_password}'),
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.wpApiSettings ? window.wpApiSettings.nonce : ''
        },
        body: JSON.stringify({
            'ai-plugin-version': '{$new_version}'
        })
    }).then(response => response.json()).then(data => {
        return { success: true, data: data };
    }).catch(error => {
        return { success: false, error: error.message };
    });
");

$I->comment("API Response for version update: " . json_encode($version_api_response));

$I->makeScreenshot("06-after-version-api-call");
$I->comment("After version API call 🔄 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/06-after-version-api-call.png' target='_blank'>screenshot available here</a>");

// Wait for polling mechanism to pick up the change
$I->comment("Waiting 7 seconds for polling mechanism to detect version change...");
$I->wait(7);

// Check if version updated in UI
$updated_version = $I->executeJS("
    var versionElement = document.querySelector('#ai-plugin-version-display');
    return versionElement ? versionElement.textContent.trim() : null;
");

$I->comment("Updated version in UI: {$updated_version}");
$I->expect("Version should be updated in the UI after polling");
$I->assertEquals($new_version, $updated_version, "Version should match the updated value");

$I->makeScreenshot("07-version-updated-in-ui");
$I->comment("Version updated in UI ✅ <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/07-version-updated-in-ui.png' target='_blank'>screenshot available here</a>");

// 🔄 Test 3: Final verification - updating both title and version simultaneously
$I->comment("🔄 TEST 3: Final verification - updating both title and version simultaneously");

$final_title = "Final Test Title " . date('H:i:s');
$final_version = "3.0." . time();

// Update both fields in one API call
$all_fields_response = $I->executeJS("
    return fetch('/wp-json/wp/v2/ai-plugins/{$post_id}', {
        method: 'POST',
        headers: {
            'Authorization': 'Basic ' + btoa('Codeception:{$app_password}'),
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.wpApiSettings ? window.wpApiSettings.nonce : ''
        },
        body: JSON.stringify({
            title: '{$final_title}',
            'ai-plugin-version': '{$final_version}'
        })
    }).then(response => response.json()).then(data => {
        return { success: true, data: data };
    }).catch(error => {
        return { success: false, error: error.message };
    });
");

$I->comment("All fields update response: " . json_encode($all_fields_response));

$I->makeScreenshot("08-after-all-fields-update");
$I->comment("After all fields update 🔄 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/08-after-all-fields-update.png' target='_blank'>screenshot available here</a>");

// Wait for polling mechanism to pick up all changes
$I->comment("Waiting 7 seconds for polling mechanism to detect all field changes...");
$I->wait(7);

// Verify both fields are updated
$final_ui_title = $I->executeJS("
    var titleElement = document.querySelector('#post-title-display');
    return titleElement ? titleElement.textContent.trim() : null;
");

$final_ui_version = $I->executeJS("
    var versionElement = document.querySelector('#ai-plugin-version-display');
    return versionElement ? versionElement.textContent.trim() : null;
");

$I->comment("📋 Final UI Values:");
$I->comment("   Title: {$final_ui_title}");
$I->comment("   Version: {$final_ui_version}");

// Final assertions
$I->expect("Both fields should be updated correctly in the UI");
$I->assertEquals($final_title, $final_ui_title, "Final title should match");
$I->assertEquals($final_version, $final_ui_version, "Final version should match");

$I->makeScreenshot("09-all-fields-final-verification");
$I->comment("All fields updated successfully ✅ <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/09-all-fields-final-verification.png' target='_blank'>screenshot available here</a>");

// 🎉 Test completion summary
$I->comment("🎉 POLLING MECHANISM TEST COMPLETED SUCCESSFULLY!");
$I->comment("✅ Title updates: Backend → UI polling mechanism working");
$I->comment("✅ Version updates: Backend → UI polling mechanism working");
$I->comment("✅ Simultaneous updates: Both fields update correctly via polling");

$I->makeScreenshot("10-test-completion-summary");
$I->comment("Test completion summary 🎉 <a href='http://localhost/wp-content/plugins/ai-plugin-dev/tests/_output/debug/10-test-completion-summary.png' target='_blank'>screenshot available here</a>");

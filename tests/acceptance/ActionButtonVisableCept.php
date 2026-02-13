<?php
/**
 * Test: Action Button Visibility in New AI Plugins
 * 
 * Bug Report:
 * After refactoring the button system in the AI style theme, the action button
 * is not visible in newly created AI plugins. The button labels may have changed
 * and the system needs to be realigned.
 * 
 * Expected Behavior:
 * When a new AI plugin is created and viewed on the frontend, the action button
 * should be visible in the comment form area.
 * 
 * Actual Behavior:
 * The action button is not visible in the comment form.
 * 
 * Test Steps:
 * 1. Log in as admin
 * 2. Navigate to the front end
 * 3. Click the "New AI Plugin" button
 * 4. Wait for the word "Private" (indicating a private post was created)
 * 5. Verify the action button is visible in the comment form
 * 
 * This assertion is expected to fail until the bug is fixed.
 */

$I = new AcceptanceTester($scenario);

$I->wantTo('Verify that the action button is visible in newly created AI plugins');

// =============================================================================
// SETUP: Login and navigate to frontend
// =============================================================================

$I->comment("Setting up test environment");
$I->amOnUrl("http://localhost");
$I->loginAsAdmin();
$I->amOnPage("/wp-admin/");
$I->amOnPage("/");

$I->makeScreenshot("01-homepage-before-plugin-creation");
$I->comment("Initial homepage state 📸");

// =============================================================================
// PLUGIN CREATION: Click New AI Plugin button
// =============================================================================

$I->comment("🎯 Creating new AI Plugin by clicking the New AI Plugin button");
$I->expect("The New AI Plugin button to be clickable");
$I->seeElement("#ai-plugin-dev-new-plugin-button");
$I->click("#ai-plugin-dev-new-plugin-button");

// =============================================================================
// WAIT FOR PRIVATE POST: Confirm plugin was created
// =============================================================================

$I->comment("⏳ Waiting for the word 'Private' to appear (indicating private post)");
$I->expect("The page to show 'Private' status for the newly created plugin");

// Wait for either "Private:" text or private post indicator
// The private post should be indicated somewhere on the page
$I->waitForText("Private", 10);
$I->see("Private");

$I->makeScreenshot("02-private-post-created");
$I->comment("Private AI plugin post created successfully 🎯");

// =============================================================================
// VERIFY COMMENT FORM EXISTS: Ensure we're on the right page
// =============================================================================

$I->comment("🔍 Verifying comment form is present on the page");
$I->expect("Comment form to be visible on the AI plugin post");

// Look for common comment form elements
// The AI style theme should have a comment form with specific structure
$I->waitForElement("#respond", 10);
$I->seeElement("#respond");

$I->comment("📜 Scrolling to the comment form to ensure action button is in view");
$I->scrollTo("#respond", 0, -100);
$I->wait(1); // Wait for scroll to complete

$I->makeScreenshot("03-comment-form-visible");
$I->comment("Comment form is present on the page and scrolled into view ✅");

// =============================================================================
// ACTION BUTTON VISIBILITY CHECK: This is expected to FAIL
// =============================================================================

$I->comment("🚨 CRITICAL TEST: Checking for action button visibility");
$I->comment("This assertion is expected to fail until the bug is fixed.");

$I->expect("Action button to be visible in the comment form area");

// The action button should be registered via the cacbot_action_buttons filter
// and should appear in the comment form area. Common selectors might include:
// - Button with text "Action"
// - Button with class containing "action"
// - Button with hammer icon (dashicons-hammer)
// - Button with name "hammer-action"

// Try multiple possible selectors for the action button
$actionButtonFound = false;
$selectors = [
    'button:contains("Action")',           // Button with text "Action"
    '.cacbot-action-button',               // Class-based selector
    'button[name="hammer-action"]',        // Name attribute
    '.comment-form button:contains("Action")', // Action button in comment form
    '#respond button:contains("Action")',  // Action button in respond area
];

foreach ($selectors as $selector) {
    try {
        $I->comment("Trying selector: {$selector}");
        $I->seeElement($selector);
        $actionButtonFound = true;
        $I->comment("✅ Action button found with selector: {$selector}");
        break;
    } catch (\Exception $e) {
        $I->comment("❌ Action button not found with selector: {$selector}");
    }
}

$I->makeScreenshot("04-action-button-check");
$I->comment("Action button visibility check complete");

// =============================================================================
// FINAL ASSERTION: This should FAIL demonstrating the bug
// =============================================================================

$I->comment("📋 Final assertion: Action button must be visible");

// This assertion is expected to fail, demonstrating the bug
$I->assertTrue(
    $actionButtonFound, 
    "Action button should be visible in the comment form, but it was not found with any known selector. " .
    "This indicates the button system refactoring has broken the action button visibility."
);

$I->comment("✅ Test completed - Action button visibility verified");

<?php
// Bootstrap file for acceptance tests
// This file is executed before running acceptance tests

// Change to the project root directory
$projectRoot = dirname(__DIR__, 2);
chdir($projectRoot);

// Run npm build before acceptance tests
echo "Running npm run build before acceptance tests...\n";
$output = [];
$returnCode = 0;

// Execute npm run build
exec('npm run build 2>&1', $output, $returnCode);

// Display output
foreach ($output as $line) {
    echo $line . "\n";
}

// Check if build was successful
if ($returnCode !== 0) {
    echo "ERROR: npm run build failed with return code: $returnCode\n";
    exit($returnCode);
}

echo "npm run build completed successfully.\n";

// Load Composer autoloader
require_once $projectRoot . '/vendor/autoload.php';
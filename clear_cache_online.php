<?php
/**
 * Cache Clearing Script for Production Server
 * 
 * IMPORTANT SECURITY NOTES:
 * 1. Delete this file after use
 * 2. Or add password protection
 * 3. Or restrict by IP address
 */

// Simple password protection (CHANGE THIS PASSWORD!)
$password = 'your-secure-password-here';

if (!isset($_GET['pass']) || $_GET['pass'] !== $password) {
    die('Access denied. Usage: clear_cache_online.php?pass=your-password');
}

echo "<h2>EBIMS Cache Clearing Tool</h2>";
echo "<p>Starting cache clearing process...</p>";
echo "<hr>";

// Initialize Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$commands = [
    'config:clear' => 'Clear configuration cache',
    'cache:clear' => 'Clear application cache',
    'view:clear' => 'Clear compiled views',
    'route:clear' => 'Clear route cache',
    'optimize:clear' => 'Clear all optimization caches',
];

echo "<ul>";
foreach ($commands as $command => $description) {
    try {
        Artisan::call($command);
        $output = Artisan::output();
        echo "<li><strong>✓ {$description}</strong><br><code>{$output}</code></li>";
    } catch (Exception $e) {
        echo "<li><strong>✗ {$description}</strong><br><span style='color: red;'>Error: {$e->getMessage()}</span></li>";
    }
}
echo "</ul>";

echo "<hr>";
echo "<h3>Cache cleared successfully!</h3>";
echo "<p style='color: red;'><strong>IMPORTANT:</strong> Delete this file (clear_cache_online.php) now for security!</p>";

// Optional: Auto-delete this file after execution (uncomment if desired)
// unlink(__FILE__);
// echo "<p style='color: green;'>This file has been automatically deleted.</p>";

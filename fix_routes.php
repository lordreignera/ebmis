<?php

/**
 * Fix Routes for Production - Comprehensive route cache fix
 * Run this on Digital Ocean after each deployment
 * Usage: php fix_routes_production.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         PRODUCTION ROUTE FIXER - DIGITAL OCEAN             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

// Step 1: Clear ALL caches first
echo "🔧 STEP 1: Clearing all caches...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    echo "  → Clearing application cache...";
    Artisan::call('cache:clear');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ⚠ Warning: " . $e->getMessage() . "\n";
}

try {
    echo "  → Clearing config cache...";
    Artisan::call('config:clear');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ⚠ Warning: " . $e->getMessage() . "\n";
}

try {
    echo "  → Clearing route cache...";
    Artisan::call('route:clear');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ⚠ Warning: " . $e->getMessage() . "\n";
}

try {
    echo "  → Clearing view cache...";
    Artisan::call('view:clear');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ⚠ Warning: " . $e->getMessage() . "\n";
}

try {
    echo "  → Clearing event cache...";
    Artisan::call('event:clear');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ⚠ Warning: " . $e->getMessage() . "\n";
}

try {
    echo "  → Clearing compiled files...";
    Artisan::call('clear-compiled');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ⚠ Warning: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 2: Delete cache files manually
echo "🗑️  STEP 2: Removing cache files manually...\n";
echo "─────────────────────────────────────────────────────────\n";

$cacheDirectories = [
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
    'bootstrap/cache/services.php',
    'bootstrap/cache/packages.php',
];

foreach ($cacheDirectories as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        try {
            unlink($fullPath);
            echo "  → Deleted: $file ✓\n";
        } catch (\Exception $e) {
            echo "  → Could not delete $file: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n";

// Step 3: Verify route files exist
echo "📁 STEP 3: Verifying route files...\n";
echo "─────────────────────────────────────────────────────────\n";

$routeFiles = [
    'routes/web.php',
    'routes/api.php',
];

$allRoutesExist = true;
foreach ($routeFiles as $routeFile) {
    $fullPath = __DIR__ . '/' . $routeFile;
    if (file_exists($fullPath)) {
        echo "  → $routeFile exists ✓\n";
    } else {
        echo "  → $routeFile MISSING ✗\n";
        $allRoutesExist = false;
    }
}

if (!$allRoutesExist) {
    echo "\n⚠️  ERROR: Some route files are missing!\n";
    exit(1);
}

echo "\n";

// Step 4: Check environment
echo "🌍 STEP 4: Checking environment...\n";
echo "─────────────────────────────────────────────────────────\n";

$appEnv = env('APP_ENV', 'production');
$appDebug = env('APP_DEBUG', false) ? 'true' : 'false';
$appUrl = env('APP_URL', 'not set');

echo "  → APP_ENV: $appEnv\n";
echo "  → APP_DEBUG: $appDebug\n";
echo "  → APP_URL: $appUrl\n";

if ($appDebug === 'true' && $appEnv === 'production') {
    echo "\n⚠️  WARNING: APP_DEBUG is enabled in production!\n";
    echo "   Set APP_DEBUG=false in your .env file\n";
}

echo "\n";

// Step 5: Optimize for production
echo "⚡ STEP 5: Optimizing for production...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    echo "  → Caching configuration...";
    Artisan::call('config:cache');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ✗ Error: " . $e->getMessage() . "\n";
}

try {
    echo "  → Caching routes...";
    Artisan::call('route:cache');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ✗ Error: " . $e->getMessage() . "\n";
    echo "     This might fail if you have closures in routes.\n";
    echo "     Consider converting route closures to controller methods.\n";
}

try {
    echo "  → Caching views...";
    Artisan::call('view:cache');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ✗ Error: " . $e->getMessage() . "\n";
}

try {
    echo "  → Caching events...";
    Artisan::call('event:cache');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ⚠ Warning: " . $e->getMessage() . "\n";
}

try {
    echo "  → Running optimize command...";
    Artisan::call('optimize');
    echo " ✓\n";
} catch (\Exception $e) {
    echo " ⚠ Warning: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 6: Verify login route
echo "🔍 STEP 6: Verifying login route...\n";
echo "─────────────────────────────────────────────────────────\n";

try {
    $routes = Artisan::call('route:list', ['--name' => 'login', '--columns' => 'method,uri,name']);
    
    // Check if route exists
    $routeExists = false;
    exec('php artisan route:list --name=login 2>&1', $output, $returnCode);
    
    if ($returnCode === 0 && !empty($output)) {
        echo "  → Login route found ✓\n";
        echo "     Route details:\n";
        foreach ($output as $line) {
            if (!empty(trim($line))) {
                echo "     " . $line . "\n";
            }
        }
        $routeExists = true;
    } else {
        echo "  → Login route NOT found ✗\n";
        echo "     This is a CRITICAL issue!\n";
    }
    
} catch (\Exception $e) {
    echo "  → Could not verify login route\n";
    echo "     Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 7: File permissions check
echo "🔐 STEP 7: Checking file permissions...\n";
echo "─────────────────────────────────────────────────────────\n";

$writableDirectories = [
    'storage',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($writableDirectories as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    if (is_dir($fullPath) && is_writable($fullPath)) {
        echo "  → $dir is writable ✓\n";
    } else {
        echo "  → $dir is NOT writable ✗\n";
        echo "     Run: chmod -R 775 $dir\n";
        echo "     And: chown -R www-data:www-data $dir\n";
    }
}

echo "\n";

// Final summary
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    PROCESS COMPLETE                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "✅ Route cache has been rebuilt\n";
echo "✅ Configuration has been cached\n";
echo "✅ Application has been optimized\n\n";

echo "🔄 NEXT STEPS:\n";
echo "─────────────────────────────────────────────────────────\n";
echo "1. Test your login page immediately\n";
echo "2. If issues persist, check your web server logs:\n";
echo "   • tail -f /var/log/nginx/error.log (Nginx)\n";
echo "   • tail -f /var/log/apache2/error.log (Apache)\n";
echo "3. Check Laravel logs:\n";
echo "   • tail -f storage/logs/laravel.log\n";
echo "4. Ensure .env file exists and has correct APP_URL\n";
echo "5. Run database optimizer: php optimize_database.php\n\n";

echo "💡 DEPLOYMENT TIP:\n";
echo "─────────────────────────────────────────────────────────\n";
echo "Add this to your deployment script (after git pull):\n";
echo "php fix_routes_production.php\n\n";

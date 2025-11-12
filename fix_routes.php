<?php

/**
 * Fix Routes - Clear all caches and regenerate routes
 * Run this on the server after deployment
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Fixing routes and clearing caches...\n\n";

// Clear all caches
echo "1. Clearing application cache...\n";
Artisan::call('cache:clear');
echo "   ✓ Done\n\n";

echo "2. Clearing config cache...\n";
Artisan::call('config:clear');
echo "   ✓ Done\n\n";

echo "3. Clearing route cache...\n";
Artisan::call('route:clear');
echo "   ✓ Done\n\n";

echo "4. Clearing view cache...\n";
Artisan::call('view:clear');
echo "   ✓ Done\n\n";

echo "5. Clearing compiled files...\n";
Artisan::call('clear-compiled');
echo "   ✓ Done\n\n";

echo "6. Optimizing application...\n";
Artisan::call('optimize');
echo "   ✓ Done\n\n";

echo "7. Caching config...\n";
Artisan::call('config:cache');
echo "   ✓ Done\n\n";

echo "8. Caching routes...\n";
Artisan::call('route:cache');
echo "   ✓ Done\n\n";

echo "\nAll done! Routes should now work properly.\n";
echo "Try accessing the login page now.\n";

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Verifying all required routes exist:\n\n";

$routes = [
    'admin.settings.fees-products' => 'Fees Types List',
    'admin.settings.fees-products.store' => 'Create Fee Type',
    'admin.settings.fees-products.show' => 'View Fee Type',
    'admin.settings.fees-products.update' => 'Update Fee Type',
    'admin.settings.fees-products.destroy' => 'Delete Fee Type',
    'admin.settings.system-accounts' => 'System Accounts List',
    'admin.settings.system-accounts.store' => 'Create System Account',
    'admin.settings.system-accounts.show' => 'View System Account',
    'admin.settings.system-accounts.update' => 'Update System Account',
    'admin.settings.system-accounts.destroy' => 'Delete System Account',
    'admin.fees.index' => 'Fees List',
    'admin.fees.create' => 'Create Fee',
    'admin.fees.store' => 'Store Fee',
];

$allGood = true;
foreach ($routes as $routeName => $description) {
    try {
        $url = route($routeName);
        echo "✓ {$description}: {$routeName}\n";
    } catch (\Exception $e) {
        echo "✗ {$description}: {$routeName} - NOT FOUND\n";
        $allGood = false;
    }
}

if ($allGood) {
    echo "\n✅ All routes are correctly configured!\n";
} else {
    echo "\n❌ Some routes are missing!\n";
}

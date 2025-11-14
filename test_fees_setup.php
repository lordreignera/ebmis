<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Fees Setup:\n\n";

// Test FeeType with systemAccount relationship
echo "1. Testing FeeType with SystemAccount relationship:\n";
$feeType = \App\Models\FeeType::with('systemAccount')->first();
if ($feeType) {
    echo "✓ Fee Type: {$feeType->name}\n";
    if ($feeType->systemAccount) {
        echo "✓ System Account: {$feeType->systemAccount->code} - {$feeType->systemAccount->name}\n";
    } else {
        echo "✗ System Account relationship not working\n";
    }
} else {
    echo "✗ No fee types found\n";
}

echo "\n2. Testing Fee model:\n";
$fee = \App\Models\Fee::with(['member', 'feeType'])->first();
if ($fee) {
    echo "✓ Fee found: ID {$fee->id}\n";
    echo "  Amount: {$fee->amount}\n";
    echo "  Status: {$fee->status}\n";
    if ($fee->feeType) {
        echo "✓ Fee Type: {$fee->feeType->name}\n";
    }
    if ($fee->member) {
        echo "✓ Member found\n";
    }
} else {
    echo "✗ No fees found (this is OK if no fees have been added yet)\n";
}

echo "\n3. Checking routes:\n";
$routes = [
    'admin.fees.index',
    'admin.fees.create',
    'admin.fees.store',
    'admin.settings.fees-products',
];

foreach ($routes as $routeName) {
    try {
        $url = route($routeName);
        echo "✓ Route '{$routeName}' exists: {$url}\n";
    } catch (\Exception $e) {
        echo "✗ Route '{$routeName}' not found\n";
    }
}

echo "\nSetup complete!\n";

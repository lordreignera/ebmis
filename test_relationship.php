<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FeeType;

echo "Testing FeeType->systemAccount relationship:\n\n";

$feeType = FeeType::with('systemAccount')->first();

echo "Fee Type: {$feeType->name}\n";
echo "Account ID: {$feeType->account}\n";

// Try to access the relationship
echo "Accessing systemAccount relationship:\n";
var_dump($feeType->systemAccount);

if ($feeType->systemAccount) {
    echo "\n✓ Relationship works!\n";
    echo "Code: {$feeType->systemAccount->code}\n";
    echo "Name: {$feeType->systemAccount->name}\n";
} else {
    echo "\n✗ Relationship returned null\n";
    
    // Try manual query
    echo "\nTrying manual query:\n";
    $account = \App\Models\SystemAccount::find($feeType->account);
    if ($account) {
        echo "Manual query works: {$account->code} - {$account->name}\n";
    }
}

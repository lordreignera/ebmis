<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FeeType;
use App\Models\SystemAccount;

echo "Checking fees_types and system_accounts relationships...\n\n";

$feeTypes = FeeType::with('systemAccount')->get();

foreach ($feeTypes as $feeType) {
    echo "Fee Type: {$feeType->name}\n";
    echo "Account ID: {$feeType->account}\n";
    
    if ($feeType->systemAccount) {
        echo "✓ System Account Found: {$feeType->systemAccount->code} - {$feeType->systemAccount->name}\n";
    } else {
        echo "✗ System Account NOT FOUND for ID {$feeType->account}\n";
        
        // Check if this account exists at all
        $exists = SystemAccount::find($feeType->account);
        if ($exists) {
            echo "  Account exists in DB: {$exists->code} - {$exists->name}\n";
            echo "  Status: {$exists->status}\n";
        } else {
            echo "  Account ID {$feeType->account} does not exist in system_accounts table!\n";
        }
    }
    echo "\n";
}

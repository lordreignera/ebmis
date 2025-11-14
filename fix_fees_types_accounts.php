<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FeeType;
use App\Models\SystemAccount;

echo "Fixing fees_types account field...\n\n";

$feeTypes = FeeType::all();

foreach ($feeTypes as $feeType) {
    echo "Processing: {$feeType->name}\n";
    echo "Current account value: {$feeType->account}\n";
    
    // If account is already an integer and valid, skip
    if (is_numeric($feeType->account) && SystemAccount::find($feeType->account)) {
        echo "✓ Already has valid account ID\n\n";
        continue;
    }
    
    // Try to extract the account code from the string (format: "40210 - Admin fees")
    if (preg_match('/^(\d+)\s*-/', $feeType->account, $matches)) {
        $accountCode = $matches[1];
        echo "Extracted code: {$accountCode}\n";
        
        // Find the system account by code
        $systemAccount = SystemAccount::where('code', $accountCode)->first();
        
        if ($systemAccount) {
            $feeType->account = $systemAccount->id;
            $feeType->save();
            echo "✓ Updated to account ID: {$systemAccount->id} ({$systemAccount->code} - {$systemAccount->name})\n\n";
        } else {
            echo "✗ System account with code {$accountCode} not found\n\n";
        }
    } else {
        echo "✗ Could not parse account string\n\n";
    }
}

echo "Done!\n";

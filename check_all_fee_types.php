<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FeeType;

echo "=== ALL FEE TYPES ===\n\n";

$allFeeTypes = FeeType::all();

foreach ($allFeeTypes as $feeType) {
    echo "ID: {$feeType->id}\n";
    echo "Name: {$feeType->name}\n";
    echo "Amount: " . ($feeType->amount ?? 'Not set') . "\n";
    echo "Required for Disbursement: " . ($feeType->required_disbursement == 0 ? 'YES (Mandatory)' : 'NO') . "\n";
    echo "Active: " . ($feeType->isactive == 1 ? 'YES' : 'NO') . "\n";
    echo "---\n";
}

echo "\n\n=== RECOMMENDATION ===\n";
echo "Fee types 1,5,6,7,8 are marked as 'required_disbursement = 0' (mandatory).\n";
echo "These should probably be changed to 'required_disbursement = 1' (optional) or deactivated,\n";
echo "unless they are truly required for every single loan disbursement.\n";

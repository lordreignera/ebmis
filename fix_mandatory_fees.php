<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FeeType;

echo "=== UPDATE FEE TYPES TO NOT BE MANDATORY ===\n\n";

// Fee types that should NOT be mandatory for disbursement
$feeTypesToUpdate = [
    1 => 'Late fees',
    5 => 'License fees',
    6 => 'Affiliation certificate',
    7 => 'Individual affiliation fee',
    8 => 'Restructuring fee'
];

foreach ($feeTypesToUpdate as $id => $name) {
    $feeType = FeeType::find($id);
    if ($feeType) {
        $feeType->update(['required_disbursement' => 1]); // 1 = NOT mandatory
        echo "✅ Updated '{$name}' (ID: {$id}) - Changed from MANDATORY to OPTIONAL\n";
    }
}

echo "\n\nNow only truly mandatory fees (like Registration fees) will block disbursements.\n";
echo "Late fees, License fees, and Restructuring fees are now optional.\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== UPDATE FEE TYPES TO NOT BE MANDATORY ===\n\n";

// Fee types that should NOT be mandatory for disbursement
$feeTypesToUpdate = [1, 5, 6, 7, 8];

foreach ($feeTypesToUpdate as $id) {
    $updated = DB::table('fees_types')
        ->where('id', $id)
        ->update(['required_disbursement' => 1]); // 1 = NOT mandatory
    
    if ($updated) {
        $feeName = DB::table('fees_types')->where('id', $id)->value('name');
        echo "✅ Updated '{$feeName}' (ID: {$id}) - Changed from MANDATORY to OPTIONAL\n";
    }
}

echo "\n\n=== VERIFICATION ===\n";
$mandatory = DB::table('fees_types')
    ->where('isactive', 1)
    ->where('required_disbursement', 0)
    ->get();

echo "Remaining mandatory fees: " . $mandatory->count() . "\n";
foreach ($mandatory as $fee) {
    echo "  - {$fee->name} (ID: {$fee->id})\n";
}

if ($mandatory->count() == 0) {
    echo "\n✅ SUCCESS! No more mandatory fees blocking disbursements.\n";
    echo "All fees are now optional unless you specifically mark them as mandatory.\n";
}

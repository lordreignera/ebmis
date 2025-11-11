<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SET REGISTRATION FEE AS MANDATORY ===\n\n";

// Make Registration fee mandatory
$updated = DB::table('fees_types')
    ->where('id', 2)
    ->update(['required_disbursement' => 0]); // 0 = MANDATORY

if ($updated) {
    echo "✅ Registration fees (ID: 2) is now MANDATORY for disbursement\n";
}

echo "\n=== VERIFICATION ===\n";
$mandatory = DB::table('fees_types')
    ->where('isactive', 1)
    ->where('required_disbursement', 0)
    ->get();

echo "Mandatory fees: " . $mandatory->count() . "\n";
foreach ($mandatory as $fee) {
    echo "  - {$fee->name} (ID: {$fee->id})\n";
}

echo "\n=== CHECK NAKAMATTE NORAH ===\n";
$norahFees = DB::table('fees')
    ->where('member_id', 612)
    ->where('fees_type_id', 2)
    ->where('status', 1)
    ->first();

if ($norahFees) {
    echo "✅ Nakamatte Norah HAS PAID Registration fee\n";
    echo "Amount: {$norahFees->amount}\n";
    echo "She can proceed with disbursement!\n";
} else {
    echo "❌ Nakamatte Norah has NOT paid Registration fee\n";
    echo "She needs to pay this before disbursement can proceed.\n";
}

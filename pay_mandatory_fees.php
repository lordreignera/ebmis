<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;
use App\Models\FeeType;
use App\Models\Fee;

echo "=== MARK MANDATORY FEES AS PAID FOR NAKAMATTE NORAH ===\n\n";

$member = Member::where('fname', 'Nakamatte')
    ->where('lname', 'Norah')
    ->first();

if (!$member) {
    echo "Member not found!\n";
    exit;
}

echo "Member: {$member->fname} {$member->lname} (ID: {$member->id})\n\n";

// Get mandatory fee types
$mandatoryFeeTypes = FeeType::where('isactive', 1)
    ->where('required_disbursement', 0)
    ->get();

echo "Creating payment records for mandatory fees...\n\n";

foreach ($mandatoryFeeTypes as $feeType) {
    // Check if already paid
    $existingFee = Fee::where('member_id', $member->id)
        ->where('fees_type_id', $feeType->id)
        ->first();
    
    if ($existingFee) {
        echo "Fee '{$feeType->name}' already recorded (ID: {$existingFee->id})\n";
        continue;
    }
    
    // Create fee record as paid
    $fee = Fee::create([
        'member_id' => $member->id,
        'fees_type_id' => $feeType->id,
        'amount' => $feeType->amount ?? 0,
        'status' => 1, // Paid
        'payment_date' => now(),
        'added_by' => 1,
        'branch_id' => $member->branch_id ?? 2,
    ]);
    
    echo "✅ Created payment record for '{$feeType->name}' (Fee ID: {$fee->id})\n";
}

echo "\n\nAll mandatory fees marked as paid for {$member->fname} {$member->lname}!\n";

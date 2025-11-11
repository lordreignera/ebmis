<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;
use App\Models\FeeType;
use App\Models\Fee;

echo "=== MANDATORY FEES CHECK FOR NAKAMATTE NORAH ===\n\n";

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

echo "Mandatory Fee Types (required_disbursement = 0):\n";
echo "Count: " . $mandatoryFeeTypes->count() . "\n\n";

foreach ($mandatoryFeeTypes as $feeType) {
    echo "Fee Type: {$feeType->name} (ID: {$feeType->id})\n";
    echo "Amount: {$feeType->amount}\n";
    
    // Check if member has paid this fee
    $paidFee = Fee::where('member_id', $member->id)
        ->where('fees_type_id', $feeType->id)
        ->where('status', 1)
        ->first();
    
    if ($paidFee) {
        echo "Status: ✅ PAID\n";
        echo "Payment ID: {$paidFee->id}\n";
        echo "Amount Paid: {$paidFee->amount}\n";
    } else {
        echo "Status: ❌ NOT PAID\n";
    }
    echo "---\n";
}

// Also check fees for loan 132
echo "\n\n=== FEES RECORDED FOR LOAN 132 ===\n";
$loanFees = Fee::where('loan_id', 132)->get();
echo "Count: " . $loanFees->count() . "\n\n";
foreach ($loanFees as $fee) {
    echo "Fee ID: {$fee->id}\n";
    echo "Fee Type ID: {$fee->fees_type_id}\n";
    echo "Amount: {$fee->amount}\n";
    echo "Status: " . ($fee->status == 1 ? 'Paid' : 'Unpaid') . "\n";
    echo "Member ID: " . ($fee->member_id ?? 'Not set') . "\n";
    echo "Loan ID: " . ($fee->loan_id ?? 'Not set') . "\n";
    echo "---\n";
}

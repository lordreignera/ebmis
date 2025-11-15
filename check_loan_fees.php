<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get loan 137
$loan = \App\Models\PersonalLoan::with('member', 'product')->find(137);

if (!$loan) {
    echo "Loan 137 not found\n";
    exit;
}

echo "=== LOAN DETAILS ===\n";
echo "Loan Code: {$loan->code}\n";
echo "Member: {$loan->member->fname} {$loan->member->lname} (ID: {$loan->member_id})\n";
echo "Charge Type: " . ($loan->charge_type == 1 ? 'Deduct from Disbursement' : 'Upfront Payment') . "\n";
echo "Status: {$loan->status}\n";
echo "Product: {$loan->product->name}\n";
echo "\n";

// Get mandatory fee types (required_disbursement = 0)
$mandatoryFeeTypes = \App\Models\FeeType::where('isactive', 1)
    ->where('required_disbursement', 0)
    ->get();

echo "=== MANDATORY FEE TYPES (required_disbursement = 0) ===\n";
foreach ($mandatoryFeeTypes as $feeType) {
    echo "- {$feeType->name} (ID: {$feeType->id})\n";
}
echo "\n";

// Check which mandatory fees have been paid by this member
echo "=== PAID MANDATORY FEES FOR MEMBER {$loan->member_id} ===\n";
$paidFees = \App\Models\Fee::where('member_id', $loan->member_id)
    ->where('status', 1) // Paid
    ->with('feeType')
    ->get();

if ($paidFees->count() > 0) {
    foreach ($paidFees as $fee) {
        $feeTypeName = $fee->feeType ? $fee->feeType->name : 'Unknown (ID: ' . $fee->fees_type_id . ')';
        echo "✓ {$feeTypeName} - Amount: UGX " . number_format($fee->amount, 0) . "\n";
        echo "  Payment Date: {$fee->datecreated}\n";
        echo "  Payment Type: {$fee->payment_type}\n";
        echo "  Status: " . ($fee->status == 1 ? 'Paid' : 'Pending') . "\n";
    }
} else {
    echo "No paid fees found for this member\n";
}
echo "\n";

// Check specific mandatory fees mentioned
$specificMandatoryFees = ['License fees', 'Affiliation certificate', 'Individual affiliation fee', 'test fee'];
echo "=== CHECKING SPECIFIC MANDATORY FEES ===\n";

foreach ($specificMandatoryFees as $feeName) {
    // Find fee type by name (partial match)
    $feeType = \App\Models\FeeType::where('isactive', 1)
        ->where('name', 'like', "%{$feeName}%")
        ->first();
    
    if ($feeType) {
        echo "\n{$feeName} (ID: {$feeType->id}):\n";
        
        // Check if paid
        $paidFee = \App\Models\Fee::where('member_id', $loan->member_id)
            ->where('fees_type_id', $feeType->id)
            ->where('status', 1)
            ->first();
        
        if ($paidFee) {
            echo "  ✓ PAID - UGX " . number_format($paidFee->amount, 0) . " on {$paidFee->datecreated}\n";
        } else {
            echo "  ✗ NOT PAID\n";
        }
    } else {
        echo "\n{$feeName}:\n";
        echo "  ✗ Fee type not found in system\n";
    }
}
echo "\n";

// Check if charge_type = 2, also check product charges
if ($loan->charge_type == 2) {
    echo "=== UPFRONT PRODUCT CHARGES (charge_type = 2) ===\n";
    $productCharges = $loan->product->charges()->where('isactive', 1)->get();
    
    if ($productCharges->count() > 0) {
        foreach ($productCharges as $charge) {
            $chargeTypeName = $charge->charge_type == 1 ? 'One-time' : 'Recurring';
            echo "\n{$charge->name} (ID: {$charge->id}) - Type: {$chargeTypeName}\n";
            
            // Check if paid for this loan
            $paidCharge = \App\Models\Fee::where('loan_id', $loan->id)
                ->where('fees_type_id', $charge->id)
                ->where('status', 1)
                ->first();
            
            if ($paidCharge) {
                echo "  ✓ PAID - UGX " . number_format($paidCharge->amount, 0) . "\n";
            } else {
                echo "  ✗ NOT PAID\n";
            }
        }
    } else {
        echo "No product charges found\n";
    }
}

echo "\n=== PREVIOUS LOANS COUNT ===\n";
$previousLoans = \App\Models\PersonalLoan::where('member_id', $loan->member_id)
    ->where('status', '>=', 1)
    ->where('id', '!=', 137)
    ->count();
echo "Previous approved loans: {$previousLoans}\n";
echo "Is first loan: " . ($previousLoans == 0 ? 'YES' : 'NO') . "\n";

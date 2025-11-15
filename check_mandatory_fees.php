<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== MANDATORY FEE TYPES (required_disbursement = 0) ===\n\n";

$mandatoryFees = DB::table('fee_types')
    ->where('isactive', 1)
    ->where('required_disbursement', 0)
    ->get();

foreach ($mandatoryFees as $fee) {
    $isSituational = stripos($fee->name, 'late') !== false ||
                    stripos($fee->name, 'restructuring') !== false ||
                    stripos($fee->name, 'penalty') !== false ||
                    stripos($fee->name, 'arrears') !== false;
    
    $status = $isSituational ? '[SITUATIONAL - SKIP]' : '[REQUIRED FOR FIRST LOAN]';
    
    echo "{$status} {$fee->name} (ID: {$fee->id})\n";
}

echo "\n=== PRODUCT CHARGES FOR LOAN 137 ===\n\n";

$loan = \App\Models\PersonalLoan::find(137);
if ($loan && $loan->product) {
    $productCharges = $loan->product->charges()->where('isactive', 1)->get();
    
    echo "Product: {$loan->product->name}\n";
    echo "Charge Type: " . ($loan->charge_type == 1 ? 'Deduct from Disbursement' : 'Upfront Payment') . "\n\n";
    
    foreach ($productCharges as $charge) {
        $chargeTypeName = $charge->charge_type == 1 ? 'One-time' : 'Recurring';
        echo "{$charge->name} (ID: {$charge->id}) - {$chargeTypeName}\n";
        
        // Check if paid
        $paid = DB::table('fees')
            ->where('loan_id', $loan->id)
            ->where('fees_type_id', $charge->id)
            ->where('status', 1)
            ->first();
        
        if ($paid) {
            echo "  ✓ PAID - UGX " . number_format($paid->amount, 0) . "\n";
        } else {
            echo "  ✗ NOT PAID\n";
        }
    }
}

echo "\n=== SUMMARY FOR LOAN 137 DISBURSEMENT ===\n\n";

// Check what's blocking disbursement
$loan = \App\Models\PersonalLoan::with('member', 'product')->find(137);
$mandatoryFeeTypes = \App\Models\FeeType::where('isactive', 1)
    ->where('required_disbursement', 0)
    ->get();

$unpaidMandatory = [];
$previousLoans = \App\Models\PersonalLoan::where('member_id', $loan->member_id)
    ->where('status', '>=', 1)
    ->where('id', '!=', 137)
    ->count();

$isFirstLoan = $previousLoans == 0;

echo "Is First Loan: " . ($isFirstLoan ? 'YES' : 'NO') . "\n";
echo "Charge Type: " . ($loan->charge_type == 1 ? 'Deduct from Disbursement' : 'Upfront Payment') . "\n\n";

foreach ($mandatoryFeeTypes as $feeType) {
    $isSituational = stripos($feeType->name, 'late') !== false ||
                    stripos($feeType->name, 'restructuring') !== false ||
                    stripos($feeType->name, 'penalty') !== false ||
                    stripos($feeType->name, 'arrears') !== false;
    
    if ($isSituational) {
        continue; // Skip situational fees
    }
    
    $isRegistration = stripos($feeType->name, 'registration') !== false ||
                     stripos($feeType->name, 'affiliation') !== false ||
                     stripos($feeType->name, 'license') !== false;
    
    // Skip one-time fees for subsequent loans
    if (!$isFirstLoan && $isRegistration) {
        continue;
    }
    
    // Check if paid
    $paid = DB::table('fees')
        ->where('member_id', $loan->member_id)
        ->where('fees_type_id', $feeType->id)
        ->where('status', 1)
        ->first();
    
    if (!$paid) {
        $unpaidMandatory[] = $feeType->name;
        echo "✗ UNPAID: {$feeType->name}\n";
    } else {
        echo "✓ PAID: {$feeType->name} - UGX " . number_format($paid->amount, 0) . "\n";
    }
}

if (count($unpaidMandatory) > 0) {
    echo "\n❌ CANNOT DISBURSE - Unpaid mandatory fees: " . implode(', ', $unpaidMandatory) . "\n";
} else {
    echo "\n✅ All mandatory fees paid!\n";
    
    // Check product charges if charge_type = 2
    if ($loan->charge_type == 2) {
        echo "\nChecking upfront product charges...\n";
        $unpaidCharges = [];
        
        foreach ($loan->product->charges as $charge) {
            $paid = DB::table('fees')
                ->where('loan_id', $loan->id)
                ->where('fees_type_id', $charge->id)
                ->where('status', 1)
                ->first();
            
            if (!$paid) {
                $unpaidCharges[] = $charge->name;
                echo "✗ UNPAID: {$charge->name}\n";
            } else {
                echo "✓ PAID: {$charge->name}\n";
            }
        }
        
        if (count($unpaidCharges) > 0) {
            echo "\n❌ CANNOT DISBURSE - Unpaid product charges: " . implode(', ', $unpaidCharges) . "\n";
        } else {
            echo "\n✅ All product charges paid! READY TO DISBURSE\n";
        }
    } else {
        echo "\n✅ Charges will be deducted from disbursement. READY TO DISBURSE\n";
    }
}

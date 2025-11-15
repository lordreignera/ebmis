<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING LOAN 137 DISBURSEMENT VALIDATION ===\n\n";

$loan = \App\Models\PersonalLoan::with('member', 'product')->find(137);

if (!$loan) {
    echo "❌ Loan 137 not found\n";
    exit;
}

echo "Loan Code: {$loan->code}\n";
echo "Member: {$loan->member->fname} {$loan->member->lname} (ID: {$loan->member_id})\n";
echo "Charge Type: " . ($loan->charge_type == 1 ? 'Deduct from Disbursement' : 'Upfront Payment') . "\n";
echo "Status: {$loan->status}\n\n";

// Check previous loans
$previousLoans = \App\Models\PersonalLoan::where('member_id', $loan->member_id)
    ->where('status', '>=', 1)
    ->where('id', '!=', 137)
    ->count();

$isFirstLoan = $previousLoans <= 0;

echo "=== MANDATORY FEE VALIDATION (REGISTRATION FEE ONLY) ===\n";
echo "Previous approved loans: {$previousLoans}\n";
echo "Is first loan: " . ($isFirstLoan ? 'YES' : 'NO') . "\n\n";

if (!$isFirstLoan) {
    echo "✅ PASSED: Subsequent loan - registration fee not required\n\n";
} else {
    // Check registration fee
    $registrationFee = DB::table('fees_types')
        ->where('isactive', 1)
        ->where('required_disbursement', 0)
        ->where(function($query) {
            $query->where('name', 'like', '%registration%')
                  ->orWhere('name', 'like', '%Registration%');
        })
        ->first();
    
    if (!$registrationFee) {
        echo "⚠️ No registration fee type found in system\n";
        echo "✅ PASSED: No registration fee defined, allowing disbursement\n\n";
    } else {
        echo "Registration Fee Found: {$registrationFee->name} (ID: {$registrationFee->id})\n";
        
        // Check if paid
        $paidFee = DB::table('fees')
            ->where('member_id', $loan->member_id)
            ->where('fees_type_id', $registrationFee->id)
            ->where('status', 1)
            ->first();
        
        if ($paidFee) {
            echo "✅ PAID: UGX " . number_format($paidFee->amount, 0) . " on {$paidFee->datecreated}\n";
            echo "✅ PASSED: Registration fee validated\n\n";
        } else {
            echo "❌ NOT PAID\n";
            echo "❌ FAILED: Registration fee must be paid before first loan disbursement\n\n";
        }
    }
}

// Check upfront product charges if charge_type = 2
if ($loan->charge_type == 2) {
    echo "=== UPFRONT PRODUCT CHARGES VALIDATION ===\n";
    
    $productCharges = $loan->product->charges()->where('isactive', 1)->get();
    
    if ($productCharges->count() == 0) {
        echo "✅ PASSED: No product charges defined\n\n";
    } else {
        $allPaid = true;
        $unpaidCharges = [];
        
        foreach ($productCharges as $charge) {
            $paidCharge = DB::table('fees')
                ->where('loan_id', $loan->id)
                ->where('fees_type_id', $charge->id)
                ->where('status', 1)
                ->first();
            
            if ($paidCharge) {
                echo "✅ {$charge->name} - PAID (UGX " . number_format($paidCharge->amount, 0) . ")\n";
            } else {
                echo "❌ {$charge->name} - NOT PAID\n";
                $allPaid = false;
                $unpaidCharges[] = $charge->name;
            }
        }
        
        if ($allPaid) {
            echo "\n✅ PASSED: All upfront product charges paid\n\n";
        } else {
            echo "\n❌ FAILED: Unpaid charges - " . implode(', ', $unpaidCharges) . "\n\n";
        }
    }
} else {
    echo "=== PRODUCT CHARGES ===\n";
    echo "✅ PASSED: Charges will be deducted from disbursement\n\n";
}

echo "=== FINAL VERDICT ===\n";

// Simulate the actual validation
$member = $loan->member;

// Check 1: Mandatory fees (registration only)
$previousLoansCount = \App\Models\PersonalLoan::where('member_id', $member->id)
    ->where('status', '>=', 1)
    ->count();

$isFirstLoan = $previousLoansCount <= 1;

if ($isFirstLoan) {
    $registrationFee = \App\Models\FeeType::active()
        ->where('required_disbursement', 0)
        ->where(function($query) {
            $query->where('name', 'like', '%registration%');
        })
        ->first();
    
    if ($registrationFee) {
        $paidFee = \App\Models\Fee::where('member_id', $member->id)
            ->where('fees_type_id', $registrationFee->id)
            ->where('status', 1)
            ->first();
        
        if (!$paidFee) {
            echo "❌ CANNOT DISBURSE: Registration fee not paid\n";
            exit;
        }
    }
}

// Check 2: Upfront product charges (if charge_type = 2)
if ($loan->charge_type == 2) {
    $productCharges = $loan->product->charges()->where('isactive', 1)->get();
    $unpaidCharges = [];
    
    foreach ($productCharges as $charge) {
        $paidCharge = \App\Models\Fee::where('loan_id', $loan->id)
            ->where('fees_type_id', $charge->id)
            ->where('status', 1)
            ->first();
        
        if (!$paidCharge) {
            $unpaidCharges[] = $charge->name;
        }
    }
    
    if (count($unpaidCharges) > 0) {
        echo "❌ CANNOT DISBURSE: Unpaid product charges - " . implode(', ', $unpaidCharges) . "\n";
        exit;
    }
}

echo "✅✅✅ LOAN 137 CAN BE DISBURSED ✅✅✅\n";
echo "All validation checks passed!\n";

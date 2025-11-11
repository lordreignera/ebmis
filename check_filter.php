<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Fee;

echo "=== DISBURSEMENTS FILTER CHECK ===\n\n";

// Get all approved loans
$personalLoans = PersonalLoan::where('status', 1)
    ->whereDoesntHave('disbursements', function($q) {
        $q->where('status', 1);
    })
    ->with(['member', 'branch', 'product', 'product.charges'])
    ->get()
    ->map(function($loan) {
        $loan->loan_type = 'personal';
        return $loan;
    });

$groupLoans = GroupLoan::where('status', 1)
    ->whereDoesntHave('disbursements', function($q) {
        $q->where('status', 1);
    })
    ->with(['group', 'branch', 'product', 'product.charges'])
    ->get()
    ->map(function($loan) {
        $loan->loan_type = 'group';
        return $loan;
    });

$allLoans = $personalLoans->merge($groupLoans)->sortByDesc('datecreated');

echo "Total approved loans (before filter): " . $allLoans->count() . "\n\n";

// Filter loans - same logic as controller
$readyLoans = $allLoans->filter(function($loan) {
    // If charge_type = 1, charges are deducted - always ready
    if ($loan->charge_type == 1) {
        return true;
    }
    
    // If charge_type = 2, check if all upfront charges are paid
    if ($loan->charge_type == 2) {
        $memberId = $loan->loan_type === 'personal' ? $loan->member_id : null;
        
        // Get product charges
        $productCharges = $loan->product->charges()->where('isactive', 1)->get();
        
        // Check if all charges are paid
        foreach ($productCharges as $charge) {
            $paidFee = Fee::where('loan_id', $loan->id)
                          ->where('fees_type_id', $charge->id)
                          ->where('status', 1)
                          ->first();
            
            // For registration fees, check member level
            $isRegFee = stripos($charge->name, 'registration') !== false;
            if ($isRegFee && !$paidFee && $memberId) {
                $paidFee = Fee::where('member_id', $memberId)
                              ->where('fees_type_id', $charge->id)
                              ->where('status', 1)
                              ->first();
            }
            
            // If any charge is unpaid, loan is not ready
            if (!$paidFee) {
                return false;
            }
        }
        
        return true; // All charges are paid
    }
    
    return true; // Default: include the loan
});

echo "Total loans ready for disbursement (after filter): " . $readyLoans->count() . "\n\n";

// Check if loan 132 passed the filter
$loan132 = $readyLoans->where('id', 132)->first();
if ($loan132) {
    echo "✅ Loan 132 PASSED the filter\n";
    echo "It should appear on page " . (ceil($readyLoans->search(function($item) { return $item->id == 132; }) / 20) + 1) . "\n";
    
    // Find position
    $position = 0;
    foreach ($readyLoans->values() as $index => $loan) {
        if ($loan->id == 132) {
            $position = $index + 1;
            break;
        }
    }
    echo "Position in list: #{$position}\n";
    echo "Page: " . ceil($position / 20) . "\n";
} else {
    echo "❌ Loan 132 DID NOT PASS the filter\n";
    
    $loan = PersonalLoan::with('product.charges')->find(132);
    echo "\nDebug info:\n";
    echo "Charge Type: {$loan->charge_type}\n";
    if ($loan->charge_type == 1) {
        echo "Should be ALWAYS READY (auto-pass filter)\n";
    }
}

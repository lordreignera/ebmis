<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "Testing Loan Creation...\n\n";

// Get member
$member = Member::find(612); // Nakamatte Norah
if (!$member) {
    die("Member not found!\n");
}

echo "Member: " . $member->fname . " " . $member->lname . "\n";
echo "Member Approved: " . ($member->isApproved() ? 'YES' : 'NO') . "\n";
echo "Has Active Loan: " . ($member->hasActiveLoan() ? 'YES' : 'NO') . "\n\n";

// Get a daily product (period_type 3 = daily)
$product = Product::where('period_type', 3)->first();
if (!$product) {
    die("No daily product found!\n");
}

echo "Product: " . $product->name . "\n";
echo "Interest: " . $product->interest . "%\n\n";

// Generate loan code
$currentTime = now();
$periodCode = 'D'; // Daily
$dailyCount = PersonalLoan::whereDate('datecreated', today())->count() + 1;
$loanCode = 'PD' . 'LOAN' . $currentTime->format('ymdHi') . sprintf('%03d', $dailyCount);

echo "Generated Loan Code: " . $loanCode . "\n\n";

// Prepare loan data
$loanData = [
    'member_id' => $member->id,
    'product_type' => $product->id,
    'code' => $loanCode,
    'interest' => $product->interest,
    'period' => 2,
    'principal' => 10000,
    'installment' => 5200, // 10000 + interest / 2
    'status' => 0,
    'verified' => 0,
    'branch_id' => $member->branch_id,
    'added_by' => 1, // Admin
    'datecreated' => now(),
    'repay_strategy' => 1,
    'repay_name' => 'Test Business',
    'repay_address' => 'Test Address',
    'charge_type' => 1,
    'sign_code' => 0, // Not an eSign loan
];

echo "Attempting to create loan...\n";
print_r($loanData);
echo "\n";

try {
    DB::beginTransaction();
    
    $loan = PersonalLoan::create($loanData);
    
    DB::commit();
    
    echo "\n✓ SUCCESS! Loan created with ID: " . $loan->id . "\n";
    echo "Loan Code: " . $loan->code . "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nFull trace:\n";
    echo $e->getTraceAsString() . "\n";
}

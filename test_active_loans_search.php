<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Loan;

echo "=== Testing Active Loans Search for Otim Benard ===\n\n";

// Test 1: Search for "otim"
echo "Test 1: Searching for 'otim'...\n";
$query1 = Loan::where('status', 2)
    ->whereHas('member', function($q) {
        $q->where('fname', 'like', '%otim%')
          ->orWhere('lname', 'like', '%otim%')
          ->orWhere('mname', 'like', '%otim%');
    })
    ->with('member')
    ->get();

echo "Found {$query1->count()} active loan(s):\n";
foreach ($query1 as $loan) {
    echo "  - Loan ID: {$loan->id}, Code: {$loan->code}\n";
    echo "    Member: {$loan->member->fname} {$loan->member->lname}\n";
    echo "    Status: {$loan->status} (Disbursed)\n\n";
}

// Test 2: Search for "benard"
echo "Test 2: Searching for 'benard'...\n";
$query2 = Loan::where('status', 2)
    ->whereHas('member', function($q) {
        $q->where('fname', 'like', '%benard%')
          ->orWhere('lname', 'like', '%benard%')
          ->orWhere('mname', 'like', '%benard%');
    })
    ->with('member')
    ->get();

echo "Found {$query2->count()} active loan(s):\n";
foreach ($query2 as $loan) {
    echo "  - Loan ID: {$loan->id}, Code: {$loan->code}\n";
    echo "    Member: {$loan->member->fname} {$loan->member->lname}\n";
    echo "    Status: {$loan->status} (Disbursed)\n\n";
}

// Test 3: Check if Otim Benard's loan appears in active loans
echo "Test 3: Checking Otim Benard's specific loan (ID 60)...\n";
$otimLoan = Loan::where('id', 60)->with(['member', 'schedules'])->first();

if ($otimLoan) {
    echo "✓ Loan found:\n";
    echo "  ID: {$otimLoan->id}\n";
    echo "  Code: {$otimLoan->code}\n";
    echo "  Status: {$otimLoan->status} (" . ($otimLoan->status == 2 ? 'Disbursed/Active' : 'Other') . ")\n";
    echo "  Member: {$otimLoan->member->fname} {$otimLoan->member->lname}\n";
    echo "  Member Code: {$otimLoan->member->code}\n";
    
    $unpaidSchedules = $otimLoan->schedules->where('status', 0)->count();
    echo "  Unpaid Schedules: {$unpaidSchedules}\n";
    
    if ($otimLoan->status == 2 && $unpaidSchedules > 0) {
        echo "\n✓ This loan SHOULD appear in Active Loans page\n";
    } else {
        echo "\n❌ This loan should NOT appear in Active Loans page\n";
    }
} else {
    echo "❌ Loan ID 60 not found\n";
}

echo "\n=== Summary ===\n";
echo "Improvements made:\n";
echo "  ✓ Added mname (middle name) to search\n";
echo "  ✓ Added member code to search\n";
echo "  ✓ Added email to search\n";
echo "  ✓ Added type parameter support (personal/group)\n";
echo "\nNow you can search for:\n";
echo "  - 'otim' or 'benard' (first/middle/last name)\n";
echo "  - Member code (e.g., 'PM1749899386')\n";
echo "  - Contact number\n";
echo "  - Email\n";
echo "  - Loan code\n";

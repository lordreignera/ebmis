<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;
use App\Models\Loan;
use App\Models\LoanSchedule;

echo "=== Checking Otim Benard's Loan Status ===\n\n";

// Find Otim Benard
$otim = Member::where('fname', 'like', '%Otim%')
              ->where('lname', 'like', '%Benard%')
              ->orWhere(function($q) {
                  $q->where('lname', 'like', '%Otim%')
                    ->where('fname', 'like', '%Benard%');
              })
              ->first();

if (!$otim) {
    echo "❌ Otim Benard not found!\n";
    exit;
}

echo "✓ Found Member:\n";
echo "  ID: {$otim->id}\n";
echo "  Name: {$otim->fname} {$otim->lname}\n";
echo "  verified: {$otim->verified}\n\n";

// Get all loans
$loans = Loan::where('member_id', $otim->id)->with('schedules')->get();

echo "Total Loans: {$loans->count()}\n\n";

foreach ($loans as $loan) {
    echo "Loan ID: {$loan->id}\n";
    echo "  Status: {$loan->status} (" . getLoanStatusName($loan->status) . ")\n";
    echo "  Principal: {$loan->principal}\n";
    echo "  Date: {$loan->datecreated}\n";
    
    $schedules = $loan->schedules;
    $totalSchedules = $schedules->count();
    $unpaidSchedules = $schedules->where('status', 0)->count();
    $paidSchedules = $schedules->where('status', 1)->count();
    
    echo "  Schedules:\n";
    echo "    Total: {$totalSchedules}\n";
    echo "    Unpaid: {$unpaidSchedules}\n";
    echo "    Paid: {$paidSchedules}\n";
    
    // Determine if this is an active loan
    $isActive = in_array($loan->status, [1, 2]) && $unpaidSchedules > 0;
    
    if ($isActive) {
        echo "  ❌ ACTIVE LOAN - Blocks new loan applications\n";
    } else {
        echo "  ✓ NOT ACTIVE - Does not block new loans\n";
        if ($unpaidSchedules == 0 && $totalSchedules > 0) {
            echo "    (All schedules paid)\n";
        } else if ($loan->status == 0) {
            echo "    (Loan still pending approval)\n";
        } else if ($loan->status == 3) {
            echo "    (Loan was rejected)\n";
        } else if ($loan->status == 4) {
            echo "    (Loan completed)\n";
        }
    }
    echo "\n";
}

echo "=== Testing Current Query Logic ===\n\n";

// Current query that filters members
$eligibleWithCurrentLogic = Member::verified()
    ->notDeleted()
    ->whereDoesntHave('loans', function($query) {
        $query->whereIn('status', [1, 2])
              ->whereHas('schedules', function($subQuery) {
                  $subQuery->where('status', 0);
              });
    })
    ->where('id', $otim->id)
    ->count();

if ($eligibleWithCurrentLogic > 0) {
    echo "✓ Current Logic: Otim IS eligible (would appear in dropdown)\n";
} else {
    echo "❌ Current Logic: Otim NOT eligible (won't appear in dropdown)\n";
}

echo "\n=== Summary ===\n";
echo "Business Rule: Members with active loans (approved/disbursed + unpaid schedules) should NOT get new loans\n";
echo "Result: " . ($eligibleWithCurrentLogic > 0 ? "Otim CAN get a new loan" : "Otim CANNOT get a new loan") . "\n";

function getLoanStatusName($status) {
    $statuses = [
        0 => 'Pending',
        1 => 'Approved',
        2 => 'Disbursed',
        3 => 'Rejected',
        4 => 'Completed',
        5 => 'Defaulted'
    ];
    return $statuses[$status] ?? 'Unknown';
}

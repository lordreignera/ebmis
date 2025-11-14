<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

echo "=== Checking Why Otim Benard Not Showing in Loan Creation ===\n\n";

// Find Otim Benard
$otim = Member::where('fname', 'like', '%Otim%')
              ->where('lname', 'like', '%Benard%')
              ->orWhere('lname', 'like', '%Otim%')
              ->where('fname', 'like', '%Benard%')
              ->first();

if (!$otim) {
    echo "❌ Otim Benard not found!\n";
    exit;
}

echo "✓ Found Member:\n";
echo "  ID: {$otim->id}\n";
echo "  Code: {$otim->code}\n";
echo "  Name: {$otim->fname} {$otim->mname} {$otim->lname}\n";
echo "  verified: {$otim->verified}\n";
echo "  status: {$otim->status}\n";
echo "  soft_delete: {$otim->soft_delete}\n";
echo "  member_type: {$otim->member_type}\n\n";

// Check if member passes the loan creation filters
echo "=== Checking Filter Conditions ===\n\n";

echo "1. verified() scope:\n";
$isVerified = $otim->verified == 1;
echo "   verified = 1? " . ($isVerified ? "✓ YES" : "❌ NO - verified = {$otim->verified}") . "\n\n";

echo "2. notDeleted() scope:\n";
$notDeleted = $otim->soft_delete == 0 || is_null($otim->soft_delete);
echo "   soft_delete = 0? " . ($notDeleted ? "✓ YES" : "❌ NO - soft_delete = {$otim->soft_delete}") . "\n\n";

echo "3. Has active loans?\n";
$loans = Loan::where('member_id', $otim->id)->get();
echo "   Total loans: {$loans->count()}\n";

if ($loans->count() > 0) {
    foreach ($loans as $loan) {
        echo "\n   Loan ID: {$loan->id}\n";
        echo "   Status: {$loan->status} (" . getLoanStatus($loan->status) . ")\n";
        echo "   Principal: {$loan->principal}\n";
        echo "   Created: {$loan->datecreated}\n";
        
        // Check schedules
        $schedules = DB::table('loan_schedule')
                      ->where('loan_id', $loan->id)
                      ->get();
        
        echo "   Total schedules: {$schedules->count()}\n";
        
        if ($schedules->count() > 0) {
            $unpaidCount = $schedules->where('status', 0)->count();
            $paidCount = $schedules->where('status', 1)->count();
            echo "   Unpaid schedules: {$unpaidCount}\n";
            echo "   Paid schedules: {$paidCount}\n";
            
            // Check if this loan blocks member
            if (in_array($loan->status, [1, 2]) && $unpaidCount > 0) {
                echo "   ❌ BLOCKS MEMBER: Status is " . getLoanStatus($loan->status) . " with {$unpaidCount} unpaid schedules\n";
            } else {
                echo "   ✓ Does not block member\n";
            }
        }
    }
} else {
    echo "   ✓ No loans found - should be eligible\n";
}

echo "\n=== Testing Query That Loads Members for Loan Creation ===\n";

$eligibleCount = Member::with(['branch', 'loans.schedules'])
    ->verified()
    ->notDeleted()
    ->whereDoesntHave('loans', function($query) {
        $query->whereIn('status', [1, 2]) // Approved or Disbursed
              ->whereHas('schedules', function($subQuery) {
                  $subQuery->where('status', 0); // Unpaid schedules
              });
    })
    ->where('id', $otim->id)
    ->count();

if ($eligibleCount > 0) {
    echo "✓ Otim Benard PASSES the filter - should appear in dropdown\n";
} else {
    echo "❌ Otim Benard FAILS the filter - will NOT appear in dropdown\n";
    echo "\nReasons could be:\n";
    echo "  - Not verified (verified != 1)\n";
    echo "  - Soft deleted (soft_delete != 0)\n";
    echo "  - Has active loan with unpaid schedules\n";
}

echo "\n=== Complete ===\n";

function getLoanStatus($status) {
    $statuses = [
        0 => 'Pending',
        1 => 'Approved',
        2 => 'Disbursed/Active',
        3 => 'Rejected',
        4 => 'Completed',
        5 => 'Defaulted'
    ];
    return $statuses[$status] ?? 'Unknown';
}

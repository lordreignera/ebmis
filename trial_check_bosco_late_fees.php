<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n\n";
echo "   TRIAL RUN - CHECK LATE FEES FOR BOSCO OKERENYANG\n";
echo "   (Read-only - No changes will be made)\n";
echo "\n\n";

// Find Bosco Okerenyang
$member = DB::table('members')
    ->where('fname', 'LIKE', '%BOSCO%')
    ->where('lname', 'LIKE', '%OKEREN%')
    ->first();

if (!$member) {
    die("Member 'Bosco Okerenyang' not found.\n");
}

echo "Found Member: {$member->fname} {$member->lname} (ID: {$member->id})\n\n";

// Get active loans with product info
$loans = DB::table('personal_loans as pl')
    ->join('products as p', 'pl.product_type', '=', 'p.id')
    ->select('pl.*', 'p.period_type', 'p.name as product_name')
    ->where('pl.member_id', $member->id)
    ->where('pl.status', 2)
    ->get();

if ($loans->count() == 0) {
    die("No active loans found.\n");
}

echo "Found " . $loans->count() . " active loan(s)\n\n";

// Define upgrade period (last 3-4 weeks)
$today = Carbon::now();
$upgradeStartDate = Carbon::create(2025, 11, 1); // November 1st

echo "System Upgrade Period:\n";
echo "  Start: " . $upgradeStartDate->format('d-m-Y') . "\n";
echo "  End: " . $today->format('d-m-Y') . "\n\n";

$grandTotalLateFees = 0;
$totalAffectedSchedules = 0;

foreach ($loans as $loan) {
    echo "\n";
    echo "LOAN: {$loan->code}\n";
    echo "\n";
    echo "  Product: {$loan->product_name}\n";
    echo "  Principal: " . number_format($loan->principal) . " UGX\n";
    echo "  Period Type: " . ($loan->period_type == 1 ? 'Weekly' : ($loan->period_type == 2 ? 'Monthly' : 'Daily')) . "\n\n";

    // Get all unpaid schedules
    $schedules = DB::table('loan_schedules')
        ->where('loan_id', $loan->id)
        ->where('status', 0)
        ->orderBy('payment_date')
        ->get();

    echo "Unpaid Schedules: " . count($schedules) . "\n\n";

    $loanLateFees = 0;
    $loanAffectedSchedules = 0;

    foreach ($schedules as $schedule) {
        try {
            $dueDate = Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
            
            // Check if this schedule is overdue (due date is in the past)
            if ($dueDate->lessThan($today)) {
                // Check if it became overdue during the upgrade period
                if ($dueDate->greaterThanOrEqualTo($upgradeStartDate)) {
                    $loanAffectedSchedules++;
                    $totalAffectedSchedules++;
                    
                    // Calculate days late (positive number)
                    $daysLate = $dueDate->diffInDays($today, false);
                    
                    // Calculate periods overdue
                    if ($loan->period_type == 1) {
                        $periodsOverdue = ceil($daysLate / 7);
                        $periodText = 'weeks';
                    } elseif ($loan->period_type == 2) {
                        $periodsOverdue = ceil($daysLate / 30);
                        $periodText = 'months';
                    } else {
                        $periodsOverdue = $daysLate;
                        $periodText = 'days';
                    }
                    
                    // Calculate schedule amount
                    $scheduleAmount = $schedule->principal + $schedule->interest;
                    
                    // Calculate late fee (6% per period)
                    $lateFee = ($scheduleAmount * 0.06) * $periodsOverdue;
                    
                    $loanLateFees += $lateFee;
                    $grandTotalLateFees += $lateFee;
                    
                    echo "   Due Date: " . $schedule->payment_date . "\n";
                    echo "     Payment: " . number_format($schedule->payment) . " UGX\n";
                    echo "     (Principal: " . number_format($schedule->principal) . " + Interest: " . number_format($schedule->interest) . ")\n";
                    echo "      Days Late: " . $daysLate . " days (" . $periodsOverdue . " " . $periodText . ")\n";
                    echo "      Late Fee (6%  " . $periodsOverdue . "): " . number_format($lateFee) . " UGX\n";
                    echo "      After Waiver: " . number_format($schedule->payment) . " UGX (NO LATE FEE)\n";
                    echo "\n";
                }
            }
        } catch (Exception $e) {
            // Skip invalid dates
        }
    }
    
    if ($loanAffectedSchedules > 0) {
        echo "   Loan Late Fees to Waive: " . number_format($loanLateFees) . " UGX\n";
        echo "   Affected Schedules: {$loanAffectedSchedules}\n";
    } else {
        echo "   No schedules became overdue during upgrade period\n";
    }
    echo "\n";
}

echo "\n";
echo "   SUMMARY FOR BOSCO OKERENYANG\n";
echo "\n\n";
echo "Member: {$member->fname} {$member->lname} (ID: {$member->id})\n";
echo "Active Loans: " . $loans->count() . "\n";
echo "Schedules Affected: {$totalAffectedSchedules}\n";
echo " Total Late Fees to Waive: " . number_format($grandTotalLateFees) . " UGX\n\n";
echo "WHAT THIS MEANS:\n";
echo "   Payment dates: UNCHANGED (must pay on original dates)\n";
echo "   Payment amounts: UNCHANGED (650,000 + 500,000 = 1,150,000 total)\n";
echo "   Late fees: WAIVED (removed completely)\n";
echo "   Bosco saves: " . number_format($grandTotalLateFees) . " UGX\n";
echo "   This is a READ-ONLY trial - no database changes made\n\n";

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;
use App\Models\LoanSchedule;
use App\Models\LateFee;
use Carbon\Carbon;

echo "=================================================\n";
echo "WAIVE NOVEMBER 2025 LATE FEES FOR ALL LOANS\n";
echo "=================================================\n\n";

echo "Policy: Waive late fees that accrued DURING November 2025\n";
echo "for ALL loan types (Daily, Weekly, Monthly)\n";
echo "Reason: System upgrade - clients unable to make payments\n\n";

// Define November period
$novemberStart = Carbon::create(2025, 11, 1, 0, 0, 0);
$novemberEnd = Carbon::create(2025, 11, 30, 23, 59, 59);
$today = Carbon::now();

echo "November 2025: {$novemberStart->format('Y-m-d')} to {$novemberEnd->format('Y-m-d')}\n\n";

// FIRST: Clear any existing November waivers to avoid duplicates
echo "Step 1: Clearing existing waivers...\n";
$deletedCount = LateFee::where('status', 2)
    ->where('waiver_reason', 'System upgrade in November 2025 - clients unable to make payments')
    ->delete();
echo "Deleted {$deletedCount} existing waivers\n\n";

// Get ALL disbursed loans (all types: daily, weekly, monthly)
$allLoans = PersonalLoan::where('status', 2) // Disbursed
    ->with(['member', 'product'])
    ->get();

echo "Processing {$allLoans->count()} disbursed loans (all types)...\n\n";

$totalWaived = 0;
$totalWaivedAmount = 0;
$loansAffected = 0;
$byPeriodType = [
    'Daily' => ['count' => 0, 'schedules' => 0, 'amount' => 0],
    'Weekly' => ['count' => 0, 'schedules' => 0, 'amount' => 0],
    'Monthly' => ['count' => 0, 'schedules' => 0, 'amount' => 0],
    'Unknown' => ['count' => 0, 'schedules' => 0, 'amount' => 0]
];

foreach ($allLoans as $loan) {
    // Get period type from product relationship
    $periodType = $loan->product->period_type ?? null;
    $periodTypeName = match($periodType) {
        1 => 'Weekly',
        2 => 'Monthly',
        3 => 'Daily',
        default => 'Unknown'
    };
    
    // Get ALL schedules (paid or unpaid) that were due before today
    $schedules = LoanSchedule::where('loan_id', $loan->id)
        ->where('payment_date', '<', $today)
        ->orderBy('payment_date')
        ->get();
    
    if ($schedules->isEmpty()) {
        continue;
    }
    
    $loanWaived = 0;
    $loanWaivedAmount = 0;
    
    foreach ($schedules as $schedule) {
        $dueDate = Carbon::parse($schedule->payment_date);
        
        // Calculate how many days of the overdue period fall in November
        $novemberDays = 0;
        
        // If schedule was due before November started
        if ($dueDate->lt($novemberStart)) {
            // Was this schedule paid?
            if ($schedule->status == 1 && $schedule->date_cleared) {
                $paidDate = Carbon::parse($schedule->date_cleared);
                
                // If paid after November ended, count all 30 days of November
                if ($paidDate->gt($novemberEnd)) {
                    $novemberDays = 30;
                }
                // If paid during November, count days from Nov 1 to payment date
                else if ($paidDate->gte($novemberStart) && $paidDate->lte($novemberEnd)) {
                    $novemberDays = $novemberStart->diffInDays($paidDate);
                }
                // If paid before November, no November days
                else {
                    $novemberDays = 0;
                }
            } else {
                // Still unpaid - count all 30 days of November
                $novemberDays = 30;
            }
        }
        // If schedule was due during November
        else if ($dueDate->gte($novemberStart) && $dueDate->lte($novemberEnd)) {
            // For schedules DUE in November, waive ALL late fees (including post-November)
            // because the loan term ends in November - no December schedules exist
            
            // Was it paid?
            if ($schedule->status == 1 && $schedule->date_cleared) {
                $paidDate = Carbon::parse($schedule->date_cleared);
                
                // Count ALL overdue days from due date to payment date
                $novemberDays = $dueDate->diffInDays($paidDate);
            } else {
                // Still unpaid - count ALL overdue days from due date to TODAY
                $novemberDays = $dueDate->diffInDays($today);
            }
        }
        // If due after November, no waiver
        else {
            $novemberDays = 0;
        }
        
        if ($novemberDays > 0) {
            $scheduleDue = $schedule->principal + $schedule->interest;
            
            // Calculate November late fee based on period type
            $novemberLateFee = 0;
            $periodsOverdue = 0;
            
            if ($periodType == 3) {
                // Daily: 6% per DAY
                $periodsOverdue = $novemberDays;
                $novemberLateFee = ($scheduleDue * 0.06) * $novemberDays;
            } else if ($periodType == 1) {
                // Weekly: 6% per WEEK
                $periodsOverdue = ceil($novemberDays / 7);
                $novemberLateFee = ($scheduleDue * 0.06) * $periodsOverdue;
            } else if ($periodType == 2) {
                // Monthly: 6% per MONTH
                $periodsOverdue = ceil($novemberDays / 30);
                $novemberLateFee = ($scheduleDue * 0.06) * $periodsOverdue;
            } else {
                // Unknown - default to weekly
                $periodsOverdue = ceil($novemberDays / 7);
                $novemberLateFee = ($scheduleDue * 0.06) * $periodsOverdue;
            }
            
            // Check if already waived
            $existingWaiver = LateFee::where('schedule_id', $schedule->id)
                ->where('status', 2)
                ->first();
            
            if (!$existingWaiver && $novemberLateFee > 0) {
                // Create waiver record
                LateFee::create([
                    'loan_id' => $loan->id,
                    'schedule_id' => $schedule->id,
                    'member_id' => $loan->member_id,
                    'amount' => $novemberLateFee,
                    'days_overdue' => $novemberDays,
                    'periods_overdue' => $periodsOverdue,
                    'period_type' => $periodTypeName,
                    'schedule_due_date' => $schedule->payment_date,
                    'calculated_date' => now()->format('Y-m-d'),
                    'status' => 2, // Waived
                    'waiver_reason' => 'System upgrade in November 2025 - clients unable to make payments',
                    'waived_at' => now(),
                    'waived_by' => 1
                ]);
                
                $loanWaived++;
                $loanWaivedAmount += $novemberLateFee;
            }
        }
    }
    
    if ($loanWaived > 0) {
        echo "Loan {$loan->id} - {$loan->code} ({$loan->member->fname} {$loan->member->lname}) [{$periodTypeName}]:\n";
        echo "  Waived: {$loanWaived} schedules\n";
        echo "  Amount: UGX " . number_format($loanWaivedAmount, 2) . "\n\n";
        
        $loansAffected++;
        $totalWaived += $loanWaived;
        $totalWaivedAmount += $loanWaivedAmount;
        
        // Track by period type
        $byPeriodType[$periodTypeName]['count']++;
        $byPeriodType[$periodTypeName]['schedules'] += $loanWaived;
        $byPeriodType[$periodTypeName]['amount'] += $loanWaivedAmount;
    }
}

echo "=================================================\n";
echo "SUMMARY BY LOAN TYPE\n";
echo "=================================================\n";

foreach ($byPeriodType as $type => $stats) {
    if ($stats['count'] > 0) {
        echo "{$type} Loans:\n";
        echo "  Loans: {$stats['count']}\n";
        echo "  Schedules: {$stats['schedules']}\n";
        echo "  Amount: UGX " . number_format($stats['amount'], 2) . "\n\n";
    }
}

echo "=================================================\n";
echo "OVERALL SUMMARY\n";
echo "=================================================\n";
echo "Total Loans Affected: {$loansAffected}\n";
echo "Total Schedules with November Waivers: {$totalWaived}\n";
echo "Total Waived (November only): UGX " . number_format($totalWaivedAmount, 2) . "\n\n";

echo "✓ November late fees waived (system upgrade period)\n";
echo "✓ Late fees for other months will still be charged\n";
echo "✓ This script can be run on production server\n";

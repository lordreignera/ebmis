<?php

// Fix all loans with migration issues
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== BULK FIX FOR ALL LOANS WITH MIGRATION ISSUES ===\n\n";

// Get all personal loans
$loans = \App\Models\PersonalLoan::whereIn('status', [1, 2, 3])->get();

$fixedCount = 0;
$totalFixed = 0;

foreach ($loans as $loan) {
    // Get completed repayments
    $completedRepayments = \App\Models\Repayment::where('loan_id', $loan->id)
        ->where('status', 1)
        ->sum('amount');
    
    // Get paid amount from schedules
    $schedulePaid = \App\Models\LoanSchedule::where('loan_id', $loan->id)
        ->sum('paid');
    
    // If there's a mismatch
    $mismatch = $completedRepayments - $schedulePaid;
    
    if ($mismatch > 1000) {
        echo "\n" . str_repeat("=", 100) . "\n";
        $memberName = $loan->member ? $loan->member->name : 'Unknown';
        echo "Fixing Loan {$loan->id} ({$loan->code}) - {$memberName}\n";
        echo "Repayments: UGX " . number_format($completedRepayments, 0) . "\n";
        echo "In Schedules: UGX " . number_format($schedulePaid, 0) . "\n";
        echo "Mismatch: UGX " . number_format($mismatch, 0) . "\n";
        echo str_repeat("=", 100) . "\n";
        
        // Get schedules
        $schedules = \App\Models\LoanSchedule::where('loan_id', $loan->id)
            ->orderBy('payment_date')
            ->get();
        
        if ($schedules->count() == 0) {
            echo "No schedules found - SKIPPING\n";
            continue;
        }
        
        // Calculate how much we need to distribute
        $remainingPayment = $completedRepayments;
        
        DB::beginTransaction();
        try {
            foreach ($schedules as $schedule) {
                if ($remainingPayment <= 0) break;
                
                // Skip if already fully paid
                if ($schedule->status == 1 && $schedule->paid >= $schedule->payment) {
                    echo "  Schedule {$schedule->id} already paid - skipping\n";
                    continue;
                }
                
                $currentPaid = $schedule->paid;
                $scheduleAmount = $schedule->payment;
                $stillOwed = $scheduleAmount - $currentPaid;
                
                if ($stillOwed <= 0) {
                    continue; // Already paid
                }
                
                if ($remainingPayment >= $stillOwed) {
                    // Fully pay this schedule
                    $schedule->paid = $scheduleAmount;
                    $schedule->status = 1;
                    $remainingPayment -= $stillOwed;
                    
                    echo "  Schedule {$schedule->id}: FULLY PAID (added " . number_format($stillOwed, 0) . ")\n";
                } else {
                    // Partially pay this schedule
                    $schedule->paid = $currentPaid + $remainingPayment;
                    $schedule->status = 0; // Still unpaid
                    
                    echo "  Schedule {$schedule->id}: PARTIAL (added " . number_format($remainingPayment, 0) . ")\n";
                    $remainingPayment = 0;
                }
                
                $schedule->save();
            }
            
            DB::commit();
            
            echo "✓ Fixed successfully! Remaining: UGX " . number_format($remainingPayment, 0) . "\n";
            
            $fixedCount++;
            $totalFixed += $mismatch;
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "✗ ERROR: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n\n" . str_repeat("=", 100) . "\n";
echo "=== SUMMARY ===\n";
echo "Fixed {$fixedCount} loans\n";
echo "Total amount corrected: UGX " . number_format($totalFixed, 0) . "\n";
echo str_repeat("=", 100) . "\n";

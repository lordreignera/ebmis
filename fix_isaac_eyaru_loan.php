<?php

// Fix Isaac Eyaru's loan - map old repayments to new schedules
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$loanId = 30; // Isaac Eyaru

echo "=== FIXING LOAN {$loanId} - ISAAC EYARU ===\n\n";

// Get all repayments with schedule_id links to old schedules
$repayments = \App\Models\Repayment::where('loan_id', $loanId)
    ->where('status', 1) // Only completed payments
    ->whereNotNull('schedule_id')
    ->get();

echo "Found " . $repayments->count() . " completed repayments\n\n";

// Get current schedules
$schedules = \App\Models\LoanSchedule::where('loan_id', $loanId)
    ->orderBy('payment_date')
    ->get();

echo "Found " . $schedules->count() . " current schedules\n\n";

// Group repayments by old schedule_id
$repaymentsByOldSchedule = $repayments->groupBy('schedule_id');

echo "Grouped into " . $repaymentsByOldSchedule->count() . " old schedule IDs\n\n";

// For each old schedule, sum up the payments
$oldSchedulePayments = [];
foreach ($repaymentsByOldSchedule as $oldScheduleId => $reps) {
    $totalPaid = $reps->sum('amount');
    $oldSchedulePayments[$oldScheduleId] = $totalPaid;
    echo "Old Schedule {$oldScheduleId}: UGX " . number_format($totalPaid, 0) . "\n";
}

echo "\n=== APPLYING PAYMENTS TO NEW SCHEDULES ===\n\n";

// We need to apply payments in order
// Since we don't have the old schedule dates, we'll apply payments to schedules in order
$remainingPayment = array_sum($oldSchedulePayments);
echo "Total payment to distribute: UGX " . number_format($remainingPayment, 0) . "\n\n";

DB::beginTransaction();
try {
    foreach ($schedules as $index => $schedule) {
        if ($remainingPayment <= 0) break;
        
        $scheduleAmount = $schedule->payment;
        
        if ($remainingPayment >= $scheduleAmount) {
            // Fully pay this schedule
            $schedule->paid = $scheduleAmount;
            $schedule->status = 1;
            $remainingPayment -= $scheduleAmount;
            
            echo "Schedule {$schedule->id} ({$schedule->payment_date}): FULLY PAID - UGX " . number_format($scheduleAmount, 0) . "\n";
        } else {
            // Partially pay this schedule
            $schedule->paid = $remainingPayment;
            $schedule->status = 0; // Still unpaid
            
            echo "Schedule {$schedule->id} ({$schedule->payment_date}): PARTIAL - UGX " . number_format($remainingPayment, 0) . " / " . number_format($scheduleAmount, 0) . "\n";
            $remainingPayment = 0;
        }
        
        $schedule->save();
    }
    
    DB::commit();
    
    echo "\n=== SUCCESS ===\n";
    echo "Payments applied successfully!\n";
    echo "Remaining undistributed: UGX " . number_format($remainingPayment, 0) . "\n";
    
    // Check final status
    $paidSchedules = \App\Models\LoanSchedule::where('loan_id', $loanId)->where('status', 1)->count();
    $totalSchedules = \App\Models\LoanSchedule::where('loan_id', $loanId)->count();
    
    echo "\nPaid schedules: {$paidSchedules} / {$totalSchedules}\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n=== ERROR ===\n";
    echo $e->getMessage() . "\n";
}

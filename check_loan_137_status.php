<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== CHECKING LOAN 137 STATUS ===\n\n";

$loan = App\Models\PersonalLoan::find(137);

if (!$loan) {
    echo "❌ Loan 137 not found\n";
    exit;
}

echo "Loan Code: {$loan->code}\n";
echo "Current Status: {$loan->status} (0=Pending, 1=Approved, 2=Disbursed, 3=Completed)\n";
echo "Principal: UGX " . number_format($loan->principal, 2) . "\n";
echo "Interest: UGX " . number_format($loan->interest, 2) . "\n";
$totalDue = $loan->principal + $loan->interest;
echo "Total Due: UGX " . number_format($totalDue, 2) . "\n\n";

// Check repayments
$repayments = App\Models\Repayment::where('loan_id', 137)->get();
$completedRepayments = $repayments->where('payment_status', 'Completed');
$totalPaid = $completedRepayments->sum('amount');

echo "=== REPAYMENTS ===\n";
echo "Total Repayments: {$repayments->count()}\n";
echo "Completed Repayments: {$completedRepayments->count()}\n";
echo "Total Paid: UGX " . number_format($totalPaid, 2) . "\n";
echo "Outstanding: UGX " . number_format($totalDue - $totalPaid, 2) . "\n\n";

// Check schedules
$schedules = App\Models\LoanSchedule::where('loan_id', 137)->get();
$paidSchedules = $schedules->where('status', 1);
$unpaidSchedules = $schedules->where('status', 0);

echo "=== SCHEDULES ===\n";
echo "Total Schedules: {$schedules->count()}\n";
echo "Paid Schedules: {$paidSchedules->count()}\n";
echo "Unpaid Schedules: {$unpaidSchedules->count()}\n\n";

foreach ($schedules as $schedule) {
    $statusText = $schedule->status == 1 ? '✅ PAID' : '❌ UNPAID';
    echo "Schedule #{$schedule->id}: {$statusText} - Amount: UGX " . number_format($schedule->payment, 2);
    echo " - Paid: UGX " . number_format($schedule->paid, 2);
    echo " - Due Date: {$schedule->payment_date}\n";
}

echo "\n=== ASSESSMENT ===\n";
if ($totalPaid >= $totalDue) {
    echo "✅ All payments completed (Paid: " . number_format($totalPaid, 2) . " >= Due: " . number_format($totalDue, 2) . ")\n";
    
    if ($loan->status == 3) {
        echo "✅ Loan is already marked as COMPLETED\n";
    } else {
        echo "⚠️ Loan should be COMPLETED but status is: {$loan->status}\n";
        echo "🔧 FIXING: Updating loan status to 3 (Completed)...\n";
        
        $loan->update(['status' => 3, 'date_closed' => now()]);
        
        echo "✅ Loan status updated to COMPLETED\n";
    }
} else {
    echo "❌ Loan still has outstanding balance\n";
    echo "Expected: UGX " . number_format($totalDue, 2) . "\n";
    echo "Paid: UGX " . number_format($totalPaid, 2) . "\n";
    echo "Remaining: UGX " . number_format($totalDue - $totalPaid, 2) . "\n";
}

echo "\n";

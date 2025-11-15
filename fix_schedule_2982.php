<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== FIXING SCHEDULE #2982 ===\n\n";

$schedule = App\Models\LoanSchedule::find(2982);

if (!$schedule) {
    echo "❌ Schedule not found\n";
    exit;
}

echo "Schedule #2982 Details:\n";
echo "Payment Amount: UGX " . number_format($schedule->payment, 2) . "\n";
echo "Paid Amount: UGX " . number_format($schedule->paid, 2) . "\n";
echo "Difference: UGX " . number_format($schedule->payment - $schedule->paid, 2) . "\n";
echo "Current Status: " . ($schedule->status == 1 ? 'PAID' : 'UNPAID') . "\n\n";

// Check if paid amount is within 1 UGX of payment amount (rounding tolerance)
$difference = abs($schedule->payment - $schedule->paid);

if ($difference <= 1.0) {
    echo "✅ Difference is within rounding tolerance (≤ 1 UGX)\n";
    echo "🔧 Marking schedule as PAID...\n\n";
    
    $schedule->update([
        'status' => 1, // Paid
        'paid' => $schedule->payment // Update paid to exact payment amount
    ]);
    
    echo "✅ Schedule #2982 marked as PAID\n";
    
    // Verify loan status
    $loan = App\Models\PersonalLoan::find($schedule->loan_id);
    echo "\nLoan Status: {$loan->status} (Should be 3 for Completed)\n";
    
    // Check all schedules
    $allSchedules = App\Models\LoanSchedule::where('loan_id', $loan->id)->get();
    $unpaid = $allSchedules->where('status', 0)->count();
    
    echo "Unpaid Schedules Remaining: {$unpaid}\n";
    
    if ($unpaid == 0 && $loan->status != 3) {
        echo "🔧 All schedules paid - updating loan to COMPLETED...\n";
        $loan->update(['status' => 3, 'date_closed' => now()]);
        echo "✅ Loan marked as COMPLETED\n";
    } elseif ($unpaid == 0) {
        echo "✅ All schedules paid and loan is COMPLETED\n";
    }
} else {
    echo "⚠️ Difference exceeds tolerance: UGX " . number_format($difference, 2) . "\n";
    echo "Manual review required\n";
}

echo "\n";

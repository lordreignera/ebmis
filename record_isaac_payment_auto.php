<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "RECORDING ISAAC'S PAYMENT - LOAN 133\n";
echo str_repeat("=", 80) . "\n\n";

// Payment details
$loanId = 133;
$amount = 5678; // Including late fees
$scheduleNum = 1; // First installment
$paymentMethod = 2; // Mobile Money
$txnId = '135243353857';
$notes = 'Payment with late fees';

// Get loan and schedule
$loan = DB::table('personal_loans')->where('id', $loanId)->first();
$schedules = DB::table('loan_schedules')->where('loan_id', $loanId)->orderBy('id')->get();
$schedule = $schedules[$scheduleNum - 1];

echo "Loan: {$loan->code}\n";
echo "Amount Paid: " . number_format($amount) . " UGX\n";
echo "Schedule Due: " . number_format($schedule->payment) . " UGX\n";
echo "Late Fees: " . number_format($amount - $schedule->payment) . " UGX\n";
echo "Payment Date: {$schedule->payment_date}\n";
echo "Transaction ID: {$txnId}\n\n";

DB::beginTransaction();

try {
    // Insert repayment
    $repaymentId = DB::table('repayments')->insertGetId([
        'loan_id' => $loanId,
        'schedule_id' => $schedule->id,
        'amount' => $amount,
        'type' => $paymentMethod,
        'details' => $notes,
        'txn_id' => $txnId,
        'status' => 1, // Approved
        'platform' => 'Web',
        'date_created' => now(),
        'added_by' => 1,
        'payment_phone' => '0702682187'
    ]);
    
    echo "✅ Repayment recorded (ID: {$repaymentId})\n";
    
    // Update schedule - mark as paid
    DB::table('loan_schedules')
        ->where('id', $schedule->id)
        ->update([
            'paid' => $amount,
            'status' => 1, // Paid
            'date_cleared' => now()
        ]);
    
    echo "✅ Schedule #{$scheduleNum} marked as PAID\n";
    
    DB::commit();
    
    echo "\n";
    echo str_repeat("=", 80) . "\n";
    echo "SUCCESS! Payment recorded for Isaac Sendagire\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Show updated status
    echo "Updated loan status:\n";
    $updatedSchedules = DB::table('loan_schedules')->where('loan_id', $loanId)->get();
    foreach ($updatedSchedules as $i => $s) {
        $status = $s->status == 0 ? 'PENDING' : 'PAID';
        echo "  " . ($i + 1) . ". {$s->payment_date}: {$s->payment} UGX - {$status}\n";
    }
    echo "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
}

<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "MANUALLY RECORD REPAYMENT FOR ISAAC SENDAGIRE - LOAN 133\n";
echo str_repeat("=", 80) . "\n\n";

// Get loan details
$loan = DB::table('personal_loans')->where('id', 133)->first();
$schedules = DB::table('loan_schedules')->where('loan_id', 133)->orderBy('id')->get();

echo "Loan Details:\n";
echo "  Code: {$loan->code}\n";
echo "  Principal: " . number_format($loan->principal) . " UGX\n\n";

echo "Schedules:\n";
foreach ($schedules as $i => $sched) {
    echo "  " . ($i + 1) . ". Due: {$sched->payment_date}, Amount: {$sched->payment} UGX, Status: " . ($sched->status == 0 ? 'Pending' : 'Paid') . "\n";
}

echo "\n";
echo "Enter repayment details:\n";
echo "------------------------\n\n";

echo "Amount paid (UGX): ";
$handle = fopen("php://stdin", "r");
$amount = trim(fgets($handle));

echo "Schedule number to pay (1 or 2): ";
$scheduleNum = trim(fgets($handle));

echo "Payment method (1=Cash, 2=Mobile Money, 3=Bank): ";
$paymentMethod = trim(fgets($handle));

echo "Transaction ID (optional, press enter to skip): ";
$txnId = trim(fgets($handle));

echo "Notes (optional, press enter to skip): ";
$notes = trim(fgets($handle));

fclose($handle);

// Remove commas from amount
$amount = str_replace(',', '', $amount);

if (empty($amount) || !is_numeric($amount)) {
    echo "\n❌ Invalid amount! Please enter numbers only (e.g., 5678 or 5,678)\n\n";
    exit(1);
}

$amount = floatval($amount);

$scheduleIndex = (int)$scheduleNum - 1;
if (!isset($schedules[$scheduleIndex])) {
    echo "\n❌ Invalid schedule number!\n\n";
    exit(1);
}

$schedule = $schedules[$scheduleIndex];

$lateFees = max(0, $amount - $schedule->payment);

echo "\n";
echo "Review:\n";
echo "-------\n";
echo "Loan: {$loan->code}\n";
echo "Amount Paid: " . number_format($amount) . " UGX\n";
echo "Schedule Due: " . number_format($schedule->payment) . " UGX\n";
if ($lateFees > 0) {
    echo "Late Fees: " . number_format($lateFees) . " UGX\n";
}
echo "Payment Date: {$schedule->payment_date}\n";
echo "Payment Method: " . ['', 'Cash', 'Mobile Money', 'Bank Transfer'][$paymentMethod] . "\n";
echo "Transaction ID: " . ($txnId ?: 'None') . "\n";
echo "Notes: " . ($notes ?: 'None') . "\n\n";

echo "Confirm recording this repayment? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirm = trim(strtolower(fgets($handle)));
fclose($handle);

if ($confirm !== 'yes' && $confirm !== 'y') {
    echo "\n❌ Cancelled.\n\n";
    exit(0);
}

DB::beginTransaction();

try {
    // Insert repayment
    $repaymentId = DB::table('repayments')->insertGetId([
        'loan_id' => 133,
        'schedule_id' => $schedule->id,
        'amount' => $amount,
        'type' => $paymentMethod,
        'details' => $notes ?: 'Manual entry for old system client',
        'txn_id' => $txnId ?: null,
        'status' => 1, // Approved
        'platform' => 'Web',
        'date_created' => now(),
        'added_by' => auth()->id() ?? 1,
        'payment_phone' => '0702682187'
    ]);
    
    echo "\n✅ Repayment recorded (ID: {$repaymentId})\n";
    
    // Update schedule if fully paid
    if ($amount >= $schedule->payment) {
        DB::table('loan_schedules')
            ->where('id', $schedule->id)
            ->update([
                'paid' => DB::raw('paid + ' . $amount),
                'status' => 1,
                'date_cleared' => now()
            ]);
        echo "✅ Schedule marked as paid\n";
    } else {
        // Partial payment
        DB::table('loan_schedules')
            ->where('id', $schedule->id)
            ->update([
                'paid' => DB::raw('paid + ' . $amount)
            ]);
        echo "✅ Partial payment recorded (remaining: " . number_format($schedule->payment - $amount) . " UGX)\n";
    }
    
    DB::commit();
    
    echo "\n";
    echo str_repeat("=", 80) . "\n";
    echo "SUCCESS!\n";
    echo str_repeat("=", 80) . "\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
}

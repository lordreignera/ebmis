<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Loan 132 - Repayment Schedules Check:\n";
echo str_repeat("=", 80) . "\n\n";

// Check loan details
$loan = DB::table('personal_loans')->where('id', 132)->first();
if ($loan) {
    echo "Loan Details:\n";
    echo "  Code: " . $loan->code . "\n";
    echo "  Principal: " . number_format($loan->principal, 0) . "\n";
    echo "  Period: " . $loan->period . " days\n";
    echo "  Interest: " . $loan->interest . "%\n";
    echo "  Status: " . $loan->status . " (0=Pending, 1=Approved, 2=Disbursed, 3=Completed)\n\n";
}

// Check for repayment schedules
echo "Repayment Schedules:\n";
echo str_repeat("-", 80) . "\n";

$schedules = DB::table('loan_schedules')
    ->where('loan_id', 132)
    ->orderBy('id')
    ->get();

if ($schedules->isEmpty()) {
    echo "❌ NO REPAYMENT SCHEDULES FOUND!\n";
    echo "This is a problem - disbursed loans should have repayment schedules.\n\n";
} else {
    echo "✅ Found " . count($schedules) . " schedule(s):\n\n";
    
    foreach ($schedules as $schedule) {
        echo sprintf("  Schedule #%d\n", $schedule->id);
        echo sprintf("    Due Date: %s\n", $schedule->due_date ?? 'N/A');
        echo sprintf("    Principal Due: %s\n", number_format($schedule->principal_due ?? 0, 0));
        echo sprintf("    Interest Due: %s\n", number_format($schedule->interest_due ?? 0, 0));
        echo sprintf("    Total Due: %s\n", number_format($schedule->total_due ?? 0, 0));
        echo sprintf("    Amount Paid: %s\n", number_format($schedule->amount_paid ?? 0, 0));
        echo sprintf("    Balance: %s\n", number_format($schedule->balance ?? 0, 0));
        echo sprintf("    Status: %s\n", $schedule->status ?? 'N/A');
        echo "\n";
    }
}

// Check loan status
echo "Expected Actions:\n";
echo str_repeat("-", 80) . "\n";
if ($loan->status == 1) {
    echo "❌ Loan status is still 'Approved' (1)\n";
    echo "   Should be 'Disbursed' (2) after successful disbursement\n";
} elseif ($loan->status == 2) {
    echo "✅ Loan status is 'Disbursed' (2) - Correct!\n";
}

if ($schedules->isEmpty()) {
    echo "❌ Repayment schedules missing\n";
    echo "   Should be created during disbursement process\n";
} else {
    echo "✅ Repayment schedules exist\n";
}

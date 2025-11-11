<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check loan 132 status
$loan = \App\Models\Loan::find(132);

echo "=== LOAN 132 STATUS ===\n";
echo "Status: " . $loan->status . " (0=pending, 1=approved, 2=disbursed, 3=completed)\n";
echo "Principal: " . number_format($loan->principal, 2) . "\n";
echo "Interest: " . number_format($loan->interest, 2) . "\n";
echo "Paid: " . number_format($loan->paid ?? 0, 2) . "\n";
echo "Outstanding: " . number_format($loan->principal + $loan->interest - ($loan->paid ?? 0), 2) . "\n";

echo "\n=== REPAYMENT SCHEDULES ===\n";
$schedules = \App\Models\LoanSchedule::where('loan_id', 132)->orderBy('payment_date')->get();
echo "Total Schedules: " . $schedules->count() . "\n\n";

foreach ($schedules as $schedule) {
    $statusText = $schedule->status == 1 ? 'PAID' : 'PENDING';
    echo "Schedule " . $schedule->id . ":\n";
    echo "  Due Date: " . $schedule->payment_date . "\n";
    echo "  Amount: " . number_format($schedule->payment, 2) . "\n";
    echo "  Status: " . $statusText . " (" . $schedule->status . ")\n";
    echo "  Paid Amount: " . number_format($schedule->paid_amount ?? 0, 2) . "\n";
    echo "\n";
}

echo "=== MEMBER DETAILS ===\n";
$member = $loan->member;
echo "Name: " . $member->fname . " " . $member->lname . "\n";
echo "Phone: " . $member->contact . "\n";

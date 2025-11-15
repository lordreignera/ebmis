<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== CHECKING REPAYMENT 949 RECEIPT DATA ===\n\n";

$repayment = App\Models\Repayment::with('loan')->find(949);

if (!$repayment) {
    echo "❌ Repayment 949 not found\n";
    exit;
}

$loan = $repayment->loan;

echo "Repayment Details:\n";
echo "Amount: UGX " . number_format($repayment->amount, 2) . "\n\n";

echo "Loan Details:\n";
echo "Loan ID: {$loan->id}\n";
echo "Principal: UGX " . number_format($loan->principal, 2) . "\n";
echo "Interest: UGX " . number_format($loan->interest, 2) . "\n";
$totalDue = $loan->principal + $loan->interest;
echo "Total Due: UGX " . number_format($totalDue, 2) . "\n\n";

// Calculate total paid
$totalPaid = App\Models\Repayment::where('loan_id', $loan->id)
    ->where(function($q) {
        $q->where('status', 1)
          ->orWhere('payment_status', 'Completed');
    })
    ->sum('amount');

echo "Total Paid to Date: UGX " . number_format($totalPaid, 2) . "\n";

$outstandingBalance = $totalDue - $totalPaid;
echo "Outstanding Balance: UGX " . number_format(max(0, $outstandingBalance), 2) . "\n\n";

// List all repayments
$allRepayments = App\Models\Repayment::where('loan_id', $loan->id)
    ->orderBy('id', 'asc')
    ->get();

echo "All Repayments for Loan {$loan->id}:\n";
foreach ($allRepayments as $r) {
    $statusText = ($r->status == 1 || $r->payment_status == 'Completed') ? 'COMPLETED' : 'PENDING';
    echo "  ID: {$r->id} | Amount: UGX " . number_format($r->amount, 2) . " | Status: {$statusText}\n";
}

echo "\n";

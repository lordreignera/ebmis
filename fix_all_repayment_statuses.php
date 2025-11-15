<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== FIXING ALL COMPLETED REPAYMENTS WITH INCORRECT STATUS ===\n\n";

$repayments = App\Models\Repayment::where('payment_status', 'Completed')
    ->where('status', 0)
    ->get();

echo "Found {$repayments->count()} completed payments with incorrect status:\n\n";

foreach ($repayments as $repayment) {
    echo "Repayment ID: {$repayment->id} | Loan: {$repayment->loan_id} | Amount: UGX " . number_format($repayment->amount, 2) . " | Reference: " . ($repayment->transaction_reference ?? 'N/A') . "\n";
    
    $repayment->update(['status' => 1]);
    
    echo "  ✅ Status updated to 1 (Confirmed)\n\n";
}

if ($repayments->count() == 0) {
    echo "✅ All completed payments already have correct status\n";
}

echo "\nDone!\n";

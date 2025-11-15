<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== CHECKING RECENT REPAYMENTS ===\n\n";

$repayments = App\Models\Repayment::orderBy('id', 'desc')->limit(5)->get();

foreach ($repayments as $repayment) {
    echo "Repayment ID: {$repayment->id}\n";
    echo "Loan ID: {$repayment->loan_id}\n";
    echo "Amount: UGX " . number_format($repayment->amount, 2) . "\n";
    echo "Type: {$repayment->type} (1=Cash, 2=Mobile Money, 3=Bank)\n";
    echo "txn_id: " . ($repayment->txn_id ?? 'NULL') . "\n";
    echo "transaction_reference: " . ($repayment->transaction_reference ?? 'NULL') . "\n";
    echo "payment_status: " . ($repayment->payment_status ?? 'NULL') . "\n";
    echo "status: {$repayment->status}\n";
    echo "Date: {$repayment->date_created}\n";
    echo "---\n";
}

echo "\n";

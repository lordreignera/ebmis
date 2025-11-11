<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Updating raw_payments records...\n";

$count = DB::table('raw_payments')
    ->where('pay_status', '00')
    ->whereNull('status')
    ->update([
        'status' => '00',
        'pay_message' => 'Pending'
    ]);

echo "Updated {$count} records\n";

// Now check for loan 132 specifically
echo "\nChecking loan 132 raw_payments:\n";
$loan132 = DB::table('raw_payments')
    ->where('loan_id', 132)
    ->orderBy('id', 'desc')
    ->first();

if ($loan132) {
    echo "Found raw_payment ID: {$loan132->id}\n";
    echo "Status: " . ($loan132->status ?? 'NULL') . "\n";
    echo "PayStatus: " . ($loan132->pay_status ?? 'NULL') . "\n";
    echo "TxnID: " . ($loan132->txn_id ?? 'NULL') . "\n";
    echo "TransID: " . ($loan132->trans_id ?? 'NULL') . "\n";
} else {
    echo "No raw_payment found for loan 132\n";
}

echo "\nDone!\n";

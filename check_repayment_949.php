<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== CHECKING REPAYMENT 949 ===\n\n";

$repayment = App\Models\Repayment::find(949);

if (!$repayment) {
    echo "❌ Repayment 949 not found\n";
    exit;
}

echo "Repayment Details:\n";
echo "ID: {$repayment->id}\n";
echo "Loan ID: {$repayment->loan_id}\n";
echo "Amount: UGX " . number_format($repayment->amount, 2) . "\n";
echo "Type: {$repayment->type} (1=Cash, 2=Mobile Money, 3=Bank)\n";
echo "Status: {$repayment->status} (0=Pending, 1=Confirmed)\n";
echo "Payment Status: " . ($repayment->payment_status ?? 'NULL') . "\n";
echo "Transaction Reference: " . ($repayment->transaction_reference ?? 'NULL') . "\n";
echo "txn_id: " . ($repayment->txn_id ?? 'NULL') . "\n";
echo "Date Created: {$repayment->date_created}\n\n";

// Check if payment is completed but status is not updated
if ($repayment->payment_status == 'Completed' && $repayment->status != 1) {
    echo "⚠️ Payment is Completed but status is not updated to 1 (Confirmed)\n";
    echo "🔧 Updating status...\n\n";
    
    $repayment->update(['status' => 1]);
    
    echo "✅ Status updated to 1 (Confirmed)\n";
} elseif ($repayment->payment_status == 'Completed' && $repayment->status == 1) {
    echo "✅ Payment status and confirmation status are both correct\n";
} else {
    echo "ℹ️ Current state:\n";
    echo "   Payment Status: " . ($repayment->payment_status ?? 'NULL') . "\n";
    echo "   Status: {$repayment->status}\n";
}

echo "\n";

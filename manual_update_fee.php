<?php
/**
 * Manual Fee Payment Update Script
 * Use this to manually mark a fee as paid when payment was confirmed
 * but the system failed to update the status
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Fee;

echo "=== MANUAL FEE PAYMENT UPDATE ===\n\n";

// Get the pending fee ID from command line or use the most recent
$feeId = $argv[1] ?? null;

if ($feeId) {
    $fee = Fee::find($feeId);
} else {
    // Get the most recent pending mobile money fee
    $fee = Fee::where('status', 0)
        ->where('payment_type', 1)
        ->orderBy('datecreated', 'desc')
        ->first();
}

if (!$fee) {
    echo "❌ No fee found to update.\n";
    echo "Usage: php manual_update_fee.php [fee_id]\n";
    exit(1);
}

echo "Fee Details:\n";
echo "------------\n";
echo "Fee ID: {$fee->id}\n";
echo "Amount: UGX " . number_format($fee->amount, 2) . "\n";
echo "Current Status: {$fee->status} (0=Pending, 1=Paid, 2=Failed)\n";
echo "Payment Type: {$fee->payment_type} (1=Mobile Money)\n";
echo "Transaction Ref: {$fee->pay_ref}\n";
echo "Date Created: {$fee->datecreated}\n";
echo "\n";

if ($fee->status == 1) {
    echo "✓ This fee is already marked as Paid.\n";
    exit(0);
}

echo "⚠ WARNING: This will mark the fee as PAID.\n";
echo "Only proceed if you have confirmed the payment was successful.\n";
echo "\n";
echo "Do you want to proceed? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$response = trim(strtolower($line));
fclose($handle);

if ($response !== 'yes' && $response !== 'y') {
    echo "\n❌ Update cancelled.\n";
    exit(0);
}

echo "\nUpdating fee status...\n";

try {
    $fee->update([
        'status' => 1,  // Paid
        'payment_status' => 'Paid',
        'updated_at' => now()
    ]);
    
    echo "✅ SUCCESS! Fee #{$fee->id} has been marked as PAID.\n";
    echo "\nUpdated Details:\n";
    echo "----------------\n";
    echo "Status: {$fee->status} (Paid)\n";
    echo "Payment Status: {$fee->payment_status}\n";
    echo "Updated At: " . now() . "\n";
    echo "\n";
    echo "✓ You can now refresh the member page to see the updated status.\n";
    echo "✓ The fee will now show a green 'Paid' badge with a Receipt button.\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: Failed to update fee.\n";
    echo "Error message: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== UPDATE COMPLETE ===\n";

<?php
/**
 * Fix False Paid Status - Correct payments wrongly marked as paid
 * 
 * FlexiPay was returning statusCode '01' with statusDescription 'FAILED'
 * but our code was treating '01' as success, marking failed payments as paid.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking for wrongly marked payments ===\n\n";

// Find all fees with status=1 (Paid) that have 'FAILED' in payment_raw
$wronglyPaidFees = DB::table('fees')
    ->where('status', 1)
    ->where('payment_raw', 'LIKE', '%FAILED%')
    ->orWhere(function($query) {
        $query->where('status', 1)
              ->where('payment_raw', 'LIKE', '%DECLINED%');
    })
    ->orWhere(function($query) {
        $query->where('status', 1)
              ->where('payment_raw', 'LIKE', '%CANCELLED%');
    })
    ->get();

echo "Found " . count($wronglyPaidFees) . " payments wrongly marked as paid\n\n";

if (count($wronglyPaidFees) > 0) {
    foreach ($wronglyPaidFees as $fee) {
        echo "Fee ID: {$fee->id}\n";
        echo "Member ID: {$fee->member_id}\n";
        echo "Amount: {$fee->amount}\n";
        echo "Current Status: {$fee->payment_status}\n";
        
        // Parse payment_raw to get actual status
        $paymentData = json_decode($fee->payment_raw, true);
        $actualStatus = $paymentData['message'] ?? 'Unknown';
        echo "Actual FlexiPay Status: {$actualStatus}\n";
        
        // Update to failed
        DB::table('fees')->where('id', $fee->id)->update([
            'status' => 2, // Failed
            'payment_status' => 'Failed',
            'payment_description' => "Payment failed: {$actualStatus} (corrected from wrong status mapping)"
        ]);
        
        echo "✓ Corrected to Failed status\n";
        echo "---\n";
    }
    
    echo "\n✓ All wrongly marked payments have been corrected!\n";
} else {
    echo "✓ No wrongly marked payments found\n";
}

echo "\n=== Check Complete ===\n";

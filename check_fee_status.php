<?php
/**
 * Debug script to check fee payment status and transaction references
 * This will show us exactly what's stored in the database
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Fee;

echo "=== FEE PAYMENT STATUS DEBUG ===\n\n";

// Get the most recent pending fee
$pendingFee = Fee::where('status', 0)
    ->orderBy('datecreated', 'desc')
    ->first();

if (!$pendingFee) {
    echo "No pending fees found in the database.\n";
    exit;
}

echo "Found Pending Fee:\n";
echo "----------------\n";
echo "Fee ID: {$pendingFee->id}\n";
echo "Amount: UGX " . number_format($pendingFee->amount, 2) . "\n";
echo "Status: {$pendingFee->status} (0=Pending, 1=Paid, 2=Failed)\n";
echo "Payment Type: {$pendingFee->payment_type} (1=Mobile Money)\n";
echo "Date Created: {$pendingFee->datecreated}\n";
echo "\n";

echo "Transaction Reference (pay_ref):\n";
echo "--------------------------------\n";
if (empty($pendingFee->pay_ref)) {
    echo "❌ EMPTY - No transaction reference stored!\n";
} else {
    echo "✓ {$pendingFee->pay_ref}\n";
}
echo "\n";

echo "Payment Raw Data:\n";
echo "-----------------\n";
if (empty($pendingFee->payment_raw)) {
    echo "❌ EMPTY - No raw payment data stored!\n";
} else {
    echo "✓ Found raw data, attempting to parse...\n";
    $rawData = json_decode($pendingFee->payment_raw, true);
    if ($rawData) {
        echo "Decoded JSON:\n";
        print_r($rawData);
        
        // Extract possible transaction references
        if (isset($rawData['transactionReferenceNumber'])) {
            echo "\n✓ Transaction Reference from raw data: {$rawData['transactionReferenceNumber']}\n";
        }
        if (isset($rawData['reference'])) {
            echo "✓ Reference from raw data: {$rawData['reference']}\n";
        }
        if (isset($rawData['flexipayReferenceNumber'])) {
            echo "✓ FlexiPay Reference: {$rawData['flexipayReferenceNumber']}\n";
        }
    } else {
        echo "⚠ Could not decode JSON. Raw content:\n";
        echo $pendingFee->payment_raw . "\n";
    }
}
echo "\n";

// Now let's check what FlexiPay thinks about this transaction
if (!empty($pendingFee->pay_ref)) {
    echo "Checking FlexiPay Status...\n";
    echo "----------------------------\n";
    
    $transactionRef = $pendingFee->pay_ref;
    
    // Prepare the request
    $url = 'https://emuria.net/flexipay/checkFromMMStatusProd.php';
    $postData = [
        'reference' => $transactionRef  // FIXED: FlexiPay expects 'reference'
    ];
    
    echo "Querying FlexiPay with reference: {$transactionRef}\n";
    echo "Using parameter: reference={$transactionRef}\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo "❌ CURL Error: " . curl_error($ch) . "\n";
    } else {
        echo "HTTP Status: {$httpCode}\n";
        echo "FlexiPay Response:\n";
        echo $response . "\n";
        
        $result = json_decode($response, true);
        if ($result) {
            echo "\nDecoded Response:\n";
            print_r($result);
            
            if (isset($result['statusCode'])) {
                echo "\n";
                echo "Status Code: {$result['statusCode']}\n";
                
                if ($result['statusCode'] === '00' || $result['statusCode'] === '01') {
                    echo "✅ PAYMENT WAS SUCCESSFUL!\n";
                    echo "⚠ But database still shows status as Pending (0)\n";
                    echo "🔧 The system should have updated this to Paid (1)\n";
                } elseif ($result['statusCode'] === '02' || $result['statusCode'] === '99') {
                    echo "❌ Payment Failed\n";
                } else {
                    echo "⏳ Payment Still Pending\n";
                }
            }
        }
    }
    
    curl_close($ch);
}

echo "\n=== END DEBUG ===\n";

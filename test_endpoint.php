<?php
/**
 * Direct test of the fee status check endpoint
 */

// Get the transaction reference
$transactionRef = $argv[1] ?? 'EbP1762872537';

echo "=== TESTING FEE STATUS CHECK ENDPOINT ===\n\n";
echo "Transaction Reference: {$transactionRef}\n";
echo "URL: http://localhost:84/admin/fees/check-mm-status/{$transactionRef}\n\n";

// Make request to the endpoint
$url = "http://localhost:84/admin/fees/check-mm-status/{$transactionRef}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";
echo "Response:\n";
echo $response . "\n\n";

$data = json_decode($response, true);
if ($data) {
    echo "Parsed Response:\n";
    print_r($data);
    
    if (isset($data['status'])) {
        echo "\n";
        echo "Status: {$data['status']}\n";
        
        if ($data['status'] === 'completed') {
            echo "✅ Payment SHOULD be marked as paid now!\n";
        } elseif ($data['status'] === 'pending') {
            echo "⚠ Still pending - payment not confirmed by FlexiPay\n";
        } elseif ($data['status'] === 'failed') {
            echo "❌ Payment failed\n";
        }
    }
}

echo "\n=== END TEST ===\n";

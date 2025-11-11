<?php
/**
 * Test FlexiPay Status Check API with different parameters
 * to find out what they actually expect
 */

// Get transaction reference from command line
$transactionRef = $argv[1] ?? 'EbP1762871689';

echo "=== FLEXIPAY STATUS CHECK API TEST ===\n\n";
echo "Testing with reference: {$transactionRef}\n\n";

$url = 'https://emuria.net/flexipay/checkFromMMStatusProd.php';

// Test 1: Using 'transactionId'
echo "TEST 1: Using parameter 'transactionId'\n";
echo "========================================\n";
$postData = ['transactionId' => $transactionRef];
$response = makeRequest($url, $postData);
echo "Response: " . $response . "\n\n";

// Test 2: Using 'transactionReferenceNumber'
echo "TEST 2: Using parameter 'transactionReferenceNumber'\n";
echo "====================================================\n";
$postData = ['transactionReferenceNumber' => $transactionRef];
$response = makeRequest($url, $postData);
echo "Response: " . $response . "\n\n";

// Test 3: Using 'reference'
echo "TEST 3: Using parameter 'reference'\n";
echo "====================================\n";
$postData = ['reference' => $transactionRef];
$response = makeRequest($url, $postData);
echo "Response: " . $response . "\n\n";

// Test 4: Using 'flexipayReferenceNumber'
echo "TEST 4: Using parameter 'flexipayReferenceNumber'\n";
echo "==================================================\n";
$postData = ['flexipayReferenceNumber' => $transactionRef];
$response = makeRequest($url, $postData);
echo "Response: " . $response . "\n\n";

// Test 5: Using multiple parameters
echo "TEST 5: Using both 'transactionId' and 'phone'\n";
echo "===============================================\n";
$postData = [
    'transactionId' => $transactionRef,
    'phone' => '256708356505'
];
$response = makeRequest($url, $postData);
echo "Response: " . $response . "\n\n";

// Test 6: Check what the API documentation says by trying empty request
echo "TEST 6: Empty request (to see expected parameters)\n";
echo "===================================================\n";
$postData = [];
$response = makeRequest($url, $postData);
echo "Response: " . $response . "\n\n";

echo "=== END TESTS ===\n";

function makeRequest($url, $postData) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $response = "CURL Error: " . curl_error($ch);
    }
    
    curl_close($ch);
    
    return $response;
}

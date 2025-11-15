<?php

// Test Stanbic Private Key Loading

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo "Testing Stanbic FlexiPay Service...\n";
    
    $service = app(\App\Services\StanbicFlexiPayService::class);
    
    echo "✅ Private key loaded successfully!\n";
    
    // Test connection
    $result = $service->testConnection();
    
    if ($result['connection']) {
        echo "✅ Connection test passed!\n";
        echo "   Message: " . $result['message'] . "\n";
    } else {
        echo "❌ Connection test failed!\n";
        echo "   Message: " . $result['message'] . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Investment;
use App\Models\Investor;

try {
    echo "Testing Investment and Investor models...\n\n";
    
    // Test Investment model
    echo "Investment table count: " . Investment::count() . "\n";
    
    // Test Investor model
    echo "Investor table count: " . Investor::count() . "\n";
    
    // Test investment calculation
    echo "\nTesting investment calculation:\n";
    $calculation = Investment::calculateInterest(5000, 3, 1); // $5000, 3 years, standard interest
    echo "Amount: $5000, Period: 3 years, Type: Standard Interest\n";
    echo "Rate: " . $calculation['rate'] . "%\n";
    echo "Annual Profit: $" . number_format($calculation['annual_profit'], 2) . "\n";
    echo "Total Interest: $" . number_format($calculation['total_interest'], 2) . "\n";
    echo "Total Return: $" . number_format($calculation['total_return'], 2) . "\n";
    
    // Test compound interest
    echo "\nTesting compound interest:\n";
    $calculation2 = Investment::calculateInterest(10000, 5, 2); // $10000, 5 years, compound interest
    echo "Amount: $10000, Period: 5 years, Type: Compound Interest\n";
    echo "Rate: " . $calculation2['rate'] . "%\n";
    echo "Total Interest: $" . number_format($calculation2['total_interest'], 2) . "\n";
    echo "Total Return: $" . number_format($calculation2['total_return'], 2) . "\n";
    
    echo "\n✅ Investment models working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
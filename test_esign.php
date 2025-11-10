<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

try {
    echo "Testing eSign queries...\n";
    
    $personalCount = \App\Models\PersonalLoan::where('is_esign', true)->count();
    echo "Personal eSign loans: {$personalCount}\n";
    
    $groupCount = \App\Models\GroupLoan::where('is_esign', true)->count();
    echo "Group eSign loans: {$groupCount}\n";
    
    $totalCount = $personalCount + $groupCount;
    echo "Total eSign loans: {$totalCount}\n";
    
    echo "✓ eSign queries working correctly!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
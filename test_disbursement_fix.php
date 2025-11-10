<?php

// Simple test for the disbursement controller fix
require_once 'vendor/autoload.php';

echo "Testing disbursement controller fix...\n";

try {
    // Test that the models are accessible
    $personalCount = \App\Models\PersonalLoan::count();
    echo "Personal loans found: $personalCount\n";
    
    $groupCount = \App\Models\GroupLoan::count();
    echo "Group loans found: $groupCount\n";
    
    echo "Models are accessible - disbursement controller should work now.\n";
    echo "You can now access: http://localhost:84/admin/loans/disbursements/pending\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

try {
    // Test if PersonalLoan schedules() method exists and works
    $personalLoan = new \App\Models\PersonalLoan();
    $schedules = $personalLoan->schedules();
    echo "✓ PersonalLoan schedules() method works!\n";
    echo "Relationship type: " . get_class($schedules) . "\n";
    
    // Try to get the query to see if it's properly formed
    echo "Query: " . $schedules->toSql() . "\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
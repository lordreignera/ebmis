<?php
require_once 'vendor/autoload.php';

use Illuminate\Http\Request;

// Create Laravel app
$app = require_once 'bootstrap/app.php';

try {
    // Boot the application
    $app->boot();
    
    // Create a mock request
    $request = new Request();
    
    // Test the controller method directly
    $controller = new \App\Http\Controllers\Admin\LoanController();
    
    echo "Testing esign index method...\n";
    $result = $controller->esignIndex($request);
    
    echo "Success! Method executed without errors.\n";
    echo "Result type: " . get_class($result) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
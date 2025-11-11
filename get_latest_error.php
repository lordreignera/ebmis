<?php

$logFile = __DIR__ . '/storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "Log file not found!\n";
    exit;
}

// Read the last 50 lines of the log file
$lines = [];
$handle = fopen($logFile, 'r');
if ($handle) {
    // Get file size
    fseek($handle, 0, SEEK_END);
    $fileSize = ftell($handle);
    
    // Read last 10KB
    $readSize = min(10240, $fileSize);
    fseek($handle, -$readSize, SEEK_END);
    $content = fread($handle, $readSize);
    fclose($handle);
    
    // Get last error
    $lines = explode("\n", $content);
    
    // Find last "Loan disbursement approval failed"
    $errorLines = [];
    $capturing = false;
    
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        if (strpos($lines[$i], 'Loan disbursement approval failed') !== false) {
            $capturing = true;
        }
        
        if ($capturing) {
            array_unshift($errorLines, $lines[$i]);
            
            // Stop when we hit the previous log entry
            if (strpos($lines[$i], 'local.ERROR') !== false && count($errorLines) > 1) {
                break;
            }
        }
    }
    
    echo "Latest Disbursement Error:\n";
    echo str_repeat("=", 80) . "\n";
    echo implode("\n", $errorLines) . "\n";
}

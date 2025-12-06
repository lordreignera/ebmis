<?php
// Remove accept attributes from all loan create forms

$files = glob(__DIR__ . '/resources/views/admin/loans/create*.blade.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Remove accept attributes
    $content = str_replace(' accept=".pdf,.jpg,.jpeg,.png"', '', $content);
    $content = str_replace(' accept=".jpg,.jpeg,.png,.pdf"', '', $content);
    $content = str_replace(' accept=".jpg,.jpeg,.png"', '', $content);
    
    file_put_contents($file, $content);
    echo "Updated: " . basename($file) . "\n";
}

// Also update show.blade.php
$showFile = __DIR__ . '/resources/views/admin/loans/show.blade.php';
if (file_exists($showFile)) {
    $content = file_get_contents($showFile);
    $content = str_replace(' accept=".pdf,.jpg,.jpeg,.png"', '', $content);
    $content = str_replace(' accept=".jpg,.jpeg,.png,.pdf"', '', $content);
    file_put_contents($showFile, $content);
    echo "Updated: show.blade.php\n";
}

echo "\n✅ Done! All loan forms now accept all file types.\n";
